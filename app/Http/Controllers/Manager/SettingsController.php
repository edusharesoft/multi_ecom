<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Services\Payment\ShatajPayService;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function edit(Request $request)
    {
        $branch = Tenancy::branch();

        return view('manager.settings', [
            'branch' => $branch,
            'gatewayReady' => PaymentGateway::ready(),
            'gateway' => PaymentGateway::forBranch($branch->id),
        ]);
    }

    public function update(Request $request)
    {
        $branch = Tenancy::branch();
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:120'],
            // owner login details can only be changed by the owner
            'name' => [Tenancy::isOwner() ? 'required' : 'exclude', 'string', 'max:100'],
            'email' => [Tenancy::isOwner() ? 'required' : 'exclude', 'email', 'max:150', Rule::unique('branches', 'email')->ignore($branch->id), Rule::unique('staff', 'email')],
            'address' => ['required', 'string', 'max:500'],
            'store_phone' => ['nullable', 'string', 'max:20'],
            'opening_hours' => ['nullable', 'string', 'max:120'],
            'hero_title' => ['nullable', 'string', 'max:120'],
            'hero_subtitle' => ['nullable', 'string', 'max:200'],
            'about' => ['nullable', 'string', 'max:2000'],
            'map_url' => ['nullable', 'url', 'max:500'],
            'facebook_url' => ['nullable', 'url', 'max:500', 'regex:#^https?://#i'],
            'bkash_number' => ['nullable', 'regex:/^01[3-9]\d{8}$/'],
            'nagad_number' => ['nullable', 'regex:/^01[3-9]\d{8}$/'],
            'shipping_charge' => ['required', 'numeric', 'min:0', 'max:100000'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:50'],
            'logo' => ['nullable', 'image', 'max:12288'],
            'banner' => ['nullable', 'image', 'max:12288'],
            'favicon' => ['nullable', 'mimes:png,ico,jpg,jpeg,webp', 'max:512'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $opt = $request->validate([
            'options.points_per_100' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'options.return_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'options.high_value_amount' => ['nullable', 'numeric', 'min:0'],
            'options.invoice_note' => ['nullable', 'string', 'max:255'],
        ])['options'] ?? [];
        $data['options'] = array_merge($branch->options ?? [], array_filter($opt, fn ($v) => $v !== null), [
            'review_auto_approve' => $request->boolean('options.review_auto_approve'),
            'allow_guest_checkout' => $request->boolean('options.allow_guest_checkout'),
        ]);
        $data['cod_enabled'] = $request->boolean('cod_enabled');
        $data['shatajpay_enabled'] = $request->boolean('shatajpay_enabled');

        if (! $data['cod_enabled'] && ! $data['shatajpay_enabled'] && empty($data['bkash_number']) && empty($data['nagad_number'])) {
            return back()->withInput()->withErrors(['cod_enabled' => 'অন্তত একটি পেমেন্ট পদ্ধতি চালু রাখুন।']);
        }

        foreach (['logo', 'banner', 'favicon'] as $field) {
            if ($request->hasFile($field)) {
                if (! $branch->hasStorageFor($request->file($field))) {
                    return back()->withInput()->with('warning', 'আপনার প্ল্যানের স্টোরেজ লিমিট শেষ। আপগ্রেড করুন।');
                }
                $branch->deleteUpload($branch->$field);
                $data[$field] = $branch->storeUpload($request->file($field), "branches/{$branch->id}");
            } else {
                unset($data[$field]);
            }
        }

        $branch->update($data);
        \App\Models\ActivityLog::record('settings.updated', 'Store settings updated', $branch);

        return back()->with('success', 'সেটিংস সংরক্ষণ করা হয়েছে।');
    }

    /** The branch's own ShatajPay merchant account (owner only). Empty = use the platform account. */
    public function shatajpay(Request $request)
    {
        abort_unless(Tenancy::isOwner(), 403, 'শুধু স্টোর মালিক পেমেন্ট অ্যাকাউন্ট পরিবর্তন করতে পারবেন।');
        abort_unless(PaymentGateway::ready(), 409);
        $branch = Tenancy::branch();

        if ($request->boolean('remove')) {
            PaymentGateway::where('branch_id', $branch->id)->where('gateway', 'shatajpay')->delete();
            \App\Models\ActivityLog::record('settings.shatajpay_removed', 'Own ShatajPay account removed', $branch);

            return back()->with('success', 'নিজের ShatajPay অ্যাকাউন্ট সরানো হয়েছে। এখন প্ল্যাটফর্মের অ্যাকাউন্টে পেমেন্ট যাবে।');
        }

        $data = $request->validate([
            'mode' => ['required', Rule::in(['sandbox', 'live'])],
            'app_username' => ['required', 'string', 'max:150'],
            'gateway_id' => ['required', 'string', 'max:150'],
            'app_password' => ['nullable', 'string', 'max:300'],
            'app_key' => ['nullable', 'string', 'max:300'],
            'app_secret' => ['nullable', 'string', 'max:300'],
        ]);
        foreach (['app_password', 'app_key', 'app_secret'] as $f) {
            if (blank($data[$f] ?? null)) {
                unset($data[$f]);
            }
        }
        $data['status'] = $request->boolean('status');

        $row = PaymentGateway::updateOrCreate(['branch_id' => $branch->id, 'gateway' => 'shatajpay'], $data + ['display_name' => 'ShatajPay']);
        \App\Models\ActivityLog::record('settings.shatajpay_updated', 'Own ShatajPay account updated', $branch);

        if ($row->status && ! $row->isComplete()) {
            return back()->with('warning', 'সংরক্ষণ হয়েছে, কিন্তু Password, App Key ও App Secret সব না দেওয়া পর্যন্ত প্ল্যাটফর্মের অ্যাকাউন্টে পেমেন্ট যাবে।');
        }

        if ($request->boolean('test') && $row->isComplete()) {
            $svc = app(ShatajPayService::class);
            [$ok, $msg] = $svc->testConnection($svc->fromRow($row, config('shataj.pay'), $row->mode));

            return back()->with($ok ? 'success' : 'warning', $msg);
        }

        return back()->with('success', 'ShatajPay অ্যাকাউন্ট সংরক্ষণ হয়েছে।');
    }

    public function password(Request $request)
    {
        $guard = Tenancy::isOwner() ? 'branch' : 'staff';
        $request->validate([
            'current_password' => ['required', 'current_password:'.$guard],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        Tenancy::actor()->update(['password' => $request->password]);
        \App\Models\ActivityLog::record('auth.password_changed', 'Password changed by '.Tenancy::actorName());

        return back()->with('success', 'পাসওয়ার্ড পরিবর্তন হয়েছে।');
    }
}
