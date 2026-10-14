import { chromium } from 'playwright';
import { spawn } from 'child_process';
import fs from 'fs';
import path from 'path';

const PORT = '8099';
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
  console.log(`[INFO] Starting ephemeral server on port ${PORT}...`);
  serverProcess = spawn('php', ['-S', `127.0.0.1:${PORT}`, '-t', 'public'], { stdio: 'ignore' });
  for (let i = 0; i < 30; i++) {
    await new Promise(r => setTimeout(r, 200));
    if (await isServerRunning()) {
      console.log(`[INFO] Server ready at ${BASE_URL}`);
      return;
    }
  }
  throw new Error('Failed to start server');
}

const VIEWPORTS = [
  { name: 'desktop-wide', width: 1440, height: 900, file: 'step9-1440.png' },
  { name: 'tablet-landscape', width: 1024, height: 768, file: 'step9-1024.png' },
  { name: 'tablet-portrait', width: 768, height: 1024, file: 'step9-768.png' },
  { name: 'mobile', width: 390, height: 844, file: 'step9-390.png' },
];

async function runVerification() {
  await startServerIfNeeded();

  const browser = await chromium.launch({
    executablePath: '/usr/bin/chromium',
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  });

  const results = {
    viewports: {},
    stickyHeader: {},
    modalOverlay: {},
    errorState: {},
  };

  try {
    // 1. Capture All Viewports & Verify Map Containment
    for (const vp of VIEWPORTS) {
      console.log(`\n========================================`);
      console.log(`[TEST 1] Capturing Viewport ${vp.width}x${vp.height} (${vp.name})...`);
      console.log(`========================================`);

      const context = await browser.newContext({
        viewport: { width: vp.width, height: vp.height },
        deviceScaleFactor: 1,
      });
      const page = await context.newPage();

      await page.goto(`${BASE_URL}/peta`, { waitUntil: 'networkidle' });

      // Wait for Leaflet map polygons to be populated
      await page.waitForFunction(
        () => document.querySelectorAll('#peta-map svg path.leaflet-interactive').length > 400,
        { timeout: 15000 }
      );
      await page.waitForTimeout(1500);

      const shotPath = path.join(OUTPUT_DIR, vp.file);
      await page.screenshot({ path: shotPath, fullPage: true });
      console.log(`[SAVED] ${shotPath}`);

      // Map containment and polygon bounding checks
      const metrics = await page.evaluate(() => {
        const mapEl = document.getElementById('peta-map');
        const cardEl = document.getElementById('map-card');
        const mapRect = mapEl.getBoundingClientRect();
        const cardRect = cardEl.getBoundingClientRect();

        const mapStyles = window.getComputedStyle(mapEl);
        const cardStyles = window.getComputedStyle(cardEl);

        const polygons = Array.from(document.querySelectorAll('#peta-map path.leaflet-interactive'));
        let insideCount = 0;
        let outsideCount = 0;
        const coloredPilots = [];
        const outsideSample = [];

        polygons.forEach((p, idx) => {
          const r = p.getBoundingClientRect();
          const fill = p.getAttribute('fill') || '';
          if (fill && fill.toLowerCase() !== '#64748b' && fill.toLowerCase() !== '#3388ff') {
            coloredPilots.push(fill);
          }

          // Intersection test: does polygon box intersect map container?
          const intersects = !(r.right < mapRect.left || r.left > mapRect.right || r.bottom < mapRect.top || r.top > mapRect.bottom);
          if (intersects) {
            insideCount++;
          } else {
            outsideCount++;
            if (outsideSample.length < 5) {
              outsideSample.push({ idx, fill, left: r.left, right: r.right, top: r.top, bottom: r.bottom });
            }
          }
        });

        return {
          mapRect: { width: Math.round(mapRect.width), height: Math.round(mapRect.height), top: Math.round(mapRect.top), left: Math.round(mapRect.left) },
          cardRect: { width: Math.round(cardRect.width), height: Math.round(cardRect.height), top: Math.round(cardRect.top), left: Math.round(cardRect.left) },
          cardStyles: {
            position: cardStyles.position,
            overflow: cardStyles.overflow,
            isolation: cardStyles.isolation,
            zIndex: cardStyles.zIndex,
          },
          mapStyles: {
            position: mapStyles.position,
            overflow: mapStyles.overflow,
            isolation: mapStyles.isolation,
            zIndex: mapStyles.zIndex,
          },
          zoom: window._leaflet_map ? window._leaflet_map.getZoom() : null,
          totalPolygons: polygons.length,
          coloredPilotsCount: coloredPilots.length,
          insideCount,
          outsideCount,
          outsideSample,
          containmentPct: polygons.length > 0 ? (insideCount / polygons.length) * 100 : 0,
        };
      });

      console.log(`   Map Zoom: ${metrics.zoom}`);
      console.log(`   Total Polygons: ${metrics.totalPolygons} (Requirement: > 400) -> ${metrics.totalPolygons > 400 ? 'PASS' : 'FAIL'}`);
      console.log(`   Colored Pilot Polygons: ${metrics.coloredPilotsCount} (Requirement: >= 34) -> ${metrics.coloredPilotsCount >= 34 ? 'PASS' : 'FAIL'}`);
      console.log(`   Inside Map Container: ${metrics.insideCount} / ${metrics.totalPolygons} (${metrics.containmentPct.toFixed(1)}%)`);
      console.log(`   Outside Map Container: ${metrics.outsideCount} (Requirement: 0) -> ${metrics.outsideCount === 0 ? 'PASS' : 'FAIL'}`);
      if (metrics.outsideCount > 0) {
        console.log(`   Outside sample:`, metrics.outsideSample);
      }
      console.log(`   #map-card isolation: ${metrics.cardStyles.isolation} | overflow: ${metrics.cardStyles.overflow} | zIndex: ${metrics.cardStyles.zIndex}`);
      console.log(`   #peta-map isolation: ${metrics.mapStyles.isolation} | overflow: ${metrics.mapStyles.overflow} | zIndex: ${metrics.mapStyles.zIndex}`);

      results.viewports[vp.name] = metrics;

      if (vp.width === 1440) {
        // Save rendered HTML
        const html = await page.content();
        const htmlFile = path.join(OUTPUT_DIR, 'peta-rendered-step9.html');
        fs.writeFileSync(htmlFile, html, 'utf-8');
        console.log(`[SAVED] Rendered HTML saved to ${htmlFile} (${html.length} bytes)`);

        // 2. Sticky Header elementFromPoint Test
        console.log(`\n========================================`);
        console.log(`[TEST 2] Sticky Header Stacking Test...`);
        console.log(`========================================`);

        // Scroll page down so map moves under sticky header
        await page.evaluate(() => window.scrollTo(0, 450));
        await page.waitForTimeout(500);

        const headerHit = await page.evaluate(() => {
          const header = document.querySelector('header');
          const headerBox = header.getBoundingClientRect();
          const testX = Math.round(headerBox.left + headerBox.width / 2);
          const testY = Math.round(headerBox.top + headerBox.height / 2);

          const hitEl = document.elementFromPoint(testX, testY);
          const isHeaderOrChild = header === hitEl || header.contains(hitEl);
          const hitTagName = hitEl ? hitEl.tagName : null;
          const hitId = hitEl ? hitEl.id : null;
          const hitClass = hitEl ? String(hitEl.className).slice(0, 50) : null;

          return {
            testX,
            testY,
            isHeaderOrChild,
            hitTagName,
            hitId,
            hitClass,
          };
        });

        console.log(`   Sticky header hit test at (${headerHit.testX}, ${headerHit.testY}):`);
        console.log(`   Hit element: <${headerHit.hitTagName}> id="${headerHit.hitId}" class="${headerHit.hitClass}"`);
        console.log(`   Paints ABOVE map layers: ${headerHit.isHeaderOrChild ? 'PASS (Header strictly on top)' : 'FAIL'}`);
        results.stickyHeader = headerHit;

        // Reset scroll to top before modal test
        await page.evaluate(() => window.scrollTo(0, 0));
        await page.waitForTimeout(300);

        // 3. Modal Stacking & Backdrop Test
        console.log(`\n========================================`);
        console.log(`[TEST 3] Modal "Tentang Data" Stacking & Coverage Test...`);
        console.log(`========================================`);

        // Open modal
        await page.click('button[aria-label="Detail model dan sumber data"]');
        await page.waitForTimeout(600);

        const modalShotPath = path.join(OUTPUT_DIR, 'step9-modal.png');
        await page.screenshot({ path: modalShotPath, fullPage: true });
        console.log(`[SAVED] Modal screenshot saved to ${modalShotPath}`);

        const modalHit = await page.evaluate(() => {
          const modal = document.getElementById('modal-tentang-data');
          const mapEl = document.getElementById('peta-map');
          const mapBox = mapEl.getBoundingClientRect();

          const modalVisible = !modal.classList.contains('hidden');
          const modalComputed = window.getComputedStyle(modal);

          // Center of viewport (inside modal dialog body)
          const cx = Math.round(window.innerWidth / 2);
          const cy = Math.round(window.innerHeight / 2);
          const centerHit = document.elementFromPoint(cx, cy);
          const isModalCenter = modal.contains(centerHit);

          // Test coordinates over map container area (clamped within viewport)
          const pts = [
            { name: 'map-top', x: Math.round(mapBox.left + 50), y: Math.max(10, Math.min(window.innerHeight - 10, Math.round(mapBox.top + 50))) },
            { name: 'map-mid', x: Math.round(mapBox.left + mapBox.width / 2), y: Math.max(10, Math.min(window.innerHeight - 10, Math.round(mapBox.top + 100))) },
            { name: 'map-bot', x: Math.round(mapBox.left + 50), y: Math.max(10, Math.min(window.innerHeight - 10, Math.round(mapBox.bottom - 50))) },
          ];

          const hitsBehind = pts.map(p => {
            const h = document.elementFromPoint(p.x, p.y);
            return {
              point: p.name,
              x: p.x,
              y: p.y,
              tag: h ? h.tagName : null,
              id: h ? h.id : null,
              class: h ? String(h.className).slice(0, 40) : null,
              isModalOrBackdrop: modal.contains(h),
            };
          });

          const allHitsAreModal = hitsBehind.every(h => h.isModalOrBackdrop);

          return {
            modalVisible,
            modalZIndex: modalComputed.zIndex,
            centerHit: {
              tag: centerHit ? centerHit.tagName : null,
              id: centerHit ? centerHit.id : null,
              isInsideModal: isModalCenter,
            },
            hitsBehind,
            allHitsAreModal,
          };
        });

        console.log(`   Modal Visible: ${modalHit.modalVisible}`);
        console.log(`   Modal z-index: ${modalHit.modalZIndex} (Requirement: >= 1000) -> ${parseInt(modalHit.modalZIndex) >= 1000 ? 'PASS' : 'FAIL'}`);
        console.log(`   Center hit is inside modal: ${modalHit.centerHit.isInsideModal ? 'PASS' : 'FAIL'}`);
        console.log(`   Map coordinate hits behind modal:`, modalHit.hitsBehind);
        console.log(`   100% hits are Modal/Backdrop (0 map bleed-through): ${modalHit.allHitsAreModal ? 'PASS' : 'FAIL'}`);
        results.modalOverlay = modalHit;

        // Test Escape key close
        await page.keyboard.press('Escape');
        await page.waitForTimeout(300);
        const modalClosed = await page.evaluate(() => document.getElementById('modal-tentang-data').classList.contains('hidden'));
        console.log(`   Modal closes on Escape key: ${modalClosed ? 'PASS' : 'FAIL'}`);
      }

      await context.close();
    }

    // 4. Test API Error Scenario & Side Panel Neutral Placeholders
    console.log(`\n========================================`);
    console.log(`[TEST 4] API Failure & Side Panel Neutral State Test...`);
    console.log(`========================================`);

    const errContext = await browser.newContext({
      viewport: { width: 1440, height: 900 },
      deviceScaleFactor: 1,
    });
    const errPage = await errContext.newPage();

    // Intercept and abort API calls
    await errPage.route('**/api/status*', route => route.abort('failed'));
    await errPage.route('**/api/wilayah/**', route => route.abort('failed'));

    await errPage.goto(`${BASE_URL}/peta`, { waitUntil: 'load' });
    await errPage.waitForTimeout(2000);

    const errorStateResult = await errPage.evaluate(() => {
      const errorOverlay = document.getElementById('peta-error-state');
      const isErrorVisible = errorOverlay && !errorOverlay.classList.contains('hidden');

      const panelKeys = [
        'panel-supply-formatted',
        'panel-demand-formatted',
        'panel-supply-raw',
        'panel-demand-raw',
        'panel-selisih',
        'panel-stok-awal',
        'panel-panen',
        'panel-pasokan-masuk',
      ];

      const panelValues = {};
      panelKeys.forEach(k => {
        const el = document.getElementById(k);
        panelValues[k] = el ? el.textContent.trim() : null;
      });

      const allNeutral = Object.values(panelValues).every(v => v === '—');

      return {
        isErrorVisible,
        panelValues,
        allNeutral,
      };
    });

    const errShotPath = path.join(OUTPUT_DIR, 'step9-error.png');
    await errPage.screenshot({ path: errShotPath, fullPage: true });
    console.log(`[SAVED] Error state screenshot saved to ${errShotPath}`);
    console.log(`   Error Overlay Visible: ${errorStateResult.isErrorVisible ? 'PASS' : 'FAIL'}`);
    console.log(`   Side Panel Numbers Cleared to "—": ${errorStateResult.allNeutral ? 'PASS' : 'FAIL'}`);
    console.log(`   Sample side panel values:`, errorStateResult.panelValues);
    results.errorState = errorStateResult;

    await errContext.close();

    console.log(`\n========================================`);
    console.log(`[SUMMARY] All Step 9 Verifications Completed!`);
    console.log(`========================================`);

  } finally {
    await browser.close();
    if (serverProcess) {
      serverProcess.kill();
      console.log('[INFO] Ephemeral server terminated.');
    }
  }
}

runVerification().catch(err => {
  console.error('[ERROR]', err);
  if (serverProcess) serverProcess.kill();
  process.exit(1);
});
