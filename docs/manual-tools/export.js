// Builds PDF + Word manual + Word video script from public/user-manual.html
// Usage: node export.js
const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');
const D = require('docx');

const BASE = process.env.BASE || 'http://127.0.0.1:8000';
const PUB = 'D:/laragon/www/proyjon/public';
const OUT = PUB + '/manual';
fs.mkdirSync(OUT, { recursive: true });
const FONT = 'Nirmala UI'; // ships with Windows, renders Bengali in Word

(async () => {
  const browser = await puppeteer.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true, defaultViewport: { width: 1280, height: 900 } });
  const page = await browser.newPage();
  await page.goto(BASE + '/user-manual.html', { waitUntil: 'networkidle0', timeout: 120000 });
  await page.evaluate(async () => {
    document.querySelectorAll('img[loading=lazy]').forEach(i => i.loading = 'eager');
    document.querySelectorAll('details').forEach(d => d.open = true);
    await Promise.all([...document.images].map(i => i.complete ? 0 : new Promise(r => { i.onload = i.onerror = r; })));
    await document.fonts.ready;
  });

  // preview screenshots of the manual itself
  await page.screenshot({ path: OUT + '/preview-desktop.jpg', type: 'jpeg', quality: 80 });

  // ---------- PDF ----------
  await page.emulateMediaType('print');
  await page.pdf({
    path: OUT + '/user-manual.pdf', format: 'A4', printBackground: true,
    margin: { top: '14mm', bottom: '16mm', left: '12mm', right: '12mm' },
    displayHeaderFooter: true, headerTemplate: '<div></div>',
    footerTemplate: '<div style="font-size:9px;width:100%;text-align:center;color:#888">ইউজার ম্যানুয়াল · পৃষ্ঠা <span class="pageNumber"></span> / <span class="totalPages"></span></div>',
  });
  console.log('PDF done');

  // ---------- compact (paper-saving) PDF ----------
  await page.evaluate(() => document.documentElement.classList.add('compact'));
  await page.pdf({
    path: OUT + '/user-manual-compact.pdf', format: 'A4', printBackground: true,
    margin: { top: '9mm', bottom: '11mm', left: '9mm', right: '9mm' },
    displayHeaderFooter: true, headerTemplate: '<div></div>',
    footerTemplate: '<div style="font-size:8px;width:100%;text-align:center;color:#888">ইউজার ম্যানুয়াল (কাগজ-সাশ্রয়ী) · পৃষ্ঠা <span class="pageNumber"></span> / <span class="totalPages"></span></div>',
  });
  await page.evaluate(() => document.documentElement.classList.remove('compact'));
  console.log('Compact PDF done');
  await page.emulateMediaType('screen');

  // ---------- extract ordered content blocks ----------
  const blocks = await page.evaluate(() => {
    const out = [];
    const txt = el => el.innerText.replace(/\s+\n/g, '\n').replace(/[ \t]+/g, ' ').trim();
    const walk = el => {
      for (const c of el.children) {
        const t = c.tagName;
        if (c.classList.contains('dl') || c.classList.contains('tools') || c.classList.contains('bar')) continue;
        if (t === 'H1') out.push({ k: 'h1', t: txt(c) });
        else if (t === 'H2') out.push({ k: 'h2', t: txt(c) });
        else if (t === 'H3' || t === 'H4') out.push({ k: 'h3', t: txt(c) });
        else if (t === 'FIGURE') out.push({ k: 'img', src: c.querySelector('img').getAttribute('src'), cap: txt(c.querySelector('figcaption')), url: c.querySelector('.bar em')?.innerText || '' });
        else if (t === 'TABLE') out.push({ k: 'table', rows: [...c.rows].map(r => [...r.cells].map(x => txt(x))) });
        else if (t === 'OL' || t === 'UL') {
          const steps = c.classList.contains('steps');
          [...c.children].forEach((li, i) => {
            const b = li.querySelector(':scope > b');
            const head = b ? txt(b) : '';
            const rest = txt(li).replace(head, '').trim();
            out.push({ k: 'li', n: t === 'OL' ? i + 1 : 0, head, t: rest, steps });
          });
        }
        else if (t === 'DETAILS') { out.push({ k: 'q', t: txt(c.querySelector('summary')) }); out.push({ k: 'p', t: txt(c.querySelector('p')) }); }
        else if (c.classList.contains('note')) out.push({ k: 'note', cls: [...c.classList].find(x => x !== 'note'), t: txt(c) });
        else if (c.classList.contains('flow') || c.classList.contains('chips')) out.push({ k: 'p', t: [...c.children].map(x => x.innerText.trim()).filter(Boolean).join('  ') });
        else if (t === 'P' || t === 'CODE') out.push({ k: 'p', t: txt(c), lead: c.classList.contains('lead') });
        else if (c.classList.contains('path')) out.push({ k: 'path', t: [...c.childNodes].map(n => n.textContent.trim()).filter(Boolean).join(' › ') });
        else if (c.children.length === 0 || (['DIV'].includes(t) && ![...c.children].some(x => /^(H\d|P|OL|UL|FIGURE|TABLE|DIV|DETAILS|SPAN)$/.test(x.tagName) && !(x.tagName === 'SPAN' && x.classList.length === 0)))) {
          const s = txt(c); if (s) out.push({ k: 'p', t: s });
        } else {
          // text directly inside a container (e.g. "card" with text + inline tags)
          const direct = [...c.childNodes].filter(n => n.nodeType === 3 || /^(B|CODE|A|I|EM|KBD|BR)$/.test(n.tagName || '')).map(n => n.textContent).join('').replace(/\s+/g, ' ').trim();
          // heading first, then the card's own text (keeps text under its own title)
          const startsWithHeading = /^H\d$/.test(c.firstElementChild?.tagName || '');
          if (!startsWithHeading && direct.length > 2) out.push({ k: 'p', t: direct });
          walk(c);
          if (startsWithHeading && direct.length > 2) out.push({ k: 'p', t: direct });
        }
      }
    };
    walk(document.querySelector('.hero .wrap'));
    walk(document.querySelector('.main > .wrap'));
    return out;
  });
  fs.writeFileSync(OUT + '/_blocks.json', JSON.stringify(blocks, null, 1));

  // ---------- Word manual ----------
  const C = { brand: '4F46E5', pink: 'DB2777', muted: '64748B', tip: 'ECFDF5', warn: 'FFF7ED', info: 'EFF6FF' };
  const run = (t, o = {}) => new D.TextRun({ text: t, font: FONT, ...o });
  const para = (t, o = {}, ro = {}) => new D.Paragraph({ children: [run(t, ro)], spacing: { after: 120, line: 300 }, ...o });
  const img = (src, w = 600) => {
    const file = path.join(PUB, src);
    if (!fs.existsSync(file)) return null;
    const buf = fs.readFileSync(file);
    const W = buf.readUInt16BE ? jpegSize(buf) : { w: 1600, h: 900 };
    return new D.ImageRun({ type: 'jpg', data: buf, transformation: { width: w, height: Math.round(w * W.h / W.w) } });
  };
  function jpegSize(b) { let i = 2; while (i < b.length) { if (b[i] !== 0xFF) { i++; continue; } const m = b[i + 1]; const len = b.readUInt16BE(i + 2); if (m >= 0xC0 && m <= 0xC3) return { h: b.readUInt16BE(i + 5), w: b.readUInt16BE(i + 7) }; i += 2 + len; } return { w: 1600, h: 900 }; }

  const children = [];
  let figNo = 0, h2Count = 0;
  // cover
  children.push(new D.Paragraph({ spacing: { before: 2400 }, alignment: D.AlignmentType.CENTER, children: [run('🛍️', { size: 96 })] }));
  children.push(new D.Paragraph({ alignment: D.AlignmentType.CENTER, children: [run('মাল্টি ব্রাঞ্চ ই-কমার্স', { size: 56, bold: true, color: C.brand })] }));
  children.push(new D.Paragraph({ alignment: D.AlignmentType.CENTER, spacing: { after: 400 }, children: [run('ইউজার ম্যানুয়াল', { size: 48, bold: true, color: C.pink })] }));
  children.push(new D.Paragraph({ alignment: D.AlignmentType.CENTER, children: [run('দোকান মালিক · স্টাফ · ক্রেতা · সুপার অ্যাডমিন', { size: 26, color: C.muted })] }));
  const cover = img('manual-img/10-dashboard.jpg', 560); if (cover) children.push(new D.Paragraph({ alignment: D.AlignmentType.CENTER, spacing: { before: 400 }, children: [cover] }));
  children.push(new D.Paragraph({ children: [new D.PageBreak()] }));
  children.push(new D.Paragraph({ heading: D.HeadingLevel.HEADING_1, children: [run('সূচিপত্র', { color: C.brand })] }));
  children.push(new D.TableOfContents('সূচিপত্র', { hyperlink: true, headingStyleRange: '1-2' }));
  children.push(para('(Word-এ সূচিপত্রে ডান-ক্লিক করে “Update Field” চাপলে পৃষ্ঠা নম্বর আসবে)', {}, { color: C.muted, size: 18, italics: true }));

  for (const b of blocks) {
    if (b.k === 'h1') continue;
    if (b.k === 'h2') {
      h2Count++;
      children.push(new D.Paragraph({ heading: D.HeadingLevel.HEADING_1, pageBreakBefore: true, children: [run(b.t, { color: C.brand })] }));
    } else if (b.k === 'h3') children.push(new D.Paragraph({ heading: D.HeadingLevel.HEADING_2, children: [run(b.t, { color: C.pink })] }));
    else if (b.k === 'p') children.push(para(b.t, {}, b.lead ? { color: C.muted, italics: true } : {}));
    else if (b.k === 'path') children.push(para('📍 মেনু: ' + b.t, {}, { color: C.brand, bold: true, size: 20 }));
    else if (b.k === 'q') children.push(para('❓ ' + b.t, { spacing: { before: 160, after: 60 } }, { bold: true, color: C.brand }));
    else if (b.k === 'li') children.push(new D.Paragraph({
      spacing: { after: 80, line: 300 }, indent: { left: 360, hanging: 360 },
      children: [run(b.n ? `${b.n}. ` : '• ', { bold: true, color: C.pink }), ...(b.head ? [run(b.head + (b.t ? ' — ' : ''), { bold: true })] : []), run(b.t)],
    }));
    else if (b.k === 'note') children.push(new D.Paragraph({
      shading: { type: D.ShadingType.CLEAR, fill: C[b.cls] || C.info, color: 'auto' },
      border: { left: { style: D.BorderStyle.SINGLE, size: 24, color: b.cls === 'warn' ? 'F97316' : b.cls === 'tip' ? '10B981' : '3B82F6', space: 8 } },
      spacing: { before: 120, after: 160, line: 300 }, children: [run(b.t)],
    }));
    else if (b.k === 'img') {
      const im = img(b.src, 600); if (!im) continue;
      figNo++;
      children.push(new D.Paragraph({ alignment: D.AlignmentType.CENTER, spacing: { before: 120 }, keepNext: true, children: [im] }));
      children.push(new D.Paragraph({ alignment: D.AlignmentType.CENTER, spacing: { after: 240 }, children: [run(`চিত্র ${figNo}: `, { bold: true, color: C.brand, size: 20 }), run(b.cap, { color: C.muted, size: 20, italics: true })] }));
    } else if (b.k === 'table') {
      children.push(new D.Table({
        width: { size: 100, type: D.WidthType.PERCENTAGE },
        rows: b.rows.map((r, ri) => new D.TableRow({ tableHeader: ri === 0, children: r.map(cell => new D.TableCell({
          shading: ri === 0 ? { type: D.ShadingType.CLEAR, fill: 'EEF2FF', color: 'auto' } : undefined,
          margins: { top: 60, bottom: 60, left: 100, right: 100 },
          children: [new D.Paragraph({ children: [run(cell, { bold: ri === 0, size: 20 })] })],
        })) })),
      }));
      children.push(para(''));
    }
  }

  const doc = new D.Document({
    creator: 'User Manual', title: 'ইউজার ম্যানুয়াল — মাল্টি ব্রাঞ্চ ই-কমার্স',
    features: { updateFields: true },
    styles: {
      default: { document: { run: { font: FONT, size: 22 } } },
      paragraphStyles: [
        { id: 'Heading1', name: 'Heading 1', basedOn: 'Normal', next: 'Normal', quickFormat: true, run: { font: FONT, size: 36, bold: true }, paragraph: { spacing: { after: 200 } } },
        { id: 'Heading2', name: 'Heading 2', basedOn: 'Normal', next: 'Normal', quickFormat: true, run: { font: FONT, size: 28, bold: true }, paragraph: { spacing: { before: 240, after: 120 } } },
      ],
    },
    sections: [{
      properties: { page: { size: { width: 11906, height: 16838 }, margin: { top: 1000, bottom: 1000, left: 1000, right: 1000 } } },
      footers: { default: new D.Footer({ children: [new D.Paragraph({ alignment: D.AlignmentType.CENTER, children: [run('ইউজার ম্যানুয়াল · পৃষ্ঠা ', { size: 16, color: C.muted }), new D.TextRun({ children: [D.PageNumber.CURRENT], size: 16, color: C.muted, font: FONT })] })] }) },
      children,
    }],
  });
  fs.writeFileSync(OUT + '/user-manual.docx', await D.Packer.toBuffer(doc));
  console.log('DOCX done:', h2Count, 'chapters,', figNo, 'figures');

  // ---------- compact (paper-saving) Word ----------
  {
    const S = 19; // 9.5pt body
    const r = (t, o = {}) => new D.TextRun({ text: t, font: FONT, size: S, ...o });
    const p = (t, ro = {}, po = {}) => new D.Paragraph({ children: [r(t, ro)], spacing: { after: 40, line: 252 }, ...po });
    const none = { style: D.BorderStyle.NONE, size: 0, color: 'FFFFFF' };
    const noBorders = { top: none, bottom: none, left: none, right: none, insideHorizontal: none, insideVertical: none };
    const kids = [];
    let fig = 0, chapter = '', pending = [];
    const flush = () => {
      if (!pending.length) return;
      const cols = /অ্যাডমিন/.test(chapter) ? 3 : 2;
      const w = cols === 3 ? 222 : 340;
      for (let i = 0; i < pending.length; i += cols) {
        const row = pending.slice(i, i + cols);
        const cells = row.map(b => {
          fig++;
          const im = img(b.src, b.src.includes('mobile') ? 120 : w);
          return new D.TableCell({
            width: { size: Math.floor(100 / cols), type: D.WidthType.PERCENTAGE },
            margins: { top: 40, bottom: 60, left: 40, right: 40 },
            children: [
              new D.Paragraph({ alignment: D.AlignmentType.CENTER, children: im ? [im] : [] }),
              new D.Paragraph({ alignment: D.AlignmentType.CENTER, children: [r(`চিত্র ${fig}: `, { bold: true, color: C.brand, size: 15 }), r(b.cap, { color: C.muted, size: 15 })] }),
            ],
          });
        });
        while (cells.length < cols) cells.push(new D.TableCell({ width: { size: Math.floor(100 / cols), type: D.WidthType.PERCENTAGE }, children: [new D.Paragraph('')] }));
        kids.push(new D.Table({ width: { size: 100, type: D.WidthType.PERCENTAGE }, borders: noBorders, rows: [new D.TableRow({ cantSplit: true, children: cells })] }));
      }
      pending = [];
    };
    // compact title block (no separate cover page)
    kids.push(new D.Paragraph({ border: { bottom: { style: D.BorderStyle.SINGLE, size: 18, color: '6366F1', space: 4 } }, spacing: { after: 80 },
      children: [r('🛍️ মাল্টি ব্রাঞ্চ ই-কমার্স — ইউজার ম্যানুয়াল', { size: 34, bold: true, color: C.brand })] }));
    kids.push(p('দোকান মালিক · স্টাফ · ক্রেতা · সুপার অ্যাডমিন — কাগজ-সাশ্রয়ী প্রিন্ট সংস্করণ', { color: C.muted, italics: true }));
    kids.push(new D.Paragraph({ spacing: { before: 80 }, children: [r('সূচিপত্র', { bold: true, color: C.pink, size: 22 })] }));
    kids.push(new D.TableOfContents('সূচিপত্র', { hyperlink: true, headingStyleRange: '1-1' }));
    for (const b of blocks) {
      if (b.k !== 'img') flush();
      if (b.k === 'h1') continue;
      if (b.k === 'h2') { chapter = b.t; kids.push(new D.Paragraph({ heading: D.HeadingLevel.HEADING_1, keepNext: true, border: { bottom: { style: D.BorderStyle.SINGLE, size: 8, color: 'C7D2FE', space: 2 } }, children: [new D.TextRun({ text: b.t, font: FONT, color: C.brand })] })); }
      else if (b.k === 'h3') kids.push(new D.Paragraph({ heading: D.HeadingLevel.HEADING_2, keepNext: true, children: [new D.TextRun({ text: b.t, font: FONT, color: C.pink })] }));
      else if (b.k === 'p') kids.push(p(b.t, b.lead ? { color: C.muted, italics: true } : {}));
      else if (b.k === 'path') kids.push(p('📍 মেনু: ' + b.t, { color: C.brand, bold: true, size: 17 }));
      else if (b.k === 'q') kids.push(p('❓ ' + b.t, { bold: true, color: C.brand }, { spacing: { before: 60, after: 0 }, keepNext: true }));
      else if (b.k === 'li') kids.push(new D.Paragraph({ spacing: { after: 20, line: 252 }, indent: { left: 300, hanging: 300 },
        children: [r(b.n ? `${b.n}. ` : '• ', { bold: true, color: C.pink }), ...(b.head ? [r(b.head + (b.t ? ' — ' : ''), { bold: true })] : []), r(b.t)] }));
      else if (b.k === 'note') kids.push(new D.Paragraph({ spacing: { before: 40, after: 60, line: 252 },
        border: { left: { style: D.BorderStyle.SINGLE, size: 18, color: b.cls === 'warn' ? 'F97316' : b.cls === 'tip' ? '10B981' : '3B82F6', space: 6 } },
        children: [r(b.t, { size: 17 })] }));
      else if (b.k === 'img') pending.push(b);
      else if (b.k === 'table') {
        kids.push(new D.Table({ width: { size: 100, type: D.WidthType.PERCENTAGE },
          rows: b.rows.map((row, ri) => new D.TableRow({ tableHeader: ri === 0, children: row.map(cell => new D.TableCell({
            shading: ri === 0 ? { type: D.ShadingType.CLEAR, fill: 'EEF2FF', color: 'auto' } : undefined,
            margins: { top: 20, bottom: 20, left: 80, right: 80 },
            children: [new D.Paragraph({ children: [r(cell, { bold: ri === 0, size: 17 })] })] })) })) }));
        kids.push(new D.Paragraph({ spacing: { after: 40 }, children: [] }));
      }
    }
    flush();
    const cdoc = new D.Document({
      creator: 'User Manual', title: 'ইউজার ম্যানুয়াল (কাগজ-সাশ্রয়ী)', features: { updateFields: true },
      styles: {
        default: { document: { run: { font: FONT, size: S } } },
        paragraphStyles: [
          { id: 'Heading1', name: 'Heading 1', basedOn: 'Normal', next: 'Normal', quickFormat: true, run: { font: FONT, size: 28, bold: true }, paragraph: { spacing: { before: 200, after: 60 } } },
          { id: 'Heading2', name: 'Heading 2', basedOn: 'Normal', next: 'Normal', quickFormat: true, run: { font: FONT, size: 22, bold: true }, paragraph: { spacing: { before: 100, after: 30 } } },
        ],
      },
      sections: [{
        properties: { page: { size: { width: 11906, height: 16838 }, margin: { top: 567, bottom: 624, left: 567, right: 567, footer: 300 } } },
        footers: { default: new D.Footer({ children: [new D.Paragraph({ alignment: D.AlignmentType.CENTER, children: [r('ইউজার ম্যানুয়াল (কাগজ-সাশ্রয়ী) · পৃষ্ঠা ', { size: 14, color: C.muted }), new D.TextRun({ children: [D.PageNumber.CURRENT], size: 14, color: C.muted, font: FONT })] })] }) },
        children: kids,
      }],
    });
    fs.writeFileSync(OUT + '/user-manual-compact.docx', await D.Packer.toBuffer(cdoc));
    console.log('Compact DOCX done:', fig, 'figures');
  }

  // ---------- video script (scene-by-scene) ----------
  const scenes = []; let chapter = '', lead = '', sub = '', lastText = [], firstInChapter = true;
  const clean = s => s.replace(/[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}\u{FE0F}]/gu, '').replace(/\s+/g, ' ').trim();
  for (const b of blocks) {
    if (b.k === 'h2') { chapter = clean(b.t); lead = ''; sub = ''; lastText = []; firstInChapter = true; }
    else if (b.k === 'h3') { const t = clean(b.t); sub = /গ্যালারি/.test(t) ? '' : t; }
    else if (b.k === 'p' && b.lead && !lead) lead = clean(b.t);
    else if (b.k === 'p' || b.k === 'li' || b.k === 'note') lastText.push(clean((b.head ? b.head + ' — ' : '') + b.t));
    else if (b.k === 'img') {
      const cap = clean(b.cap);
      let say = '';
      if (firstInChapter) say += `এখন দেখব — ${chapter}। ` + (lead ? lead + ' ' : '');
      let body = lastText.slice(-2).join(' ');
      if (body.length > 320) body = body.slice(0, 320).replace(/\s\S*$/, '') + '…';
      say += `এই স্ক্রিনে দেখছেন ${cap}। ` + body;
      scenes.push({ chapter, sub, img: b.src, cap, url: b.url, say: say.trim() });
      lastText = []; firstInChapter = false;
    }
  }
  const vs = [];
  vs.push(new D.Paragraph({ heading: D.HeadingLevel.HEADING_1, children: [run('🎬 ভিডিও টিউটোরিয়াল স্ক্রিপ্ট', { color: C.brand })] }));
  vs.push(para('প্রতিটি দৃশ্যে (Scene) কোন স্ক্রিনশট দেখাবেন এবং কী বলবেন (ভয়েস-ওভার) তা দেওয়া আছে। ছবিগুলো public/manual-img ফোল্ডারে ক্রমানুসারে আছে — PowerPoint, Canva, CapCut বা Remotion-এ সরাসরি ব্যবহার করা যায়। প্রতি দৃশ্যে ৬–১০ সেকেন্ড রাখুন।', {}, { color: C.muted }));
  let lastCh = '';
  scenes.forEach((s, i) => {
    if (s.chapter !== lastCh) { lastCh = s.chapter; vs.push(new D.Paragraph({ heading: D.HeadingLevel.HEADING_2, spacing: { before: 360 }, children: [run(s.chapter, { color: C.pink })] })); }
    const im = img(s.img, 300);
    vs.push(new D.Table({
      width: { size: 100, type: D.WidthType.PERCENTAGE },
      rows: [new D.TableRow({ cantSplit: true, children: [
        new D.TableCell({ width: { size: 45, type: D.WidthType.PERCENTAGE }, margins: { top: 80, bottom: 80, left: 80, right: 80 }, children: [new D.Paragraph({ children: im ? [im] : [] })] }),
        new D.TableCell({ width: { size: 55, type: D.WidthType.PERCENTAGE }, margins: { top: 80, bottom: 80, left: 120, right: 80 }, children: [
          new D.Paragraph({ children: [run(`Scene ${i + 1}`, { bold: true, color: C.brand }), run(`   ${path.basename(s.img)}`, { color: C.muted, size: 16 })] }),
          new D.Paragraph({ spacing: { before: 60 }, children: [run('🖥️ স্ক্রিন: ', { bold: true, size: 20 }), run(s.cap + (s.url ? ` (${s.url})` : ''), { size: 20 })] }),
          new D.Paragraph({ spacing: { before: 60 }, children: [run('🎙️ বলুন: ', { bold: true, size: 20, color: C.pink }), run(s.say, { size: 20 })] }),
        ] }),
      ] })],
    }));
    vs.push(para('', { spacing: { after: 60 } }));
  });
  const vdoc = new D.Document({ styles: { default: { document: { run: { font: FONT, size: 22 } } }, paragraphStyles: [
    { id: 'Heading1', name: 'Heading 1', basedOn: 'Normal', run: { font: FONT, size: 36, bold: true } },
    { id: 'Heading2', name: 'Heading 2', basedOn: 'Normal', run: { font: FONT, size: 28, bold: true } }] },
    sections: [{ properties: { page: { margin: { top: 900, bottom: 900, left: 900, right: 900 } } }, children: vs }] });
  fs.writeFileSync(OUT + '/video-script.docx', await D.Packer.toBuffer(vdoc));
  fs.writeFileSync(OUT + '/video-scenes.json', JSON.stringify(scenes.map((s, i) => ({ scene: i + 1, image: s.img, chapter: s.chapter, caption: s.cap, narration: s.say })), null, 2));
  console.log('Video script done:', scenes.length, 'scenes');
  fs.copyFileSync(OUT + '/_blocks.json', '_blocks.json'); fs.unlinkSync(OUT + '/_blocks.json');
  await browser.close();
})();
