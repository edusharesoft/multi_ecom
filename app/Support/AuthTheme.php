<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Setting;
use Throwable;

/**
 * Phase 12 — login / registration / OTP page look.
 *
 * Background image and YouTube video are set by the Super Admin (Settings → Login & Branding),
 * separately for the login and the registration pages (registration falls back to login).
 * The logo follows the branch: store customer pages and /login?branch={slug} (also remembered
 * after a branch logs in) show that branch's logo, otherwise the platform logo.
 */
class AuthTheme
{
    public const KINDS = ['login', 'register'];

    /** Branch whose logo the page should show. */
    public static function branch(): ?Branch
    {
        try {
            if (($b = request()->route('branch')) instanceof Branch) {
                return $b;                                   // storefront customer pages
            }
            if (($u = auth('branch')->user()) instanceof Branch) {
                return $u;                                   // OTP page
            }
            $slug = request()->query('branch') ?: request()->cookie('mb_last_branch');
            if (is_string($slug) && preg_match('/^[a-z0-9-]{3,60}$/', $slug)) {
                return Branch::where('slug', $slug)->whereNull('suspended_at')->whereNotNull('mobile_verified_at')
                    ->first(['id', 'name', 'store_name', 'slug', 'logo', 'favicon', 'options']);   // only real, active stores (no fake branding via ?branch=)
            }
        } catch (Throwable) {
        }

        return null;
    }

    public static function logo(?Branch $branch = null): ?string
    {
        if ($branch && $branch->logo) {
            return $branch->logo_url;
        }
        $site = Setting::get('site_logo');

        return $site ? asset('storage/'.$site) : null;
    }

    public static function name(?Branch $branch = null): string
    {
        return $branch ? $branch->display_name : Setting::siteName();
    }

    public static function background(string $kind): ?string
    {
        $path = Setting::get('auth_'.$kind.'_bg') ?: Setting::get('auth_login_bg');

        return $path ? asset('storage/'.$path) : null;
    }

    /** YouTube video id for the page, or null. */
    public static function video(string $kind): ?string
    {
        if (! Setting::get('auth_show_video', '1')) {
            return null;
        }

        return self::youtubeId((string) (Setting::get('auth_'.$kind.'_video') ?: Setting::get('auth_login_video')));
    }

    /** Accepts watch?v=, youtu.be/, /shorts/, /embed/, /live/ links or a bare 11-character id. */
    public static function youtubeId(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }
        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $url)) {
            return $url;
        }
        if (preg_match('~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})~i', $url, $m)) {
            return $m[1];
        }

        return null;
    }
}
