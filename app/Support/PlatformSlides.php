<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Main-website (landing page) banner slides, managed by the Super Admin (Site → Home Slider).
 * Stored as JSON in settings.home_slides: [{id, image, link, sort, active}, …]
 */
class PlatformSlides
{
    public const KEY = 'home_slides';

    public const MAX = 10;

    public static function all(): array
    {
        $rows = json_decode((string) Setting::get(self::KEY, '[]'), true);
        $rows = is_array($rows) ? array_values(array_filter($rows, fn ($r) => is_array($r) && ! empty($r['id']) && ! empty($r['image']))) : [];
        usort($rows, fn ($a, $b) => ((int) ($a['sort'] ?? 0)) <=> ((int) ($b['sort'] ?? 0)));

        return $rows;
    }

    public static function save(array $rows): void
    {
        Setting::put([self::KEY => json_encode(array_values($rows), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
    }

    /** Slides for the landing page; falls back to the old single hero image, then the default banner. */
    public static function forLanding(): array
    {
        $slides = [];
        foreach (self::all() as $r) {
            if (! empty($r['active'])) {
                $slides[] = ['url' => asset('storage/'.$r['image']), 'link' => $r['link'] ?? null, 'alt' => Setting::siteName()];
            }
        }
        if (! $slides) {
            $hero = Setting::get('hero_image');
            $slides[] = ['url' => $hero ? asset('storage/'.$hero) : asset('images/banner.svg'), 'link' => null, 'alt' => Setting::siteName()];
        }

        return $slides;
    }
}
