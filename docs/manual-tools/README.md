# ইউজার ম্যানুয়াল — আবার তৈরি করার টুল

সাইটে নতুন ফিচার এলে বা ডিজাইন বদলালে এই স্ক্রিপ্টগুলো দিয়ে স্ক্রিনশট, PDF, Word ও ভিডিও স্ক্রিপ্ট আবার বানানো যায়।

| ফাইল | কাজ |
|---|---|
| `../../public/user-manual.html` | মূল রঙিন ম্যানুয়াল (ব্রাউজারে: `/user-manual.html`) |
| `../../public/manual-img/` | ৮৪টি স্ক্রিনশট (১৬০০×৯০০, ভিডিওর জন্য ১৬:৯) |
| `../../public/manual/user-manual.pdf` | প্রিন্ট/শেয়ারের জন্য PDF |
| `../../public/manual/user-manual.docx` | Word ফাইল — ইচ্ছামতো এডিট করুন |
| `../../public/manual/video-script.docx` | দৃশ্য-ভিত্তিক ভিডিও স্ক্রিপ্ট (ছবি + ভয়েস-ওভার) |
| `../../public/manual/video-scenes.json` | একই স্ক্রিপ্ট JSON আকারে (Remotion/অটোমেশনের জন্য) |

## 🌐 লাইভ সার্ভারে আপলোড (ছবি যেন দেখা যায়)

**সহজ পথ — একটি ফাইল:** `public/manual/user-manual-standalone.html` (≈১৩ MB)। এর ভেতরেই ৮২টি ছবি আছে,
তাই যেকোনো সার্ভার, ইমেইল, পেনড্রাইভ বা ইন্টারনেট ছাড়াও সব ছবি দেখা যাবে।
ঠিকানা হবে: `https://আপনার-ডোমেইন/manual/user-manual-standalone.html`

**পূর্ণ সংস্করণ আপলোড করলে এগুলো সার্ভারে থাকতে হবে:**

```
public/user-manual.html
public/manual-img/     (৮৪টি .jpg)
public/manual/         (PDF, Word, ভিডিও স্ক্রিপ্ট)
routes/web.php         (নতুন /manual-file ও /user-manual রুট আছে)
```

- ছবি সরাসরি না পেলে পেজটি নিজে থেকে `/manual-file/...` রুট দিয়ে ছবি আনে। ফলে ডোমেইন `public_html`-এ থাকলে
  বা ফোল্ডারের পারমিশন ভুল থাকলেও ছবি দেখা যায়। ঠিকানা: `https://আপনার-ডোমেইন/user-manual`
- cPanel-এ পারমিশন: ফোল্ডার **755**, ফাইল **644**।
- `php artisan route:cache` ব্যবহার করলে আপলোডের পর `php artisan route:clear` চালান।
- কোনো ছবি তবুও না এলে ছবির জায়গায় লাল লেখায় কারণ দেখাবে।
- `public/.htaccess`-এর `php_value` লাইনগুলো `<IfModule php_module>`-এ রাখা আছে, তাই সার্ভারে ৫০০ এরর হবে না।

## আবার তৈরি করতে

সাইট চালু রাখুন (লারাগন বা `php artisan serve` → `http://127.0.0.1:8000`), তারপর এই ফোল্ডারে:

```bash
npm install
node capture.js ../../public/manual-img all
node export.js
node standalone.js
```

- `capture.js` — লগইন করে সব স্ক্রিনের ছবি নেয়; API key / secret ছবিতে নিজে থেকে ঢেকে দেয়।
  লগইন তথ্য: `ADMIN_EMAIL`, `ADMIN_PASS` (সুপার অ্যাডমিন — না দিলে অ্যাডমিন স্ক্রিনশট বাদ যাবে), `BRANCH_EMAIL`, `BRANCH_PASS`, `CUST_LOGIN`, `CUST_PASS`, `BASE` এনভায়রনমেন্ট ভেরিয়েবল।
  ⚠️ `all` বা `customer` মোড ক্রেতা হিসেবে একটি টেস্ট অর্ডার (Cash on Delivery) তৈরি করে — লাইভ সার্ভারে চালাবেন না।
- `export.js` — `user-manual.html` থেকে PDF, Word ও ভিডিও স্ক্রিপ্ট বানায়। ম্যানুয়ালের লেখা বদলালে শুধু এটি চালালেই হবে।
- `embed.js` — প্রথমবার স্ক্রিনশটগুলো HTML-এ বসানোর জন্য ব্যবহৃত হয়েছিল (আর দরকার নেই)।
- কাজ করতে Google Chrome ইনস্টল থাকা লাগবে (`C:/Program Files/Google/Chrome/Application/chrome.exe`)।
