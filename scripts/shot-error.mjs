import { chromium } from 'playwright';
import { spawn } from 'child_process';
import fs from 'fs';
import path from 'path';

const PORT = process.env.PORT || '8099';
const BASE_URL = `http://127.0.0.1:${PORT}`;
const OUTPUT_DIR = path.resolve('docs/ui-review');

let serverProcess = null;

async function isServerRunning() {
  try {
    const res = await fetch(`${BASE_URL}/peta`, { method: 'HEAD' });
    return res.status === 200 || res.status === 302;
  } catch (err) {
    return false;
  }
}

async function startServerIfNeeded() {
  const running = await isServerRunning();
  if (running) {
    console.log(`[INFO] Server already running at ${BASE_URL}`);
    return;
  }

  console.log(`[INFO] Starting ephemeral PHP server on port ${PORT}...`);
  serverProcess = spawn('php', ['-S', `127.0.0.1:${PORT}`, '-t', 'public'], {
    stdio: 'ignore',
    detached: false,
  });

  for (let i = 0; i < 30; i++) {
    await new Promise((r) => setTimeout(r, 200));
    if (await isServerRunning()) {
      console.log(`[INFO] Ephemeral server ready at ${BASE_URL}`);
      return;
    }
  }
  throw new Error(`Failed to start server at ${BASE_URL}`);
}

async function run() {
  await startServerIfNeeded();

  console.log('[INFO] Launching Chromium to test API Error State...');
  const browser = await chromium.launch({
    executablePath: process.env.CHROMIUM_PATH || '/usr/bin/chromium',
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  });

  try {
    const context = await browser.newContext({
      viewport: { width: 1440, height: 900 },
      deviceScaleFactor: 1,
    });
    const page = await context.newPage();

    page.on('console', (msg) => console.log('[BROWSER CONSOLE]', msg.type(), msg.text()));
    page.on('pageerror', (err) => console.error('[BROWSER PAGEERROR]', err));
    page.on('request', (req) => console.log('[PAGE REQ]', req.url()));

    // Abort API requests to /api/peta/status or /api/status to trigger error state
    await page.route(/\/api\/status/, (route) => {
      console.log(`[INTERCEPT] Aborting request to ${route.request().url()}`);
      route.abort('failed');
    });

    console.log(`[INFO] Navigating to ${BASE_URL}/peta with API blocked...`);
    await page.goto(`${BASE_URL}/peta`, { waitUntil: 'domcontentloaded' });

    // Wait for error overlay to be visible
    const errorEl = page.locator('#peta-error-state');
    await errorEl.waitFor({ state: 'visible', timeout: 8000 });

    const isVisible = await errorEl.isVisible();
    const errorMsg = await page.locator('#peta-error-msg').innerText();
    const errorTitle = await page.locator('#peta-error-state h4').innerText();

    console.log(`[VERIFIED] Error overlay visible: ${isVisible}`);
    console.log(`[VERIFIED] Error title: "${errorTitle}"`);
    console.log(`[VERIFIED] Error message: "${errorMsg}"`);

    // Wait a brief moment for UI animation
    await page.waitForTimeout(1000);

    const errorScreenshotPath = path.join(OUTPUT_DIR, 'step7-error.png');
    await page.screenshot({ path: errorScreenshotPath, fullPage: true });
    console.log(`[SAVED] Error state screenshot saved to ${errorScreenshotPath}`);

    await context.close();
  } finally {
    await browser.close();
    if (serverProcess) {
      serverProcess.kill();
      console.log('[INFO] Ephemeral server terminated.');
    }
  }
}

run().catch((err) => {
  console.error('[ERROR]', err);
  if (serverProcess) serverProcess.kill();
  process.exit(1);
});
