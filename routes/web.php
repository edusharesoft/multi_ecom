<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Manager;
use App\Http\Controllers\Payment\ShatajPayController;
use App\Http\Controllers\Store;
use Illuminate\Support\Facades\Route;

/*
| Main / Landing
*/

// Fallback for hosts where public/storage is a directory instead of a symlink.
// This keeps newly uploaded/resized files immediately visible in the storefront.
Route::get('/storage/{path}', function (string $path) {
    abort_if(str_contains($path, '..'), 404);
    $full = \Illuminate\Support\Facades\Storage::disk('public')->path($path);
    abort_unless(is_file($full), 404);

    return response()->file($full);
})->where('path', '.*')->name('storage.fallback');

// User manual: serve the manual page, its screenshots and downloads through Laravel too.
// Live hosts often point the domain at public_html (or block direct file access), so the static
// files under public/ are not reachable; the page falls back to /manual-file/... automatically.
$manualFile = function (string $path) {
    abort_unless(preg_match('#^(manual-img|manual)/[A-Za-z0-9._-]+$#', $path) || $path === 'user-manual.html', 404);
    foreach ([public_path($path), base_path('public/'.$path)] as $full) {
        if (is_file($full)) {
            return response()->file($full, ['Cache-Control' => 'public, max-age=604800']);
        }
    }
    abort(404);
};
Route::get('/manual-file/{path}', $manualFile)->where('path', '.*')->name('manual.file');
Route::get('/user-manual', fn () => $manualFile('user-manual.html'))->name('user.manual');

Route::get('/', [LandingController::class, 'index'])->name('home');
Route::get('/contact-us', [LandingController::class, 'contactUs'])->name('contact.us.frontend');
Route::post('/contact-us', [LandingController::class, 'submitContactMessage'])->name('contact.submit')->middleware('throttle:5,1');
Route::get('/subscription/plan', [LandingController::class, 'subscriptionPlan'])->name('subscription.plan.frontend');
Route::get('/robots.txt', [\App\Http\Controllers\SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [\App\Http\Controllers\SeoController::class, 'sitemapIndex'])->name('sitemap');
Route::post('/subscribe', [LandingController::class, 'subscribe'])->name('subscribe')->middleware('throttle:10,1');


/*
| Branch manager auth: Registration -> OTP -> Login
*/
Route::middleware('guest:branch')->group(function () {
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/register/check-slug', [RegisterController::class, 'checkSlug'])->name('register.check-slug');
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');    // Phase 12: forgot password (SMS code to the branch mobile)
    Route::get('/password/forgot', [\App\Http\Controllers\Auth\PasswordResetController::class, 'request'])->name('password.forgot');
    Route::post('/password/forgot', [\App\Http\Controllers\Auth\PasswordResetController::class, 'send'])->middleware('throttle:5,1');
    Route::get('/password/reset', [\App\Http\Controllers\Auth\PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/password/reset', [\App\Http\Controllers\Auth\PasswordResetController::class, 'update'])->middleware('throttle:10,1');
});

// 2FA login step (after password; user is not logged in yet)
Route::middleware('guest:branch,staff')->group(function () {
    Route::get('/two-factor', [\App\Http\Controllers\TwoFactorController::class, 'challenge'])->name('two-factor.challenge');
    Route::post('/two-factor', [\App\Http\Controllers\TwoFactorController::class, 'verify'])->name('two-factor.verify')->middleware('throttle:6,1');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth:branch,staff');

Route::middleware('auth:branch')->group(function () {
    Route::get('/otp', [OtpController::class, 'show'])->name('otp.show');
    Route::post('/otp', [OtpController::class, 'verify'])->name('otp.verify')->middleware('throttle:10,1');
    Route::post('/otp/resend', [OtpController::class, 'resend'])->name('otp.resend')->middleware('throttle:5,1');
});

/*
| Branch Backend  (/manager)  — owner or staff, tenant-scoped
*/
// Phase 10 PWA — back-office / POS app (public: the files contain no private data)
Route::get('/manager/manifest.webmanifest', [\App\Http\Controllers\PwaController::class, 'managerManifest'])->name('pwa.manager.manifest');
Route::get('/manager/sw.js', [\App\Http\Controllers\PwaController::class, 'managerWorker'])->name('pwa.manager.sw');

Route::prefix('manager')->name('manager.')->group(function () {

    // Onboarding: 1) Select Subscription  2) Payment  3) Create Store   (owner only)
    Route::middleware(['auth:branch', 'tenant', 'branch.verified'])->prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/plan', [Manager\OnboardingController::class, 'plan'])->name('plan');
        Route::post('/plan', [Manager\OnboardingController::class, 'choosePlan'])->name('plan.choose');
        Route::get('/payment/{invoice}', [Manager\OnboardingController::class, 'payment'])->name('payment');
        Route::post('/payment/{invoice}/skip', [Manager\OnboardingController::class, 'skipPayment'])->name('payment.skip');
        Route::get('/store', [Manager\OnboardingController::class, 'store'])->name('store');
        Route::post('/store', [Manager\OnboardingController::class, 'saveStore'])->name('store.save');
    });

    Route::middleware(['auth:branch,staff', 'tenant', 'branch.verified'])->group(function () {
        // Billing (reachable even when read-only / suspended for non-payment)
        Route::middleware('perm:subscription.view')->group(function () {
            Route::get('/billing', [Manager\BillingController::class, 'index'])->name('billing');
            Route::get('/billing/plans', [Manager\BillingController::class, 'plans'])->name('billing.plans');
            Route::get('/billing/invoices/{invoice}', [Manager\BillingController::class, 'invoice'])->name('billing.invoice');
        });
        Route::middleware('perm:subscription.edit')->group(function () {
            Route::post('/billing/change', [Manager\BillingController::class, 'change'])->name('billing.change');
            Route::post('/billing/renew', [Manager\BillingController::class, 'renew'])->name('billing.renew');
            Route::post('/billing/cancel', [Manager\BillingController::class, 'cancel'])->name('billing.cancel');
            Route::post('/billing/resume', [Manager\BillingController::class, 'resume'])->name('billing.resume');
            Route::post('/billing/invoices/{invoice}/pay', [Manager\BillingController::class, 'pay'])->name('billing.pay');
        });
        // Account security (2FA) — every owner / staff member, also when read-only
        Route::get('/security', [\App\Http\Controllers\TwoFactorController::class, 'manager'])->name('security');
        Route::post('/security/2fa/enable', [\App\Http\Controllers\TwoFactorController::class, 'enable'])->name('security.enable')->middleware('throttle:10,1');
        Route::post('/security/2fa/disable', [\App\Http\Controllers\TwoFactorController::class, 'disable'])->name('security.disable')->middleware('throttle:10,1');
        Route::post('/security/2fa/recovery', [\App\Http\Controllers\TwoFactorController::class, 'regenerate'])->name('security.recovery')->middleware('throttle:10,1');
        Route::post('/export', \App\Http\Controllers\Manager\ExportController::class)->name('export')->middleware('throttle:5,1');
        Route::get('/notifications', [Manager\NotificationController::class, 'index'])->name('notifications');
        Route::post('/notifications/read', [Manager\NotificationController::class, 'readAll'])->name('notifications.read');
    });

    Route::middleware(['auth:branch,staff', 'tenant', 'branch.verified', 'onboarded', 'readonly'])->group(function () {
        Route::get('/', [Manager\DashboardController::class, 'index'])->name('dashboard');

        Route::get('/settings', [Manager\SettingsController::class, 'edit'])->name('settings');
        Route::put('/settings', [Manager\SettingsController::class, 'update'])->name('settings.update')->middleware('perm:settings.edit');
        Route::put('/settings/password', [Manager\SettingsController::class, 'password'])->name('settings.password');
        Route::put('/settings/shatajpay', [Manager\SettingsController::class, 'shatajpay'])->name('settings.shatajpay')->middleware('perm:settings.edit');

        Route::middleware('perm:products.view')->group(function () {
            Route::get('/products', [Manager\ProductController::class, 'index'])->name('products.index');
            Route::get('/products/labels', [Manager\ProductController::class, 'labels'])->name('products.labels')->middleware('perm:products.print');
            Route::get('/brands', [Manager\BrandController::class, 'index'])->name('brands.index');
            Route::post('/brands', [Manager\BrandController::class, 'store'])->name('brands.store')->middleware('perm:products.add');
            Route::put('/brands/{brand}', [Manager\BrandController::class, 'update'])->name('brands.update')->middleware('perm:products.edit');
            Route::delete('/brands/{brand}', [Manager\BrandController::class, 'destroy'])->name('brands.destroy')->middleware('perm:products.delete');
            Route::get('/products/create', [Manager\ProductController::class, 'create'])->name('products.create')->middleware('perm:products.add');
            Route::post('/products', [Manager\ProductController::class, 'store'])->name('products.store')->middleware('perm:products.add');
            Route::get('/products/{product}/edit', [Manager\ProductController::class, 'edit'])->name('products.edit')->middleware('perm:products.edit');
            Route::put('/products/{product}', [Manager\ProductController::class, 'update'])->name('products.update')->middleware('perm:products.edit');
            Route::delete('/products/{product}', [Manager\ProductController::class, 'destroy'])->name('products.destroy')->middleware('perm:products.delete');
            Route::delete('/products/images/{image}', [Manager\ProductController::class, 'destroyImage'])->name('products.images.destroy')->middleware('perm:products.edit');
        });

        Route::middleware('perm:orders.view')->group(function () {
            Route::get('/orders', [Manager\OrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/{order}', [Manager\OrderController::class, 'show'])->name('orders.show');
            Route::patch('/orders/{order}/status', [Manager\OrderController::class, 'updateStatus'])->name('orders.status')->middleware('perm:orders.edit');
            Route::patch('/orders/{order}/payment', [Manager\OrderController::class, 'updatePayment'])->name('orders.payment')->middleware('perm:orders.approve');
            Route::post('/orders/bulk-status', [Manager\OrderController::class, 'bulkStatus'])->name('orders.bulk')->middleware('perm:orders.edit');
            Route::post('/orders/{order}/refund', [Manager\OrderController::class, 'refund'])->name('orders.refund')->middleware('perm:orders.approve');
            Route::get('/orders/{order}/print/{type}', [Manager\OrderController::class, 'print'])->name('orders.print')->middleware('perm:orders.print');
        });

        Route::middleware('perm:returns.view')->group(function () {
            Route::get('/returns', [Manager\ReturnController::class, 'index'])->name('returns.index');
            Route::get('/returns/{return}', [Manager\ReturnController::class, 'show'])->name('returns.show');
            Route::patch('/returns/{return}', [Manager\ReturnController::class, 'update'])->name('returns.update')->middleware('perm:returns.edit');
        });

        Route::middleware('perm:customers.view')->group(function () {
            Route::get('/customers', [Manager\CustomerController::class, 'index'])->name('customers.index');
            Route::post('/customers', [Manager\CustomerController::class, 'store'])->name('customers.store')->middleware('perm:customers.add');
            Route::get('/customers/{customer}', [Manager\CustomerController::class, 'show'])->name('customers.show');
            Route::put('/customers/{customer}', [Manager\CustomerController::class, 'update'])->name('customers.update')->middleware('perm:customers.edit');
            Route::post('/customers/{customer}/wallet', [Manager\CustomerController::class, 'wallet'])->name('customers.wallet')->middleware('perm:customers.edit');
            Route::post('/customers/{customer}/points', [Manager\CustomerController::class, 'points'])->name('customers.points')->middleware('perm:customers.edit');
        });

        Route::middleware('perm:reviews.view')->group(function () {
            Route::get('/reviews', [Manager\ReviewController::class, 'index'])->name('reviews.index');
            Route::patch('/reviews/{review}', [Manager\ReviewController::class, 'update'])->name('reviews.update')->middleware('perm:reviews.approve');
            Route::delete('/reviews/{review}', [Manager\ReviewController::class, 'destroy'])->name('reviews.destroy')->middleware('perm:reviews.delete');
        });

        Route::middleware(['feature:coupon', 'perm:coupons.view'])->group(function () {
            Route::get('/coupons', [Manager\CouponController::class, 'index'])->name('coupons.index');
            Route::post('/coupons', [Manager\CouponController::class, 'store'])->name('coupons.store')->middleware('perm:coupons.add');
            Route::put('/coupons/{coupon}', [Manager\CouponController::class, 'update'])->name('coupons.update')->middleware('perm:coupons.edit');
            Route::delete('/coupons/{coupon}', [Manager\CouponController::class, 'destroy'])->name('coupons.destroy')->middleware('perm:coupons.delete');
        });

        Route::middleware('perm:website.view')->group(function () {
            Route::get('/sliders', [Manager\SliderController::class, 'index'])->name('sliders.index');
            Route::post('/sliders', [Manager\SliderController::class, 'store'])->name('sliders.store')->middleware('perm:website.edit');
            Route::put('/sliders/{slider}', [Manager\SliderController::class, 'update'])->name('sliders.update')->middleware('perm:website.edit');
            Route::delete('/sliders/{slider}', [Manager\SliderController::class, 'destroy'])->name('sliders.destroy')->middleware('perm:website.edit');
        });

        Route::middleware('perm:staff.view')->group(function () {
            Route::get('/staff', [Manager\StaffController::class, 'index'])->name('staff.index');
            Route::post('/staff', [Manager\StaffController::class, 'store'])->name('staff.store')->middleware('perm:staff.add');
            Route::put('/staff/{staff}', [Manager\StaffController::class, 'update'])->name('staff.update')->middleware('perm:staff.edit');
            Route::delete('/staff/{staff}', [Manager\StaffController::class, 'destroy'])->name('staff.destroy')->middleware('perm:staff.delete');
            Route::get('/roles', [Manager\RoleController::class, 'index'])->name('roles.index');
            Route::post('/roles', [Manager\RoleController::class, 'store'])->name('roles.store')->middleware('perm:staff.add');
            Route::put('/roles/{role}', [Manager\RoleController::class, 'update'])->name('roles.update')->middleware('perm:staff.edit');
            Route::delete('/roles/{role}', [Manager\RoleController::class, 'destroy'])->name('roles.destroy')->middleware('perm:staff.delete');
        });

        Route::get('/activity', [Manager\ActivityController::class, 'index'])->name('activity')->middleware('perm:activity.view');

        // Product lookup JSON (POS, purchases, adjustments, transfers)
        Route::get('/inventory/lookup', [Manager\InventoryController::class, 'lookup'])->name('inventory.lookup');

        // Inventory / Warehouse (plan feature: inventory)
        Route::middleware(['feature:inventory', 'perm:inventory.view'])->group(function () {
            Route::get('/warehouses', [Manager\WarehouseController::class, 'index'])->name('warehouses.index');
            Route::post('/warehouses', [Manager\WarehouseController::class, 'store'])->name('warehouses.store')->middleware('perm:inventory.add');
            Route::put('/warehouses/{warehouse}', [Manager\WarehouseController::class, 'update'])->name('warehouses.update')->middleware('perm:inventory.edit');
            Route::delete('/warehouses/{warehouse}', [Manager\WarehouseController::class, 'destroy'])->name('warehouses.destroy')->middleware('perm:inventory.edit');
            Route::get('/inventory', [Manager\InventoryController::class, 'stock'])->name('inventory.stock');
            Route::get('/inventory/ledger', [Manager\InventoryController::class, 'ledger'])->name('inventory.ledger');
            Route::get('/inventory/adjustments', [Manager\InventoryController::class, 'adjustments'])->name('inventory.adjustments');
            Route::post('/inventory/adjustments', [Manager\InventoryController::class, 'storeAdjustment'])->name('inventory.adjustments.store')->middleware('perm:inventory.approve');
            Route::get('/inventory/transfers', [Manager\InventoryController::class, 'transfers'])->name('inventory.transfers');
            Route::post('/inventory/transfers', [Manager\InventoryController::class, 'storeTransfer'])->name('inventory.transfers.store')->middleware('perm:inventory.edit');
            Route::get('/inventory/doc/{type}/{number}', [Manager\InventoryController::class, 'document'])->name('inventory.document');
        });

        // Suppliers & Purchases (plan feature: inventory)
        Route::middleware(['feature:inventory', 'perm:suppliers.view'])->group(function () {
            Route::get('/suppliers', [Manager\SupplierController::class, 'index'])->name('suppliers.index');
            Route::post('/suppliers', [Manager\SupplierController::class, 'store'])->name('suppliers.store')->middleware('perm:suppliers.add');
            Route::get('/suppliers/{supplier}', [Manager\SupplierController::class, 'show'])->name('suppliers.show');
            Route::put('/suppliers/{supplier}', [Manager\SupplierController::class, 'update'])->name('suppliers.update')->middleware('perm:suppliers.edit');
            Route::delete('/suppliers/{supplier}', [Manager\SupplierController::class, 'destroy'])->name('suppliers.destroy')->middleware('perm:suppliers.delete');
            Route::post('/suppliers/{supplier}/pay', [Manager\SupplierController::class, 'pay'])->name('suppliers.pay')->middleware('perm:purchases.edit');
        });
        Route::middleware(['feature:inventory', 'perm:purchases.view'])->group(function () {
            Route::get('/purchases', [Manager\PurchaseController::class, 'index'])->name('purchases.index');
            Route::get('/purchases/create', [Manager\PurchaseController::class, 'create'])->name('purchases.create')->middleware('perm:purchases.add');
            Route::post('/purchases', [Manager\PurchaseController::class, 'store'])->name('purchases.store')->middleware('perm:purchases.add');
            Route::get('/purchases/{purchase}', [Manager\PurchaseController::class, 'show'])->name('purchases.show');
            Route::post('/purchases/{purchase}/pay', [Manager\PurchaseController::class, 'pay'])->name('purchases.pay')->middleware('perm:purchases.edit');
            Route::post('/purchases/{purchase}/return', [Manager\PurchaseController::class, 'storeReturn'])->name('purchases.return')->middleware('perm:purchases.edit');
        });

        // POS (plan feature: pos)
        Route::middleware(['feature:pos', 'perm:pos.view'])->prefix('pos')->name('pos.')->group(function () {
            Route::get('/', [Manager\PosController::class, 'index'])->name('index');
            Route::post('/open', [Manager\PosController::class, 'open'])->name('open')->middleware('perm:pos.add');
            Route::get('/close', [Manager\PosController::class, 'closing'])->name('closing');
            Route::post('/close', [Manager\PosController::class, 'close'])->name('close')->middleware('perm:pos.add');
            Route::post('/cash', [Manager\PosController::class, 'cash'])->name('cash')->middleware('perm:pos.add');
            Route::get('/sessions', [Manager\PosController::class, 'sessions'])->name('sessions');
            Route::get('/sessions/{session}', [Manager\PosController::class, 'session'])->name('session');
            Route::get('/catalog', [Manager\PosController::class, 'catalog'])->name('catalog');
            Route::get('/customers', [Manager\PosController::class, 'customers'])->name('customers');
            Route::post('/customers', [Manager\PosController::class, 'quickCustomer'])->name('customers.store')->middleware('perm:pos.add');
            Route::post('/checkout', [Manager\PosController::class, 'checkout'])->name('checkout')->middleware('perm:pos.add');
            Route::get('/holds', [Manager\PosController::class, 'holds'])->name('holds');
            Route::post('/holds', [Manager\PosController::class, 'hold'])->name('holds.store')->middleware('perm:pos.add');
            Route::delete('/holds/{hold}', [Manager\PosController::class, 'deleteHold'])->name('holds.destroy')->middleware('perm:pos.add');
            Route::get('/receipt/{order}', [Manager\PosController::class, 'receipt'])->name('receipt');
            Route::get('/returns', [Manager\PosController::class, 'returns'])->name('returns')->middleware('perm:pos.approve');
            Route::post('/returns/{order}', [Manager\PosController::class, 'processReturn'])->name('returns.process')->middleware('perm:pos.approve');
        });

        // Reports (plan feature: reports)
        Route::middleware(['feature:reports', 'perm:reports.view'])->prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [Manager\ReportController::class, 'index'])->name('index');
            Route::get('/sales', [Manager\ReportController::class, 'sales'])->name('sales');
            Route::get('/products', [Manager\ReportController::class, 'products'])->name('products');
            Route::get('/tax', [Manager\ReportController::class, 'tax'])->name('tax');
            Route::get('/customers', [Manager\ReportController::class, 'customers'])->name('customers');
            Route::get('/dues', [Manager\ReportController::class, 'dues'])->name('dues');
            Route::middleware('feature:accounting')->group(function () {
                Route::get('/profit-loss', [Manager\ReportController::class, 'profitLoss'])->name('profit-loss');
                Route::get('/purchases', [Manager\ReportController::class, 'purchases'])->name('purchases');
            });
        });

        // Courier (API couriers need plan feature: courier; manual courier always available)
        Route::middleware('perm:orders.view')->group(function () {
            Route::get('/shipments', [Manager\CourierController::class, 'index'])->name('shipments.index');
            Route::post('/shipments/sync-all', [Manager\CourierController::class, 'syncAll'])->name('shipments.sync-all')->middleware('perm:orders.edit');
            Route::post('/shipments/{shipment}/sync', [Manager\CourierController::class, 'sync'])->name('shipments.sync')->middleware('perm:orders.edit');
            Route::patch('/shipments/{shipment}/status', [Manager\CourierController::class, 'setStatus'])->name('shipments.status')->middleware('perm:orders.edit');
            Route::post('/orders/{order}/ship', [Manager\CourierController::class, 'book'])->name('orders.ship')->middleware('perm:orders.edit');
            Route::post('/orders/bulk-ship', [Manager\CourierController::class, 'bulkBook'])->name('orders.bulk-ship')->middleware(['perm:orders.edit', 'feature:courier']);
            Route::get('/courier/redx-areas', [Manager\CourierController::class, 'areas'])->name('courier.areas');
            Route::get('/courier/fraud-check', [Manager\CourierController::class, 'fraudCheck'])->name('courier.fraud');
        });
        Route::middleware('perm:settings.edit')->group(function () {
            Route::get('/settings/courier', [Manager\CourierController::class, 'settings'])->name('courier.settings');
            Route::post('/settings/courier/{courier}', [Manager\CourierController::class, 'save'])->name('courier.save');
            Route::post('/settings/courier/{courier}/test', [Manager\CourierController::class, 'test'])->name('courier.test');
        });

        // Marketing & notifications
        Route::middleware('perm:marketing.view')->prefix('marketing')->name('marketing.')->group(function () {
            Route::get('/', [Manager\MarketingController::class, 'index'])->name('index');
            Route::get('/create', [Manager\MarketingController::class, 'create'])->name('create')->middleware('perm:marketing.add');
            Route::post('/preview', [Manager\MarketingController::class, 'preview'])->name('preview')->middleware('perm:marketing.add');
            Route::post('/', [Manager\MarketingController::class, 'store'])->name('store')->middleware('perm:marketing.add');
            Route::get('/logs', [Manager\MarketingController::class, 'logs'])->name('logs');
            Route::get('/settings', [Manager\MarketingController::class, 'settings'])->name('settings')->middleware('perm:marketing.edit');
            Route::post('/settings', [Manager\MarketingController::class, 'saveSettings'])->name('settings.save')->middleware('perm:marketing.edit');
            Route::post('/test-sms', [Manager\MarketingController::class, 'testSms'])->name('test-sms')->middleware('perm:marketing.edit');
            Route::get('/{campaign}', [Manager\MarketingController::class, 'show'])->name('show');
            Route::post('/{campaign}/start', [Manager\MarketingController::class, 'start'])->name('start')->middleware('perm:marketing.add');
            Route::post('/{campaign}/step', [Manager\MarketingController::class, 'step'])->name('step')->middleware('perm:marketing.add');
            Route::post('/{campaign}/cancel', [Manager\MarketingController::class, 'cancel'])->name('cancel')->middleware('perm:marketing.add');
            Route::post('/{campaign}/duplicate', [Manager\MarketingController::class, 'duplicate'])->name('duplicate')->middleware('perm:marketing.add');
        });

        // Website builder, pages, SEO
        Route::middleware('perm:website.view')->group(function () {
            Route::get('/website/theme', [Manager\WebsiteController::class, 'theme'])->name('website.theme');
            Route::post('/website/theme', [Manager\WebsiteController::class, 'saveTheme'])->name('website.theme.save')->middleware('perm:website.edit');
            Route::get('/website/homepage', [Manager\WebsiteController::class, 'builder'])->name('website.builder');
            Route::post('/website/homepage', [Manager\WebsiteController::class, 'saveBuilder'])->name('website.builder.save')->middleware('perm:website.edit');
            Route::post('/website/homepage/reset', [Manager\WebsiteController::class, 'resetBuilder'])->name('website.builder.reset')->middleware('perm:website.edit');
            Route::get('/pages', [Manager\PageController::class, 'index'])->name('pages.index');
            Route::get('/pages/create', [Manager\PageController::class, 'create'])->name('pages.create')->middleware('perm:website.edit');
            Route::post('/pages', [Manager\PageController::class, 'store'])->name('pages.store')->middleware('perm:website.edit');
            Route::get('/pages/{page}/edit', [Manager\PageController::class, 'edit'])->name('pages.edit')->middleware('perm:website.edit');
            Route::put('/pages/{page}', [Manager\PageController::class, 'update'])->name('pages.update')->middleware('perm:website.edit');
            Route::delete('/pages/{page}', [Manager\PageController::class, 'destroy'])->name('pages.destroy')->middleware('perm:website.edit');
        });
        Route::post('/editor/image', [Manager\WebsiteController::class, 'uploadImage'])->name('editor.image')->middleware('throttle:30,1');
        Route::middleware('perm:settings.edit')->group(function () {
            Route::get('/website/seo', [Manager\WebsiteController::class, 'seo'])->name('website.seo');
            Route::post('/website/seo', [Manager\WebsiteController::class, 'saveSeo'])->name('website.seo.save');
            Route::get('/domains', [Manager\DomainController::class, 'index'])->name('domains.index');
            Route::post('/domains', [Manager\DomainController::class, 'store'])->name('domains.store');
            Route::post('/domains/{domain}/verify', [Manager\DomainController::class, 'verify'])->name('domains.verify')->middleware('throttle:20,1');
            Route::patch('/domains/{domain}', [Manager\DomainController::class, 'update'])->name('domains.update');
            Route::delete('/domains/{domain}', [Manager\DomainController::class, 'destroy'])->name('domains.destroy');
        });

        // Blog (plan feature: blog)
        Route::middleware(['feature:blog', 'perm:blog.view'])->group(function () {
            Route::get('/blog', [Manager\BlogController::class, 'index'])->name('blog.index');
            Route::get('/blog/create', [Manager\BlogController::class, 'create'])->name('blog.create')->middleware('perm:blog.add');
            Route::post('/blog', [Manager\BlogController::class, 'store'])->name('blog.store')->middleware('perm:blog.add');
            Route::get('/blog/{post}/edit', [Manager\BlogController::class, 'edit'])->name('blog.edit')->middleware('perm:blog.edit');
            Route::put('/blog/{post}', [Manager\BlogController::class, 'update'])->name('blog.update')->middleware('perm:blog.edit');
            Route::delete('/blog/{post}', [Manager\BlogController::class, 'destroy'])->name('blog.destroy')->middleware('perm:blog.delete');
            Route::post('/blog-categories', [Manager\BlogController::class, 'storeCategory'])->name('blog.categories.store')->middleware('perm:blog.edit');
            Route::delete('/blog-categories/{category}', [Manager\BlogController::class, 'destroyCategory'])->name('blog.categories.destroy')->middleware('perm:blog.delete');
        });

        // REST API tokens & webhooks (settings.edit; API calls need plan feature "api")
        Route::middleware('perm:settings.edit')->group(function () {
            Route::get('/api', [Manager\ApiSettingsController::class, 'index'])->name('api.index');
            Route::post('/api/tokens', [Manager\ApiSettingsController::class, 'store'])->name('api.tokens.store');
            Route::delete('/api/tokens/{token}', [Manager\ApiSettingsController::class, 'destroy'])->name('api.tokens.destroy');
            Route::post('/api/webhook', [Manager\ApiSettingsController::class, 'saveWebhook'])->name('api.webhook');
            Route::post('/api/webhook/test', [Manager\ApiSettingsController::class, 'testWebhook'])->name('api.webhook.test')->middleware('throttle:10,1');
        });

        // HRM (plan feature: hrm)
        Route::middleware(['feature:hrm', 'perm:hrm.view'])->prefix('hrm')->name('hrm.')->group(function () {
            Route::get('/employees', [Manager\HrmController::class, 'employees'])->name('employees');
            Route::post('/employees', [Manager\HrmController::class, 'saveEmployee'])->name('employees.store')->middleware('perm:hrm.add');
            Route::put('/employees/{employee}', [Manager\HrmController::class, 'saveEmployee'])->name('employees.update')->middleware('perm:hrm.edit');
            Route::get('/attendance', [Manager\HrmController::class, 'attendance'])->name('attendance');
            Route::post('/attendance', [Manager\HrmController::class, 'saveAttendance'])->name('attendance.save')->middleware('perm:hrm.edit');
            Route::get('/attendance/report', [Manager\HrmController::class, 'attendanceReport'])->name('attendance.report');
            Route::get('/leaves', [Manager\HrmController::class, 'leaves'])->name('leaves');
            Route::post('/leaves', [Manager\HrmController::class, 'storeLeave'])->name('leaves.store')->middleware('perm:hrm.add');
            Route::patch('/leaves/{leave}', [Manager\HrmController::class, 'decideLeave'])->name('leaves.decide')->middleware('perm:hrm.approve');
            Route::get('/payroll', [Manager\HrmController::class, 'payrolls'])->name('payrolls');
            Route::post('/payroll', [Manager\HrmController::class, 'generatePayroll'])->name('payrolls.generate')->middleware('perm:hrm.edit');
            Route::get('/payroll/{payroll}', [Manager\HrmController::class, 'payroll'])->name('payroll');
            Route::put('/payroll/{payroll}', [Manager\HrmController::class, 'updatePayroll'])->name('payroll.update')->middleware('perm:hrm.edit');
            Route::post('/payroll/{payroll}/pay', [Manager\HrmController::class, 'payPayroll'])->name('payroll.pay')->middleware('perm:hrm.approve');
            Route::get('/payslip/{item}', [Manager\HrmController::class, 'payslip'])->name('payslip');
        });

        // AI assistant (plan feature: ai; each call counts against ai_requests_month)
        Route::middleware(['feature:ai', 'throttle:20,1'])->prefix('ai')->name('ai.')->group(function () {
            Route::post('/product', [Manager\AiController::class, 'product'])->name('product');
            Route::post('/blog', [Manager\AiController::class, 'blog'])->name('blog');
            Route::post('/campaign', [Manager\AiController::class, 'campaign'])->name('campaign');
            Route::post('/reviews/{review}/reply', [Manager\AiController::class, 'reviewReply'])->name('review-reply');
            Route::post('/insights', [Manager\AiController::class, 'generateInsights'])->name('insights.generate')->middleware('perm:reports.view');
        });
        Route::get('/ai/insights', [Manager\AiController::class, 'insights'])->name('ai.insights')->middleware('perm:reports.view');

        // Accounting (plan feature: accounting)
        Route::middleware(['feature:accounting', 'perm:accounts.view'])->group(function () {
            Route::get('/accounts', [Manager\AccountController::class, 'index'])->name('accounts.index');
            Route::post('/accounts', [Manager\AccountController::class, 'store'])->name('accounts.store')->middleware('perm:accounts.add');
            Route::put('/accounts/{account}', [Manager\AccountController::class, 'update'])->name('accounts.update')->middleware('perm:accounts.edit');
            Route::post('/account-categories', [Manager\AccountController::class, 'storeCategory'])->name('accounts.categories.store')->middleware('perm:accounts.edit');
            Route::post('/account-categories/{category}/toggle', [Manager\AccountController::class, 'toggleCategory'])->name('accounts.categories.toggle')->middleware('perm:accounts.edit');
            Route::get('/cashbook', [Manager\AccountController::class, 'cashbook'])->name('accounts.cashbook');
            Route::get('/account-transfers', [Manager\AccountController::class, 'transfers'])->name('accounts.transfers');
            Route::post('/account-transfers', [Manager\AccountController::class, 'storeTransfer'])->name('accounts.transfers.store')->middleware('perm:accounts.add');
            Route::delete('/account-transfers/{transfer}', [Manager\AccountController::class, 'destroyTransfer'])->name('accounts.transfers.destroy')->middleware('perm:accounts.delete');
            Route::get('/vouchers', [Manager\VoucherController::class, 'index'])->name('vouchers.index');
            Route::post('/vouchers', [Manager\VoucherController::class, 'store'])->name('vouchers.store')->middleware('perm:accounts.add');
            Route::delete('/vouchers/{voucher}', [Manager\VoucherController::class, 'destroy'])->name('vouchers.destroy')->middleware('perm:accounts.delete');
        });
    });
});

/*
| Super Admin Backend  (/admin)
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:web')->group(function () {
        Route::get('/login', [Admin\AuthController::class, 'show'])->name('login');
        Route::post('/login', [Admin\AuthController::class, 'login'])->middleware('throttle:10,1');
        Route::get('/two-factor', [\App\Http\Controllers\TwoFactorController::class, 'challenge'])->name('two-factor.challenge');
        Route::post('/two-factor', [\App\Http\Controllers\TwoFactorController::class, 'verify'])->name('two-factor.verify')->middleware('throttle:6,1');
    });

    Route::middleware(['auth:web', 'super.admin'])->group(function () {
        Route::post('/logout', [Admin\AuthController::class, 'logout'])->name('logout');
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

        Route::get('/branches', [Admin\BranchController::class, 'index'])->name('branches.index');
        Route::get('/branches/{branch}', [Admin\BranchController::class, 'show'])->name('branches.show');
        Route::get('/branches/{branch}/edit', [Admin\BranchController::class, 'edit'])->name('branches.edit');
        Route::put('/branches/{branch}', [Admin\BranchController::class, 'update'])->name('branches.update');
        Route::patch('/branches/{branch}/suspend', [Admin\BranchController::class, 'suspend'])->name('branches.suspend');
        Route::patch('/branches/{branch}/activate', [Admin\BranchController::class, 'activate'])->name('branches.activate');
        Route::patch('/branches/{branch}/verify', [Admin\BranchController::class, 'verify'])->name('branches.verify');
        Route::post('/branches/{branch}/impersonate', [Admin\BranchController::class, 'impersonate'])->name('branches.impersonate');
        Route::delete('/branches/{branch}', [Admin\BranchController::class, 'destroy'])->name('branches.destroy');

        Route::get('/categories', [Admin\CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [Admin\CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [Admin\CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [Admin\CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');

        // SaaS
        Route::get('/plans', [Admin\PlanController::class, 'index'])->name('plans.index');
        Route::get('/plans/create', [Admin\PlanController::class, 'create'])->name('plans.create');
        Route::post('/plans', [Admin\PlanController::class, 'store'])->name('plans.store');
        Route::get('/plans/{plan}/edit', [Admin\PlanController::class, 'edit'])->name('plans.edit');
        Route::put('/plans/{plan}', [Admin\PlanController::class, 'update'])->name('plans.update');
        Route::delete('/plans/{plan}', [Admin\PlanController::class, 'destroy'])->name('plans.destroy');

        Route::get('/subscriptions', [Admin\SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::put('/subscriptions/{subscription}', [Admin\SubscriptionController::class, 'update'])->name('subscriptions.update');
        Route::post('/subscriptions/{subscription}/extend', [Admin\SubscriptionController::class, 'extend'])->name('subscriptions.extend');
        Route::post('/subscriptions/run', [Admin\SubscriptionController::class, 'run'])->name('subscriptions.run');

        Route::get('/invoices', [Admin\InvoiceController::class, 'index'])->name('invoices.index');
        Route::post('/invoices', [Admin\InvoiceController::class, 'store'])->name('invoices.store');
        Route::get('/invoices/{invoice}', [Admin\InvoiceController::class, 'show'])->name('invoices.show');
        Route::patch('/invoices/{invoice}/approve', [Admin\InvoiceController::class, 'approve'])->name('invoices.approve');
        Route::patch('/invoices/{invoice}/reject', [Admin\InvoiceController::class, 'reject'])->name('invoices.reject');
        Route::patch('/invoices/{invoice}/cancel', [Admin\InvoiceController::class, 'cancel'])->name('invoices.cancel');

        Route::get('/coupons', [Admin\CouponController::class, 'index'])->name('coupons.index');
        Route::post('/coupons', [Admin\CouponController::class, 'store'])->name('coupons.store');
        Route::put('/coupons/{coupon}', [Admin\CouponController::class, 'update'])->name('coupons.update');
        Route::delete('/coupons/{coupon}', [Admin\CouponController::class, 'destroy'])->name('coupons.destroy');

        Route::get('/commission', [Admin\CommissionController::class, 'index'])->name('commission');
        Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports');
        Route::get('/messages', [Admin\MessageController::class, 'index'])->name('messages');
        Route::get('/security', [\App\Http\Controllers\TwoFactorController::class, 'admin'])->name('security');
        Route::post('/security/2fa/enable', [\App\Http\Controllers\TwoFactorController::class, 'enable'])->name('security.enable')->middleware('throttle:10,1');
        Route::post('/security/2fa/disable', [\App\Http\Controllers\TwoFactorController::class, 'disable'])->name('security.disable')->middleware('throttle:10,1');
        Route::post('/security/2fa/recovery', [\App\Http\Controllers\TwoFactorController::class, 'regenerate'])->name('security.recovery')->middleware('throttle:10,1');
        // main website banner slideshow (images only)
        Route::get('/home-slider', [Admin\HomeSliderController::class, 'index'])->name('sliders.index');
        Route::post('/home-slider', [Admin\HomeSliderController::class, 'store'])->name('sliders.store');
        Route::put('/home-slider/{slide}', [Admin\HomeSliderController::class, 'update'])->name('sliders.update')->where('slide', '[A-Za-z0-9-]+');
        Route::delete('/home-slider/{slide}', [Admin\HomeSliderController::class, 'destroy'])->name('sliders.destroy')->where('slide', '[A-Za-z0-9-]+');
        Route::get('/ai', [Admin\AiSettingsController::class, 'index'])->name('ai');
        Route::post('/ai', [Admin\AiSettingsController::class, 'save'])->name('ai.save');
        Route::post('/ai/test', [Admin\AiSettingsController::class, 'test'])->name('ai.test')->middleware('throttle:5,1');
        Route::get('/backups', [Admin\BackupController::class, 'index'])->name('backups.index');
        Route::post('/backups', [Admin\BackupController::class, 'create'])->name('backups.create');
        Route::post('/backups/settings', [Admin\BackupController::class, 'settings'])->name('backups.settings');
        Route::get('/backups/{name}', [Admin\BackupController::class, 'download'])->name('backups.download')->where('name', '[A-Za-z0-9._-]+');
        Route::delete('/backups/{name}', [Admin\BackupController::class, 'destroy'])->name('backups.destroy')->where('name', '[A-Za-z0-9._-]+');
        Route::post('/backups/{name}/restore', [Admin\BackupController::class, 'restore'])->name('backups.restore')->where('name', '[A-Za-z0-9._-]+');
        Route::get('/domains', [Admin\DomainController::class, 'index'])->name('domains.index');
        Route::post('/domains/settings', [Admin\DomainController::class, 'settings'])->name('domains.settings');
        Route::patch('/domains/{domain}', [Admin\DomainController::class, 'status'])->name('domains.status');
        Route::get('/activity', [Admin\ActivityLogController::class, 'index'])->name('activity');
        Route::get('/notifications', [Admin\ActivityLogController::class, 'notifications'])->name('notifications');

        Route::get('/subscribers', [Admin\SettingController::class, 'subscribers'])->name('subscribers');
        Route::get('/settings', [Admin\SettingController::class, 'edit'])->name('settings');
        Route::put('/settings', [Admin\SettingController::class, 'update'])->name('settings.update');
        Route::post('/settings/test-sms', [Admin\SettingController::class, 'testSms'])->name('settings.test-sms')->middleware('throttle:5,1');
        Route::put('/settings/account', [Admin\SettingController::class, 'account'])->name('settings.account');
        // Payment & SMS gateways (ShatajPay + Shataj SMS)
        Route::get('/gateways', [Admin\GatewayController::class, 'index'])->name('gateways');
        Route::post('/gateways/shatajpay', [Admin\GatewayController::class, 'savePay'])->name('gateways.pay');
        Route::post('/gateways/shatajpay/test', [Admin\GatewayController::class, 'testPay'])->name('gateways.pay.test')->middleware('throttle:5,1');
        Route::post('/gateways/sms', [Admin\GatewayController::class, 'saveSms'])->name('gateways.sms');
        Route::post('/gateways/sms/test', [Admin\GatewayController::class, 'testSms'])->name('gateways.sms.test')->middleware('throttle:5,1');
        Route::post('/gateways/sms/status', [Admin\GatewayController::class, 'smsStatus'])->name('gateways.sms.status')->middleware('throttle:10,1');
    });
});

/*
| ShatajPay callbacks
*/
// Courier status webhooks (token per branch courier account)
Route::post('/webhooks/courier/{token}', \App\Http\Controllers\CourierWebhookController::class)->name('webhooks.courier')->middleware('throttle:120,1');

// Official ShatajPay callback URL (set this in the ShatajPay merchant panel): ?status=Complete|Failed&orderId=...
Route::match(['get', 'post'], '/gateway/payment/callback', [ShatajPayController::class, 'callback'])
    ->name('payment.callback.management')->middleware('throttle:60,1');

Route::prefix('payment/shatajpay')->name('shatajpay.')->middleware('throttle:60,1')->group(function () {
    Route::match(['get', 'post'], '/callback', [ShatajPayController::class, 'callback'])->name('callback');
    // older names kept so existing links keep working
    Route::match(['get', 'post'], '/success', [ShatajPayController::class, 'callback'])->name('success');
    Route::match(['get', 'post'], '/fail', [ShatajPayController::class, 'callback'])->name('fail');
    Route::match(['get', 'post'], '/cancel', [ShatajPayController::class, 'callback'])->name('cancel');
    Route::post('/ipn', [ShatajPayController::class, 'ipn'])->name('ipn');
    Route::get('/test/{reference}', [ShatajPayController::class, 'fake'])->name('fake')->middleware('signed');
});

/*
| Branch storefront: www.domain.com/{branch-slug}/...   (keep LAST)
*/
Route::prefix('{branch:slug}')
    ->where(['branch' => '[a-z0-9][a-z0-9-]{1,58}[a-z0-9]'])
    ->middleware('store')
    ->name('store.')
    ->group(function () {
        Route::get('/', [Store\StoreController::class, 'home'])->name('home');
        Route::get('/contact', [Store\ContentController::class, 'contact'])->name('contact');
        Route::post('/contact', [Store\ContentController::class, 'submitContact'])->name('contact.submit')->middleware('throttle:5,1');
        // Phase 10 PWA — installable store app
        Route::get('/manifest.webmanifest', [\App\Http\Controllers\PwaController::class, 'storeManifest'])->name('pwa.manifest');
        Route::get('/sw.js', [\App\Http\Controllers\PwaController::class, 'storeWorker'])->name('pwa.sw');
        Route::get('/pwa-icon-{size}.png', [\App\Http\Controllers\PwaController::class, 'storeIcon'])->name('pwa.icon')->whereIn('size', ['192', '512']);
        Route::get('/sitemap.xml', [Store\ContentController::class, 'sitemap'])->name('sitemap');
        Route::post('/chat', Store\ChatController::class)->name('chat')->middleware('throttle:10,1');
        Route::get('/page/{slug}', [Store\ContentController::class, 'page'])->name('page');
        Route::get('/blog', [Store\ContentController::class, 'blog'])->name('blog');
        Route::get('/blog/{slug}', [Store\ContentController::class, 'post'])->name('blog.show');
        Route::get('/products', [Store\StoreController::class, 'products'])->name('products');
        Route::get('/product/{slug}', [Store\StoreController::class, 'product'])->name('product');
        Route::post('/product/{slug}/review', [Store\StoreController::class, 'review'])->name('review')->middleware('throttle:5,1');

        Route::post('/product/{slug}/review/{review}/report', [Store\StoreController::class, 'reportReview'])->name('review.report')->middleware('throttle:10,1');
        Route::post('/subscribe', [Store\StoreController::class, 'subscribe'])->name('subscribe')->middleware('throttle:10,1');

        Route::get('/cart', [Store\CartController::class, 'index'])->name('cart');
        Route::post('/cart/coupon', [Store\CartController::class, 'applyCoupon'])->name('cart.coupon')->middleware('throttle:20,1');
        Route::delete('/cart/coupon', [Store\CartController::class, 'removeCoupon'])->name('cart.coupon.remove');
        Route::post('/cart/add', [Store\CartController::class, 'add'])->name('cart.add');
        Route::patch('/cart/update', [Store\CartController::class, 'update'])->name('cart.update');
        Route::delete('/cart/remove', [Store\CartController::class, 'remove'])->name('cart.remove');
        Route::delete('/cart/clear', [Store\CartController::class, 'clear'])->name('cart.clear');

        Route::get('/wishlist', [Store\WishlistController::class, 'index'])->name('wishlist');
        Route::post('/wishlist/{product}', [Store\WishlistController::class, 'toggle'])->name('wishlist.toggle');

        Route::get('/checkout', [Store\CheckoutController::class, 'show'])->name('checkout');
        Route::post('/checkout', [Store\CheckoutController::class, 'place'])->name('checkout.place')->middleware('throttle:10,1');
        Route::get('/order/{order}/payment', [Store\CheckoutController::class, 'payment'])->name('payment');
        Route::post('/order/{order}/payment', [Store\CheckoutController::class, 'pay'])->name('payment.pay');
        Route::get('/order/{order}', [Store\CheckoutController::class, 'success'])->name('order.success');
        Route::get('/track', [Store\CheckoutController::class, 'track'])->name('track');

        // Customer account
        Route::get('/account/login', [Store\AccountController::class, 'showLogin'])->name('account.login');
        Route::post('/account/login', [Store\AccountController::class, 'login'])->middleware('throttle:10,1');
        Route::get('/account/register', [Store\AccountController::class, 'showRegister'])->name('account.register');
        Route::post('/account/register', [Store\AccountController::class, 'register'])->middleware('throttle:10,1');
        Route::middleware('customer.auth')->prefix('account')->group(function () {
            Route::get('/', [Store\AccountController::class, 'dashboard'])->name('account');
            Route::post('/logout', [Store\AccountController::class, 'logout'])->name('account.logout');
            Route::get('/orders/{order}', [Store\AccountController::class, 'order'])->name('account.order');
            Route::post('/orders/{order}/return', [Store\AccountController::class, 'requestReturn'])->name('account.return');
            Route::get('/profile', [Store\AccountController::class, 'profile'])->name('account.profile');
            Route::put('/profile', [Store\AccountController::class, 'updateProfile'])->name('account.profile.update');
            Route::put('/password', [Store\AccountController::class, 'updatePassword'])->name('account.password');
            Route::get('/addresses', [Store\AccountController::class, 'addresses'])->name('account.addresses');
            Route::post('/addresses', [Store\AccountController::class, 'saveAddress'])->name('account.addresses.save');
            Route::delete('/addresses/{address}', [Store\AccountController::class, 'deleteAddress'])->name('account.addresses.delete');
            Route::get('/wallet', [Store\AccountController::class, 'wallet'])->name('account.wallet');
            Route::get('/returns', [Store\AccountController::class, 'returns'])->name('account.returns');
        });
    });
