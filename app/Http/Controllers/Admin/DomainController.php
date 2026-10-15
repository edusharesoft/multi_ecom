<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Domain;
use App\Models\Setting;
use App\Services\DomainService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class DomainController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.domains', [
            'domains' => Domain::with('branch:id,name,slug')->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))->latest()->paginate(30)->withQueryString(),
            'settings' => [
                'server_ip' => Setting::get('server_ip'), 'domain_cname_target' => Setting::get('domain_cname_target'),
                'domain_manual_approval' => (bool) Setting::get('domain_manual_approval'),
                'store_subdomains' => (bool) Setting::get('store_subdomains'), 'store_base_domain' => Setting::get('store_base_domain'),
            ],
        ]);
    }

    public function status(Request $request, Domain $domain, DomainService $service)
    {
        $action = $request->validate(['action' => ['required', Rule::in(['activate', 'reject', 'recheck'])]])['action'];
        if ($action === 'recheck') {
            $r = $service->check($domain);

            return back()->with($r['ok'] ? 'success' : 'warning', $r['message']);
        }
        $domain->update(['status' => $action === 'activate' ? 'active' : 'rejected']);
        ActivityLog::record('domain.'.$action, "Domain {$domain->host} → {$domain->status}", $domain, [], $domain->branch_id);

        return back()->with('success', "{$domain->host}: {$domain->status}");
    }

    public function settings(Request $request)
    {
        $data = $request->validate([
            'server_ip' => ['nullable', 'ip'],
            'domain_cname_target' => ['nullable', 'string', 'max:190', 'regex:/^([a-z0-9-]+\.)+[a-z]{2,24}$/i'],
            'domain_manual_approval' => ['nullable', 'boolean'],
            'store_subdomains' => ['nullable', 'boolean'],
            'store_base_domain' => ['nullable', 'string', 'max:190', 'regex:/^([a-z0-9-]+\.)+[a-z]{2,24}$/i'],
        ]);
        Setting::put([
            'server_ip' => $data['server_ip'] ?? '', 'domain_cname_target' => strtolower($data['domain_cname_target'] ?? ''),
            'domain_manual_approval' => ! empty($data['domain_manual_approval']) ? '1' : '', 'store_subdomains' => ! empty($data['store_subdomains']) ? '1' : '',
            'store_base_domain' => strtolower($data['store_base_domain'] ?? ''),
        ]);
        Cache::flush();   // host → store lookups are cached

        return back()->with('success', 'Domain settings saved.');
    }
}
