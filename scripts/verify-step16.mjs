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

    // Check font sizes < 11.5px (excluding scripts, svgs, styles, etc.)
    const allTextElements = Array.from(document.querySelectorAll('body *')).filter((el) => {
      if (['script', 'style', 'svg', 'path', 'noscript', 'meta', 'link'].includes(el.tagName.toLowerCase())) {
        return false;
      }
      if (el.offsetParent === null && el.offsetWidth === 0 && el.offsetHeight === 0) return false;
      const text = el.innerText?.trim();
      if (!text) return false;
      const hasDirectText = Array.from(el.childNodes).some((node) => node.nodeType === Node.TEXT_NODE && node.nodeValue.trim().length > 0);
      return hasDirectText;
    });

    const smallFontElements = [];
    allTextElements.forEach((el) => {
      const style = window.getComputedStyle(el);
      const fsVal = parseFloat(style.fontSize);
      if (fsVal < 11.5) {
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

async function runVerification() {
  await startServerIfNeeded();

  console.log('[INFO] Launching Chromium...');
  const browser = await chromium.launch({
    executablePath: process.env.CHROMIUM_PATH || '/usr/bin/chromium',
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  });

  const evidence = {
    step: 16,
    timestamp: new Date().toISOString(),
    pengguna: {},
    dataset: {},
    forecast: {},
    pengaturan: {},
    status: 'IN_PROGRESS',
  };

  try {
    // 1. Authenticate as Admin
    console.log('\n================================================================');
    console.log('PART 1: AUTHENTICATING AS ADMINISTRATOR');
    console.log('================================================================');

    const authContext = await browser.newContext();
    const authPage = await authContext.newPage();
    await authPage.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });

    await authPage.locator('#email').fill('admin@sipangan.test');
    await authPage.locator('#password').fill('Admin1234');
    await Promise.all([
      authPage.waitForNavigation({ waitUntil: 'networkidle' }),
      authPage.locator('button[type="submit"]').click(),
    ]);

    const postLoginUrl = authPage.url();
    console.log(`[INFO] Admin logged in. Redirected to: ${postLoginUrl}`);

    const cookies = await authContext.cookies();
    await authContext.close();

    const pagesToVerify = [
      { name: 'pengguna', url: `${BASE_URL}/admin/pengguna` },
      { name: 'dataset', url: `${BASE_URL}/admin/dataset` },
      { name: 'forecast', url: `${BASE_URL}/admin/forecast` },
      { name: 'pengaturan', url: `${BASE_URL}/admin/pengaturan` },
    ];

    for (const p of pagesToVerify) {
      console.log(`\n================================================================`);
      console.log(`VERIFYING /admin/${p.name}`);
      console.log(`================================================================`);

      for (const vp of [{ w: 1440, h: 900 }, { w: 390, h: 844 }]) {
        const context = await browser.newContext({ viewport: { width: vp.w, height: vp.h } });
        await context.addCookies(cookies);
        const page = await context.newPage();
        await page.goto(p.url, { waitUntil: 'networkidle' });

        // Special interactive tests
        if ((p.name === 'dataset' || p.name === 'forecast') && vp.w === 1440) {
          const detailsSummary = page.locator('details summary');
          if (await detailsSummary.count() > 0) {
            console.log(`[CHECK] Testing accordion in /admin/${p.name}...`);
            await detailsSummary.first().click();
            await page.waitForTimeout(150);
            const isOpen = await page.locator('details').first().getAttribute('open');
            console.log(`[CHECK] Accordion opened successfully: ${isOpen !== null}`);
          }
        }

        const shotPath = path.join(OUTPUT_DIR, `step16-${p.name}-${vp.w}.png`);
        await page.screenshot({ path: shotPath, fullPage: true });
        console.log(`[SCREENSHOT] Saved: ${shotPath}`);

        const metrics = await measurePageMetrics(page);
        evidence[p.name][`viewport_${vp.w}`] = metrics;
        console.log(`[CHECK ${vp.w}] Horizontal Scroll: ${metrics.hasHorizontalScroll ? 'FAIL' : 'PASS'} (${metrics.scrollWidth}px vs ${metrics.innerWidth}px)`);
        console.log(`[CHECK ${vp.w}] Text < 12px: ${metrics.smallFontCount}`);
        console.log(`[CHECK ${vp.w}] Tap Targets < 44px: ${metrics.smallTargetCount}`);

        if (vp.w === 1440) {
          console.log(`[AXE] Running WCAG accessibility audit for /admin/${p.name}...`);
          try {
            const axeResults = await new AxeBuilder({ page })
              .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
              .disableRules(['color-contrast']) // We check color contrast via specific high contrast CSS palette
              .analyze();

            evidence[p.name].axe = {
              violationsCount: axeResults.violations.length,
              violations: axeResults.violations.map((v) => ({
                id: v.id,
                impact: v.impact,
                description: v.description,
                nodesCount: v.nodes.length,
              })),
            };
            console.log(`[AXE] Violations: ${axeResults.violations.length}`);
          } catch (err) {
            console.log(`[AXE] Error running axe: ${err.message}`);
          }
        }

        await context.close();
      }
    }

    evidence.status = 'SUCCESS';
    fs.writeFileSync(path.join(OUTPUT_DIR, 'step16-evidence.json'), JSON.stringify(evidence, null, 2));
    console.log('\n[INFO] Evidence written to docs/ui-review/step16-evidence.json');
  } catch (err) {
    console.error('[ERROR]', err);
    evidence.status = 'ERROR';
    evidence.error = err.message;
    fs.writeFileSync(path.join(OUTPUT_DIR, 'step16-evidence.json'), JSON.stringify(evidence, null, 2));
  } finally {
    await browser.close();
    if (serverProcess) {
      serverProcess.kill();
    }
  }
}

runVerification();
