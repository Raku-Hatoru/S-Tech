import { chromium } from 'playwright';
import { spawn } from 'child_process';
import fs from 'fs';
import path from 'path';

const PORT = process.env.PORT || '8099';
const BASE_URL = `http://127.0.0.1:${PORT}`;
const prefix = process.argv[2] || 'step1';

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

  for (let i = 0; i < 30; i++) {
    await new Promise((r) => setTimeout(r, 200));
    if (await isServerRunning()) {
      console.log(`[INFO] Ephemeral server ready at ${BASE_URL}`);
      return;
    }
  }
  throw new Error(`Failed to start server at ${BASE_URL}`);
}

function lum(hex) {
  const rgb = hex.replace('#', '').match(/.{2}/g).map((x) => parseInt(x, 16) / 255);
  const a = rgb.map((v) => (v <= 0.04045 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4)));
  return 0.2126 * a[0] + 0.7152 * a[1] + 0.0722 * a[2];
}

function contrastRatio(hex1, hex2) {
  const l1 = lum(hex1);
  const l2 = lum(hex2);
  return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
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

      const screenshotPath = path.join(OUTPUT_DIR, `${prefix}-${vp.width}.png`);
      await page.screenshot({ path: screenshotPath, fullPage: true });
      console.log(`[SAVED] Screenshot saved to ${screenshotPath}`);

      if (vp.width === 1440) {
        // Measure real computed styles from the browser DOM
        const measurements = await page.evaluate(() => {
          const docRoot = document.documentElement;
          const rootStyles = getComputedStyle(docRoot);
          const mapEl = document.getElementById('peta-map');
          const mapStyles = mapEl ? getComputedStyle(mapEl) : null;
          const panelStatusBox = document.getElementById('panel-status-box');
          const panelStatusLabel = document.getElementById('panel-status-label');
          const panelIconBox = document.getElementById('panel-status-icon-box');
          const mapLegend = document.getElementById('map-legend');

          // Check if any polygon has fill of surplus
          const polygons = Array.from(document.querySelectorAll('#peta-map svg path.leaflet-interactive'));
          const fills = polygons.map((p) => p.getAttribute('fill')).filter(Boolean);

          return {
            cssVars: {
              statusDefisit: rootStyles.getPropertyValue('--status-defisit').trim(),
              statusSeimbang: rootStyles.getPropertyValue('--status-seimbang').trim(),
              statusWaspada: rootStyles.getPropertyValue('--status-waspada').trim(),
              statusPenuh: rootStyles.getPropertyValue('--status-penuh').trim(),
              statusTanpaData: rootStyles.getPropertyValue('--status-tanpa-data').trim(),
            },
            mapBg: mapStyles ? mapStyles.backgroundColor : null,
            mapHeight: mapStyles ? mapStyles.height : null,
            legendPresent: !!mapLegend,
            legendHtml: mapLegend ? mapLegend.innerText : null,
            panelStatusText: panelStatusLabel ? panelStatusLabel.innerText : null,
            panelIconText: panelIconBox ? panelIconBox.innerText : null,
            panelBoxBg: panelStatusBox ? getComputedStyle(panelStatusBox).backgroundColor : null,
            sampleFills: fills.slice(0, 10),
            hasPenuhFill: fills.some((f) => f.toLowerCase() === '#8b5cf6'),
            hasOldRedPenuhFill: fills.some((f) => f.toLowerCase() === '#991b1b'),
          };
        });

        console.log('\n[BROWSER COMPUTED MEASUREMENTS]');
        console.log('CSS Custom Properties:', JSON.stringify(measurements.cssVars, null, 2));
        console.log('Map Canvas Background:', measurements.mapBg);
        console.log('Map Canvas Height (1440px):', measurements.mapHeight);
        console.log('Docked Legend Present:', measurements.legendPresent);
        console.log('Docked Legend Text Preview:', measurements.legendHtml.replace(/\n+/g, ' | '));
        console.log('Side Panel Status Text:', measurements.panelStatusText);
        console.log('Side Panel Icon Text:', measurements.panelIconText);
        console.log('Side Panel Box Bg:', measurements.panelBoxBg);
        console.log('Sample Polygon Fills:', measurements.sampleFills);
        console.log('Polygon has #8b5cf6 (Penuh):', measurements.hasPenuhFill);
        console.log('Polygon has #991b1b (Old dark red):', measurements.hasOldRedPenuhFill);

        if (measurements.cssVars.statusPenuh) {
          const bgMapHex = '#080f1d';
          const cr = contrastRatio(bgMapHex, measurements.cssVars.statusPenuh);
          console.log(`Contrast Ratio of --status-penuh (${measurements.cssVars.statusPenuh}) against Map (${bgMapHex}): ${cr.toFixed(2)}:1`);
        }

        const renderedHtml = await page.content();
        const htmlPath = path.join(OUTPUT_DIR, `peta-rendered-${prefix}.html`);
        fs.writeFileSync(htmlPath, renderedHtml, 'utf-8');
        console.log(`[SAVED] Rendered HTML saved to ${htmlPath} (${renderedHtml.length} bytes)\n`);
      }

      await context.close();
    }
    console.log(`[SUCCESS] All ${prefix} screenshots and rendered data captured successfully!`);
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

