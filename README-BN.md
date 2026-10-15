# Multi Branch E-commerce (Laravel 12 + MySQL)

ShatajPay ও Shataj SMS Gateway সহ মাল্টি-ব্রাঞ্চ ই-কমার্স। প্রতিটি ব্রাঞ্চ ম্যানেজার রেজিস্ট্রেশন করলে তার নিজস্ব স্টোর তৈরি হয়: `www.domain.com/{branch-name}`।

## ইনস্টলেশন

প্রয়োজন: PHP 8.2+, Composer, MySQL 8 / MariaDB 10.6+

```bash
# ১) নতুন Laravel 12 প্রজেক্ট
composer create-project laravel/laravel multibranch "^12.0"
cd multibranch

# ২) এই zip-এর সব ফাইল প্রজেক্টের ভেতরে কপি করুন (overwrite = Yes)
#    app/  bootstrap/  config/  database/  public/  resources/  routes/

# ৩) .env ঠিক করুন — .env.shataj.example থেকে লাইনগুলো .env এ কপি করুন
#    (DB_CONNECTION=mysql, DB_DATABASE, DB_USERNAME, DB_PASSWORD ইত্যাদি)

# ৪) টাইমজোন: config/app.php তে  'timezone' => 'Asia/Dhaka'

# ৫) ডাটাবেস ও স্টোরেজ
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

## লগইন (ডেমো ডাটা)

| প্যানেল | URL | লগইন |
|---|---|---|
| Super Admin | `/admin/login` | admin@example.com / password |
| Branch Manager | `/login` | barishal@example.com (অথবা ইউজার `barishal`) / password |
| ব্রাঞ্চ স্টোর | `/barishal`, `/bhola`, `/jhalokathi` | — |

> প্রোডাকশনে যাওয়ার আগে অবশ্যই Super Admin-এর পাসওয়ার্ড পরিবর্তন করুন (Admin → Settings → My Admin Account)।

## ফ্লো

1. `/register` → Name, Mobile, Email, USER/Sub-folder, Address, Password
2. মোবাইলে OTP যায় (Shataj SMS) → `/otp` এ কোড দিলে স্টোর সরাসরি লাইভ
3. পরে `/login` এ Email (বা USER) + Password দিয়ে লগইন → `/manager`

## পেজসমূহ

**Frontend:** Registration, OTP, Login, Main/Landing (সব ব্রাঞ্চ), Branch Home, Products, Single Product (ভ্যারিয়েন্ট সিলেক্ট), Cart/Order, Checkout, Payment (ShatajPay / bKash / Nagad / COD), Order Success, Track Order, Wishlist

**Branch Backend (`/manager`):** Dashboard, Setting, Manage Products (ছবি, গ্যালারি, ভ্যারিয়েন্ট, স্টক), Manage Order (স্ট্যাটাস, bKash/Nagad TrxID ভেরিফাই, COD কালেকশন)

**Super Admin (`/admin`):** Dashboard (সব ব্রাঞ্চের বিক্রি), Branches (Suspend/Activate, OTP ম্যানুয়াল ভেরিফাই, Edit, পাসওয়ার্ড রিসেট, Login-as-branch, Delete), All Orders (ব্রাঞ্চ/তারিখ/পেমেন্ট ফিল্টার), Categories (সব ব্রাঞ্চের জন্য), Subscribers, Settings & Gateways (ল্যান্ডিং পেজ কনটেন্ট, রেজিস্ট্রেশন চালু/বন্ধ, SMS টেস্ট, ShatajPay callback URL)

## Shataj SMS Gateway

- `SHATAJ_SMS_DRIVER=log` → OTP `storage/logs/laravel.log` এ লেখা হয় (টেস্টের জন্য)
- `SHATAJ_SMS_DRIVER=shataj` → আসল SMS। URL, API key, Sender ID এবং প্যারামিটারের নাম (`SHATAJ_SMS_PARAM_*`) Shataj-এর API ডক অনুযায়ী `.env` এ দিন।
- সফল হয়েছে কিনা যাচাই: `SHATAJ_SMS_SUCCESS_KEY` (যেমন `status`) ও `SHATAJ_SMS_SUCCESS_VALUES`
- Admin → Settings → "Send Test SMS" দিয়ে পরীক্ষা করুন।
- কোড: `app/Services/Sms/ShatajSmsService.php`, কনফিগ: `config/shataj.php`

## ShatajPay

- `SHATAJPAY_MODE=fake` → বিল্ট-ইন টেস্ট পেমেন্ট পেজ (API ছাড়াই পুরো ফ্লো টেস্ট করা যায়)
- `SHATAJPAY_MODE=sandbox` / `live` → আসল API। Base URL, Merchant ID, API Key, Secret দিন।
- ফ্লো: Create payment (`SHATAJPAY_INIT_ENDPOINT`) → রেসপন্সের `data.payment_url` এ কাস্টমার রিডাইরেক্ট → ফিরে এলে সার্ভার থেকে Verify (`SHATAJPAY_VERIFY_ENDPOINT`) → status + amount + invoice মিললে অর্ডার Paid। ব্রাউজার কলব্যাকের উপর কখনোই ভরসা করা হয় না।
- রিকোয়েস্টের ফিল্ডের নাম `config/shataj.php` → `pay.fields` এ, রেসপন্সের key গুলো `.env` এ (`SHATAJPAY_*_KEY`) পরিবর্তনযোগ্য।
- API key → `Authorization: Bearer`, Secret থাকলে বডির HMAC-SHA256 → `X-Signature` হেডার।
- ShatajPay মার্চেন্ট প্যানেলে এই URL গুলো দিন (Admin → Settings এ দেখা যায়):
  `/payment/shatajpay/success`, `/fail`, `/cancel`, `/ipn`
- কোড: `app/Services/Payment/ShatajPayService.php`, `app/Http/Controllers/Payment/ShatajPayController.php`

## bKash / Nagad (ম্যানুয়াল)

ব্রাঞ্চ Setting এ bKash/Nagad নম্বর দিলে পেমেন্ট পেজে অপশন আসে। কাস্টমার Send Money করে TrxID দেয় → Manage Order এ ম্যানেজার Approve/Reject করেন।

## নোট

- সংরক্ষিত sub-folder নাম (যেমন `admin`, `login`, `manager`, `payment`) কোনো ব্রাঞ্চ নিতে পারবে না — `Branch::RESERVED_SLUGS`
- Super Admin "Suspend" করলে স্টোর ও ম্যানেজার লগইন বন্ধ; ম্যানেজার নিজে "Inactive" করলে শুধু স্টোর লুকানো থাকে।
