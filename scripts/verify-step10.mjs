import { chromium } from 'playwright';
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
    await new Promise(r => setTimeout(r, 200));
    if (await isServerRunning()) {
      console.log(`[INFO] Ephemeral server ready at ${BASE_URL}`);
      return;
    }
  }
  throw new Error(`Failed to start server at ${BASE_URL}`);
}

async function runVerification() {
  await startServerIfNeeded();

  console.log('[INFO] Launching Chromium via Playwright...');
  const browser = await chromium.launch({
    executablePath: process.env.CHROMIUM_PATH || '/usr/bin/chromium',
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  });

  try {
    const viewports = [
      { width: 1440, height: 900 },
      { width: 390, height: 844 },
    ];

    let desktopData = null;

    for (const vp of viewports) {
      console.log(`[INFO] Capturing viewport ${vp.width}x${vp.height}...`);
      const context = await browser.newContext({ viewport: vp, deviceScaleFactor: 1 });
      const page = await context.newPage();

      await page.goto(`${BASE_URL}/peta`, { waitUntil: 'networkidle' });

      // Wait for Leaflet map canvas and polygons
      try {
        await page.locator('#peta-map .leaflet-pane').first().waitFor({ timeout: 10000 });
        await page.waitForTimeout(2500);
      } catch (e) {
        console.warn(`[WARN] Leaflet wait warning: ${e.message}`);
      }

      const shotPath = path.join(OUTPUT_DIR, `step10-${vp.width}.png`);
      await page.screenshot({ path: shotPath, fullPage: true });
      console.log(`[SAVED] Screenshot: ${shotPath}`);

      if (vp.width === 1440) {
        // Evaluate all DOM, text counts, map containment, and first screen visibility
        desktopData = await page.evaluate(() => {
          function countTextNodes(container) {
            if (!container) return { count: 0, sample: [] };
            const walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT, {
              acceptNode(node) {
                const txt = node.textContent.trim();
                if (!txt) return NodeFilter.FILTER_REJECT;
                const parent = node.parentElement;
                if (!parent) return NodeFilter.FILTER_REJECT;
                const s = window.getComputedStyle(parent);
                if (s.display === 'none' || s.visibility === 'hidden' || parent.offsetParent === null) {
                  return NodeFilter.FILTER_REJECT;
                }
                return NodeFilter.FILTER_ACCEPT;
              }
            });
            let count = 0;
            const texts = [];
            while (walker.nextNode()) {
              count++;
              texts.push(walker.currentNode.textContent.trim().slice(0, 50));
            }
            return { count, sample: texts };
          }

          // Header area
          const headerEl = document.querySelector('h1')?.closest('.flex.flex-col.lg\\:flex-row') || document.querySelector('h1')?.parentElement?.parentElement;
          const filterEl = document.getElementById('filter-container');
          const bannerEl = document.getElementById('banner-nasional');
          const mapCardEl = document.getElementById('map-card');
          const sidePanelEl = document.getElementById('side-panel-card');
          const kpiEl = document.querySelector('#kpi-total-supply')?.closest('.bg-white.rounded-2xl');
          const footerEl = document.querySelector('footer');

          // Titles & Subtitles
          const h1El = document.querySelector('h1');
          const titleText = h1El ? h1El.textContent.trim() : '';
          const subtitleEl = h1El ? h1El.closest('.min-w-0')?.querySelector('p') : null;
          const subtitleText = subtitleEl ? subtitleEl.textContent.trim() : '';

          // First screen check (within 900px viewport without scrolling)
          const vh = 900;
          function checkInFirstScreen(el) {
            if (!el) return false;
            const r = el.getBoundingClientRect();
            return r.top < vh && r.bottom > 0;
          }

          // Map containment & polygons
          const mapEl = document.getElementById('peta-map');
          const mapCardRect = mapCardEl ? mapCardEl.getBoundingClientRect() : null;
          const mapRect = mapEl ? mapEl.getBoundingClientRect() : null;
          const polygons = Array.from(document.querySelectorAll('#peta-map svg path.leaflet-interactive'));
          const fills = polygons.map(p => p.getAttribute('fill')).filter(Boolean);

          let outsideCount = 0;
          if (mapCardRect) {
            for (const poly of polygons) {
              const r = poly.getBoundingClientRect();
              if (r.width > 0 && r.height > 0) {
                // Polygon must be inside map card bounds (with 5px tolerance)
                if (r.top < mapCardRect.top - 5 || r.bottom > mapCardRect.bottom + 5 ||
                    r.left < mapCardRect.left - 5 || r.right > mapCardRect.right + 5) {
                  outsideCount++;
                }
              }
            }
          }

          const coloredPolygons = fills.filter(f => {
            const hex = f.toLowerCase();
            return hex !== '#64748b' && hex !== 'none' && hex !== '';
          });

          return {
            titleText,
            titleLength: titleText.length,
            subtitleText,
            subtitleLength: subtitleText.length,
            counts: {
              header: countTextNodes(headerEl),
              filter: countTextNodes(filterEl),
              banner: countTextNodes(bannerEl),
              mapCard: countTextNodes(mapCardEl),
              sidePanel: countTextNodes(sidePanelEl),
              kpi: countTextNodes(kpiEl),
              footer: countTextNodes(footerEl),
            },
            firstScreen: {
              header: checkInFirstScreen(headerEl),
              filter: checkInFirstScreen(filterEl),
              banner: checkInFirstScreen(bannerEl),
              mapCard: checkInFirstScreen(mapCardEl),
              sidePanel: checkInFirstScreen(sidePanelEl),
              mapCardTop: mapCardRect ? mapCardRect.top : null,
              mapCardBottom: mapCardRect ? mapCardRect.bottom : null,
              visibleHeight: mapCardRect ? Math.max(0, Math.min(vh, mapCardRect.bottom) - Math.max(0, mapCardRect.top)) : 0,
            },
            mapVerification: {
              totalInteractivePolygons: polygons.length,
              coloredPilotPolygons: coloredPolygons.length,
              polygonsOutsideMapCard: outsideCount,
            }
          };
        });

        // Save rendered HTML
        const html = await page.content();
        fs.writeFileSync(path.join(OUTPUT_DIR, 'peta-rendered-step10.html'), html, 'utf-8');
      }

      await context.close();
    }

    console.log('\n======================================================');
    console.log('STEP 10 INFORMATION REDUCTION - BROWSER EVIDENCE');
    console.log('======================================================');
    console.log(`Title: "${desktopData.titleText}" (${desktopData.titleLength} chars, <= 60 chars: ${desktopData.titleLength <= 60 ? 'PASS' : 'FAIL'})`);
    console.log(`Subtitle: "${desktopData.subtitleText}" (${desktopData.subtitleLength} chars, <= 100 chars: ${desktopData.subtitleLength <= 100 ? 'PASS' : 'FAIL'})`);
    console.log('\n[FIRST SCREEN VISIBILITY AT 1440x900 (NO SCROLL)]');
    console.log(`Header in first screen: ${desktopData.firstScreen.header ? 'PASS' : 'FAIL'}`);
    console.log(`Filter in first screen: ${desktopData.firstScreen.filter ? 'PASS' : 'FAIL'}`);
    console.log(`Banner in first screen: ${desktopData.firstScreen.banner ? 'PASS' : 'FAIL'}`);
    console.log(`Map Card in first screen: ${desktopData.firstScreen.mapCard ? 'PASS' : 'FAIL'} (top: ${desktopData.firstScreen.mapCardTop}px, visible height: ${desktopData.firstScreen.visibleHeight}px)`);
    console.log(`Side Panel in first screen: ${desktopData.firstScreen.sidePanel ? 'PASS' : 'FAIL'}`);

    console.log('\n[MAP CONTAINMENT RULE (HARD RULE 2)]');
    console.log(`Total interactive polygons: ${desktopData.mapVerification.totalInteractivePolygons} (expect > 400: ${desktopData.mapVerification.totalInteractivePolygons > 400 ? 'PASS' : 'FAIL'})`);
    console.log(`Colored pilot polygons: ${desktopData.mapVerification.coloredPilotPolygons} (expect >= 34: ${desktopData.mapVerification.coloredPilotPolygons >= 34 ? 'PASS' : 'FAIL'})`);
    console.log(`Polygons outside map card: ${desktopData.mapVerification.polygonsOutsideMapCard} (expect 0: ${desktopData.mapVerification.polygonsOutsideMapCard === 0 ? 'PASS' : 'FAIL'})`);

    console.log('\n[VISIBLE TEXT BLOCK COUNTS (BEFORE vs AFTER)]');
    const beforeCounts = {
      header: 5,
      filter: 8,
      banner: 3,
      mapCard: 19,
      sidePanel: 32,
      kpi: 34,
      footer: 4,
    };

    console.log('| Area | Before | After | Reduksi | Status |');
    console.log('| :--- | :---: | :---: | :---: | :---: |');
    for (const [k, v] of Object.entries(beforeCounts)) {
      const after = desktopData.counts[k]?.count ?? 0;
      const diff = v - after;
      const pct = ((diff / v) * 100).toFixed(0);
      console.log(`| ${k} | ${v} | ${after} | -${diff} (-${pct}%) | ${after <= v ? 'REDUCED' : 'INCREASED'} |`);
    }

    fs.writeFileSync(path.join(OUTPUT_DIR, 'step10-evidence.json'), JSON.stringify(desktopData, null, 2), 'utf-8');
    console.log('\n[SAVED] Full evidence JSON saved to docs/ui-review/step10-evidence.json');

  } finally {
    await browser.close();
    if (serverProcess) serverProcess.kill();
  }
}

runVerification().catch(err => {
  console.error('[ERROR]', err);
  if (serverProcess) serverProcess.kill();
  process.exit(1);
});
