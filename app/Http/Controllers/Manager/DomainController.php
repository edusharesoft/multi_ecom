<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Domain;
use App\Models\Setting;
use App\Services\DomainService;
use App\Support\Tenancy;
use Illuminate\Http\Request;

class DomainController extends Controller
{
    public function __construct(protected DomainService $domains) {}

    public function index()
    {
        $b = Tenancy::branch();

        return view('manager.website.domains', [
            'b' => $b,
            'domains' => Domain::where('branch_id', $b->id)->latest()->get(),
            'canCustom' => $b->hasFeature('custom_domain'),
            'target' => $this->domains->target(),
            'serverIp' => Setting::get('server_ip'),
            'subdomain' => Setting::get('store_subdomains') ? $b->slug.'.'.(Setting::get('store_base_domain') ?: parse_url((string) config('app.url'), PHP_URL_HOST)) : null,
        ]);
    }

    public function store(Request $request)
    {
        $b = Tenancy::branch();
        if (! $b->hasFeature('custom_domain')) {
            return redirect()->route('manager.billing.plans')->with('warning', 'Custom domain আপনার প্ল্যানে নেই।');
        }
        $request->merge(['host' => Domain::normalize((string) $request->host)]);
        $data = $request->validate(['host' => ['required', 'string', 'max:190', 'regex:/^(?!-)([a-z0-9-]{1,63}\.)+[a-z]{2,24}$/', 'unique:domains,host']],
            ['host.regex' => 'সঠিক ডোমেইন দিন, যেমন shop.example.com', 'host.unique' => 'এই ডোমেইন আগেই যুক্ত আছে।']);
        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        if ($data['host'] === $appHost || str_ends_with($data['host'], '.'.$appHost)) {
            return back()->with('warning', 'প্ল্যাটফর্মের নিজের ডোমেইন যুক্ত করা যাবে না।');
        }
        if (Domain::where('branch_id', $b->id)->count() >= 3) {
            return back()->with('warning', 'সর্বোচ্চ ৩টি ডোমেইন যুক্ত করা যায়।');
        }
        $d = Domain::create(['branch_id' => $b->id, 'host' => $data['host'], 'status' => 'pending', 'is_primary' => ! Domain::where('branch_id', $b->id)->exists()]);
        ActivityLog::record('domain.added', "Domain added: {$d->host}", $d);

        return back()->with('success', 'ডোমেইন যুক্ত হয়েছে — নিচের DNS রেকর্ডগুলো বসিয়ে “Verify” চাপুন।');
    }

    public function verify(Domain $domain)
    {
        $this->own($domain);
        $result = $this->domains->check($domain);

        return back()->with($result['ok'] ? 'success' : 'warning', $result['message']);
    }

    public function update(Request $request, Domain $domain)
    {
        $this->own($domain);
        $data = $request->validate(['is_primary' => ['nullable', 'boolean'], 'redirect_to_domain' => ['nullable', 'boolean']]);
        $primary = (bool) ($data['is_primary'] ?? false);
        if ($primary) {
            Domain::where('branch_id', $domain->branch_id)->whereKeyNot($domain->id)->update(['is_primary' => false, 'redirect_to_domain' => false]);
        }
        $domain->update(['is_primary' => $primary, 'redirect_to_domain' => $primary && ($data['redirect_to_domain'] ?? false) && $domain->status === 'active']);

        return back()->with('success', 'আপডেট হয়েছে।');
    }

    public function destroy(Domain $domain)
    {
        $this->own($domain);
        $domain->delete();
        ActivityLog::record('domain.removed', "Domain removed: {$domain->host}");

        return back()->with('success', 'ডোমেইন মুছে ফেলা হয়েছে।');
    }

    protected function own(Domain $d): void
    {
        abort_unless($d->branch_id === Tenancy::branch()->id, 404);
    }
}
