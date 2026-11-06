<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Setting;
use App\Services\Saas\SubscriptionService;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/** After registration + OTP:  1) Select Subscription  →  2) Payment  →  3) Create Store */
class OnboardingController extends Controller
{
    public function plan()
    {
        $branch = Tenancy::branch();
        if ($branch->isOnboarded()) {
            return redirect()->route('manager.billing.plans');
        }

        return view('manager.onboarding.plan', [
            'branch' => $branch,
            'plans' => Plan::availableFor($branch)->get(),
            'step' => 1,
        ]);
    }

    public function choosePlan(Request $request, SubscriptionService $service)
    {
        $branch = Tenancy::branch();
        $data = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'cycle' => ['required', Rule::in(array_keys(Plan::CYCLES))],
            'trial' => ['nullable', 'boolean'],
        ]);
        $plan = Plan::findOrFail($data['plan_id']);

        try {
            $result = $service->choose($branch, $plan, $data['cycle'], (bool) ($data['trial'] ?? false));
        } catch (InvalidArgumentException $e) {
            return back()->with('warning', $e->getMessage());
        }

        if ($result['invoice'] && $result['invoice']->isPayable()) {
            return redirect()->route('manager.onboarding.payment', $result['invoice'])->with('success', $result['message']);
        }

        return redirect()->route('manager.onboarding.store')->with('success', $result['message']);
    }

    public function payment(Invoice $invoice)
    {
        $this->own($invoice);
        if (! $invoice->isPayable()) {
            return redirect()->route('manager.onboarding.store');
        }

        return view('manager.onboarding.payment', ['invoice' => $invoice->load('plan'), 'step' => 2, 'pay' => $this->paySettings()]);
    }

    /** "Pay later" — continue setup on Free-plan limits until the invoice is paid. */
    public function skipPayment(Invoice $invoice)
    {
        $this->own($invoice);

        return redirect()->route('manager.onboarding.store')
            ->with('warning', "পেমেন্ট সম্পন্ন না হওয়া পর্যন্ত Free প্ল্যানের লিমিট প্রযোজ্য। Billing পেজ থেকে যেকোনো সময় {$invoice->number} পরিশোধ করুন।");
    }

    public function store()
    {
        $branch = Tenancy::branch();
        if (! $branch->subscription) {
            return redirect()->route('manager.onboarding.plan');
        }
        if ($branch->isOnboarded()) {
            return redirect()->route('manager.dashboard');
        }

        return view('manager.onboarding.store', ['branch' => $branch, 'step' => 3]);
    }

    public function saveStore(Request $request)
    {
        $branch = Tenancy::branch();
        abort_unless($branch->subscription, 403);

        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:120'],
            'store_phone' => ['nullable', 'string', 'max:20'],
            'hero_title' => ['nullable', 'string', 'max:120'],
            'hero_subtitle' => ['nullable', 'string', 'max:200'],
            'about' => ['nullable', 'string', 'max:2000'],
            'shipping_charge' => ['required', 'numeric', 'min:0', 'max:100000'],
            'bkash_number' => ['nullable', 'regex:/^01[3-9]\d{8}$/'],
            'nagad_number' => ['nullable', 'regex:/^01[3-9]\d{8}$/'],
            'facebook_url' => ['nullable', 'url', 'max:500'],
            'logo' => ['nullable', 'image', 'max:12288'],
            'banner' => ['nullable', 'image', 'max:12288'],
            'favicon' => ['nullable', 'mimes:png,ico,jpg,jpeg,webp', 'max:512'],
        ]);

        foreach (['logo', 'banner', 'favicon'] as $field) {
            unset($data[$field]);
            if ($request->hasFile($field)) {
                $branch->deleteUpload($branch->$field);
                $data[$field] = $branch->storeUpload($request->file($field), "branches/{$branch->id}");
            }
        }

        $branch->update($data);
        $branch->forceFill(['onboarded_at' => now()])->save();
        Role::createDefaultsFor($branch);
        ActivityLog::record('store.created', "Store created: {$branch->display_name}", $branch);
        app(\App\Services\Notifier::class)->admin('branch.new', "New store live: {$branch->display_name}", $branch->url, route('admin.branches.show', $branch), 'success');

        return redirect()->route('manager.dashboard')->with('success', "অভিনন্দন! আপনার স্টোর লাইভ: {$branch->url}");
    }

    protected function own(Invoice $invoice): void
    {
        abort_unless($invoice->branch_id === Tenancy::branch()->id, 404);
    }

    public static function paySettings(): array
    {
        return [
            'bkash' => Setting::get('saas_bkash_number'),
            'nagad' => Setting::get('saas_nagad_number'),
            'bank' => Setting::get('saas_bank_details'),
        ];
    }
}
