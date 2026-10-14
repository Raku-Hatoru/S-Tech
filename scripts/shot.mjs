import { chromium } from 'playwright';
import { spawn } from 'child_process';
import fs from 'fs';
import path from 'path';

const PORT = process.env.PORT || '8099';
const BASE_URL = `http://127.0.0.1:${PORT}`;
const VIEWPORTS = [
  { width: 1440, height: 900 },
  { width: 1024, height: 768 },
  { width: 768, height: 1024 },
  { width: 390, height: 844 },
];

const OUTPUT_DIR = path.resolve('docs/ui-review');
if (!fs.existsSync(OUTPUT_DIR)) {
  fs.mkdirSync(OUTPUT_DIR, { recursive: true });
}

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

  // Wait for server to respond
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

  console.log('[INFO] Launching Chromium via Playwright...');
  const browser = await chromium.launch({
    executablePath: process.env.CHROMIUM_PATH || '/usr/bin/chromium',
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  });

  try {
    for (const vp of VIEWPORTS) {
      console.log(`[INFO] Capturing viewport ${vp.width}x${vp.height}...`);
      const context = await browser.newContext({
        viewport: vp,
        deviceScaleFactor: 1,
      });
      const page = await context.newPage();

      await page.goto(`${BASE_URL}/peta`, { waitUntil: 'networkidle' });

      // Wait for Leaflet map canvas and tiles/polygons to settle
      try {
        await page.locator('#peta-map .leaflet-pane').first().waitFor({ timeout: 10000 });
        await page.waitForTimeout(2000);
      } catch (e) {
        console.warn(`[WARN] Leaflet selector wait timed out: ${e.message}`);
      }

      const screenshotPath = path.join(OUTPUT_DIR, `baseline-${vp.width}.png`);
      await page.screenshot({ path: screenshotPath, fullPage: true });
      console.log(`[SAVED] Screenshot saved to ${screenshotPath}`);

      // Save rendered HTML on the desktop viewport
      if (vp.width === 1440) {
        const renderedHtml = await page.content();
        const htmlPath = path.join(OUTPUT_DIR, 'peta-rendered.html');
        fs.writeFileSync(htmlPath, renderedHtml, 'utf-8');
        console.log(`[SAVED] Rendered HTML saved to ${htmlPath} (${renderedHtml.length} bytes)`);
      }

      await context.close();
    }
    console.log('[SUCCESS] All baseline screenshots and rendered HTML captured successfully!');
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
