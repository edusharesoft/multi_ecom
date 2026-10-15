<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * UI language: ?lang=bn|en (remembered in session) → session → store default (branch option default_locale)
 * → platform default (Super Admin setting default_locale) → config('app.locale').
 */
class SetLocale
{
    public const LOCALES = ['bn' => 'বাংলা', 'en' => 'English'];

    public function handle(Request $request, Closure $next): Response
    {
        $lang = $request->query('lang');
        if (is_string($lang) && isset(self::LOCALES[$lang]) && $request->hasSession()) {
            $request->session()->put('locale', $lang);
        }
        $locale = $request->hasSession() ? $request->session()->get('locale') : null;
        if (! $locale) {
            $branch = $request->route('branch');
            $locale = $branch instanceof Branch ? $branch->option('default_locale') : null;
        }
        $locale = $locale ?: (Setting::get('default_locale') ?: config('app.locale'));
        if (! isset(self::LOCALES[$locale])) {
            $locale = 'en';
        }
        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
