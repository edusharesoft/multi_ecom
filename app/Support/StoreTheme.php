<?php

namespace App\Support;

use App\Models\Branch;
use Illuminate\Support\Str;

/**
 * Website builder settings kept in branches.options:
 *   theme          => colours, font, announcement bar, footer, social links
 *   home_sections  => ordered list of homepage blocks
 *   menu           => extra header links
 *   seo            => store-level meta / verification tags
 */
class StoreTheme
{
    public const FONTS = [
        'Poppins' => 'Poppins', 'Hind Siliguri' => 'Hind Siliguri (বাংলা)', 'Noto Sans Bengali' => 'Noto Sans Bengali',
        'Inter' => 'Inter', 'Roboto' => 'Roboto', 'Montserrat' => 'Montserrat', 'Lato' => 'Lato',
    ];

    public const PRESETS = [
        '#e41e26' => 'Red (default)', '#2563eb' => 'Blue', '#16a34a' => 'Green', '#9333ea' => 'Purple',
        '#ea580c' => 'Orange', '#db2777' => 'Pink', '#0f766e' => 'Teal', '#111827' => 'Black',
    ];

    /** type => [label, repeatable, has limit] */
    public const SECTIONS = [
        'hero' => ['Hero slider / banner', false, false],
        'stats' => ['Stats band (products, orders…)', false, false],
        'flash_sale' => ['Flash sale + countdown', false, true],
        'categories' => ['Popular categories', false, true],
        'new_arrivals' => ['New arrivals', false, true],
        'banners' => ['Promo banners (Slider & Banner → banner)', false, false],
        'best_selling' => ['Best selling', false, true],
        'discounted' => ['Discount products', false, true],
        'featured' => ['Featured showcase', false, true],
        'brands' => ['Brands strip', false, true],
        'reviews' => ['Customer reviews', false, true],
        'blog' => ['Latest blog posts', false, true],
        'text' => ['Text / HTML block', true, false],
        'image_banner' => ['Image banner with link', true, false],
    ];

    public const DEFAULT_ORDER = ['hero', 'stats', 'flash_sale', 'categories', 'new_arrivals', 'banners', 'best_selling', 'discounted', 'featured', 'brands', 'reviews', 'blog'];

    public static function theme(Branch $b): array
    {
        return array_merge([
            'primary' => '#e41e26', 'font' => 'Poppins', 'radius' => 12,
            'tagline' => 'Style for Every Style | Shop Now at '.$b->display_name,
            'announcement_text' => '', 'announcement_link' => '', 'announcement_bg' => '#111827',
            'footer_about' => '', 'instagram' => '', 'youtube' => '', 'tiktok' => '',
            'show_newsletter' => true, 'show_all_branches' => true,
        ], (array) $b->option('theme', []));
    }

    /** Ordered homepage sections, each [id, type, enabled, title, limit, content, image, link]. */
    public static function sections(Branch $b): array
    {
        $saved = $b->option('home_sections');
        if (! is_array($saved) || ! $saved) {
            return array_map(fn ($t) => self::blank($t), self::DEFAULT_ORDER);
        }
        $out = [];
        foreach ($saved as $s) {
            if (! isset(self::SECTIONS[$s['type'] ?? ''])) {
                continue;
            }
            $out[] = array_merge(self::blank($s['type']), array_intersect_key($s, array_flip(['id', 'enabled', 'title', 'limit', 'content', 'image', 'link'])));
        }

        return $out;
    }

    public static function blank(string $type): array
    {
        return ['id' => $type.'-'.Str::lower(Str::random(6)), 'type' => $type, 'enabled' => true, 'title' => '', 'limit' => 8, 'content' => '', 'image' => '', 'link' => ''];
    }

    /** [[label, url]] extra header links */
    public static function menu(Branch $b): array
    {
        return array_values(array_filter((array) $b->option('menu', []), fn ($m) => ! empty($m['label']) && ! empty($m['url'])));
    }

    public static function seo(Branch $b): array
    {
        return array_merge(['title' => '', 'description' => '', 'keywords' => '', 'google_verification' => '', 'bing_verification' => '', 'noindex' => false],
            (array) $b->option('seo', []));
    }

    /** Darker shade of a hex colour for hover states. */
    public static function shade(string $hex, float $f = 0.8): string
    {
        $hex = ltrim($hex, '#');
        if (! preg_match('/^[0-9a-f]{6}$/i', $hex)) {
            return '#b8141b';
        }
        $c = array_map(fn ($i) => max(0, min(255, (int) round(hexdec(substr($hex, $i, 2)) * $f))), [0, 2, 4]);

        return sprintf('#%02x%02x%02x', ...$c);
    }

    /** Very light tint for soft backgrounds. */
    public static function tint(string $hex, float $f = 0.9): string
    {
        $hex = ltrim($hex, '#');
        if (! preg_match('/^[0-9a-f]{6}$/i', $hex)) {
            return '#fdecec';
        }
        $c = array_map(fn ($i) => (int) round(hexdec(substr($hex, $i, 2)) + (255 - hexdec(substr($hex, $i, 2))) * $f), [0, 2, 4]);

        return sprintf('#%02x%02x%02x', ...$c);
    }
}
