import { chromium } from 'playwright';
import { mkdirSync } from 'fs';

const OUT = 'C:/Projects/caraccidenthelp/screenshots';
mkdirSync(OUT, { recursive: true });

const browser = await chromium.launch();
const page = await browser.newPage();
await page.setViewportSize({ width: 1280, height: 900 });

// Original WordPress
console.log('Shooting original...');
await page.goto('https://saddlebrown-nightingale-345621.hostingersite.com/car-accident/', { waitUntil: 'networkidle', timeout: 30000 });
await page.screenshot({ path: `${OUT}/original_full.png`, fullPage: true });

// Sections of original
const origSections = [
  { name: 'header',    selector: 'header' },
  { name: 'hero',      selector: '.fusion-fullwidth:nth-child(1)' },
  { name: 'form1',     selector: '.fusion-fullwidth:nth-child(3)' },
  { name: 'stats',     selector: '.fusion-fullwidth:nth-child(4)' },
  { name: 'form2',     selector: '.fusion-fullwidth:nth-child(5)' },
  { name: 'benefits',  selector: '.fusion-fullwidth:nth-child(6)' },
  { name: 'footer_cta',selector: '.fusion-fullwidth:nth-child(7)' },
];
for (const { name, selector } of origSections) {
  try {
    const el = await page.$(selector);
    if (el) await el.screenshot({ path: `${OUT}/orig_${name}.png` });
  } catch(e) { console.log(`skip ${name}:`, e.message); }
}

// Local Next.js
console.log('Shooting local...');
await page.goto('http://localhost:3001', { waitUntil: 'networkidle', timeout: 30000 });
await page.screenshot({ path: `${OUT}/local_full.png`, fullPage: true });

// Sections of local
const localSections = [
  { name: 'header',    selector: 'header' },
  { name: 'hero',      selector: 'section:nth-of-type(1)' },
  { name: 'form1',     selector: 'section:nth-of-type(2)' },
  { name: 'stats',     selector: 'section:nth-of-type(3)' },
  { name: 'form2',     selector: 'section:nth-of-type(4)' },
  { name: 'benefits',  selector: 'section:nth-of-type(5)' },
  { name: 'footer_cta',selector: 'section:nth-of-type(6)' },
];
for (const { name, selector } of localSections) {
  try {
    const el = await page.$(selector);
    if (el) await el.screenshot({ path: `${OUT}/local_${name}.png` });
  } catch(e) { console.log(`skip ${name}:`, e.message); }
}

await browser.close();
console.log('Done! Screenshots saved to', OUT);
