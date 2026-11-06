<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Subscriber;
use App\Services\Sms\ShatajSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings', [
            's' => Setting::all_(),
            'sms' => app(ShatajSmsService::class)->config(),
            'pay' => app(\App\Services\Payment\ShatajPayService::class)->platformConfig(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'site_name' => ['nullable', 'string', 'max:100'],
            'default_locale' => ['nullable', 'in:bn,en'],
            'site_phone' => ['nullable', 'string', 'max:30'],
            'site_email' => ['nullable', 'email', 'max:150'],
            'site_address' => ['nullable', 'string', 'max:255'],
            'hero_title' => ['nullable', 'string', 'max:120'],
            'hero_badge' => ['nullable', 'string', 'max:60'],
            'promo_title' => ['nullable', 'string', 'max:120'],
            'promo_text' => ['nullable', 'string', 'max:300'],
            'facebook_url' => ['nullable', 'url', 'max:255', 'regex:#^https?://#i'],
            'footer_text' => ['nullable', 'string', 'max:150'],
            'hero_image' => ['nullable', 'image', 'max:4096'],
            // footer (main website)
            'footer_about' => ['nullable', 'string', 'max:400'],
            'footer_video' => ['nullable', 'string', 'max:255'],
            'footer_hours' => ['nullable', 'string', 'max:120'],
            'footer_whatsapp' => ['nullable', 'string', 'max:200', 'regex:#^(https?://\S+|\+?[\d\s-]{10,16})$#i'],
            'footer_instagram' => ['nullable', 'url', 'max:200', 'regex:#^https?://#i'],
            'footer_youtube' => ['nullable', 'url', 'max:200', 'regex:#^https?://#i'],
            'footer_tiktok' => ['nullable', 'url', 'max:200', 'regex:#^https?://#i'],
            'footer_links' => ['nullable', 'string', 'max:3000'],
            'footer_copyright' => ['nullable', 'string', 'max:150'],
            // Phase 12: logo + login / registration page look
            'site_logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'auth_login_bg' => ['nullable', 'image', 'max:4096'],
            'auth_register_bg' => ['nullable', 'image', 'max:4096'],
            'auth_login_video' => ['nullable', 'string', 'max:255'],
            'auth_register_video' => ['nullable', 'string', 'max:255'],
            'grace_days' => ['required', 'integer', 'min:0', 'max:60'],
            'expiry_action' => ['required', Rule::in(['downgrade', 'readonly', 'suspend'])],
            'reminder_days' => ['required', 'regex:/^\d{1,2}(,\d{1,2})*$/'],
            'saas_bkash_number' => ['nullable', 'regex:/^01[3-9]\d{8}$/'],
            'saas_nagad_number' => ['nullable', 'regex:/^01[3-9]\d{8}$/'],
            'saas_bank_details' => ['nullable', 'string', 'max:500'],
        ], ['reminder_days.regex' => 'Reminder days: comma separated numbers, e.g. 7,3,1,0']);
        $data['allow_registration'] = $request->boolean('allow_registration') ? '1' : '0';
        $data['sms_notifications'] = $request->boolean('sms_notifications') ? '1' : '0';
        $data['auth_show_video'] = $request->boolean('auth_show_video') ? '1' : '0';
        $data['footer_show_tiles'] = $request->boolean('footer_show_tiles') ? '1' : '0';
        $data['footer_show_pay'] = $request->boolean('footer_show_pay') ? '1' : '0';
        $data['footer_video'] = trim((string) ($data['footer_video'] ?? '')) ?: null;
        if ($data['footer_video'] && ! \App\Support\SiteFooter::youtubeEmbedUrl($data['footer_video'])) {
            return back()->withInput()->withErrors(['footer_video' => 'Enter a valid YouTube video link.']);
        }
        $links = \App\Support\SiteFooter::parseLinks($data['footer_links'] ?? '', $badLinks);
        if ($badLinks) {
            return back()->withInput()->withErrors(['footer_links' => 'এই লাইনগুলো ঠিক নেই (লিখুন: নাম | https://... অথবা নাম | /page): '.implode(' ; ', array_slice($badLinks, 0, 3))]);
        }
        $data['footer_links'] = json_encode($links, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        foreach (['auth_login_video', 'auth_register_video'] as $v) {
            if (! empty($data[$v]) && ! \App\Support\AuthTheme::youtubeId($data[$v])) {
                return back()->withInput()->withErrors([$v => 'সঠিক YouTube লিংক দিন (যেমন https://www.youtube.com/watch?v=XXXXXXXXXXX)।']);
            }
        }
        foreach (['site_logo', 'auth_login_bg', 'auth_register_bg'] as $key) {
            $old = Setting::get($key);
            if ($request->hasFile($key)) {
                $data[$key] = app(\App\Services\ImageUploadService::class)->store($request->file($key), 'site');
            } elseif ($request->boolean('remove_'.$key)) {
                $data[$key] = null;
            } else {
                unset($data[$key]);
                continue;
            }
            if ($old) {
                Storage::disk('public')->delete($old);
            }
        }

        if ($request->hasFile('hero_image')) {
            if ($old = Setting::get('hero_image')) {
                Storage::disk('public')->delete($old);
            }
            $data['hero_image'] = app(\App\Services\ImageUploadService::class)->store($request->file('hero_image'), 'site');
        } else {
            unset($data['hero_image']);
        }

        $oldFooterVideo = Setting::get('footer_video');
        if ($oldFooterVideo && ! preg_match('#^https?://#i', $oldFooterVideo)) {
            Storage::disk('public')->delete($oldFooterVideo);
        }

        Setting::put($data);

        return back()->with('success', 'Site settings saved.');
    }

    public function testSms(Request $request, ShatajSmsService $sms)
    {
        $data = $request->validate(['mobile' => ['required', 'regex:/^(\+?88)?01[3-9]\d{8}$/']]);
        $ok = $sms->send($data['mobile'], 'Test SMS from '.Setting::siteName().' (Shataj SMS Gateway).');

        return back()->with($ok ? 'success' : 'warning', $ok
            ? ($sms->isLive() ? 'Test SMS sent.' : 'SMS driver is "log" — message written to storage/logs/laravel.log.')
            : 'SMS failed — check storage/logs/laravel.log and Admin → Payment & SMS Gateways.');
    }

    public function account(Request $request)
    {
        $user = $request->user('web');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['required', 'current_password:web'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);
        unset($data['current_password']);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);

        return back()->with('success', 'Admin account updated.');
    }

    public function subscribers()
    {
        return view('admin.subscribers', ['subscribers' => Subscriber::latest()->paginate(50)]);
    }
}
