// Builds public/manual/user-manual-standalone.html — the manual with every screenshot embedded
// (base64), so it shows all images on any server, by e-mail, on a USB stick or offline.
// Usage: node standalone.js
const fs = require('fs');
const path = require('path');

const PUB = path.resolve(__dirname, '../../public');
let html = fs.readFileSync(path.join(PUB, 'user-manual.html'), 'utf8');

let count = 0, bytes = 0;
html = html.replace(/(["'])(manual-img\/[A-Za-z0-9._-]+\.(jpe?g|png|webp))\1/g, (all, q, rel, ext) => {
  const file = path.join(PUB, rel);
  if (!fs.existsSync(file)) { console.warn('missing', rel); return all; }
  const buf = fs.readFileSync(file);
  count++; bytes += buf.length;
  const mime = ext === 'png' ? 'image/png' : ext === 'webp' ? 'image/webp' : 'image/jpeg';
  return q + `data:${mime};base64,` + buf.toString('base64') + q;
});
// loading=lazy is pointless for embedded images and can delay printing
html = html.replace(/ loading="lazy"/g, '');

fs.mkdirSync(path.join(PUB, 'manual'), { recursive: true });
const out = path.join(PUB, 'manual', 'user-manual-standalone.html');
// downloads sit next to this file inside manual/
html = html.replace(/href="manual\//g, 'href="');
fs.writeFileSync(out, html);
console.log(`standalone: ${count} images embedded, ${(fs.statSync(out).size / 1048576).toFixed(1)} MB -> ${out}`);
