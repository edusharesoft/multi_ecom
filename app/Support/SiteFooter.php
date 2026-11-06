<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Setting;
use Illuminate\Support\Collection;

/**
 * Footer content for the main website (Super Admin → Settings → Footer)
 * and for every store (Manager → Website → Theme → Footer).
 * Rendered by partials/site-footer.blade.php.
 */
class SiteFooter
{
    public const MAX_LINKS = 12;

    /**
     * "Label | URL" per line → [['label' => …, 'url' => …]].
     * Invalid lines are returned in $bad so the controller can show an error.
     */
    public static function parseLinks(?string $text, ?array &$bad = null): array
    {
        $bad = [];
        $out = [];
        foreach (preg_split('/\R/', (string) $text) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            [$label, $url] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
            if ($label === '' || ! preg_match('#^(https?://|/)#i', $url) || mb_strlen($label) > 40 || strlen($url) > 250) {
                $bad[] = $line;
                continue;
            }
            $out[] = ['label' => $label, 'url' => $url];
        }

        return array_slice($out, 0, self::MAX_LINKS);
    }

    public static function linksToText(array $links): string
    {
        return collect($links)->map(fn ($l) => ($l['label'] ?? '').' | '.($l['url'] ?? ''))->implode("\n");
    }

    public static function platformLinks(): array
    {
        $links = json_decode((string) Setting::get('footer_links', '[]'), true);

        return is_array($links) ? $links : [];
    }

    /** Return a safe YouTube embed URL for a supported YouTube video link. */
    public static function youtubeEmbedUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $isYoutubeHost = $host === 'youtu.be'
            || $host === 'youtube.com'
            || str_ends_with($host, '.youtube.com')
            || $host === 'youtube-nocookie.com'
            || str_ends_with($host, '.youtube-nocookie.com');

        if (! $isYoutubeHost || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return null;
        }

        $videoId = AuthTheme::youtubeId($url);

        return $videoId ? 'https://www.youtube-nocookie.com/embed/'.$videoId : null;
    }

    /** Main website footer data. */
    public static function platform(): array
    {
        $logo = Setting::get('site_logo');
        $links = self::platformLinks() ?: [
            ['label' => __('Home'), 'url' => route('home')],
            ['label' => __('Our Branches'), 'url' => route('home').'#branches'],
            ['label' => __('How to Buy'), 'url' => route('home').'#how'],
            ['label' => __('Open a Branch'), 'url' => route('register')],
        ];

        return [
            'name' => Setting::siteName(),
            'logo' => $logo ? asset('storage/'.$logo) : null,
            'about' => Setting::get('footer_about'),
            'video_url' => self::youtubeEmbedUrl(Setting::get('footer_video')),
            'address' => Setting::get('site_address'),
            'phone' => Setting::get('site_phone'),
            'email' => Setting::get('site_email'),
            'map_src' => 'https://maps.google.com/maps?q='.rawurlencode(Setting::get('site_address', 'Bangladesh')).'&z=12&output=embed',
            'hours' => Setting::get('footer_hours'),
            'socials' => self::socials([
                'facebook' => Setting::get('facebook_url'), 'instagram' => Setting::get('footer_instagram'),
                'youtube' => Setting::get('footer_youtube'), 'tiktok' => Setting::get('footer_tiktok'),
                'whatsapp' => Setting::get('footer_whatsapp'),
            ]),
            'links' => $links,
            'copyright' => Setting::get('footer_copyright'),
            'credit' => Setting::get('footer_text'),
            'show_tiles' => Setting::get('footer_show_tiles', '1') !== '0',
            'show_pay' => Setting::get('footer_show_pay', '1') !== '0',
            'tiles' => [
                ['icon' => 'fa-store', 'label' => __('Our Branches'), 'url' => route('home').'#branches', 'bg' => 'linear-gradient(135deg,#f472b6,#fb923c)'],
                ['icon' => 'fa-bag-shopping', 'label' => __('New Arrivals'), 'url' => route('home').'#arrivals', 'bg' => 'linear-gradient(135deg,#6366f1,#ec4899)'],
                ['icon' => 'fa-circle-question', 'label' => __('How to Buy'), 'url' => route('home').'#how', 'bg' => 'linear-gradient(135deg,#0ea5e9,#22c55e)'],
                ['icon' => 'fa-envelope-open-text', 'label' => __('Contact'), 'url' => route('home').'#contact', 'bg' => 'linear-gradient(135deg,#f59e0b,#ef4444)'],
            ],
        ];
    }

    /** Store footer data. */
    public static function store(Branch $store, array $theme, ?Collection $footerPages = null): array
    {
        $links = (array) ($theme['footer_links'] ?? []);
        foreach (($footerPages ?? collect())->where('show_in_footer', true) as $pg) {
            $links[] = ['label' => $pg->title, 'url' => route('store.page', [$store, $pg->slug])];
        }
        if (! $links) {
            $links = [
                ['label' => __('All Products'), 'url' => route('store.products', $store)],
                ['label' => __('Offer'), 'url' => route('store.products', [$store, 'sale' => 1])],
                ['label' => __('Track Order'), 'url' => route('store.track', $store)],
                ['label' => __('My Account'), 'url' => route('store.account', $store)],
            ];
        }
        $map = $store->map_url && preg_match('#^https?://#i', $store->map_url) ? $store->map_url : null;

        return [
            'name' => $store->display_name,
            'logo' => $store->logo_url,
            'about' => ($theme['footer_about'] ?? '') ?: $store->about,
            'video_url' => self::youtubeEmbedUrl($theme['footer_video'] ?? null),
            'address' => $store->address,
            'phone' => $store->store_phone ?: $store->mobile,
            'email' => $store->email,
            'map_src' => MapEmbed::src($store),
            'hours' => $store->opening_hours,
            'socials' => self::socials([
                'facebook' => $store->facebook_url, 'instagram' => $theme['instagram'] ?? null,
                'youtube' => $theme['youtube'] ?? null, 'tiktok' => $theme['tiktok'] ?? null,
                'whatsapp' => $theme['whatsapp'] ?? null,
            ]),
            'links' => array_slice($links, 0, self::MAX_LINKS + 6),
            'copyright' => $theme['footer_copyright'] ?? null,
            'credit' => Setting::get('footer_text'),
            'show_tiles' => (bool) ($theme['footer_show_tiles'] ?? true),
            'show_pay' => (bool) ($theme['footer_show_pay'] ?? true),
            'tiles' => [
                ['icon' => 'fa-store', 'label' => __('Our Store'), 'url' => route('store.home', $store), 'bg' => 'linear-gradient(135deg,#f472b6,#fb923c)'],
                ['icon' => 'fa-bag-shopping', 'label' => __('All Products'), 'url' => route('store.products', $store), 'bg' => 'linear-gradient(135deg,#6366f1,#ec4899)'],
                ['icon' => 'fa-truck-fast', 'label' => __('Track Order'), 'url' => route('store.track', $store), 'bg' => 'linear-gradient(135deg,#0ea5e9,#22c55e)'],
                ['icon' => 'fa-location-dot', 'label' => __('Visit Our Store'), 'url' => $map ?: route('store.home', $store).'#contact', 'bg' => 'linear-gradient(135deg,#f59e0b,#ef4444)', 'blank' => (bool) $map],
            ],
        ];
    }

    /** Only http(s) links; WhatsApp accepts a phone number too. */
    protected static function socials(array $raw): array
    {
        $out = [];
        foreach ($raw as $k => $v) {
            $v = trim((string) $v);
            if ($v === '') {
                continue;
            }
            if ($k === 'whatsapp' && preg_match('/^\+?[\d\s-]{10,16}$/', $v)) {
                $digits = preg_replace('/\D/', '', $v);
                $v = 'https://wa.me/'.(str_starts_with($digits, '01') ? '88'.$digits : $digits);
            }
            if (preg_match('#^https?://#i', $v)) {
                $out[$k] = $v;
            }
        }

        return $out;
    }
}
