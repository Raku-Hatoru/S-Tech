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
      // If element is a checkbox/radio within a label, the target area is the label
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
    step: 14,
    timestamp: new Date().toISOString(),
    login: {},
    pengirimanIndex: {},
    pengirimanCreate: {},
    pengirimanShow: {},
    bannedWordsSummary: {},
    status: 'IN_PROGRESS',
  };

  try {
    // =========================================================================
    // 1. HALAMAN LOGIN (/login)
    // =========================================================================
    console.log('\n================================================================');
    console.log('PART 1: VERIFYING /login (Tamu / Guest)');
    console.log('================================================================');

    for (const vp of [{ w: 1440, h: 900 }, { w: 390, h: 844 }]) {
      const context = await browser.newContext({ viewport: { width: vp.w, height: vp.h } });
      const page = await context.newPage();
      await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });

      const shotPath = path.join(OUTPUT_DIR, `step14-login-${vp.w}.png`);
      await page.screenshot({ path: shotPath, fullPage: true });
      console.log(`[SCREENSHOT] Saved: ${shotPath}`);

      const metrics = await measurePageMetrics(page);
      evidence.login[`viewport_${vp.w}`] = metrics;
      console.log(`[CHECK ${vp.w}] Horizontal Scroll: ${metrics.hasHorizontalScroll ? 'FAIL' : 'PASS'} (${metrics.scrollWidth}px vs ${metrics.innerWidth}px)`);
      console.log(`[CHECK ${vp.w}] Text < 12px: ${metrics.smallFontCount}`);
      console.log(`[CHECK ${vp.w}] Tap Targets < 44px: ${metrics.smallTargetCount}`);

      if (vp.w === 1440) {
        // Eye toggle interaction
        const pwdInput = page.locator('#password');
        const eyeBtn = page.locator('button[aria-label="Tampilkan atau sembunyikan kata sandi"]');
        const eyeCount = await eyeBtn.count();
        console.log(`[INFO] Eye toggle button found: ${eyeCount === 1 ? 'YES' : 'NO'}`);

        let pwdTypeInitial = await pwdInput.getAttribute('type');
        await eyeBtn.click();
        let pwdTypeAfter1 = await pwdInput.getAttribute('type');
        await eyeBtn.click();
        let pwdTypeAfter2 = await pwdInput.getAttribute('type');

        const eyeToggleWorks = pwdTypeInitial === 'password' && pwdTypeAfter1 === 'text' && pwdTypeAfter2 === 'password';
        console.log(`[CHECK] Eye toggle password visibility: ${eyeToggleWorks ? 'PASS' : 'FAIL'} (${pwdTypeInitial} -> ${pwdTypeAfter1} -> ${pwdTypeAfter2})`);
        evidence.login.eyeToggleWorks = eyeToggleWorks;

        // Axe accessibility scan
        console.log('[INFO] Running axe-core scan on /login...');
        const axeResults = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']).analyze();
        const colorContrastViolations = axeResults.violations.filter((v) => v.id === 'color-contrast');
        const criticalViolations = axeResults.violations.filter((v) => v.impact === 'critical' || v.impact === 'serious');
        console.log(`[INFO] Axe Violations on /login: ${axeResults.violations.length} (Contrast: ${colorContrastViolations.length}, Critical/Serious: ${criticalViolations.length})`);
        evidence.login.axe = {
          totalViolations: axeResults.violations.length,
          colorContrastViolations: colorContrastViolations.length,
          criticalViolations: criticalViolations.length,
        };

        const banned = await checkBannedWords(page, '/login');
        console.log(`[CHECK] Banned words on /login: ${banned.clean ? 'CLEAN (0 found)' : 'FOUND: ' + banned.found.join(', ')}`);
        evidence.bannedWordsSummary['/login'] = banned;
      }

      await context.close();
    }

    // =========================================================================
    // 2. AUTHENTICATION AS DISTRIBUTOR
    // =========================================================================
    console.log('\n================================================================');
    console.log('PART 2: AUTHENTICATING AS DISTRIBUTOR');
    console.log('================================================================');

    const authContext = await browser.newContext();
    const authPage = await authContext.newPage();
    await authPage.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });

    await authPage.locator('#email').fill('distributor1@sipangan.test');
    await authPage.locator('#password').fill('Distrib1234');
    await Promise.all([
      authPage.waitForNavigation({ waitUntil: 'networkidle' }),
      authPage.locator('button[type="submit"]').click(),
    ]);

    const postLoginUrl = authPage.url();
    console.log(`[INFO] Logged in successfully. Current URL: ${postLoginUrl}`);

    // Save cookies for reuse
    const cookies = await authContext.cookies();
    await authContext.close();

    // =========================================================================
    // 3. DAFTAR PENGIRIMAN (/pengiriman)
    // =========================================================================
    console.log('\n================================================================');
    console.log('PART 3: VERIFYING /pengiriman (Portal Distributor)');
    console.log('================================================================');

    for (const vp of [{ w: 1440, h: 900 }, { w: 390, h: 844 }]) {
      const context = await browser.newContext({ viewport: { width: vp.w, height: vp.h } });
      await context.addCookies(cookies);
      const page = await context.newPage();
      await page.goto(`${BASE_URL}/pengiriman`, { waitUntil: 'networkidle' });

      const shotPath = path.join(OUTPUT_DIR, `step14-index-${vp.w}.png`);
      await page.screenshot({ path: shotPath, fullPage: true });
      console.log(`[SCREENSHOT] Saved: ${shotPath}`);

      const metrics = await measurePageMetrics(page);
      evidence.pengirimanIndex[`viewport_${vp.w}`] = metrics;
      console.log(`[CHECK ${vp.w}] Horizontal Scroll: ${metrics.hasHorizontalScroll ? 'FAIL' : 'PASS'} (${metrics.scrollWidth}px vs ${metrics.innerWidth}px)`);
      console.log(`[CHECK ${vp.w}] Text < 12px: ${metrics.smallFontCount}`);
      console.log(`[CHECK ${vp.w}] Tap Targets < 44px: ${metrics.smallTargetCount}`);

      if (vp.w === 1440) {
        // Axe scan
        console.log('[INFO] Running axe-core scan on /pengiriman...');
        const axeResults = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']).analyze();
        const colorContrastViolations = axeResults.violations.filter((v) => v.id === 'color-contrast');
        const criticalViolations = axeResults.violations.filter((v) => v.impact === 'critical' || v.impact === 'serious');
        console.log(`[INFO] Axe Violations on /pengiriman: ${axeResults.violations.length} (Contrast: ${colorContrastViolations.length}, Critical/Serious: ${criticalViolations.length})`);
        evidence.pengirimanIndex.axe = {
          totalViolations: axeResults.violations.length,
          colorContrastViolations: colorContrastViolations.length,
          criticalViolations: criticalViolations.length,
        };

        const banned = await checkBannedWords(page, '/pengiriman');
        console.log(`[CHECK] Banned words on /pengiriman: ${banned.clean ? 'CLEAN (0 found)' : 'FOUND: ' + banned.found.join(', ')}`);
        evidence.bannedWordsSummary['/pengiriman'] = banned;
      }

      await context.close();
    }

    // =========================================================================
    // 4. FORM BUAT PENGIRIMAN (/pengiriman/buat)
    // =========================================================================
    console.log('\n================================================================');
    console.log('PART 4: VERIFYING /pengiriman/buat (Form Dispatch Logistik)');
    console.log('================================================================');

    for (const vp of [{ w: 1440, h: 900 }, { w: 390, h: 844 }]) {
      const context = await browser.newContext({ viewport: { width: vp.w, height: vp.h } });
      await context.addCookies(cookies);
      const page = await context.newPage();
      await page.goto(`${BASE_URL}/pengiriman/buat`, { waitUntil: 'networkidle' });

      // Live conversion kg -> ton test
      const inputKg = page.locator('#jumlah_kg');
      await inputKg.fill('5000');
      await inputKg.dispatchEvent('input');
      await page.waitForTimeout(300);

      const previewBadgeText = await page.locator('#badge-ton-preview').innerText();
      console.log(`[CHECK] Live conversion 5000 kg -> ton preview badge: "${previewBadgeText}"`);

      const shotPath = path.join(OUTPUT_DIR, `step14-create-${vp.w}.png`);
      await page.screenshot({ path: shotPath, fullPage: true });
      console.log(`[SCREENSHOT] Saved: ${shotPath}`);

      const metrics = await measurePageMetrics(page);
      evidence.pengirimanCreate[`viewport_${vp.w}`] = metrics;
      console.log(`[CHECK ${vp.w}] Horizontal Scroll: ${metrics.hasHorizontalScroll ? 'FAIL' : 'PASS'} (${metrics.scrollWidth}px vs ${metrics.innerWidth}px)`);
      console.log(`[CHECK ${vp.w}] Text < 12px: ${metrics.smallFontCount}`);
      console.log(`[CHECK ${vp.w}] Tap Targets < 44px: ${metrics.smallTargetCount}`);

      if (vp.w === 1440) {
        // Warning banner check
        const warningBoxText = await page.locator('#warning-icon-box').innerText();
        const headingWarning = await page.locator('#heading-warning').innerText();
        const hasDisclaimer = (await page.locator('#container-early-warning').innerText()).includes('Perhatian:');
        const hasCheckbox = (await page.locator('#konfirmasi_peringatan').count()) === 1;
        const hasReroute = (await page.locator('#btn-reroute').count()) === 1;

        console.log(`[CHECK] Warning Icon Symbol: "${warningBoxText.trim()}"`);
        console.log(`[CHECK] Warning Heading: "${headingWarning.trim()}"`);
        console.log(`[CHECK] Disclaimer rendered: ${hasDisclaimer ? 'PASS' : 'FAIL'}`);
        console.log(`[CHECK] Checkbox confirmation: ${hasCheckbox ? 'PASS' : 'FAIL'}`);
        console.log(`[CHECK] Reroute button: ${hasReroute ? 'PASS' : 'FAIL'}`);

        evidence.pengirimanCreate.warningBanner = {
          symbol: warningBoxText.trim(),
          heading: headingWarning.trim(),
          hasDisclaimer,
          hasCheckbox,
          hasReroute,
        };

        // Axe scan
        console.log('[INFO] Running axe-core scan on /pengiriman/buat...');
        const axeResults = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']).analyze();
        const colorContrastViolations = axeResults.violations.filter((v) => v.id === 'color-contrast');
        const criticalViolations = axeResults.violations.filter((v) => v.impact === 'critical' || v.impact === 'serious');
        console.log(`[INFO] Axe Violations on /pengiriman/buat: ${axeResults.violations.length} (Contrast: ${colorContrastViolations.length}, Critical/Serious: ${criticalViolations.length})`);
        evidence.pengirimanCreate.axe = {
          totalViolations: axeResults.violations.length,
          colorContrastViolations: colorContrastViolations.length,
          criticalViolations: criticalViolations.length,
        };

        const banned = await checkBannedWords(page, '/pengiriman/buat');
        console.log(`[CHECK] Banned words on /pengiriman/buat: ${banned.clean ? 'CLEAN (0 found)' : 'FOUND: ' + banned.found.join(', ')}`);
        evidence.bannedWordsSummary['/pengiriman/buat'] = banned;
      }

      await context.close();
    }

    // =========================================================================
    // 5. DETAIL & LACAK RESI (/pengiriman/1)
    // =========================================================================
    console.log('\n================================================================');
    console.log('PART 5: VERIFYING /pengiriman/1 (Detail Waybill Manifest)');
    console.log('================================================================');

    for (const vp of [{ w: 1440, h: 900 }, { w: 390, h: 844 }]) {
      const context = await browser.newContext({ viewport: { width: vp.w, height: vp.h } });
      await context.addCookies(cookies);
      const page = await context.newPage();
      await page.goto(`${BASE_URL}/pengiriman/1`, { waitUntil: 'networkidle' });

      const shotPath = path.join(OUTPUT_DIR, `step14-show-${vp.w}.png`);
      await page.screenshot({ path: shotPath, fullPage: true });
      console.log(`[SCREENSHOT] Saved: ${shotPath}`);

      const metrics = await measurePageMetrics(page);
      evidence.pengirimanShow[`viewport_${vp.w}`] = metrics;
      console.log(`[CHECK ${vp.w}] Horizontal Scroll: ${metrics.hasHorizontalScroll ? 'FAIL' : 'PASS'} (${metrics.scrollWidth}px vs ${metrics.innerWidth}px)`);
      console.log(`[CHECK ${vp.w}] Text < 12px: ${metrics.smallFontCount}`);
      console.log(`[CHECK ${vp.w}] Tap Targets < 44px: ${metrics.smallTargetCount}`);

      if (vp.w === 1440) {
        // Copy resi button check
        const copyBtn = page.locator('button[aria-label="Salin nomor resi waybill"]');
        const copyBtnCount = await copyBtn.count();
        const copyBtnType = await copyBtn.getAttribute('type');
        console.log(`[CHECK] Copy resi button found: ${copyBtnCount === 1 ? 'PASS' : 'FAIL'}, type="${copyBtnType}"`);

        // Check milestones
        const milestonesCount = await page.locator('.flex.items-center.justify-between.relative .w-8.h-8').count();
        console.log(`[CHECK] Milestones rendered count: ${milestonesCount}`);

        // Check timeline items
        const timelineCount = await page.locator('.relative.pl-6 > div.relative').count();
        console.log(`[CHECK] Timeline checkpoints count: ${timelineCount}`);

        evidence.pengirimanShow.details = {
          copyBtnPresent: copyBtnCount === 1,
          copyBtnType,
          milestonesCount,
          timelineCount,
        };

        // Axe scan
        console.log('[INFO] Running axe-core scan on /pengiriman/1...');
        const axeResults = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']).analyze();
        const colorContrastViolations = axeResults.violations.filter((v) => v.id === 'color-contrast');
        const criticalViolations = axeResults.violations.filter((v) => v.impact === 'critical' || v.impact === 'serious');
        console.log(`[INFO] Axe Violations on /pengiriman/1: ${axeResults.violations.length} (Contrast: ${colorContrastViolations.length}, Critical/Serious: ${criticalViolations.length})`);
        evidence.pengirimanShow.axe = {
          totalViolations: axeResults.violations.length,
          colorContrastViolations: colorContrastViolations.length,
          criticalViolations: criticalViolations.length,
        };

        const banned = await checkBannedWords(page, '/pengiriman/1');
        console.log(`[CHECK] Banned words on /pengiriman/1: ${banned.clean ? 'CLEAN (0 found)' : 'FOUND: ' + banned.found.join(', ')}`);
        evidence.bannedWordsSummary['/pengiriman/1'] = banned;
      }

      await context.close();
    }

    evidence.status = 'COMPLETED';
    fs.writeFileSync(path.join(OUTPUT_DIR, 'step14-evidence.json'), JSON.stringify(evidence, null, 2));
    console.log(`\n[SUCCESS] Step 14 evidence saved to: ${path.join(OUTPUT_DIR, 'step14-evidence.json')}`);

  } catch (err) {
    console.error('[ERROR]', err);
    evidence.status = 'FAILED';
    evidence.error = err.message;
    fs.writeFileSync(path.join(OUTPUT_DIR, 'step14-evidence.json'), JSON.stringify(evidence, null, 2));
    process.exitCode = 1;
  } finally {
    await browser.close();
    if (serverProcess) {
      serverProcess.kill();
    }
  }
}

runVerification();
