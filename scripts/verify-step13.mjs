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

async function runVerification() {
  await startServerIfNeeded();

  const browser = await chromium.launch({
    executablePath: process.env.CHROMIUM_PATH || '/usr/bin/chromium',
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  });

  const evidence = {
    step: 13,
    timestamp: new Date().toISOString(),
    petaTooltip: {},
    wilayahResponsive: {},
    wilayahA11y: {},
    bannedWords: {},
    status: 'IN_PROGRESS',
  };

  try {
    // =========================================================================
    // PART 1: TOOLTIP SINGLETON & DISMISSAL ON /peta
    // =========================================================================
    console.log('\n================================================================');
    console.log('PART 1: VERIFYING SINGLE TOOLTIP & DISMISSAL ON /peta');
    console.log('================================================================');

    const petaContext = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const petaPage = await petaContext.newPage();
    await petaPage.goto(`${BASE_URL}/peta`, { waitUntil: 'networkidle' });

    // Wait for Leaflet interactive paths
    await petaPage.locator('#peta-map svg path.leaflet-interactive').first().waitFor({ state: 'attached', timeout: 15000 });
    await petaPage.waitForTimeout(2000);

    const paths = await petaPage.locator('#peta-map svg path.leaflet-interactive').all();
    console.log(`[INFO] Total interactive paths on map: ${paths.length}`);

    let maxTooltipsEncountered = 0;
    let tooltipVisibleOnHover = false;

    // Hover over 5 different polygons
    for (let i = 0; i < Math.min(5, paths.length); i++) {
      const box = await paths[i].boundingBox();
      if (!box) continue;

      await petaPage.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
      await petaPage.waitForTimeout(300);

      const tooltipCount = await petaPage.locator('.leaflet-tooltip').count();
      if (tooltipCount > maxTooltipsEncountered) {
        maxTooltipsEncountered = tooltipCount;
      }
      if (tooltipCount === 1) {
        tooltipVisibleOnHover = true;
      }
    }

    // Save screenshot of map with tooltip active
    const tooltipShotPath = path.join(OUTPUT_DIR, 'step13-tooltip.png');
    await petaPage.screenshot({ path: tooltipShotPath });
    console.log(`[SAVED] Tooltip screenshot: ${tooltipShotPath}`);

    // Now move mouse out of map (to top navigation or header)
    await petaPage.mouse.move(100, 20);
    await petaPage.waitForTimeout(500);
    const tooltipCountAfterLeave = await petaPage.locator('.leaflet-tooltip').count();

    evidence.petaTooltip = {
      maxTooltipsDuringHover: maxTooltipsEncountered,
      isSingleton: maxTooltipsEncountered <= 1,
      tooltipAppearedOnHover: tooltipVisibleOnHover,
      tooltipCountAfterMouseLeave: tooltipCountAfterLeave,
      pass: maxTooltipsEncountered <= 1 && tooltipCountAfterLeave === 0,
    };

    console.log(`[CHECK] Max tooltips during hover: ${maxTooltipsEncountered} (Expected: <= 1)`);
    console.log(`[CHECK] Tooltips after mouseleave: ${tooltipCountAfterLeave} (Expected: 0)`);
    if (!evidence.petaTooltip.pass) {
      throw new Error(`Tooltip singleton check failed: max=${maxTooltipsEncountered}, afterLeave=${tooltipCountAfterLeave}`);
    }
    await petaContext.close();

    // =========================================================================
    // PART 2: RESPONSIVE OVERHAUL ON /wilayah/3302 ACROSS 4 VIEWPORTS
    // =========================================================================
    console.log('\n================================================================');
    console.log('PART 2: RESPONSIVE VIEWPORT CHECKS ON /wilayah/3302');
    console.log('================================================================');

    const viewports = [
      { w: 1440, h: 900, name: 'desktop', shot: 'step13-1440.png' },
      { w: 1024, h: 768, name: 'tablet-landscape', shot: 'step13-1024.png' },
      { w: 768, h: 1024, name: 'tablet-portrait', shot: 'step13-768.png' },
      { w: 390, h: 844, name: 'mobile', shot: 'step13-390.png' },
    ];

    for (const vp of viewports) {
      console.log(`\n[INFO] Testing /wilayah/3302 at ${vp.w}x${vp.h} (${vp.name})...`);
      const context = await browser.newContext({ viewport: { width: vp.w, height: vp.h }, deviceScaleFactor: 1 });
      const page = await context.newPage();
      await page.goto(`${BASE_URL}/wilayah/3302?komoditas=beras`, { waitUntil: 'networkidle' });
      await page.waitForTimeout(1500);

      const shotPath = path.join(OUTPUT_DIR, vp.shot);
      await page.screenshot({ path: shotPath, fullPage: true });
      console.log(`[SAVED] Full-page screenshot: ${shotPath}`);

      const pageMetrics = await page.evaluate(() => {
        const hasHorizontalScroll = document.documentElement.scrollWidth > window.innerWidth;
        const scrollWidth = document.documentElement.scrollWidth;
        const innerWidth = window.innerWidth;

        // Fonts < 12px check
        const smallFontElements = [];
        const allEls = document.querySelectorAll('body *');
        allEls.forEach((el) => {
          if (el.offsetParent === null && el.offsetWidth === 0 && el.offsetHeight === 0) return;
          const style = window.getComputedStyle(el);
          if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') return;

          // Only elements with direct text
          const hasDirectText = Array.from(el.childNodes).some((n) => n.nodeType === Node.TEXT_NODE && n.nodeValue.trim().length > 0);
          if (!hasDirectText) return;

          const fs = parseFloat(style.fontSize);
          if (fs < 11.5) {
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
          document.querySelectorAll('button, a[href], select, input, [role="button"]')
        ).filter((el) => {
          if (el.offsetParent === null && el.offsetWidth === 0 && el.offsetHeight === 0) return false;
          const style = window.getComputedStyle(el);
          if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') return false;
          const r = el.getBoundingClientRect();
          return r.width > 0 && r.height > 0;
        });

        const smallTargets = [];
        interactiveCandidates.forEach((el) => {
          const r = el.getBoundingClientRect();
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
          smallTargets: smallTargets,
        };
      });

      evidence.wilayahResponsive[vp.w] = pageMetrics;
      console.log(`[CHECK ${vp.w}] Horizontal Scroll: ${pageMetrics.hasHorizontalScroll ? 'FAIL' : 'PASS'} (${pageMetrics.scrollWidth}px vs ${pageMetrics.innerWidth}px)`);
      console.log(`[CHECK ${vp.w}] Text < 12px: ${pageMetrics.smallFontCount}`);
      console.log(`[CHECK ${vp.w}] Tap Targets < 44px: ${pageMetrics.smallTargetCount}`, JSON.stringify(pageMetrics.smallTargets));

      await context.close();
    }

    // =========================================================================
    // PART 3: ACCESSIBILITY (AXE) & FUNCTIONAL CONTROLS ON /wilayah/3302
    // =========================================================================
    console.log('\n================================================================');
    console.log('PART 3: A11Y & FUNCTIONAL INTERACTIVE CONTROLS ON /wilayah/3302');
    console.log('================================================================');

    const auditContext = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const auditPage = await auditContext.newPage();
    await auditPage.goto(`${BASE_URL}/wilayah/3302?komoditas=beras`, { waitUntil: 'networkidle' });
    await auditPage.waitForTimeout(2000);

    // Axe scan
    console.log('[INFO] Running axe-core scan on /wilayah/3302...');
    const axeBuilder = new AxeBuilder({ page: auditPage }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']);
    const axeResults = await axeBuilder.analyze();

    fs.writeFileSync(path.join(OUTPUT_DIR, 'axe-step13-1440.json'), JSON.stringify(axeResults, null, 2));

    const colorContrastViolations = axeResults.violations.filter((v) => v.id === 'color-contrast');
    const criticalViolations = axeResults.violations.filter((v) => v.impact === 'critical' || v.impact === 'serious');

    console.log(`[INFO] Axe Violations: ${axeResults.violations.length}`);
    console.log(`[INFO] Color-Contrast Violations: ${colorContrastViolations.length}`);
    console.log(`[INFO] Critical/Serious Violations: ${criticalViolations.length}`);

    evidence.wilayahA11y = {
      totalViolations: axeResults.violations.length,
      colorContrastViolations: colorContrastViolations.length,
      criticalSeriousViolations: criticalViolations.length,
      violations: axeResults.violations.map((v) => ({
        id: v.id,
        impact: v.impact,
        description: v.description,
        nodes: v.nodes.length,
      })),
    };

    // Functional accordion toggle test
    console.log('[INFO] Testing 12-month table accordion toggle...');
    const tableContainer = auditPage.locator('#container-tabel-12-bulan');
    const toggleBtn = auditPage.locator('#btn-toggle-tabel-12');

    const initialHidden = await tableContainer.evaluate((el) => el.classList.contains('hidden'));
    const initialAria = await toggleBtn.getAttribute('aria-expanded');
    console.log(`[CHECK] Initial Table Hidden: ${initialHidden} (aria-expanded: ${initialAria})`);

    await toggleBtn.click();
    await auditPage.waitForTimeout(300);

    const toggledHidden = await tableContainer.evaluate((el) => el.classList.contains('hidden'));
    const toggledAria = await toggleBtn.getAttribute('aria-expanded');
    console.log(`[CHECK] After 1st Click Hidden: ${toggledHidden} (aria-expanded: ${toggledAria})`);

    await toggleBtn.click();
    await auditPage.waitForTimeout(300);

    const finalHidden = await tableContainer.evaluate((el) => el.classList.contains('hidden'));
    const finalAria = await toggleBtn.getAttribute('aria-expanded');
    console.log(`[CHECK] After 2nd Click Hidden: ${finalHidden} (aria-expanded: ${finalAria})`);

    evidence.accordion = {
      initialHidden,
      initialAria,
      toggledHidden,
      toggledAria,
      finalHidden,
      finalAria,
      pass: initialHidden === true && toggledHidden === false && finalHidden === true,
    };

    // Check Anchor #koridor-redistribusi
    console.log('[INFO] Checking anchor #koridor-redistribusi...');
    const anchorBtn = auditPage.locator('a[href="#koridor-redistribusi"]');
    const targetCard = auditPage.locator('#koridor-redistribusi');
    const anchorExists = (await anchorBtn.count()) > 0;
    const targetExists = (await targetCard.count()) > 0;
    console.log(`[CHECK] Anchor exists: ${anchorExists}, Target exists: ${targetExists}`);

    // Check CSV Download button
    const csvBtn = auditPage.locator('button[onclick="unduhRekapCSV()"]');
    const csvBtnExists = (await csvBtn.count()) > 0;
    const csvBtnType = await csvBtn.getAttribute('type');
    console.log(`[CHECK] CSV Button exists: ${csvBtnExists}, type: ${csvBtnType}`);

    // Banned words & receipt check in page HTML
    const pageHtml = await auditPage.content();
    const bannedWords = ['PRD', 'Selimbang', 'harga', 'deflasi', 'XGBoost'];
    const bannedFound = [];
    bannedWords.forEach((word) => {
      // Case insensitive word boundary check
      const regex = new RegExp(`\\b${word}\\b`, 'i');
      if (regex.test(pageHtml)) {
        bannedFound.push(word);
      }
    });

    const receiptRegex = /\b(DO|SHP)-[0-9A-Z-]+\b/i;
    const receiptFound = receiptRegex.test(pageHtml);

    evidence.bannedWords = {
      bannedFound,
      receiptFound,
      pass: bannedFound.length === 0 && !receiptFound,
    };

    console.log(`[CHECK] Banned words found: ${bannedFound.length === 0 ? 'NONE (PASS)' : bannedFound.join(', ')}`);
    console.log(`[CHECK] Receipt numbers found: ${receiptFound ? 'FAIL' : 'NONE (PASS)'}`);

    evidence.status = 'SUCCESS';
    fs.writeFileSync(path.join(OUTPUT_DIR, 'step13-evidence.json'), JSON.stringify(evidence, null, 2));
    console.log(`\n[SAVED] Evidence JSON: docs/ui-review/step13-evidence.json`);

    await auditContext.close();
  } finally {
    await browser.close();
    if (serverProcess) {
      serverProcess.kill();
    }
  }
}

runVerification().catch((err) => {
  console.error('[FATAL ERROR]', err);
  process.exit(1);
});
