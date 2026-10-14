import { chromium } from 'playwright';
import { spawn, execSync } from 'child_process';
import fs from 'fs';
import path from 'path';

const PORT = process.env.PORT || '8000';
const BASE_URL = `http://127.0.0.1:${PORT}`;
const OUTPUT_DIR = path.resolve('docs/ui-review');

if (!fs.existsSync(OUTPUT_DIR)) {
  fs.mkdirSync(OUTPUT_DIR, { recursive: true });
}

// Parse args
const args = process.argv.slice(2);
let phase = 'before';
for (const arg of args) {
  if (arg.startsWith('--phase=')) {
    phase = arg.split('=')[1];
  }
}

let serverProcess = null;

async function isServerRunning() {
  try {
    const res = await fetch(`${BASE_URL}/peta`, { method: 'HEAD' });
    return res.status === 200 || res.status === 302;
  } catch {
    return false;
  }
}

async function startServerIfNeeded() {
  if (await isServerRunning()) {
    console.log(`[INFO] Server already running at ${BASE_URL}`);
    return;
  }
  console.log(`[INFO] Starting ephemeral PHP server on port ${PORT}...`);
  serverProcess = spawn('php', ['-S', `127.0.0.1:${PORT}`, '-t', 'public'], { stdio: 'ignore' });
  for (let i = 0; i < 30; i++) {
    await new Promise((r) => setTimeout(r, 200));
    if (await isServerRunning()) {
      console.log(`[INFO] Ephemeral server ready at ${BASE_URL}`);
      return;
    }
  }
  throw new Error(`Failed to start server at ${BASE_URL}`);
}

async function scanPageClutter(page) {
  return await page.evaluate(() => {
    // a) Headings followed by helper text
    const headings = Array.from(document.querySelectorAll('h1, h2, h3, h4, [role="heading"], .card-title'));
    const headingsWithHelper = [];

    headings.forEach((h) => {
      // Find next element sibling
      let next = h.nextElementSibling;
      // If heading is wrapped in a div that only contains heading and helper, inspect parent's next or child
      if (!next && h.parentElement) {
        const parentSib = h.parentElement.nextElementSibling;
        if (parentSib && parentSib.tagName && ['P', 'SPAN', 'DIV'].includes(parentSib.tagName)) {
          next = parentSib;
        }
      }

      if (next && next.offsetParent !== null) {
        const text = next.innerText?.trim() || '';
        const style = window.getComputedStyle(next);
        const fontSize = parseFloat(style.fontSize);

        // Check exclusions:
        // - Form labels
        // - Validation error
        // - Sample data notice
        // - Empty / error states
        const isLabel = next.tagName === 'LABEL' || next.querySelector('label') !== null;
        const isError = next.classList.contains('text-rose-600') || next.classList.contains('text-red-600') || style.color.includes('225') || style.color.includes('239');
        const isSampleNotice = /SIMULASI|DATA CONTOH/i.test(text);
        const isEmptyState = /Tidak ada|Belum ada/i.test(text);

        if (!isLabel && !isError && !isSampleNotice && !isEmptyState && text.length >= 1 && text.length <= 250) {
          if (fontSize <= 14.5 || style.color.includes('100') || style.color.includes('148') || style.color.includes('115') || style.color.includes('107')) {
            headingsWithHelper.push({
              heading: h.innerText.trim().slice(0, 40),
              headingTag: h.tagName.toLowerCase(),
              helperText: text.slice(0, 100),
              helperTag: next.tagName.toLowerCase(),
            });
          }
        }
      }
    });

    // b) Eyebrows and badges near headings
    const eyebrowsAndBadges = [];
    headings.forEach((h) => {
      const container = h.closest('.border, .rounded-2xl, .rounded-xl, header, .card, div') || h.parentElement;
      if (!container) return;

      const candidates = Array.from(container.querySelectorAll('span, div, p, em')).filter((el) => {
        if (el === h || h.contains(el)) return false;
        if (el.offsetParent === null) return false;
        // Near heading: either sibling, inside previous element, or nearby
        const hRect = h.getBoundingClientRect();
        const elRect = el.getBoundingClientRect();
        const verticalDist = Math.abs(elRect.top - hRect.top);
        return verticalDist < 70;
      });

      candidates.forEach((el) => {
        const text = el.innerText?.trim() || '';
        if (!text || text.length > 50) return;

        // Exclude table rows/cells
        if (el.closest('table, tr, td, th')) return;

        // Exclude sample-data notice
        if (/SIMULASI|DATA CONTOH/i.test(text)) return;

        // Exclude form controls or buttons
        if (el.closest('button, a, input, select, label')) return;

        const style = window.getComputedStyle(el);
        const isUppercase = style.textTransform === 'uppercase' || (text.length >= 3 && text === text.toUpperCase() && /[A-Z]/.test(text));
        const isPill = style.borderRadius.includes('9999px') || el.classList.contains('rounded-full');
        const isSmall = parseFloat(style.fontSize) <= 12.5;

        // Check if it's an eyebrow or chip near heading
        if (isUppercase || isPill || isSmall) {
          if (el.classList.contains('bg-') || style.backgroundColor !== 'rgba(0, 0, 0, 0)' || isUppercase) {
            eyebrowsAndBadges.push({
              nearHeading: h.innerText.trim().slice(0, 40),
              text,
              isUppercase,
              isPill,
              tag: el.tagName.toLowerCase(),
            });
          }
        }
      });
    });

    // Filter duplicates in eyebrowsAndBadges
    const uniqueEyebrows = [];
    const seenTexts = new Set();
    eyebrowsAndBadges.forEach((item) => {
      const key = `${item.nearHeading}|${item.text}`;
      if (!seenTexts.has(key)) {
        seenTexts.add(key);
        uniqueEyebrows.push(item);
      }
    });

    // c) Container nesting depth
    let maxDepth = 0;
    const deepElements = [];

    const allElements = Array.from(document.querySelectorAll('body *')).filter((el) => {
      if (['script', 'style', 'svg', 'path', 'meta', 'link'].includes(el.tagName.toLowerCase())) return false;
      return el.offsetParent !== null;
    });

    allElements.forEach((el) => {
      let depth = 0;
      let curr = el;
      while (curr && curr !== document.body) {
        const style = window.getComputedStyle(curr);
        const hasBorder = parseFloat(style.borderWidth) > 0 && style.borderStyle !== 'none';
        const hasShadow = style.boxShadow !== 'none' && !style.boxShadow.includes('rgba(0, 0, 0, 0)');
        const hasBg = style.backgroundColor !== 'rgba(0, 0, 0, 0)' && style.backgroundColor !== 'transparent';
        if (hasBorder || hasShadow || hasBg) {
          depth++;
        }
        curr = curr.parentElement;
      }

      if (depth > maxDepth) maxDepth = depth;
      if (depth >= 3) {
        const tag = el.tagName.toLowerCase();
        const id = el.id ? `#${el.id}` : '';
        const cls = typeof el.className === 'string' ? '.' + el.className.trim().split(/\s+/).slice(0, 2).join('.') : '';
        deepElements.push({
          selector: `${tag}${id}${cls}`,
          depth,
          textSnippet: el.innerText?.trim().slice(0, 30) || '',
        });
      }
    });

    // d) Word count and text blocks in first viewport
    const pageText = document.body.innerText.trim();
    const words = pageText ? pageText.split(/\s+/).filter(Boolean).length : 0;

    const viewportHeight = window.innerHeight;
    const textBlocksInViewport = allElements.filter((el) => {
      if (!['P', 'H1', 'H2', 'H3', 'H4', 'SPAN', 'DIV', 'LI', 'TD', 'TH'].includes(el.tagName)) return false;
      const r = el.getBoundingClientRect();
      if (r.height === 0 || r.width === 0) return false;
      if (r.top >= 0 && r.top < viewportHeight) {
        // direct text
        return Array.from(el.childNodes).some((n) => n.nodeType === Node.TEXT_NODE && n.nodeValue.trim().length > 0);
      }
      return false;
    }).length;

    return {
      headingsWithHelper,
      headingsWithHelperCount: headingsWithHelper.length,
      eyebrowsAndBadges: uniqueEyebrows,
      eyebrowsAndBadgesCount: uniqueEyebrows.length,
      maxContainerDepth: maxDepth,
      deepElementsCount: deepElements.length,
      deepElementsSample: deepElements.slice(0, 5),
      wordCount: words,
      textBlocksInViewport,
    };
  });
}

async function runScan() {
  await startServerIfNeeded();

  console.log(`[INFO] Launching Chromium for declutter scan (Phase: ${phase})...`);
  const browser = await chromium.launch({
    executablePath: process.env.CHROMIUM_PATH || '/usr/bin/chromium',
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  });

  const scanResults = {
    phase,
    timestamp: new Date().toISOString(),
    pages: {},
  };

  try {
    // 1. Guest pages
    const guestPages = [
      { id: 'peta', url: `${BASE_URL}/peta` },
      { id: 'wilayah-3302', url: `${BASE_URL}/wilayah/3302` },
      { id: 'login', url: `${BASE_URL}/login` },
    ];

    for (const p of guestPages) {
      scanResults.pages[p.id] = {};
      for (const width of [1440, 390]) {
        const height = width === 1440 ? 900 : 844;
        const ctx = await browser.newContext({ viewport: { width, height } });
        const page = await ctx.newPage();
        await page.goto(p.url, { waitUntil: 'networkidle' });

        const shotName = `declutter-${p.id}-${phase}-${width}.png`;
        const shotPath = path.join(OUTPUT_DIR, shotName);
        await page.screenshot({ path: shotPath, fullPage: true });
        console.log(`[SCREENSHOT] (${phase}) Saved: ${shotName}`);

        const metrics = await scanPageClutter(page);
        scanResults.pages[p.id][`w_${width}`] = metrics;
        await ctx.close();
      }
    }

    // Helper to reset rate limits
    const resetThrottle = () => {
      try {
        execSync('php artisan tinker --execute \'DB::table("cache")->where("key", "like", "%:timer")->orWhere("key", "like", "sipangan-waspada-cache-[0-9a-f]%")->delete();\'', { stdio: 'ignore' });
      } catch {}
    };

    // 2. Distributor pages
    resetThrottle();
    const distCtx = await browser.newContext();
    const distAuth = await distCtx.newPage();
    await distAuth.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });
    await distAuth.locator('#email').fill('distributor1@sipangan.test');
    await distAuth.locator('#password').fill('Distrib1234');
    await Promise.all([
      distAuth.waitForNavigation({ waitUntil: 'networkidle' }),
      distAuth.locator('button[type="submit"]').click(),
    ]);
    if (distAuth.url().includes('/login')) {
      const err = await distAuth.locator('.text-rose-600').allInnerTexts().catch(() => []);
      throw new Error(`Login failed for distributor: ${distAuth.url()} - ${err.join(', ')}`);
    }
    const distState = await distCtx.storageState();
    await distCtx.close();

    const distPages = [
      { id: 'pengiriman', url: `${BASE_URL}/pengiriman` },
      { id: 'pengiriman-buat', url: `${BASE_URL}/pengiriman/buat` },
      { id: 'pengiriman-detail', url: `${BASE_URL}/pengiriman/1` },
    ];

    for (const p of distPages) {
      scanResults.pages[p.id] = {};
      for (const width of [1440, 390]) {
        const height = width === 1440 ? 900 : 844;
        const ctx = await browser.newContext({ storageState: distState, viewport: { width, height } });
        const page = await ctx.newPage();
        await page.goto(p.url, { waitUntil: 'networkidle' });
        if (page.url().includes('/login')) {
          throw new Error(`Auth failed for distributor page ${p.id}, redirected to ${page.url()}`);
        }

        const shotName = `declutter-${p.id}-${phase}-${width}.png`;
        const shotPath = path.join(OUTPUT_DIR, shotName);
        await page.screenshot({ path: shotPath, fullPage: true });
        console.log(`[SCREENSHOT] (${phase}) Saved: ${shotName}`);

        const metrics = await scanPageClutter(page);
        scanResults.pages[p.id][`w_${width}`] = metrics;
        await ctx.close();
      }
    }

    // 3. BUMDes pages
    resetThrottle();
    const bumdesCtx = await browser.newContext();
    const bumdesAuth = await bumdesCtx.newPage();
    await bumdesAuth.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });
    await bumdesAuth.locator('#email').fill('bumdes.banyumas@sipangan.test');
    await bumdesAuth.locator('#password').fill('Bumdes1234');
    await Promise.all([
      bumdesAuth.waitForNavigation({ waitUntil: 'networkidle' }),
      bumdesAuth.locator('button[type="submit"]').click(),
    ]);
    if (bumdesAuth.url().includes('/login')) {
      const err = await bumdesAuth.locator('.text-rose-600').allInnerTexts().catch(() => []);
      throw new Error(`Login failed for bumdes: ${bumdesAuth.url()} - ${err.join(', ')}`);
    }
    const bumdesState = await bumdesCtx.storageState();
    await bumdesCtx.close();

    const bumdesPages = [
      { id: 'panen', url: `${BASE_URL}/panen` },
      { id: 'jadwal-tanam', url: `${BASE_URL}/jadwal-tanam` },
    ];

    for (const p of bumdesPages) {
      scanResults.pages[p.id] = {};
      for (const width of [1440, 390]) {
        const height = width === 1440 ? 900 : 844;
        const ctx = await browser.newContext({ storageState: bumdesState, viewport: { width, height } });
        const page = await ctx.newPage();
        await page.goto(p.url, { waitUntil: 'networkidle' });
        if (page.url().includes('/login')) {
          throw new Error(`Auth failed for bumdes page ${p.id}, redirected to ${page.url()}`);
        }

        const shotName = `declutter-${p.id}-${phase}-${width}.png`;
        const shotPath = path.join(OUTPUT_DIR, shotName);
        await page.screenshot({ path: shotPath, fullPage: true });
        console.log(`[SCREENSHOT] (${phase}) Saved: ${shotName}`);

        const metrics = await scanPageClutter(page);
        scanResults.pages[p.id][`w_${width}`] = metrics;
        await ctx.close();
      }
    }

    // 4. Admin pages
    resetThrottle();
    const adminCtx = await browser.newContext();
    const adminAuth = await adminCtx.newPage();
    await adminAuth.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });
    await adminAuth.locator('#email').fill('admin@sipangan.test');
    await adminAuth.locator('#password').fill('Admin1234');
    await Promise.all([
      adminAuth.waitForNavigation({ waitUntil: 'networkidle' }),
      adminAuth.locator('button[type="submit"]').click(),
    ]);
    if (adminAuth.url().includes('/login')) {
      const err = await adminAuth.locator('.text-rose-600').allInnerTexts().catch(() => []);
      throw new Error(`Login failed for admin: ${adminAuth.url()} - ${err.join(', ')}`);
    }
    const adminState = await adminCtx.storageState();
    await adminCtx.close();

    const adminPages = [
      { id: 'admin-pengguna', url: `${BASE_URL}/admin/pengguna` },
      { id: 'admin-pengguna-buat', url: `${BASE_URL}/admin/pengguna/buat` },
      { id: 'admin-pengguna-edit', url: `${BASE_URL}/admin/pengguna/1/edit` },
      { id: 'admin-dataset', url: `${BASE_URL}/admin/dataset` },
      { id: 'admin-forecast', url: `${BASE_URL}/admin/forecast` },
      { id: 'admin-pengaturan', url: `${BASE_URL}/admin/pengaturan` },
    ];

    for (const p of adminPages) {
      scanResults.pages[p.id] = {};
      for (const width of [1440, 390]) {
        const height = width === 1440 ? 900 : 844;
        const ctx = await browser.newContext({ storageState: adminState, viewport: { width, height } });
        const page = await ctx.newPage();
        await page.goto(p.url, { waitUntil: 'networkidle' });
        if (page.url().includes('/login')) {
          throw new Error(`Auth failed for admin page ${p.id}, redirected to ${page.url()}`);
        }

        const shotName = `declutter-${p.id}-${phase}-${width}.png`;
        const shotPath = path.join(OUTPUT_DIR, shotName);
        await page.screenshot({ path: shotPath, fullPage: true });
        console.log(`[SCREENSHOT] (${phase}) Saved: ${shotName}`);

        const metrics = await scanPageClutter(page);
        scanResults.pages[p.id][`w_${width}`] = metrics;
        await ctx.close();
      }
    }

    const outJsonPath = path.join(OUTPUT_DIR, `declutter-${phase}.json`);
    fs.writeFileSync(outJsonPath, JSON.stringify(scanResults, null, 2));
    console.log(`\n[SUCCESS] Clutter scan (${phase}) saved to: ${outJsonPath}`);

    // If phase === 'after' and declutter-before.json exists, print comparison table
    const beforeJsonPath = path.join(OUTPUT_DIR, 'declutter-before.json');
    if (phase === 'after' && fs.existsSync(beforeJsonPath)) {
      const beforeData = JSON.parse(fs.readFileSync(beforeJsonPath, 'utf8'));
      console.log('\n========================================================================================');
      console.log('BEFORE vs AFTER DECLUTTER SCAN COMPARISON TABLE (1440px)');
      console.log('========================================================================================');
      console.log(
        'Page ID'.padEnd(22) +
        'Helper Text (B/A)'.padEnd(20) +
        'Eyebrows (B/A)'.padEnd(18) +
        'Max Depth (B/A)'.padEnd(18) +
        'Words (B/A)'
      );
      console.log('----------------------------------------------------------------------------------------');

      for (const pageId of Object.keys(scanResults.pages)) {
        const b = beforeData.pages[pageId]?.w_1440;
        const a = scanResults.pages[pageId]?.w_1440;
        if (b && a) {
          const helperStr = `${b.headingsWithHelperCount} -> ${a.headingsWithHelperCount}`;
          const eyebrowStr = `${b.eyebrowsAndBadgesCount} -> ${a.eyebrowsAndBadgesCount}`;
          const depthStr = `${b.maxContainerDepth} -> ${a.maxContainerDepth}`;
          const wordStr = `${b.wordCount} -> ${a.wordCount}`;
          console.log(
            pageId.padEnd(22) +
            helperStr.padEnd(20) +
            eyebrowStr.padEnd(18) +
            depthStr.padEnd(18) +
            wordStr
          );
        }
      }
      console.log('========================================================================================\n');
    }
  } catch (err) {
    console.error('[ERROR during clutter scan]', err);
  } finally {
    await browser.close();
    if (serverProcess) {
      serverProcess.kill();
    }
  }
}

runScan();
