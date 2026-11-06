// Usage: node capture.js <outDir>
// Logs into the local app (credentials from environment variables) and screenshots key screens.
const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');

const BASE = process.env.BASE || 'http://127.0.0.1:8000';
const OUT = process.argv[2];
fs.mkdirSync(OUT, { recursive: true });

// super admin login: set ADMIN_EMAIL / ADMIN_PASS before running
const ADMIN = { email: process.env.ADMIN_EMAIL || '', pass: process.env.ADMIN_PASS || '' };
// shop owner: demo branch from database/seeders/DatabaseSeeder.php
const BRANCH = { email: process.env.BRANCH_EMAIL || 'aponhat@example.com', pass: process.env.BRANCH_PASS || 'password' };

const sleep = ms => new Promise(r => setTimeout(r, ms));

async function shot(page, url, name, opts = {}) {
  try {
    await page.goto(BASE + url, { waitUntil: 'networkidle2', timeout: 45000 });
    await sleep(opts.wait || 900);
    if (opts.before) await opts.before(page);
    // hide secrets (API keys, tokens, passwords) before taking the picture
    await page.evaluate(() => {
      document.querySelectorAll('input, textarea').forEach(el => {
        const n = ((el.name || '') + ' ' + (el.id || '') + ' ' + (el.placeholder || '')).toLowerCase();
        if (el.type === 'hidden' || el.type === 'password') return;
        if (/key|secret|token|pass|webhook|signature|api|gateway|username/.test(n) && el.value) el.value = '••••••••••••';
      });
      document.querySelectorAll('code, pre, .token, [data-token]').forEach(el => {
        if (/[A-Za-z0-9_\-]{24,}/.test(el.textContent)) el.textContent = el.textContent.replace(/[A-Za-z0-9_\-|]{24,}/g, '••••••••••••');
      });
    });
    const final = page.url();
    await page.screenshot({ path: path.join(OUT, name + '.jpg'), type: 'jpeg', quality: 82, fullPage: false });
    console.log('OK  ', name, '<-', final.replace(BASE, ''));
  } catch (e) { console.log('FAIL', name, e.message.split('\n')[0]); }
}

async function login(page, url, cred) {
  await page.goto(BASE + url, { waitUntil: 'networkidle2' });
  await page.type('input[name=email]', cred.email);
  await page.type('input[name=password]', cred.pass);
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => {}), page.$eval('input[name=password]', el => el.form.submit())]);
  console.log('login ->', page.url().replace(BASE, ''));
}

(async () => {
  const browser = await puppeteer.launch({
    executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe',
    headless: true, args: ['--lang=bn-BD'], defaultViewport: { width: 1600, height: 900 },
  });
  const mode = process.argv[3] || 'all';

  // ---------- public / auth (guest) ----------
  if (mode === 'all' || mode === 'public') {
    const ctx = await browser.createBrowserContext();
    const p = await ctx.newPage();
    await shot(p, '/', '01-landing');
    await shot(p, '/subscription/plan', '02-plans-public');
    await shot(p, '/register', '03-register');
    await shot(p, '/login', '04-login');
    await shot(p, '/admin/login', '05-admin-login');
    await ctx.close();
  }

  // ---------- branch manager ----------
  let slug = null;
  if (mode === 'all' || mode === 'manager') {
    const ctx = await browser.createBrowserContext();
    const p = await ctx.newPage();
    await login(p, '/login', BRANCH);
    const list = [
      ['/manager', '10-dashboard'],
      ['/manager/products', '11-products'],
      ['/manager/products/create', '12-product-create'],
      ['/manager/brands', '13-brands'],
      ['/manager/orders', '14-orders'],
      ['/manager/shipments', '15-shipments'],
      ['/manager/settings/courier', '16-courier-settings'],
      ['/manager/returns', '17-returns'],
      ['/manager/pos', '18-pos'],
      ['/manager/pos/sessions', '19-pos-sessions'],
      ['/manager/inventory', '20-inventory-stock'],
      ['/manager/warehouses', '21-warehouses'],
      ['/manager/purchases', '22-purchases'],
      ['/manager/suppliers', '23-suppliers'],
      ['/manager/customers', '24-customers'],
      ['/manager/marketing', '25-marketing'],
      ['/manager/coupons', '26-coupons'],
      ['/manager/reviews', '27-reviews'],
      ['/manager/website/homepage', '28-website-builder'],
      ['/manager/website/theme', '29-website-theme'],
      ['/manager/sliders', '30-sliders'],
      ['/manager/pages', '31-pages'],
      ['/manager/website/seo', '32-seo'],
      ['/manager/domains', '33-domains'],
      ['/manager/blog', '34-blog'],
      ['/manager/reports', '35-reports'],
      ['/manager/reports/sales', '36-report-sales'],
      ['/manager/ai/insights', '37-ai'],
      ['/manager/accounts', '38-accounts'],
      ['/manager/vouchers', '39-vouchers'],
      ['/manager/hrm/employees', '40-hrm'],
      ['/manager/hrm/payroll', '41-payroll'],
      ['/manager/staff', '42-staff'],
      ['/manager/roles', '43-roles'],
      ['/manager/settings', '44-settings'],
      ['/manager/billing', '45-billing'],
      ['/manager/billing/plans', '46-billing-plans'],
      ['/manager/security', '47-security'],
      ['/manager/api', '48-api'],
      ['/manager/activity', '49-activity'],
      ['/manager/notifications', '50-notifications'],
    ];
    for (const [u, n] of list) await shot(p, u, n);
    // first order detail
    await p.goto(BASE + '/manager/orders', { waitUntil: 'networkidle2' });
    const ord = await p.$$eval('a[href*="/manager/orders/"]', as => as.map(a => a.getAttribute('href')).find(h => /\/manager\/orders\/(?!create)[A-Za-z0-9-]+$/.test(h)));
    console.log('order link =', ord);
    if (ord) await shot(p, ord.replace(BASE, ''), '14b-order-detail');
    // store slug from "View Store" link
    slug = await p.$$eval('a[target=_blank]', as => as.map(a => a.getAttribute('href'))).then(hs => {
      const h = hs.find(x => x && x.startsWith(BASE + '/') && !/manager|admin|storage/.test(x));
      return h ? h.replace(BASE + '/', '').split('/')[0] : null;
    });
    slug = slug || 'aponhat'; console.log('slug =', slug);
    fs.writeFileSync(path.join(OUT, 'slug.txt'), slug || '');
    await ctx.close();
  }

  // ---------- storefront ----------
  if (mode === 'all' || mode === 'store') {
    slug = slug || fs.readFileSync(path.join(OUT, 'slug.txt'), 'utf8').trim();
    if (slug) {
      const ctx = await browser.createBrowserContext();
      const p = await ctx.newPage();
      await shot(p, `/${slug}`, '60-store-home');
      await shot(p, `/${slug}/products`, '61-store-products');
      await p.goto(`${BASE}/${slug}/products`, { waitUntil: 'networkidle2' });
      const prod = await p.$$eval('a[href*="/product/"]', as => as.map(a => a.getAttribute('href'))[0]);
      if (prod) {
        await shot(p, prod.replace(BASE, ''), '62-store-product');
        // add to cart
        try {
          const btn = await p.$('#addForm button[value=cart]');
          if (btn) { await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => {}), btn.click()]); await sleep(800); }
        } catch (e) {}
      }
      await shot(p, `/${slug}/cart`, '63-store-cart');
      await shot(p, `/${slug}/checkout`, '64-store-checkout');
      await shot(p, `/${slug}/track`, '65-store-track');
      await shot(p, `/${slug}/account/login`, '66-store-account-login');
      // mobile view
      await p.setViewport({ width: 390, height: 844, deviceScaleFactor: 2, isMobile: true });
      await shot(p, `/${slug}`, '67-store-mobile');
      await ctx.close();
    }
  }


  // ---------- customer journey (logged-in buyer) ----------
  if (mode === 'all' || mode === 'customer') {
    slug = slug || fs.readFileSync(path.join(OUT, 'slug.txt'), 'utf8').trim() || 'aponhat';
    const ctx = await browser.createBrowserContext();
    const p = await ctx.newPage();
    // demo buyer from database/seeders/EcommerceSeeder.php
    await p.goto(`${BASE}/${slug}/account/login`, { waitUntil: 'networkidle2' });
    await p.type('input[name=login]', process.env.CUST_LOGIN || 'customer@example.com');
    await p.type('input[name=password]', process.env.CUST_PASS || 'password');
    await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => {}), p.$eval('input[name=password]', el => el.form.submit())]);
    console.log('customer login ->', p.url().replace(BASE, ''));
    await shot(p, `/${slug}/account`, '70-cust-dashboard');
    // add a product to cart
    await p.goto(`${BASE}/${slug}/products`, { waitUntil: 'networkidle2' });
    const prods = await p.$$eval('a[href*="/product/"]', as => [...new Set(as.map(a => a.getAttribute('href')))]);
    for (const pr of prods.slice(0, 2)) {
      await p.goto(pr.startsWith('http') ? pr : BASE + pr, { waitUntil: 'networkidle2' });
      const btn = await p.$('#addForm button[value=cart]');
      if (btn) await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => {}), btn.click()]);
    }
    await shot(p, `/${slug}/cart`, '71-cust-cart');
    await shot(p, `/${slug}/checkout`, '72-cust-checkout');
    // place a Cash-on-Delivery test order
    try {
      await p.goto(`${BASE}/${slug}/checkout`, { waitUntil: 'networkidle2' });
      const fill = async (sel, v) => { const el = await p.$(sel); if (el) { const cur = await p.$eval(sel, e => e.value); if (!cur) await el.type(v); } };
      await fill('[name=customer_name]', 'Demo Customer');
      await fill('[name=customer_phone]', '01900000001');
      await fill('[name=shipping_address]', 'House 1, Road 2, Mirpur, Dhaka');
      await p.evaluate(() => { const r = document.querySelector('input[name=payment_method][value=cod]'); if (r) r.checked = true; });
      await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => {}), p.$eval('form[action*="checkout"]', f => f.submit())]);
      await sleep(800);
      await p.screenshot({ path: path.join(OUT, '73-cust-order-success.jpg'), type: 'jpeg', quality: 82 });
      console.log('order ->', p.url().replace(BASE, ''));
    } catch (e) { console.log('order FAIL', e.message); }
    await shot(p, `/${slug}/account`, '74-cust-orders');
    const od = await p.$$eval('a[href*="/account/orders/"]', as => as.map(a => a.getAttribute('href'))[0]);
    if (od) await shot(p, od.replace(BASE, ''), '75-cust-order-detail');
    await shot(p, `/${slug}/account/profile`, '76-cust-profile');
    await shot(p, `/${slug}/account/addresses`, '77-cust-addresses');
    await shot(p, `/${slug}/account/wallet`, '78-cust-wallet');
    await shot(p, `/${slug}/account/returns`, '79-cust-returns');
    await shot(p, `/${slug}/wishlist`, '79b-cust-wishlist');
    await ctx.close();
  }

  // ---------- super admin ----------
  if ((mode === 'all' || mode === 'admin') && ADMIN.email) {
    const ctx = await browser.createBrowserContext();
    const p = await ctx.newPage();
    await login(p, '/admin/login', ADMIN);
    const list = [
      ['/admin', '80-admin-dashboard'], ['/admin/branches', '81-admin-branches'], ['/admin/plans', '82-admin-plans'],
      ['/admin/plans/create', '83-admin-plan-create'], ['/admin/subscriptions', '84-admin-subscriptions'], ['/admin/invoices', '85-admin-invoices'],
      ['/admin/orders', '86-admin-orders'], ['/admin/categories', '87-admin-categories'], ['/admin/coupons', '88-admin-coupons'],
      ['/admin/commission', '89-admin-commission'], ['/admin/reports', '90-admin-reports'], ['/admin/messages', '91-admin-messages'],
      ['/admin/home-slider', '92-admin-slider'], ['/admin/domains', '93-admin-domains'], ['/admin/gateways', '94-admin-gateways'],
      ['/admin/ai', '95-admin-ai'], ['/admin/backups', '96-admin-backups'], ['/admin/settings', '97-admin-settings'], ['/admin/activity', '98-admin-activity'],
    ];
    for (const [u, n] of list) await shot(p, u, n);
    await ctx.close();
  }
  await browser.close();
})();
