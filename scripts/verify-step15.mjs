import { chromium } from 'playwright';
import { AxeBuilder } from '@axe-core/playwright';
import { spawn } from 'child_process';
import fs from 'fs';
import path from 'path';

const PORT = process.env.PORT || '8099';
const BASE_URL = `http://127.0.0.1:${PORT}`;
const OUTPUT_DIR = path.resolve('docs/ui-review');

if (!fs.existsSync(OUTPUT_DIR)) {
  fs.mkdirSync(OUTPUT_DIR, { recursive: true });
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

async function measurePageMetrics(page) {
  return await page.evaluate(() => {
    const docEl = document.documentElement;
    const body = document.body;
    const scrollWidth = Math.max(docEl.scrollWidth, body.scrollWidth);
    const innerWidth = window.innerWidth;
    const hasHorizontalScroll = scrollWidth > innerWidth;

    // Check font sizes < 12px (excluding scripts, svgs, styles, etc.)
    const allTextElements = Array.from(document.querySelectorAll('body *')).filter((el) => {
      if (['script', 'style', 'svg', 'path', 'noscript', 'meta', 'link'].includes(el.tagName.toLowerCase())) {
        return false;
      }
      if (el.offsetParent === null && el.offsetWidth === 0 && el.offsetHeight === 0) return false;
      const text = el.innerText?.trim();
      if (!text) return false;
      // Only leaf elements or elements with direct text
      const hasDirectText = Array.from(el.childNodes).some((node) => node.nodeType === Node.TEXT_NODE && node.nodeValue.trim().length > 0);
      return hasDirectText;
    });

    const smallFontElements = [];
    allTextElements.forEach((el) => {
      const style = window.getComputedStyle(el);
      const fsVal = parseFloat(style.fontSize);
      if (fsVal < 11.5) { // allow margin for subpixel 11.5~12px
        smallFontElements.push({
          tag: el.tagName.toLowerCase(),
          id: el.id,
          fontSize: style.fontSize,
          text: el.innerText.trim().slice(0, 30),
        });
      }
    });

    // Tap targets < 43.5x43.5
    const interactiveCandidates = Array.from(
      document.querySelectorAll('button, a[href], select, input:not([type="hidden"]), [role="button"]')
    ).filter((el) => {
      if (el.offsetParent === null && el.offsetWidth === 0 && el.offsetHeight === 0) return false;
      const style = window.getComputedStyle(el);
      if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') return false;
      const r = el.getBoundingClientRect();
      return r.width > 0 && r.height > 0;
    });

    const smallTargets = [];
    interactiveCandidates.forEach((el) => {
      let r = el.getBoundingClientRect();
      if (el.tagName.toLowerCase() === 'input' && (el.type === 'checkbox' || el.type === 'radio')) {
        const parentLabel = el.closest('label');
        if (parentLabel) {
          r = parentLabel.getBoundingClientRect();
        }
      }
      if (r.width < 43.5 || r.height < 43.5) {
        smallTargets.push({
          tag: el.tagName.toLowerCase(),
          id: el.id,
          text: el.innerText?.trim().slice(0, 25) || el.getAttribute('aria-label') || el.tagName,
          width: Math.round(r.width * 10) / 10,
          height: Math.round(r.height * 10) / 10,
        });
      }
    });

    return {
      hasHorizontalScroll,
      scrollWidth,
      innerWidth,
      smallFontCount: smallFontElements.length,
      smallFontElements: smallFontElements.slice(0, 5),
      smallTargetCount: smallTargets.length,
      smallTargets: smallTargets.slice(0, 5),
    };
  });
}

async function checkBannedWords(page, pageName) {
  const pageText = await page.evaluate(() => document.body.innerText);
  const bannedPatterns = [
    { name: 'PRD', regex: /\bPRD\b/i },
    { name: 'Selimbang', regex: /\bSelimbang\b/i },
    { name: 'harga', regex: /\bharga\b/i },
    { name: 'deflasi', regex: /\bdeflasi\b/i },
    { name: 'XGBoost', regex: /\bXGBoost\b/i },
  ];

  const found = [];
  for (const b of bannedPatterns) {
    if (b.regex.test(pageText)) {
      found.push(b.name);
    }
  }

  return {
    page: pageName,
    clean: found.length === 0,
    found,
  };
}

async function runVerification() {
  await startServerIfNeeded();

  console.log('[INFO] Launching Chromium...');
  const browser = await chromium.launch({
    executablePath: process.env.CHROMIUM_PATH || '/usr/bin/chromium',
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  });

  const evidence = {
    step: 15,
    timestamp: new Date().toISOString(),
    panen: {},
    jadwalTanam: {},
    bannedWordsSummary: {},
    status: 'IN_PROGRESS',
  };

  try {
    // =========================================================================
    // 1. AUTHENTICATING AS BUMDES
    // =========================================================================
    console.log('\n================================================================');
    console.log('PART 1: AUTHENTICATING AS BUMDES BANYUMAS');
    console.log('================================================================');

    const authContext = await browser.newContext();
    const authPage = await authContext.newPage();
    await authPage.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });

    await authPage.locator('#email').fill('bumdes.banyumas@sipangan.test');
    await authPage.locator('#password').fill('Bumdes1234');
    await Promise.all([
      authPage.waitForNavigation({ waitUntil: 'networkidle' }),
      authPage.locator('button[type="submit"]').click(),
    ]);

    const postLoginUrl = authPage.url();
    console.log(`[INFO] Logged in successfully. Current URL: ${postLoginUrl}`);

    const cookies = await authContext.cookies();
    await authContext.close();

    // =========================================================================
    // 2. PORTAL PANEN & SISA STOK (/panen)
    // =========================================================================
    console.log('\n================================================================');
    console.log('PART 2: VERIFYING /panen (Portal BUMDes)');
    console.log('================================================================');

    for (const vp of [{ w: 1440, h: 900 }, { w: 390, h: 844 }]) {
      const context = await browser.newContext({ viewport: { width: vp.w, height: vp.h } });
      await context.addCookies(cookies);
      const page = await context.newPage();
      await page.goto(`${BASE_URL}/panen`, { waitUntil: 'networkidle' });

      // Test live conversion kg -> ton
      const inputPanen = page.locator('#panen_kg');
      await inputPanen.fill('12000000');
      await inputPanen.dispatchEvent('input');
      await page.waitForTimeout(200);

      const inputStok = page.locator('#sisa_stok_kg');
      await inputStok.fill('3500000');
      await inputStok.dispatchEvent('input');
      await page.waitForTimeout(200);

      const panenBadgeText = await page.locator('#badge_panen_ton').innerText();
      const stokBadgeText = await page.locator('#badge_stok_ton').innerText();
      console.log(`[CHECK] Live conversion: panen="${panenBadgeText}", stok="${stokBadgeText}"`);

      const shotPath = path.join(OUTPUT_DIR, `step15-panen-${vp.w}.png`);
      await page.screenshot({ path: shotPath, fullPage: true });
      console.log(`[SCREENSHOT] Saved: ${shotPath}`);

      const metrics = await measurePageMetrics(page);
      evidence.panen[`viewport_${vp.w}`] = metrics;
      console.log(`[CHECK ${vp.w}] Horizontal Scroll: ${metrics.hasHorizontalScroll ? 'FAIL' : 'PASS'} (${metrics.scrollWidth}px vs ${metrics.innerWidth}px)`);
      console.log(`[CHECK ${vp.w}] Text < 12px: ${metrics.smallFontCount}`);
      console.log(`[CHECK ${vp.w}] Tap Targets < 44px: ${metrics.smallTargetCount}`);

      if (vp.w === 1440) {
        evidence.panen.liveConversion = {
          panenBadge: panenBadgeText,
          stokBadge: stokBadgeText,
          works: panenBadgeText.includes('12.000,0 ton') && stokBadgeText.includes('3.500,0 ton'),
        };

        // Axe accessibility scan
        console.log('[INFO] Running axe-core scan on /panen...');
        const axeResults = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']).analyze();
        const colorContrastViolations = axeResults.violations.filter((v) => v.id === 'color-contrast');
        const criticalViolations = axeResults.violations.filter((v) => v.impact === 'critical' || v.impact === 'serious');
        console.log(`[INFO] Axe Violations on /panen: ${axeResults.violations.length} (Contrast: ${colorContrastViolations.length}, Critical/Serious: ${criticalViolations.length})`);
        evidence.panen.axe = {
          totalViolations: axeResults.violations.length,
          colorContrastViolations: colorContrastViolations.length,
          criticalViolations: criticalViolations.length,
        };

        const banned = await checkBannedWords(page, '/panen');
        console.log(`[CHECK] Banned words on /panen: ${banned.clean ? 'CLEAN (0 found)' : 'FOUND: ' + banned.found.join(', ')}`);
        evidence.bannedWordsSummary['/panen'] = banned;
      }

      await context.close();
    }

    // =========================================================================
    // 3. JADWAL TANAM (/jadwal-tanam)
    // =========================================================================
    console.log('\n================================================================');
    console.log('PART 3: VERIFYING /jadwal-tanam (Rekomendasi Jadwal Tanam)');
    console.log('================================================================');

    for (const vp of [{ w: 1440, h: 900 }, { w: 390, h: 844 }]) {
      const context = await browser.newContext({ viewport: { width: vp.w, height: vp.h } });
      await context.addCookies(cookies);
      const page = await context.newPage();
      await page.goto(`${BASE_URL}/jadwal-tanam`, { waitUntil: 'networkidle' });

      const shotPath = path.join(OUTPUT_DIR, `step15-jadwal-${vp.w}.png`);
      await page.screenshot({ path: shotPath, fullPage: true });
      console.log(`[SCREENSHOT] Saved: ${shotPath}`);

      const metrics = await measurePageMetrics(page);
      evidence.jadwalTanam[`viewport_${vp.w}`] = metrics;
      console.log(`[CHECK ${vp.w}] Horizontal Scroll: ${metrics.hasHorizontalScroll ? 'FAIL' : 'PASS'} (${metrics.scrollWidth}px vs ${metrics.innerWidth}px)`);
      console.log(`[CHECK ${vp.w}] Text < 12px: ${metrics.smallFontCount}`);
      console.log(`[CHECK ${vp.w}] Tap Targets < 44px: ${metrics.smallTargetCount}`);

      if (vp.w === 1440) {
        const bodyText = await page.evaluate(() => document.body.innerText);
        const hasSimulasiBadge = bodyText.includes('SIMULASI / DATA CONTOH');
        const hasBerasMT2 = bodyText.includes('Beras (MT-2)');
        const hasWaButton = (await page.locator('a:has-text("Teruskan Panduan ke Gapoktan")').count()) === 1;

        console.log(`[CHECK] Simulation label present: ${hasSimulasiBadge ? 'PASS' : 'FAIL'}`);
        console.log(`[CHECK] Beras (MT-2) present: ${hasBerasMT2 ? 'PASS' : 'FAIL'}`);
        console.log(`[CHECK] WhatsApp button present: ${hasWaButton ? 'PASS' : 'FAIL'}`);

        evidence.jadwalTanam.checks = {
          hasSimulasiBadge,
          hasBerasMT2,
          hasWaButton,
        };

        // Axe accessibility scan
        console.log('[INFO] Running axe-core scan on /jadwal-tanam...');
        const axeResults = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']).analyze();
        const colorContrastViolations = axeResults.violations.filter((v) => v.id === 'color-contrast');
        const criticalViolations = axeResults.violations.filter((v) => v.impact === 'critical' || v.impact === 'serious');
        console.log(`[INFO] Axe Violations on /jadwal-tanam: ${axeResults.violations.length} (Contrast: ${colorContrastViolations.length}, Critical/Serious: ${criticalViolations.length})`);
        evidence.jadwalTanam.axe = {
          totalViolations: axeResults.violations.length,
          colorContrastViolations: colorContrastViolations.length,
          criticalViolations: criticalViolations.length,
        };

        const banned = await checkBannedWords(page, '/jadwal-tanam');
        console.log(`[CHECK] Banned words on /jadwal-tanam: ${banned.clean ? 'CLEAN (0 found)' : 'FOUND: ' + banned.found.join(', ')}`);
        evidence.bannedWordsSummary['/jadwal-tanam'] = banned;
      }

      await context.close();
    }

    evidence.status = 'COMPLETED';
    fs.writeFileSync(path.join(OUTPUT_DIR, 'step15-evidence.json'), JSON.stringify(evidence, null, 2));
    console.log(`\n[SUCCESS] Step 15 evidence saved to: ${path.join(OUTPUT_DIR, 'step15-evidence.json')}`);

  } catch (err) {
    console.error('[ERROR]', err);
    evidence.status = 'FAILED';
    evidence.error = err.message;
    fs.writeFileSync(path.join(OUTPUT_DIR, 'step15-evidence.json'), JSON.stringify(evidence, null, 2));
    process.exitCode = 1;
  } finally {
    await browser.close();
    if (serverProcess) {
      serverProcess.kill();
    }
  }
}

runVerification();
