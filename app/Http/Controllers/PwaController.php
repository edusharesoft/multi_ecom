<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Setting;
use App\Support\StoreTheme;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Phase 10 — Progressive Web App.
 *
 * Storefront: every store is its own installable app (name, colour, logo icon), scoped to the
 * store's URL (/{slug}) or to "/" on its custom domain.
 * Manager: one app for the back-office, opening on the POS; the POS keeps selling offline.
 */
class PwaController extends Controller
{
    /** bump when the service worker logic changes → browsers install the new worker */
    public const VERSION = 'v1';

    /* ---------------- storefront ---------------- */

    /** scope + start url of the store app: "/" on its own domain, "/{slug}" on the platform */
    public static function storeScope(Branch $branch): string
    {
        if (app()->bound('custom_domain_branch')) {
            return '/';
        }

        return rtrim((string) parse_url(route('store.home', $branch), PHP_URL_PATH), '/') ?: '/';
    }

    public function storeManifest(Branch $branch)
    {
        $theme = StoreTheme::theme($branch);
        $scope = self::storeScope($branch);
        $name = $branch->display_name;

        return response()->json([
            'id' => $scope,
            'name' => $name,
            'short_name' => Str::limit($name, 12, ''),
            'description' => Str::limit(strip_tags((string) ($branch->about ?: $theme['tagline'])), 150),
            'lang' => app()->getLocale(),
            'start_url' => $scope.'?source=pwa',
            'scope' => $scope,
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#ffffff',
            'theme_color' => $theme['primary'],
            'icons' => [
                ['src' => $this->storeIconUrl($branch, 192), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $this->storeIconUrl($branch, 512), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $branch->logo ? $this->storeIconUrl($branch, 512) : asset('images/pwa/store-maskable-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
            'shortcuts' => [
                ['name' => __('Products'), 'url' => route('store.products', $branch)],
                ['name' => __('Track Order'), 'url' => route('store.track', $branch)],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json', 'Cache-Control' => 'no-cache'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function storeWorker(Branch $branch)
    {
        $scope = self::storeScope($branch);

        return $this->worker(view('pwa.store-sw', [
            'config' => [
                'app' => 's'.$branch->id,
                'version' => self::VERSION,
                'scope' => $scope,
                'base' => rtrim((string) parse_url(route('store.home', $branch), PHP_URL_PATH), '/'),
                'start' => route('store.home', $branch),
                'precache' => self::precacheList(),
                'name' => $branch->display_name,
                'color' => StoreTheme::theme($branch)['primary'],
                // never cached: personal / changing pages
                'private' => ['/cart', '/checkout', '/account', '/order/', '/payment', '/wishlist', '/track', '/chat', '/logout', '/sw.js', '/manifest.webmanifest'],
            ],
        ])->render(), $scope);
    }

    /** Icon generated from the store logo (GD), else the default shop icon. */
    public function storeIcon(Branch $branch, int $size)
    {
        abort_unless(in_array($size, [192, 512], true), 404);
        $path = $this->makeStoreIcon($branch, $size);

        return $path
            ? response()->file(Storage::disk('public')->path($path), ['Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=604800'])
            : redirect(asset("images/pwa/store-{$size}.png"));
    }

    protected function storeIconUrl(Branch $branch, int $size): string
    {
        if (! $branch->logo) {
            return asset("images/pwa/store-{$size}.png");
        }

        // version in the query string so a new logo gets a new icon
        return route('store.pwa.icon', [$branch, 'size' => $size]).'?v='.substr(md5((string) $branch->logo), 0, 8);
    }

    protected function makeStoreIcon(Branch $branch, int $size): ?string
    {
        if (! $branch->logo || ! function_exists('imagecreatetruecolor')) {
            return null;
        }
        $disk = Storage::disk('public');
        $out = 'pwa/'.$branch->id.'-'.$size.'-'.substr(md5((string) $branch->logo), 0, 8).'.png';
        if ($disk->exists($out)) {
            return $out;
        }
        try {
            if (! $disk->exists($branch->logo) || $disk->size($branch->logo) > 5 * 1048576) {
                return null;
            }
            $bytes = $disk->get($branch->logo);
            $info = @getimagesizefromstring($bytes);
            if (! $info || $info[0] * $info[1] > 16_000_000) {   // too large for GD memory
                return null;
            }
            $src = @imagecreatefromstring($bytes);
            if (! $src) {
                return null;
            }
            $canvas = imagecreatetruecolor($size, $size);
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
            // logo fitted into the centre 70 % (safe zone for round / "maskable" icons)
            $box = (int) round($size * 0.7);
            $w = imagesx($src);
            $h = imagesy($src);
            $scale = min($box / max(1, $w), $box / max(1, $h));
            $nw = max(1, (int) round($w * $scale));
            $nh = max(1, (int) round($h * $scale));
            imagecopyresampled($canvas, $src, (int) (($size - $nw) / 2), (int) (($size - $nh) / 2), 0, 0, $nw, $nh, $w, $h);
            ob_start();
            imagepng($canvas, null, 9);
            $png = (string) ob_get_clean();
            imagedestroy($src);
            imagedestroy($canvas);
            foreach ($disk->files('pwa') as $old) {   // remove icons of an earlier logo
                if (str_starts_with(basename($old), $branch->id.'-'.$size.'-')) {
                    $disk->delete($old);
                }
            }
            $disk->put($out, $png);

            return $out;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /* ---------------- manager / POS ---------------- */

    public static function managerScope(): string
    {
        return rtrim((string) parse_url(url('/manager'), PHP_URL_PATH), '/').'/';
    }

    public function managerManifest()
    {
        $scope = self::managerScope();
        $site = Setting::siteName();

        return response()->json([
            'id' => $scope,
            'name' => $site.' — POS & Manager',
            'short_name' => 'POS',
            'description' => 'Point of Sale and store management',
            'start_url' => rtrim($scope, '/').'/pos?source=pwa',
            'scope' => $scope,
            'display' => 'standalone',
            'background_color' => '#111827',
            'theme_color' => '#111827',
            'icons' => [
                ['src' => asset('images/pwa/pos-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => asset('images/pwa/pos-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => asset('images/pwa/pos-maskable-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
            'shortcuts' => [
                ['name' => 'POS', 'url' => rtrim($scope, '/').'/pos'],
                ['name' => 'Orders', 'url' => rtrim($scope, '/').'/orders'],
                ['name' => 'Dashboard', 'url' => rtrim($scope, '/').'/dashboard'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json', 'Cache-Control' => 'no-cache'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function managerWorker()
    {
        $scope = self::managerScope();

        return $this->worker(view('pwa.manager-sw', [
            'config' => [
                'app' => 'm',
                'version' => self::VERSION,
                'scope' => $scope,
                'precache' => array_merge(self::precacheList(), [asset('js/pos-offline.js').'?v='.self::VERSION]),
                'maxPages' => 2,
                'pos' => rtrim($scope, '/').'/pos',
                'start' => url('/manager/pos'),   // cached at install → POS opens offline even after a single visit
                'name' => Setting::siteName(),
            ],
        ])->render(), $scope);
    }

    /** files every page needs (partials/head + layouts) — cached at install so the first offline start works */
    public static function precacheList(): array
    {
        return [
            asset('css/app.css').'?v=1',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/webfonts/fa-solid-900.woff2',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/webfonts/fa-regular-400.woff2',
            'https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap',
        ];
    }

    protected function worker(string $js, string $scope)
    {
        return response($js, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Service-Worker-Allowed' => $scope,
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
