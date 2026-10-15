# MASTER PROMPT — Multi-Branch + Multi-Tenant SaaS eCommerce (Laravel 12)

> এই পুরো লেখাটি কপি করে নতুন Claude চ্যাটে পাঠান। নতুন চ্যাটে কাজ শুরুর আগে আপনার কম্পিউটার (D:\laragon\www\multibranch ফোল্ডার) লিংক করে দিন।

---

## 0. তোমার ভূমিকা ও কাজের নিয়ম

তুমি একজন সিনিয়র Laravel আর্কিটেক্ট। আমার জন্য একটি production-ready **Multi-Branch + SaaS Subscription + Multi-Tenant eCommerce platform** বানাচ্ছো বা চালিয়ে যাচ্ছো। বাজার: বাংলাদেশ। মুদ্রা ৳ BDT। পেমেন্ট হবে bKash, Nagad, Rocket, COD আর ShatajPay দিয়ে। ভাষা বাংলা আর ইংরেজি।

- **আমার সাথে সবসময় বাংলায় কথা বলবে।** কোড, কমেন্ট আর UI লেবেল ইংরেজিতে হতে পারে। ব্যবহারকারীকে দেখানো মেসেজ (flash, error) বাংলায় হবে।
- **ধাপে ধাপে কাজ করবে।** প্রতিটি ধাপ শেষ হলে আমি টেস্ট করে "সব ঠিক আছে" বলব, তারপরই পরের ধাপ শুরু করবে।
- কাজ শুরুর আগে বিদ্যমান কোড পড়ে বুঝে নেবে। বিশেষ করে এগুলো: `routes/web.php`, `bootstrap/app.php`, `app/Models/*`, `app/Services/*`, `app/Support/*`, `database/migrations/*`, `resources/views/layouts/*`। বিদ্যমান প্যাটার্নের বাইরে যাবে না।
- কোনো ধাপ শেষে নিজে একটি **রিভিউ করবে** (সম্ভব হলে সাব-এজেন্ট দিয়ে)। রিভিউতে দেখবে: রানটাইম error, tenant data leak/IDOR, XSS, MySQL strict mode, টাকার হিসাব, স্টকের হিসাব, permission gap। পাওয়া বাগ ঠিক করে তারপর আমাকে দেবে।
- কাজ শেষে আমাকে বাংলায় ছোট করে বলবে: কী যোগ হলো, `update.bat` চালাতে হবে, আর কীভাবে টেস্ট করব (ধাপে ধাপে)।

## 1. পরিবেশ (খুব গুরুত্বপূর্ণ)

- **Windows + Laragon**: Apache, PHP 8.3, MySQL।
  - প্রজেক্ট: `D:\laragon\www\multibranch`
  - URL: `http://multibranch.test`
  - DB: `multibranch_db`
- **Laravel 12** (12.69.x)।
- আমার পিসিতে **queue worker চলে না।** Scheduler (`php artisan schedule:work`) না-ও চলতে পারে। তাই:
  - সব কাজ synchronous বা browser-driven হবে। যেমন বাল্ক SMS AJAX batch দিয়ে প্রগ্রেস বারসহ পাঠাবে।
  - Scheduler শুধু fallback হিসেবে থাকবে।
- **নতুন composer package যোগ করবে না** (যেমন HTMLPurifier, Sanctum ছাড়া অন্য কিছু)। দরকার হলে নিজে ছোট ক্লাস লিখবে। একান্ত প্রয়োজনে প্যাকেজ লাগলে আগে আমাকে জিজ্ঞেস করবে এবং `composer require` কমান্ড দেবে।
- **`.env` ফাইল remote টুল দিয়ে লেখা যায় না।** দরকার হলে `.env.example` আপডেট করবে এবং কী বদলাতে হবে বলে দেবে।
- আমি নিজে artisan কমান্ড চালাই। প্রতিটি ধাপে প্রজেক্ট রুটের **`update.bat`** আপডেট রাখবে। এই স্ক্রিপ্ট যা করে:
  - artisan ফাইল আছে কিনা চেক করে। না থাকলে ভুল ফোল্ডার বলে থামে।
  - `php artisan migrate --force` চালায়। fail করলে থামে আর স্ক্রিনশট পাঠাতে বলে।
  - `php artisan migrate:status` দেখায়।
  - `db:seed --class=SaasSeeder --force` আর `db:seed --class=EcommerceSeeder --force` চালায়।
  - `public\robots.txt` থাকলে `robots.txt.bak` নামে বদলে দেয়, কারণ robots.txt ডাইনামিক।
  - `public\storage` না থাকলে `storage:link` চালায়।
  - শেষে `php artisan optimize:clear` চালায় এবং `pause` দেয়।
- ফাইল লেখার পর আমার পিসির ফোল্ডারে commit করবে। Batch-এ সর্বোচ্চ ৫০টি ফাইল যাবে। পিসিতে shell টুল থাকলে সরাসরি সেখানে কাজ করবে।

## 2. আর্কিটেকচার

### Tenancy
- **Branch = tenant।** প্রতিটি ব্রাঞ্চের নিজের URL `domain.com/{slug}`। Branch-এর route key হলো `slug`।
- `App\Support\Tenancy` বর্তমান branch আর actor (owner Branch অথবা Staff) ধরে রাখে। এর মেথড: `branch()`, `can('module.action')`, `isOwner()`, `actorName()` (কেউ না থাকলে `'System'`)।
- **প্রতিটি কুয়েরি branch দিয়ে scope করবে।** Route model binding-এর পর controller-এ `abort_unless($model->branch_id === Tenancy::branch()->id, 404)` দেবে। Validation-এ `Rule::exists(...)->where('branch_id', ...)` দেবে।

### Guards
| Guard | কার জন্য | মন্তব্য |
|---|---|---|
| `web` | Super Admin | `users.is_super_admin` |
| `branch` | ব্রাঞ্চ owner | |
| `staff` | কর্মচারী | role permission JSON দিয়ে |
| `customer` | স্টোর কাস্টমার | ব্রাঞ্চভিত্তিক; `App\Support\StoreCustomer::current($branch)` |

### Middleware aliases
- সব alias `bootstrap/app.php`-এ **একটিমাত্র `$middleware->alias([...])` কলে** থাকবে। দ্বিতীয়বার কল করলে আগেরগুলো মুছে যায়।
- Alias তালিকা: `branch.verified`, `store`, `super.admin`, `tenant`, `onboarded`, `perm`, `readonly`, `feature`, `customer.auth`।
- Manager গ্রুপের middleware: `auth:branch,staff, tenant, branch.verified, onboarded, readonly`।
- ফিচার লক: `feature:pos` ইত্যাদি। Permission: `perm:orders.edit` ইত্যাদি।
- `ResolveCustomDomain` গ্লোবাল middleware, `append` দিয়ে যোগ করা (`prepend` নয়; নিচে কারণ আছে)।
- CSRF থেকে বাদ: `payment/shatajpay/*`, `webhooks/*`।

### Blade
- `@perm('x.y') … @endperm` হলো `Blade::if`। `@else` সাপোর্ট করে।
- `@money($x)` দেখায় `৳ 1,234.00`।
- Layouts: `layouts.manager`, `layouts.admin`, `layouts.store`। প্রতিটির শেষে `@stack('scripts')` আছে।
- CSS class: `panel`, `panel-head`, `panel-body`, `stat-card`, `btn-brand`।
- Frontend: Bootstrap 5.3 আর Font Awesome 6 CDN থেকে।

### SaaS
- **Plans**-এ `limits` JSON থাকে (`-1` মানে unlimited) আর `features` JSON (`key => bool`)।
  - `Plan::LIMITS`: `max_products`, `max_orders_month`, `max_customers`, `max_staff`, `max_warehouses`, `storage_mb`, `monthly_sales_limit`, `sms_month`।
  - `Plan::FEATURES`: `pos`, `inventory`, `accounting`, `courier`, `crm`, `coupon`, `reports`, `advanced_reports`, `api`, `custom_domain`, `blog`, `whatsapp`, `sms`, `email_marketing`, `advanced_marketing`।
- Branch-এর মেথড: `effectivePlan()` (সাবস্ক্রিপশন না থাকলে Free), `planLimit()`, `hasFeature()`, `usage($key)`, `withinLimit($key)`, `storeUpload()`, `deleteUpload()`, `hasStorageFor(UploadedFile ...)`, `option($key, $default)`।
  - `option()` `branches.options` JSON থেকে পড়ে। ব্রাঞ্চের ছোটখাটো সেটিং এখানে রাখা হয়: `theme`, `home_sections`, `menu`, `seo`, `notify`, `fb_pixel_id` ইত্যাদি।
- **Subscription** status: `trial`, `active`, `pending`, `grace`, `expired`, `cancelled`।
  - `saas:process` কমান্ড (hourly) reminder পাঠায় (৭/৩/১/০ দিন আগে), renewal invoice বানায়, grace দেয়, আর মেয়াদ শেষে downgrade/readonly/suspend করে।
  - Invoice-এর পেমেন্ট ShatajPay দিয়ে হয়, অথবা ম্যানুয়াল bKash/Nagad/Bank-এ, যা Super Admin অনুমোদন করে।
  - লগইনের পরের ফ্লো: Select Plan → Payment → Create Store (onboarding)।
- Order-এ plan-এর `commission_percent` অনুযায়ী প্ল্যাটফর্ম কমিশন হিসাব হয়।

### SaasSeeder
Plans: Free, Basic, Professional, Enterprise।
- **এটি merge-only।** প্ল্যান আগে থেকে থাকলে শুধু নতুন feature/limit key যোগ করবে (`$existing->features + $defaults`), কখনো overwrite করবে না। না হলে Super Admin-এর বদলানো সেটিং মুছে যায়।
- রোল না থাকা ব্রাঞ্চে ডিফল্ট রোল তৈরি করে।
- **নতুন permission পুরনো রোলে যোগ করতে হলে সেটা migration-এ একবার (one-time) করবে, seeder-এ নয়।**

### Demo লগইন
- Admin: `admin@example.com` / `password`
- ব্রাঞ্চ owner: `barishal@example.com`, `bhola@example.com`, `jhalokathi@example.com` / `password`
- কাস্টমার: `01900000001` / `password`
- Local-এ SMS driver `log` থাকলে OTP স্ক্রিনেই দেখায় (`dev_otp`)।

## 3. যা তৈরি হয়ে গেছে (ধাপ ১–৬) — একই আচরণ বজায় রাখবে

### মূল অংশ
- Branch manager রেজিস্ট্রেশন: নাম, মোবাইল, ইমেইল, slug, ঠিকানা, পাসওয়ার্ড।
- SMS OTP দিয়ে ভেরিফাই, তারপর লগইন।
- স্টোরফ্রন্ট: home, products, product, cart, checkout, payment, track, account, wishlist।
- **ShatajPay**: generic driver, `config/shataj.php`-এ মোড `fake|sandbox|live`। ফ্লো: initiate → redirect → server-side verify। একই controller Order আর Invoice দুটোই সামলায়।
- **Shataj SMS**: generic HTTP driver (`App\Services\Sms\ShatajSmsService`), `log` মোড আছে।
- Super Admin backend: plans, subscriptions, invoices, coupons, commission, branches, categories (global), orders, settings, activity, notifications।

### ধাপ ১ — SaaS ভিত্তি
- Plans, subscriptions, invoices, upgrade credit, downgrade (পিরিয়ড শেষে), trial।
- Staff ও roles: module × action permission (`App\Support\Permissions::MODULES`), ডিফল্ট রোলসহ।
- Activity log (`ActivityLog::record`) আর in-app notifications।

### ধাপ ২ — E-commerce আপগ্রেড
- Subcategory, brand, variants, flash sale (`currentPrice`/`comparePrice`), product type/unit, SEO field।
- `CouponService` (atomic usage)।
- `CartService`-এ মোট টাকা = subtotal − coupon + shipping + tax − wallet।
- `OrderService::place()`: atomic শর্তসাপেক্ষ stock decrement।
- `changeStatus()`: cancelled/failed চূড়ান্ত। বাতিল হলে stock, wallet আর coupon usage ফেরত যায়।
- ১৪টি order status (`Order::STATUS_META`), refund, return request workflow।
- Customer CRM: segment, wallet, loyalty points। Reviews।

### ধাপ ৩ — Inventory + Supplier + POS
- **স্টক রাখার নিয়ম:**
  - `products.stock` / `product_variants.stock` = সব warehouse মিলিয়ে মোট।
  - `warehouse_stocks`-এ warehouse অনুযায়ী ভাগ থাকে (`variant_id = 0` মানে সাধারণ পণ্য)।
  - `stock_movements` হলো লেজার।
  - **সব স্টক পরিবর্তন শুধু `InventoryService::move()` দিয়ে হবে।**
- Warehouse, adjustment, transfer, stock ledger, `lookup` JSON (বারকোড সার্চ)।
- Supplier ledger, purchase (stock in), purchase payment, purchase return।
- **POS:**
  - Session খোলা/বন্ধ, cash in/out, হিসাব মেলানো।
  - Barcode দিয়ে বিক্রি, hold/resume, 80mm receipt, return/exchange।
  - `order_items.returned_qty` দিয়ে একই পণ্য দুবার ফেরত নেওয়া আটকানো।
  - দাম বদল বা ১০%-এর বেশি ডিসকাউন্টে `pos.approve` permission লাগে।

### ধাপ ৪ — Accounting + Reports
- Accounts (Cash, Bank, bKash, Nagad, Rocket)। প্রতিটি অ্যাকাউন্টে `methods` JSON থাকে: কোন পেমেন্ট মেথডের টাকা কোথায় জমা হবে।
- `account_transactions` হলো cash book।
- `AccountingService::post()` idempotent: একই reference-এ `firstOrCreate` আর unique key।
- **অটো-পোস্টিং:**
  - `Payment::booted` saved hook: status success/refunded হলে পোস্ট করে। `wallet` বাদ। `original` হলে অর্ডারের মেথড ধরে।
  - `SupplierPayment` created hook।
  - POS drawer cash in/out।
- Vouchers (খরচ/আয়, ছবি সংযুক্ত করা যায়), transfers, categories।
- **Reports:** sales, products & profit, P&L, purchases/supplier due, customers, receivables, VAT/tax। CSV download আর Print।
  - COGS হিসাব হয় `stock_movements.unit_cost` থেকে।
- Admin Platform Reports: MRR/ARR, GMV, commission।

### ধাপ ৫ — Courier + Marketing + Notification
- **Courier drivers:** Steadfast, Pathao, RedX, Manual। প্রতিটিতে `demo` মোড আছে।
  - Credentials `encrypted:array` হিসেবে রাখা হয়।
  - `ShipmentService::book()`-এ `Cache::lock` আছে (দুবার বুক হওয়া আটকাতে)। এরপর sync, সাথে webhook `/webhooks/courier/{token}`। Webhook-এর payload বিশ্বাস করবে না; API থেকে status নিজে টেনে আনবে।
  - Status map: in_transit হলে অর্ডার on_the_way, delivered হলে delivered।
- **Messaging:**
  - `MessageService`: মাসিক SMS কোটা (`sms_month`, প্রতিটি অংশ আলাদা গোনা; বাংলা ৭০ অক্ষর, ইংরেজি ১৬০ অক্ষরে এক অংশ), সাথে `message_logs`।
  - `OrderNotifier`: event template `options.notify`-এ থাকে। এটা কখনো throw করবে না। Status change হুক `DB::afterCommit` দিয়ে চলে।
  - Campaigns: বাল্ক SMS/email, browser-driven batch, প্রাপকের সারি atomic claim।
- FB Pixel, GA4, GTM, WhatsApp আর Messenger বাটন।
- `courier:sync` আর `marketing:send` কমান্ড।

### ধাপ ৬ — Website builder, Blog, SEO, Domain
- `App\Support\StoreTheme`: theme (রং, ফন্ট, announcement), home_sections (ক্রম বদলানো যায়, SortableJS), menu, seo।
- হোমপেজ ভাগ করা হয়েছে `store/sections/*.blade.php` ফাইলে।
- Pages (টেমপ্লেট আছে), Blog (posts, categories, scheduled, preview), Quill editor (ছবি আপলোডসহ)।
- `App\Support\HtmlSanitizer` (DOMDocument whitelist) দিয়ে সব rich text পরিষ্কার করা হয়।
- **SEO:**
  - প্রতিটি স্টোরের `/{slug}/sitemap.xml`।
  - Root-এ `/sitemap.xml` (index) আর ডাইনামিক `/robots.txt`।
  - JSON-LD: Store, Product, BlogPosting। Meta tag আর verification tag। SEO checker।
- **Custom domain:**
  - DNS দিয়ে ভেরিফাই: TXT `_mbverify.<host>`, সাথে CNAME অথবা A।
  - `ResolveCustomDomain` `REQUEST_URI` বদলে `/{slug}/...` করে দেয় (`$request->duplicate()` দিয়ে) আর `URL::forceRootUrl` দেয়।
  - Store sub-domain অপশন আছে।

## 4. ধাপ ৭ — সম্পন্ন (API, 2FA, Backup, HRM, ভাষা)
- **REST API**: Sanctum নেই, তাই নিজস্ব token সিস্টেম।
  - `api_tokens` টেবিলে token-এর sha256 hash রাখা হয়। plain token শুধু একবার দেখানো হয়।
  - Middleware `api.token:<ability>` (যেমন `products.read`)। plan feature `api` লাগে। read-only সাবস্ক্রিপশনে write করা যায় না।
  - Routes: `routes/api.php` → `/api/v1/...` (store, categories, products, stock, orders, status, customers)। সব উত্তর JSON-এ (ForceJsonResponse)।
  - Webhooks: `WebhookService` HMAC signature পাঠায়। ইভেন্ট: `order.created`, `order.status_changed`, `order.paid`। Internal/localhost URL ব্লক (SSRF থেকে সুরক্ষা)। সেটিং পাতা: Manager → REST API।
- **2FA**:
  - `app/Support/Totp.php` (RFC 6238) আর `TwoFactor` helper। একই code দুবার ব্যবহার করা যায় না।
  - Login-এর পরে `/two-factor` আর `/admin/two-factor` challenge। Recovery code আছে।
  - সেটিং পাতা: Manager → Security (2FA) আর Admin → Security।
- **Backup**:
  - `BackupService`: pure-PHP DB dump (.sql.gz) আর uploads zip, দুটোই `storage/app/backups`-এ।
  - Restore-এর আগে নিরাপত্তা কপি নেয়, আর নিশ্চিত করতে `RESTORE` টাইপ করতে হয়।
  - `backup:run` প্রতি রাত ০২:৩০-এ চলে (Admin → Backup থেকে চালু করতে হয়)।
  - Owner নিজের স্টোরের ডেটা CSV zip আকারে export করতে পারে (Manager → Security)।
- **HRM**:
  - কর্মচারী, দৈনিক হাজিরা, মাসিক শিট, ছুটি।
  - Payroll: কর্তন হয় হাজিরা আর Unpaid ছুটি থেকে। বোনাস আর অন্যান্য কর্তন হাতে দেওয়া যায়।
  - Pay করলে Salary expense voucher তৈরি হয়। Payslip প্রিন্ট করা যায়।
  - plan feature `hrm`, permission `hrm.*`।
- **ভাষা**:
  - `SetLocale` middleware ভাষা ঠিক করে এই ক্রমে: `?lang=` → session → স্টোরের default → Admin default।
  - অনুবাদ ফাইল: `lang/bn.json` আর `lang/bn/validation.php`।
  - Layout আর স্টোরফ্রন্টের লেখা `__()` দিয়ে লেখা। নতুন UI লেখা সবসময় `__('English text')` দিয়ে লিখবে আর `lang/bn.json`-এ অনুবাদ যোগ করবে।

## 4b. ধাপ ৮ — AI ফিচার (সম্পন্ন)
- `app/Services/Ai/AiService.php`: provider বদলানো যায় (Anthropic Claude, OpenAI বা Gemini)।
  - Key encrypted অবস্থায় `settings.ai_api_key`-এ থাকে। Super Admin → AI Settings থেকে provider, model আর key দেওয়া যায়, সাথে Test বাটন।
  - Plan feature `ai`। মাসিক লিমিট `ai_requests_month`। সব কল `ai_logs`-এ লেখা থাকে।
- `AiAssistant` যা করে:
  - Product description ও SEO (product form-এ ✨ বাটন)।
  - Blog post লেখা।
  - SMS/Email campaign-এর ৩টি ভিন্ন লেখা।
  - Review-এর উত্তর সাজেশন।
  - AI Business Insights: ৩০ দিনের বিশ্লেষণ আর পূর্বাভাস, সাথে Reorder suggestion। Reorder হিসাব AI ছাড়াই হয়।
  - Storefront চ্যাটবট (`/{slug}/chat`): চালু করতে হয় Marketing → Notifications-এ `ai_chat` দিয়ে। শুধু নিজের branch-এর পণ্য আর Pages থেকে উত্তর দেয়। উত্তর plain text হিসেবে দেখায়। session আর IP দুটো দিয়েই rate limit।
- **সতর্কতা:**
  - AI output সবসময় text হিসেবে দেখাবে; HTML হলে `HtmlSanitizer` দিয়ে পরিষ্কার করবে।
  - JSON উত্তর robust ভাবে parse করবে।
  - Gemini 2.5-এ thinkingBudget 0 রাখবে।
  - OpenAI-তে `max_completion_tokens` ব্যবহার করবে।

## 4c. ধাপ ৯ — পারফরম্যান্স (সম্পন্ন)
- Migration `2026_10_01_000001_add_performance_indexes`: orders, products, reviews, customers, payments, purchases ইত্যাদিতে composite index (নাম `perf_*`)। Idempotent: টেবিল, কলাম বা একই কলামের index আগে থেকে থাকলে বাদ দেয়।
- `App\Support\StoreCache`: প্রতি store-এর জন্য version-যুক্ত cache (TTL ৫ মিনিট)। version 0 মানে platform-wide ডেটা (categories, plans)।
  - `AppServiceProvider::registerCacheInvalidation()` এই model-গুলো save বা delete হলে `bump()` করে: Product, Variant, Image, Review, Slider, Brand, Post, BlogCategory, Page, Domain, Subscription, Branch, Category, Plan।
  - নতুন কোনো model storefront-এ দেখালে এই তালিকায় যোগ করবে। Listener থেকে কখনো `false` return করবে না।
  - Cache করা হয়: home sections (key-তে section-এর গঠন থাকে), footer pages, has_blog, primary domain, category tree, brands আর sitemap (শুধু যাচাই করা host-এর জন্য)।
  - Cache-এ Branch model রাখবে না। বের করার পর `setRelation('branch', $branch)` দিয়ে লাগাবে।
- `Features::ready()`: টেবিলের তালিকা একবার cache করে। `MigrationsEnded` event হলে cache মুছে যায়।
- Manager dashboard-এর হিসাব `dash:{branch_id}` key-তে ১২০ সেকেন্ড cache থাকে। Order save হলে মুছে যায়।
- তারিখের filter-এ `whereDate()` ব্যবহার করবে না। `App\Support\Dates::start/end` দিয়ে range দেবে, তাতে index কাজ করে।
- N+1 ঠিক করা হয়েছে: reviews, customers segment, stock ledger, employees, labels, cart/POS variants।
  - Debug মোডে lazy loading হলে তা `storage/logs/lazy-loading.log`-এ লেখা হয়।
- `optimize.bat`: config, route, view আর event cache তৈরি করে, সাথে composer autoload। `update.bat` আবার normal মোডে ফিরিয়ে দেয়।
- `cache:prune-expired` প্রতিদিন 03:30-এ চলে এবং পুরনো file-cache মুছে দেয়।

## 4d. ধাপ ১০ — PWA ও অফলাইন POS (সম্পন্ন)
- `App\Http\Controllers\PwaController`: প্রতিটি store আলাদা অ্যাপ হিসেবে install হয়।
  - Route: `/{slug}/manifest.webmanifest`, `/{slug}/sw.js`, `/{slug}/pwa-icon-{192|512}.png`। Logo থাকলে GD দিয়ে আইকন বানায়, না থাকলে `public/images/pwa/*`।
  - Scope: platform-এ `/{slug}`, custom domain-এ `/` (`storeScope()`)। `Service-Worker-Allowed` header দিয়ে দেওয়া হয়।
- Manager/POS অ্যাপ: `/manager/manifest.webmanifest` আর `/manager/sw.js` (public route), scope `/manager/`, start `/manager/pos`।
- Service worker (`resources/views/pwa/*-sw.blade.php`):
  - পাতা: network-first, অফলাইনে শেষ কপি দেখায়।
  - Static আর CDN: stale-while-revalidate।
  - Cache নাম `mb-{app}-pages|assets-{VERSION}`। SW-এর logic বদলালে `PwaController::VERSION` বাড়াবে।
  - Store-এ cart, checkout, account, order, payment, wishlist, track কখনো cache হবে না।
  - Manager-এ শুধু POS পাতা cache হয়। JSON কখনো cache হবে না।
  - Logout করলে pages cache মুছে যায়।
- অফলাইন POS (`public/js/pos-offline.js`, IndexedDB `mb-pos`):
  - `manager/pos/catalog` থেকে পুরো ক্যাটালগ ডিভাইসে রাখে। সার্চ লোকাল ক্যাটালগে হয়; না পেলে সার্ভারে খোঁজে।
  - নেট না থাকলে বিক্রি queue-তে জমা হয়। নেট ফিরলে বা প্রতি ৬০ সেকেন্ডে সিঙ্ক হয়।
  - `orders.client_ref` (unique branch_id + client_ref) থাকায় একই বিক্রি দুবার হয় না।
  - অফলাইন বিক্রি: negative stock চলে, tendered ≥ total ধরা হয়, note-এ "অফলাইন বিক্রি" লেখা থাকে।
  - সার্ভার বিক্রি না নিলে (যেমন register বন্ধ) queue-তে error হিসেবে থাকে। তখন আবার চেষ্টা, রসিদ বা বাতিল করা যায়। Closing পাতায় সতর্কবার্তা দেখায়।
- **সতর্কতা:**
  - Service worker শুধু HTTPS বা localhost-এ চলে।
  - Nginx-এ `.js` / `.png` / `.webmanifest`-এর জন্য `try_files $uri /index.php?$query_string` রাখবে, নাহলে `/{slug}/sw.js` 404 দেবে।

## 4e. ধাপ ১১ — নতুন প্যানেল ডিজাইন (সম্পন্ন)
- `public/css/panel.css`: শুধু manager আর admin layout-এ লোড হয় (`body.panel-ui`)।
  - সাদা সাইডবার, বক্স আকারের মেনু, রঙিন আইকন।
  - ☰ বাটন: ডেস্কটপে শুধু আইকনের মেনু (`sb-mini`, localStorage-এ মনে রাখে), মোবাইলে মেনু পাশ থেকে আসে।
  - টপবারে avatar, নাম আর logout আইকন।
  - Pastel `.st-card` আর রঙিন হেডারের `.dp` প্যানেল।
- মেনু: layout-এর `@php`-এ `$menu` array থাকে, আর `partials/panel-menu.blade.php` সেটা render করে।
  - গ্রুপের child active থাকলে গ্রুপটা খোলা থাকে। `show => false` দিলে আইটেম লুকানো থাকে।
  - নতুন মেনু আইটেম এই array-তে যোগ করবে, permission (`Tenancy::can`) আর feature lock সহ।
- Dashboard (manager ও admin): ১২টি statistic card, New Order (status filter `?os=`), একটি doughnut, আর দুটি বছরভিত্তিক line chart (`?y_pay`, `?y_ie`, `?y_sr`)।
  - Chart.js 4.4.1 CDN থেকে আসে। CDN না এলে চার্ট ছাড়া বাকি সব দেখায়।
  - Manager-এ Income = বিক্রি (VOID বাদে), Expense = expense voucher। Admin-এ Income = paid invoice, সাথে Commission।
  - Card cache থাকে `dash:{id}` key-তে; চার্ট cache ৫ মিনিট।

## 4f. ধাপ ১২ — লগইন / রেজিস্ট্রেশন / OTP ডিজাইন (সম্পন্ন)
- `layouts/auth.blade.php` আর `public/css/auth.css`: পুরো পর্দায় ছবি আর গাঢ় overlay, মাঝে সাদা কার্ড।
  - কার্ডের উপরে লোগো আর শিরোনাম, বাঁয়ে YouTube (thumbnail-এ ক্লিক করলে iframe লোড হয়), ডানে ফর্ম, নিচে "Developed By" (`footer_text`)।
  - Child view থেকে সেট করা যায়: `auth_kind=register`, `auth_size=wide`, `auth_video=off`, `auth_brand=platform`।
- `App\Support\AuthTheme`:
  - লোগো: store পেইজে branch-এর লোগো; `/login?branch=slug` বা `mb_last_branch` cookie থাকলে (শুধু live branch) সেই branch-এর লোগো; নাহলে `site_logo`।
  - Super Admin login আর নতুন রেজিস্ট্রেশনে সবসময় platform লোগো।
- Super Admin → Settings → "Logo & Login / Registration Page":
  - Upload: `site_logo`, `auth_login_bg`, `auth_register_bg` (SVG নিষেধ)।
  - `auth_login_video` আর `auth_register_video` (YouTube লিংক যাচাই হয়), `auth_show_video`।
  - Admin → Branch edit থেকে branch-এর লোগোও আপলোড করা যায়।
- Forgot password (branch owner): `/password/forgot` → registered মোবাইলে SMS code যায়, তারপর `/password/reset`।
  - Code থাকে cache-এ, hash করা অবস্থায়: ১০ মিনিট মেয়াদ, ৫ বার চেষ্টা (atomic গণনা), প্রতি store দিনে ৫টি code।
  - Account আছে কিনা বোঝা যায় না, কারণ উত্তর সবসময় একই।
  - Staff-এর পাসওয়ার্ড owner রিসেট করে। Store customer-কে দোকানের ফোন নম্বর দেখানো হয়।
- Custom domain থেকে login/register/password-এ গেলে app domain-এ `?branch=slug` সহ redirect হয়।

## 4g. ধাপ ১৩ — ব্যানার স্লাইডার, ম্যাপ পপআপ, লোগো, স্টোর লগইন (সম্পন্ন)
- `partials/banner-slider.blade.php`: শুধু ছবির স্লাইড শো (Bootstrap carousel, 16:5)। ছবির উপরে কোনো লেখা বা বাটন থাকে না, শুধু ঐচ্ছিক লিংক। মেইন সাইট আর সব স্টোর এটাই ব্যবহার করে।
- মেইন সাইট: `App\Support\PlatformSlides` (`settings.home_slides` JSON, সর্বোচ্চ ১০টি)। Super Admin → Home Slider (`admin.sliders.*`, `Admin\HomeSliderController`)। কোনো স্লাইড না থাকলে `hero_image` দেখায়।
- স্টোর: `store/sections/hero` এখন শুধু ছবি দেখায় (Manager → Website → Slider)। স্লাইড না থাকলে branch banner।
- মেইন সাইটের header-এ `site_logo` দেখায়।
- Location বাটন নতুন ট্যাবে না গিয়ে modal-এ Google Maps embed খোলে (`App\Support\MapEmbed`; `maps.app.goo.gl` embed করা যায় না, তাই map_url-এর coordinate বা নাম+ঠিকানা দিয়ে খোঁজে)।
- স্টোর header-এ কাস্টমারের Login/Register বাটন, topbar-এ "Seller Login" (`/login?branch=slug`)।
- `php artisan admin:reset` / `reset-admin.bat`: Super Admin লগইন রিসেট।
- **Footer**: `App\Support\SiteFooter` (`platform()` / `store()`) আর `partials/site-footer.blade.php`। মেইন সাইটের footer আসে Super Admin → Settings → Footer থেকে (`footer_about`, `footer_hours`, social, `footer_links` JSON, `footer_copyright`, `footer_show_tiles`, `footer_show_pay`)। স্টোরের footer আসে Manager → Website → Theme থেকে (`options.theme` + ব্রাঞ্চের ঠিকানা/ফোন/সময়)। Quick link লেখার নিয়ম: প্রতি লাইনে `নাম | লিংক`।
- **রেজিস্ট্রেশনের URL (slug)**: `public/js/slugify-bn.js` বাংলা লেখাকে ইংরেজি অক্ষরে বদলে দেয়। মোবাইল কীবোর্ডে অক্ষর যেন হারিয়ে না যায়, সেজন্য `compositionend`-এর পরে লেখা পরিষ্কার করা হয়। Server-এর validation আগের মতোই আছে।

## 5. কোডিং নিয়ম
- `Controller → Service → Model` কাঠামো মানবে। টাকা বা স্টকের কাজ `DB::transaction` আর `lockForUpdate` / শর্তসাপেক্ষ update দিয়ে করবে।
- প্রতিটি নতুন মডিউলে যোগ করবে:
  - `Permissions::MODULES` + `LABELS`।
  - দরকারি হলে `Plan::FEATURES`।
  - Seeder-এর ডিফল্ট প্ল্যানে ফিচার।
  - Sidebar লিংক (ফিচার না থাকলে লক আইকন, লিংক যাবে billing.plans-এ)।
  - Route: `feature:` আর `perm:` middleware দিয়ে।
- **Migration লিখবে idempotent করে:**
  - শুরুতে `Schema::disableForeignKeyConstraints()` দিয়ে নতুন টেবিলগুলোর জন্য `dropIfExists`।
  - পুরনো টেবিলে কলাম যোগের আগে `Schema::hasColumn` চেক।
  - দরকার হলে পুরনো ব্রাঞ্চের জন্য ডেটা backfill।
  - নাম হবে তারিখ ধরে (`2026_0X_01_00000N_...`)।
- কোড যদি এমন টেবিল ব্যবহার করে যা migration-এর আগে নেই, তাহলে `App\Support\Features::ready('table')` বা `Schema::hasTable` দিয়ে guard দেবে। না হলে migrate চালানোর আগেই পুরো সাইট ভেঙে যায়।
- ইউজার আপলোড সবসময় `$branch->storeUpload()` দিয়ে করবে (স্টোরেজ কোটা গোনার জন্য), আগে `hasStorageFor()` চেক করবে।
- Notification বা বাইরের API কখনো মূল কাজ (order বা payment) ভাঙতে পারবে না। try/catch আর log ব্যবহার করবে।

## 6. যে ভুলগুলো আগে হয়েছিল — আগেই সতর্ক থাকবে ⚠️
1. **`Target class [branch.verified] does not exist`**: `bootstrap/app.php`-এ দুবার `alias()` কল করা হয়েছিল। সব alias একটি কলেই রাখবে।
2. **`Table ... doesn't exist` / `Unknown column ...`**: আমি migration চালাইনি। প্রতিটি ধাপ শেষে আমাকে `update.bat` চালাতে বলবে এবং Migration status-এ নতুন migration-এর পাশে **Ran** আছে কিনা দেখতে বলবে। Migration idempotent রাখবে, যাতে মাঝপথে fail হলে আবার চালানো যায়।
3. **Laragon default পাতা দেখাচ্ছিল**: vhost reload হয়নি। Laragon → Stop All → Start All করতে হয়।
4. **OTP আসছিল না**: local-এ SMS `log` মোডে থাকে, তাই OTP স্ক্রিনে দেখানো হয়।
5. **Blade `@context` bug**: Laravel 12-এ `@context` একটি directive। JSON-LD-এ `'@context'` লিখলে ভেঙে যায়। লিখবে **`'@@context'`**।
6. **Blade-এ multi-line `@php(...)` আর closure**: এতে ভাঙে। `@php … @endphp` ব্লক ব্যবহার করবে। Relation collection-এ `prepend` করে mutate করবে না; `collect([$x])->merge(...)` ব্যবহার করবে।
7. **MySQL ONLY_FULL_GROUP_BY**: select করা প্রতিটি non-aggregate কলাম GROUP BY-তে থাকতে হবে, না হলে `max()` দিয়ে নিতে হবে। Index বা constraint নাম ৬৪ অক্ষরের কম রাখবে; লম্বা হলে নিজে নাম দেবে।
8. **XSS**:
   - Laravel-এর `url` rule `javascript://...` পাস করতে দেয়। সব URL field-এ `regex:#^https?://#i` যোগ করবে (ভিতরের লিংকের জন্য `^(https?://|/)`)।
   - JS দিয়ে `innerHTML`-এ API ডেটা বসালে `esc()` করবে।
   - Rich text সবসময় `HtmlSanitizer::clean()` দিয়ে পরিষ্কার করবে, এমনকি `old()` দিয়ে আবার দেখানোর সময়ও।
9. **Custom domain middleware**: `prepend` করলে TrustProxies-এর আগে চলে, ফলে proxy-র পেছনে scheme `http` হয়ে যায়। `append` করবে। কাস্টম ডোমেইনে `SESSION_DOMAIN` খালি রাখবে, না হলে 419 CSRF error আসে। Back-office redirect-এ query string রাখবে।
10. **পুরনো রোলে নতুন permission নেই**: owner সব পায়, কিন্তু পুরনো Manager/Accountant রোল পায় না। Migration-এ একবার যোগ করবে।
11. **দুবার হওয়া কাজ**: কুরিয়ার দুবার বুক, SMS দুবার, হিসাবে দুবার পোস্ট। এর জন্য lock, idempotent key আর atomic claim ব্যবহার করবে।
12. **স্টকের ভুল হিসাব**:
    - বিক্রির সময় `unit_cost` রেকর্ড করবে। ফেরত বা বাতিলে একই cost ব্যবহার করবে।
    - ডিজিটাল/সার্ভিস পণ্যে স্টক নড়বে না।
    - আংশিক ফেরতের পরে বাতিল হলে আগে ফেরত নেওয়া পরিমাণ আবার যোগ করবে না।
13. **FK restrict-এর কারণে ব্রাঞ্চ, পণ্য বা warehouse মুছতে 500 error**: মোছার আগে চেক করবে, অথবা Admin-এর ব্রাঞ্চ মোছার সময় transaction-এ নির্ভরশীল সারিগুলো আগে মুছবে। ইতিহাস আছে এমন জিনিস মোছা যাবে না, শুধু inactive করা যাবে।
14. **COD completed হলে payment row তৈরি হচ্ছিল না**: হিসাবে যায়নি। payment row তৈরি করে তারপর `markPaid()` করবে। Reference unique রাখবে (random suffix)।
15. **`public/robots.txt` static ফাইল থাকলে ডাইনামিক route কাজ করে না**: `update.bat` ফাইলটির নাম বদলে দেয়।
16. **`Undefined variable $menu` (Super Admin প্যানেল খুলছিল না)**: একই Blade ফাইলে এক লাইনের `@php(...)` আগে আর পরে `@php … @endphp` ব্লক থাকলে Laravel দুটোকে মিলিয়ে একটা ব্লক ধরে নেয়, ফলে মাঝের কোড চলে না। যে ফাইলে `@php … @endphp` ব্লক আছে, সেখানে এক লাইনের `@php(...)` ব্যবহার করবে না; সব ভেরিয়েবল ব্লকের ভেতরে রাখবে।

## 7. প্রতিটি ধাপ শেষে যাচাই করবে
- সব PHP ফাইলে `php -l`। Blade-এ `@if/@foreach/@perm/@push/@section` জোড়া মিলছে কিনা দেখার স্ক্রিপ্ট।
- রিভিউ এজেন্টকে পরিবর্তিত ফাইলের তালিকা দেবে (একটি stamp ফাইলের পরে বদলানো সব ফাইল)।
- আমার পিসিতে ফাইল বসানো হয়েছে কিনা নিশ্চিত করবে।
- আমাকে বাংলায় দেবে: নতুন মেনুগুলোর নাম, `update.bat` চালানোর নির্দেশ, নতুন migration-এর নাম (Ran দেখতে), ধাপে ধাপে টেস্টের ক্রম, কোন প্ল্যানে কোন ফিচার, আর কী লোকালি টেস্ট করা যাবে না।

---
**এখন প্রথম কাজ:** প্রজেক্ট ফোল্ডার পড়ে উপরের কাঠামো মিলিয়ে দেখো। তারপর বাংলায় ছোট করে জানাও যে সব বুঝেছো। এরপর আমি যে নতুন ফিচার বা ঠিক করার কাজ বলব, সেটা একই নিয়মে করবে।
