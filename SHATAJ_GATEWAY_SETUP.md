# ShatajPay + Shataj SMS সেটআপ

## ১. একবার চালান

```
php artisan migrate
php artisan optimize:clear
```

`migrate` নতুন `payment_gateways` টেবিল বানায় (ShatajPay credential, এনক্রিপ্টেড)।

## ২. Super Admin → Payment & SMS Gateways

### ShatajPay (প্ল্যাটফর্ম অ্যাকাউন্ট)
- Mode: `Test` (বিল্ট-ইন টেস্ট পেজ), `Sandbox`, `Live`
- App Username, App Password, App Key, App Secret, Gateway ID দিন (pay.shataj.com থেকে)
- Active চালু করে Save, তারপর "Test connection"
- ShatajPay মার্চেন্ট প্যানেলে Callback URL বসান: `{APP_URL}/gateway/payment/callback`

### Shataj SMS
- Driver = Shataj SMS, API key দিন (sms.shataj.com), Device ID খালি = primary ফোন
- "Balance & devices" দিয়ে সংযোগ যাচাই, তারপর Test SMS

.env এর মান শুধু fallback, প্যানেলে সেভ করা মানই আগে ব্যবহার হয়।

## ৩. মাল্টি-টেন্যান্ট নিয়ম

| পেমেন্ট | কোন ShatajPay অ্যাকাউন্ট |
| --- | --- |
| SaaS সাবস্ক্রিপশন ইনভয়েস | সবসময় প্ল্যাটফর্ম |
| স্টোরের অর্ডার, ব্রাঞ্চের নিজের অ্যাকাউন্ট চালু থাকলে | ব্রাঞ্চের অ্যাকাউন্ট |
| স্টোরের অর্ডার, নিজের অ্যাকাউন্ট নেই | প্ল্যাটফর্ম |

ব্রাঞ্চ মালিক: Manager → Setting → "নিজের ShatajPay অ্যাকাউন্ট" (শুধু owner দেখতে পান)।
প্ল্যাটফর্ম বা ব্রাঞ্চ কোনো অ্যাকাউন্ট চালু না থাকলে চেকআউটে ShatajPay অপশন লুকানো থাকে।

## ৪. পেমেন্ট ফ্লো

1. চেকআউট → `POST /api/v1/user/payment-system` (headers: username, password, appkey, appsecret, sandbox, gatewayid)
2. `redirect_url` এ কাস্টমার যায়
3. ShatajPay ফেরত পাঠায় `/gateway/payment/callback?status=Complete|Failed&orderId=...`
4. `Complete` হলে অর্ডার paid / ইনভয়েস paid (একই callback দুবার এলেও একবারই কাজ হয়)

`orderId` প্রতি পেমেন্টে নতুন UUID, `payments.reference` এ রাখা হয়। ৪৮ ঘণ্টার পুরনো পেমেন্টের success callback গ্রহণ করা হয় না।

## ৫. SMS কোথায় যায়

OTP ও পাসওয়ার্ড রিসেট (priority SMS), অর্ডার নোটিফিকেশন, সাবস্ক্রিপশন রিমাইন্ডার, মার্কেটিং ক্যাম্পেইন। ব্রাঞ্চের মাসিক SMS কোটা প্ল্যান থেকে আগের মতোই কাজ করে, লগ Admin → SMS & Email Log এ।

## ৬. যা ShatajPay টিমের সাথে নিশ্চিত করবেন

- `sandbox` header: অফিসিয়াল স্যাম্পলে sandbox = 0, production = 1। উল্টো হলে `ShatajPayService` এ এক লাইন বদলাতে হবে।
- HTTPS: ডিফল্ট `https://pay.shataj.com`। না চললে প্যানেলে API Base URL এ `http://pay.shataj.com` দিন (তবে HTTPS ছাড়া credential পাঠানো নিরাপদ না)।
- Callback এ কোনো signature বা verify API নেই। UUID orderId অনুমান করা প্রায় অসম্ভব, তবু ShatajPay একটা verify endpoint বা signature দিলে সেটা যোগ করা ভালো।
