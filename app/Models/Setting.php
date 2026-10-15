<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Throwable;

/** Site-wide key/value settings managed by the Super Admin. */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'site_name' => null,               // falls back to APP_NAME
        'site_phone' => '+880 1700-000000',
        'site_email' => null,
        'site_address' => 'Bangladesh',
        'hero_title' => null,
        'hero_badge' => 'UP TO 30% OFF',
        'hero_image' => null,
        'promo_title' => 'End of Season Sale',
        'promo_text' => "Don't miss out on our biggest sale of the year! Up to 70% off on selected items.",
        'allow_registration' => '1',
        'facebook_url' => null,
        'footer_text' => 'Developed by Shataj Soft',
        // SaaS billing
        'grace_days' => '3',
        'expiry_action' => 'downgrade',          // downgrade | readonly | suspend
        'reminder_days' => '7,3,1,0',
        'saas_bkash_number' => null,
        'saas_nagad_number' => null,
        'saas_bank_details' => null,
        'sms_notifications' => '1',
        // Phase 12: logo + login / registration page
        'site_logo' => null,
        'auth_login_bg' => null,
        'auth_register_bg' => null,
        'auth_login_video' => null,
        'auth_register_video' => null,
        'auth_show_video' => '1',
    ];

    public static function all_(): array
    {
        return Cache::rememberForever('site_settings', function () {
            try {
                $saved = static::pluck('value', 'key')->all();
            } catch (Throwable) {
                $saved = [];   // before migrations
            }

            return array_merge(self::DEFAULTS, $saved);
        });
    }

    public static function get(string $key, $default = null)
    {
        $value = static::all_()[$key] ?? null;

        return $value === null || $value === '' ? ($default ?? (self::DEFAULTS[$key] ?? null)) : $value;
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::forget('site_settings');
    }

    public static function siteName(): string
    {
        return static::get('site_name') ?: config('app.name');
    }
}
