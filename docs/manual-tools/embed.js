// Inserts screenshot figures into the manual after unique anchor strings.
const fs = require('fs');
const FILE = 'D:/laragon/www/proyjon/public/user-manual.html';
let html = fs.readFileSync(FILE, 'utf8');

const f = (img, url, cap, cls = '') =>
  `\n<figure class="shot ${cls}" data-img="${img}"><div class="bar"><span></span><span></span><span></span><em>${url}</em></div>` +
  `<img src="manual-img/${img}.jpg" alt="${cap.replace(/<[^>]+>/g, '')}" loading="lazy"><figcaption>${cap}</figcaption></figure>`;
const two = (...figs) => `\n<div class="shots2">${figs.join('')}</div>`;

const inserts = [
  // intro
  [`</table></div>\n  </div>\n</section>\n\n<!-- ================= QUICK START`,
    'before', two(f('01-landing', '/', '<b>মূল ওয়েবসাইট</b> — এখান থেকে নতুন দোকান খোলা যায়'), f('02-plans-public', '/subscription/plan', '<b>প্যাকেজ / প্ল্যান</b> — দাম ও ফিচার তুলনা'))],
  // quick start
  [`<li><b>রেজিস্টার করুন</b>`, 'before-ol', ''],
  [`<div class="note tip"><b>💡 টিপস:</b> দোকান চালুর পর`, 'before',
    two(f('03-register', '/register', '<b>ধাপ ১:</b> রেজিস্ট্রেশন ফর্ম'), f('04-login', '/login', '<b>লগইন পেজ</b> — দোকান মালিক ও স্টাফ এখান থেকে ঢুকবেন'))],
  // panel
  [`<div class="note info"><b>📱 মোবাইল অ্যাপের মতো:</b>`, 'before',
    f('10-dashboard', '/manager', '<b>দোকান পরিচালনা প্যানেল</b> — বামে রঙিন মেনু, উপরে নোটিফিকেশন ও প্রোফাইল')],
  // dashboard
  [`<div class="note tip"><b>💡</b> লাল/হলুদ সতর্কবার্তা`, 'before',
    f('50-notifications', '/manager/notifications', '<b>নোটিফিকেশন</b> — নতুন অর্ডার, কম স্টক ইত্যাদির বার্তা')],
  // products
  [`  </div></div>\n  <div class="grid">\n    <div class="card"><h3 style="margin-top:0"><i class="fa-solid fa-tag"`, 'after-first-line',
    two(f('11-products', '/manager/products', '<b>প্রোডাক্ট তালিকা</b> — খোঁজা, ফিল্টার, এডিট'), f('12-product-create', '/manager/products/create', '<b>নতুন প্রোডাক্ট ফর্ম</b>'))],
  [`<div class="note info"><b>ℹ️</b> ক্যাটাগরি তৈরি করেন`, 'before',
    f('13-brands', '/manager/brands', '<b>ব্র্যান্ড</b> ব্যবস্থাপনা')],
  // orders
  [`    <div class="card"><h3 style="margin-top:0">✅ কনফার্ম করা</h3>`, 'before-grid',
    two(f('14-orders', '/manager/orders', '<b>অর্ডার তালিকা</b> — স্ট্যাটাস অনুযায়ী ফিল্টার'), f('14b-order-detail', '/manager/orders/…', '<b>অর্ডারের বিস্তারিত</b> — স্ট্যাটাস পরিবর্তন, প্রিন্ট, কুরিয়ার'))],
  // courier
  [`<div class="note warn"><b>⚠️ Fraud Check:</b>`, 'before',
    two(f('15-shipments', '/manager/shipments', '<b>শিপমেন্ট</b> — সব পার্সেলের অবস্থা'), f('16-courier-settings', '/manager/settings/courier', '<b>কুরিয়ার সেটিংস</b> — API কী (ছবিতে ঢাকা)'))],
  // returns
  [`ক্রেতা তার অ্যাকাউন্ট থেকে রিটার্ন রিকোয়েস্ট পাঠালে`, 'after-card',
    f('17-returns', '/manager/returns', '<b>রিটার্ন ও রিফান্ড</b> তালিকা')],
  // pos
  [`    <div class="card"><h3 style="margin-top:0">⏸️ Hold</h3>`, 'before-grid',
    f('18-pos', '/manager/pos', '<b>POS স্ক্রিন</b> — বামে বারকোড/সার্চ ও কার্ট, ডানে কাস্টমার, ডিসকাউন্ট ও পেমেন্ট') +
    f('19-pos-sessions', '/manager/pos/sessions', '<b>সেশন / দৈনিক ক্লোজিং</b> — প্রতিদিনের ক্যাশ হিসাব')],
  // inventory
  [`    <h3>ক্রয় (Purchases)</h3>`, 'before-card',
    two(f('20-inventory-stock', '/manager/inventory', '<b>স্টক</b> — কোথায় কত আছে'), f('21-warehouses', '/manager/warehouses', '<b>গুদাম</b> তালিকা'))],
  [`    <h3>সাপ্লায়ার</h3>`, 'before-card',
    f('22-purchases', '/manager/purchases', '<b>ক্রয়ের তালিকা</b>')],
  [`সাপ্লায়ারের পেজ থেকে পেমেন্ট দিন।\n  </div></div>`, 'after',
    f('23-suppliers', '/manager/suppliers', '<b>সাপ্লায়ার</b> ও তাদের বাকি')],
  // customers
  [`তালিকা এক্সপোর্ট করা যায়।\n  </div>`, 'after',
    f('24-customers', '/manager/customers', '<b>কাস্টমার তালিকা (CRM)</b>')],
  // marketing
  [`<div class="note warn"><b>⚠️</b> প্রতিটি প্ল্যানে মাসিক SMS-এর সীমা`, 'before',
    f('25-marketing', '/manager/marketing', '<b>মার্কেটিং ও SMS</b> ক্যাম্পেইন') +
    two(f('26-coupons', '/manager/coupons', '<b>কুপন</b> তৈরি ও তালিকা'), f('27-reviews', '/manager/reviews', '<b>রিভিউ</b> অনুমোদন'))],
  // website
  [`  <div class="grid">\n    <div class="card"><h3 style="margin-top:0">🎨 থিম</h3>`, 'before',
    f('28-website-builder', '/manager/website/homepage', '<b>হোমপেজ বিল্ডার</b> — সেকশন চালু/বন্ধ ও সাজানো')],
  [`<div class="note tip"><b>💡</b> মেনুর <b>View Store</b>`, 'before',
    two(f('29-website-theme', '/manager/website/theme', '<b>থিম</b>'), f('30-sliders', '/manager/sliders', '<b>স্লাইডার</b>')) +
    two(f('32-seo', '/manager/website/seo', '<b>SEO সেটিংস</b>'), f('33-domains', '/manager/domains', '<b>কাস্টম ডোমেইন</b>')) +
    two(f('31-pages', '/manager/pages', '<b>পেজ</b> (About, Policy…)'), f('34-blog', '/manager/blog', '<b>ব্লগ</b>'))],
  // reports
  [`  <div class="card mod" style="margin-top:16px"><div class="ic c10">`, 'before',
    two(f('35-reports', '/manager/reports', '<b>রিপোর্ট</b> মেনু'), f('36-report-sales', '/manager/reports/sales', '<b>সেলস রিপোর্ট</b>'))],
  [`প্ল্যান অনুযায়ী মাসিক AI রিকোয়েস্টের সীমা থাকে।\n  </div></div>`, 'after',
    f('37-ai', '/manager/ai/insights', '<b>AI Assistant</b> — ব্যবসার পরামর্শ')],
  // accounts
  [`<div class="note info"><b>ℹ️</b> অর্ডার ও POS বিক্রি, ক্রয়`, 'before',
    two(f('38-accounts', '/manager/accounts', '<b>অ্যাকাউন্টস</b> ও ব্যালেন্স'), f('39-vouchers', '/manager/vouchers', '<b>ভাউচার</b> — খরচ/আয় এন্ট্রি'))],
  // hr
  [`  <div class="card mod"><div class="ic c10"><i class="fa-solid fa-user-clock">`, 'before',
    two(f('42-staff', '/manager/staff', '<b>স্টাফ</b> তালিকা'), f('43-roles', '/manager/roles', '<b>রোল ও পারমিশন</b>'))],
  [`<b>পে-স্লিপ</b> প্রিন্ট করুন।</li>\n    </ul>\n  </div></div>`, 'after',
    two(f('40-hrm', '/manager/hrm/employees', '<b>কর্মচারী</b>'), f('41-payroll', '/manager/hrm/payroll', '<b>বেতন (Payroll)</b>'))],
  // settings
  [`      <li><b>পাসওয়ার্ড পরিবর্তন।</b></li>\n    </ul>\n  </div></div>`, 'after',
    f('44-settings', '/manager/settings', '<b>দোকানের সেটিংস</b>')],
  [`<div class="note warn"><b>⚠️ মেয়াদ শেষ হলে কী হয়?</b>`, 'before',
    two(f('45-billing', '/manager/billing', '<b>সাবস্ক্রিপশন</b> — বর্তমান প্ল্যান ও ব্যবহার'), f('46-billing-plans', '/manager/billing/plans', '<b>প্ল্যান আপগ্রেড</b>')) +
    two(f('47-security', '/manager/security', '<b>2FA নিরাপত্তা</b>'), f('48-api', '/manager/api', '<b>REST API</b> (টোকেন ঢাকা)')) +
    f('49-activity', '/manager/activity', '<b>অ্যাক্টিভিটি লগ</b>')],
  // admin
  [`<p class="lead">শুধু প্ল্যাটফর্মের মালিকের জন্য — <code>/admin/login</code></p>`, 'after',
    f('80-admin-dashboard', '/admin', '<b>সুপার অ্যাডমিন ড্যাশবোর্ড</b>')],
  [`    <tr><td>🔐 Security / Activity Logs</td><td>2FA এবং সব কার্যক্রমের রেকর্ড</td></tr>\n  </table></div>`, 'after',
    '\n<h3>স্ক্রিনশট গ্যালারি</h3>' +
    two(f('81-admin-branches', '/admin/branches', '<b>ব্রাঞ্চ / দোকান</b> তালিকা'), f('82-admin-plans', '/admin/plans', '<b>প্যাকেজ / প্ল্যান</b>')) +
    two(f('83-admin-plan-create', '/admin/plans/create', '<b>নতুন প্ল্যান</b> — দাম, সীমা ও ফিচার'), f('84-admin-subscriptions', '/admin/subscriptions', '<b>সাবস্ক্রিপশন</b>')) +
    two(f('85-admin-invoices', '/admin/invoices', '<b>ইনভয়েস ও পেমেন্ট</b>'), f('86-admin-orders', '/admin/orders', '<b>সব অর্ডার</b>')) +
    two(f('87-admin-categories', '/admin/categories', '<b>ক্যাটাগরি</b>'), f('88-admin-coupons', '/admin/coupons', '<b>গ্লোবাল কুপন</b>')) +
    two(f('89-admin-commission', '/admin/commission', '<b>কমিশন</b>'), f('90-admin-reports', '/admin/reports', '<b>প্ল্যাটফর্ম রিপোর্ট</b>')) +
    two(f('91-admin-messages', '/admin/messages', '<b>SMS ও ইমেইল লগ</b>'), f('92-admin-slider', '/admin/home-slider', '<b>হোম স্লাইডার</b>')) +
    two(f('93-admin-domains', '/admin/domains', '<b>কাস্টম ডোমেইন</b>'), f('94-admin-gateways', '/admin/gateways', '<b>পেমেন্ট ও SMS গেটওয়ে</b> (গোপন তথ্য ঢাকা)')) +
    two(f('95-admin-ai', '/admin/ai', '<b>AI সেটিংস</b>'), f('96-admin-backups', '/admin/backups', '<b>ব্যাকআপ ও রিস্টোর</b>')) +
    two(f('97-admin-settings', '/admin/settings', '<b>সিস্টেম সেটিংস</b>'), f('98-admin-activity', '/admin/activity', '<b>অ্যাক্টিভিটি লগ</b>'))],
];

function insert(anchor, mode, block) {
  const i = html.indexOf(anchor);
  if (i < 0 || html.indexOf(anchor, i + 1) >= 0) throw new Error('anchor not unique/found: ' + anchor.slice(0, 60));
  if (!block) return;
  let at;
  if (mode === 'before' || mode === 'before-grid' || mode === 'before-card') {
    at = i;
    if (mode === 'before-grid') at = html.lastIndexOf('<div class="grid">', i);
    if (mode === 'before-card') at = html.lastIndexOf('<div class="card mod">', i);
  } else if (mode === 'after') at = i + anchor.length;
  else if (mode === 'after-first-line') at = i + anchor.indexOf('\n', 1);
  else if (mode === 'after-card') at = html.indexOf('</div>', i) + 6;
  html = html.slice(0, at) + block + '\n' + html.slice(at);
}

if (html.includes('data-img="10-dashboard"')) { console.log('already embedded'); process.exit(0); }
for (const [a, m, b] of inserts) insert(a, m, b);
fs.writeFileSync(FILE, html);
console.log('figures:', (html.match(/<figure class="shot/g) || []).length);
