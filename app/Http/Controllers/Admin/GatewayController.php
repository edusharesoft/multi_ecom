<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Setting;
use App\Services\Payment\ShatajPayService;
use App\Services\Sms\ShatajSmsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Super Admin → Payment & SMS Gateways
 * Platform ShatajPay account + Shataj SMS Gateway credentials (stored encrypted in the database).
 */
class GatewayController extends Controller
{
    public function index(ShatajPayService $pay, ShatajSmsService $sms)
    {
        $ready = PaymentGateway::ready();

        return view('admin.gateways', [
            'ready' => $ready,
            'row' => $ready ? PaymentGateway::platform() : null,
            'pay' => $pay->platformConfig(),
            'sms' => $sms->config(),
            'smsHasKey' => filled($sms->config()['api_key']),
            'branchAccounts' => $ready ? PaymentGateway::whereNotNull('branch_id')->where('status', true)->count() : 0,
            'recent' => Payment::where('gateway', 'shatajpay')->with(['order:id,order_number,branch_id', 'invoice:id,number,branch_id'])
                ->latest('id')->take(15)->get(),
        ]);
    }

    public function savePay(Request $request)
    {
        abort_unless(PaymentGateway::ready(), 409, 'Run: php artisan migrate');
        $row = PaymentGateway::platform();

        $data = $request->validate([
            'mode' => ['required', Rule::in(['fake', 'sandbox', 'live'])],
            'display_name' => ['nullable', 'string', 'max:100'],
            'base_url' => ['nullable', 'url', 'max:200', 'regex:#^https?://#i'],
            'app_username' => ['nullable', 'string', 'max:150'],
            'gateway_id' => ['nullable', 'string', 'max:150'],
            'app_password' => ['nullable', 'string', 'max:300'],
            'app_key' => ['nullable', 'string', 'max:300'],
            'app_secret' => ['nullable', 'string', 'max:300'],
        ]);

        // empty secret fields = keep the saved value
        foreach (['app_password', 'app_key', 'app_secret'] as $f) {
            if (blank($data[$f] ?? null)) {
                unset($data[$f]);
            }
        }
        $data['status'] = $request->boolean('status');

        $row = PaymentGateway::updateOrCreate(['branch_id' => null, 'gateway' => 'shatajpay'], $data);

        if ($row->mode !== 'fake' && $row->status && ! $row->isComplete()) {
            return back()->with('warning', 'সংরক্ষণ হয়েছে, কিন্তু Username, Password, App Key, App Secret ও Gateway ID সব না দিলে লাইভ পেমেন্ট চলবে না।');
        }

        return back()->with('success', 'ShatajPay settings saved.');
    }

    public function testPay(ShatajPayService $pay)
    {
        $row = PaymentGateway::platform();
        if (! $row || ! $row->isComplete()) {
            return back()->with('warning', 'আগে সব credential সংরক্ষণ করুন।');
        }
        [$ok, $msg] = $pay->testConnection($pay->fromRow($row, config('shataj.pay'), $row->mode === 'sandbox' ? 'sandbox' : 'live'));

        return back()->with($ok ? 'success' : 'warning', $msg);
    }

    public function saveSms(Request $request)
    {
        $data = $request->validate([
            'sms_driver' => ['required', Rule::in(['log', 'shataj'])],
            'sms_server' => ['nullable', 'url', 'max:200', 'regex:#^https?://#i'],
            'sms_device' => ['nullable', 'regex:/^\d{1,6}(\|\d)?$/'],
            'sms_api_key' => ['nullable', 'string', 'max:200'],
        ], ['sms_device.regex' => 'Device ID লিখুন যেমন 1, অথবা নির্দিষ্ট SIM এর জন্য 1|0']);

        $put = [
            'sms_driver' => $data['sms_driver'],
            'sms_server' => $data['sms_server'] ?? '',
            'sms_device' => $data['sms_device'] ?? '',
        ];
        if ($request->boolean('remove_sms_key')) {
            $put['sms_api_key'] = '';
        } elseif (filled($data['sms_api_key'] ?? null)) {
            $put['sms_api_key'] = ShatajSmsService::storeKey($data['sms_api_key']);
        }
        Setting::put($put);

        return back()->with('success', 'Shataj SMS settings saved.');
    }

    public function testSms(Request $request, ShatajSmsService $sms)
    {
        $data = $request->validate(['mobile' => ['required', 'regex:/^(\+?88)?01[3-9]\d{8}$/']]);
        $ok = $sms->send($data['mobile'], 'Test SMS from '.Setting::siteName().' (Shataj SMS Gateway) ✓');

        return back()->with($ok ? 'success' : 'warning', $ok
            ? ($sms->isLive() ? 'Test SMS পাঠানো হয়েছে।' : 'Driver = log, তাই SMS যায়নি, storage/logs/laravel.log এ লেখা হয়েছে।')
            : 'SMS যায়নি। API key, Device ID ঠিক আছে কি না আর ফোনের SMS Gateway অ্যাপ অনলাইন কি না দেখুন (storage/logs/laravel.log)।');
    }

    public function smsStatus(ShatajSmsService $sms)
    {
        try {
            $credits = $sms->balance();
            $devices = collect($sms->devices())->map(fn ($d) => '#'.($d['ID'] ?? '?').' '.trim(($d['model'] ?? '').' '.($d['name'] ?? '')))->implode(', ');
        } catch (Throwable $e) {
            return back()->with('warning', 'Shataj SMS: '.$e->getMessage());
        }

        return back()->with('success', "Credits: {$credits} · Devices: ".($devices ?: 'কোনো ডিভাইস চালু নেই'));
    }
}
