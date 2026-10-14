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

  const results = {
    normalState: {},
    modalState: {},
    errorState: {},
  };

  try {
    // ── 1. NORMAL STATE (1440x900 & 390x844) ──────────────────────────────────
    const viewports = [
      { width: 1440, height: 900 },
      { width: 390, height: 844 },
    ];

    for (const vp of viewports) {
      console.log(`\n[INFO] Testing Normal State at ${vp.width}x${vp.height}...`);
      const context = await browser.newContext({ viewport: vp, deviceScaleFactor: 1 });
      const page = await context.newPage();

      await page.goto(`${BASE_URL}/peta`, { waitUntil: 'networkidle' });
      try {
        await page.locator('#peta-map .leaflet-pane').first().waitFor({ timeout: 10000 });
        await page.waitForTimeout(2000);
      } catch (e) {
        console.warn(`[WARN] Leaflet wait warning: ${e.message}`);
      }

      const shotPath = path.join(OUTPUT_DIR, `step11-${vp.width}.png`);
      await page.screenshot({ path: shotPath, fullPage: true });
      console.log(`[SAVED] Screenshot: ${shotPath}`);

      if (vp.width === 1440) {
        const desktopMetrics = await page.evaluate(() => {
          // Map container & polygons
          const mapEl = document.getElementById('map-card');
          const mapBox = mapEl ? mapEl.getBoundingClientRect() : null;
          const interactivePaths = Array.from(document.querySelectorAll('path.leaflet-interactive'));

          let outsideCount = 0;
          let coloredPilotCount = 0;
          const defaultGray = 'rgb(100, 116, 139)'; // #64748b

          interactivePaths.forEach(p => {
            const r = p.getBoundingClientRect();
            if (mapBox) {
              const intersects = !(
                r.right < mapBox.left ||
                r.left > mapBox.right ||
                r.bottom < mapBox.top ||
                r.top > mapBox.bottom
              );
              if (!intersects && (r.width > 0 || r.height > 0)) outsideCount++;
            }
            const fill = window.getComputedStyle(p).fill;
            if (fill && fill !== defaultGray && !fill.includes('100, 116, 139') && fill !== 'none') {
              coloredPilotCount++;
            }
          });

          // Banner text
          const bannerEl = document.getElementById('banner-nasional-text');
          const bannerText = bannerEl ? bannerEl.textContent.trim() : '';

          // Side panel values
          const panelKode = document.getElementById('panel-kode')?.textContent.trim() || '';
          const panelPeriode = document.getElementById('panel-neraca-periode')?.textContent.trim() || '';
          const panelStatus = document.getElementById('panel-status-label')?.textContent.trim() || '';
          const panelRasio = document.getElementById('panel-rasio-label')?.textContent.trim() || '';
          const panelSupply = document.getElementById('panel-supply-formatted')?.textContent.trim() || '';
          const panelDemand = document.getElementById('panel-demand-formatted')?.textContent.trim() || '';
          const panelSelisih = document.getElementById('panel-selisih')?.textContent.trim() || '';

          // Legend text
          const legendEl = document.getElementById('map-legend');
          const legendText = legendEl ? legendEl.innerText.trim() : '';

          return {
            interactiveCount: interactivePaths.length,
            coloredPilotCount,
            outsideCount,
            bannerText,
            panelKode,
            panelPeriode,
            panelStatus,
            panelRasio,
            panelSupply,
            panelDemand,
            panelSelisih,
            legendText,
          };
        });

        results.normalState = desktopMetrics;
      }

      await context.close();
    }

    // ── 2. MODAL TENTANG DATA TEST (1440x900) ──────────────────────────────────
    console.log(`\n[INFO] Testing Tentang Data Modal at 1440x900...`);
    const modalContext = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const modalPage = await modalContext.newPage();
    await modalPage.goto(`${BASE_URL}/peta`, { waitUntil: 'networkidle' });
    await modalPage.waitForTimeout(1500);

    // Click button to open modal
    await modalPage.locator('#btn-tentang-data').click();
    await modalPage.locator('#modal-tentang-data:not(.hidden)').waitFor({ timeout: 5000 });
    await modalPage.waitForTimeout(1000);

    const modalShotPath = path.join(OUTPUT_DIR, 'step11-modal.png');
    await modalPage.screenshot({ path: modalShotPath });
    console.log(`[SAVED] Screenshot: ${modalShotPath}`);

    const modalData = await modalPage.evaluate(() => {
      const modalEl = document.getElementById('modal-tentang-data');
      const isVisible = modalEl && !modalEl.classList.contains('hidden');

      // Extract table rows
      const rows = Array.from(modalEl.querySelectorAll('table tbody tr')).map(tr => {
        const cols = Array.from(tr.querySelectorAll('td')).map(td => td.textContent.trim());
        return {
          status: cols[0] || '',
          rentang: cols[1] || '',
          makna: cols[2] || '',
        };
      });

      // Extract 4 blocks headings
      const blocks = Array.from(modalEl.querySelectorAll('.space-y-4 > div')).map(b => {
        const title = b.querySelector('.font-bold')?.textContent.trim() || '';
        const desc = b.querySelector('p')?.textContent.trim() || '';
        return { title, desc };
      });

      const modalText = modalEl.innerText;
      const hasPRD = /PRD/i.test(modalText);
      const hasWGS84 = /WGS84/i.test(modalText);
      const hasSelimbang = /Selimbang/i.test(modalText);

      return {
        isVisible,
        rows,
        blocks,
        hasPRD,
        hasWGS84,
        hasSelimbang,
      };
    });

    results.modalState = modalData;
    await modalContext.close();

    // ── 3. FAILURE STATE (API BLOCKED, RENDERED MAP FRAME, NO STALE NUMBERS) ──
    console.log(`\n[INFO] Testing Failure State (API Blocked) at 1440x900...`);
    const errContext = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const errPage = await errContext.newPage();

    // Intercept and abort API calls, but allow GeoJSON and static files
    await errPage.route('**/api/status*', route => route.abort('failed'));
    await errPage.route('**/api/wilayah/**', route => route.abort('failed'));

    await errPage.goto(`${BASE_URL}/peta`, { waitUntil: 'load' });
    await errPage.waitForTimeout(2500);

    const errShotPath = path.join(OUTPUT_DIR, 'step11-error.png');
    await errPage.screenshot({ path: errShotPath });
    console.log(`[SAVED] Screenshot: ${errShotPath}`);

    const errorData = await errPage.evaluate(() => {
      const errorOverlay = document.getElementById('peta-error-state');
      const isErrorOverlayVisible = errorOverlay && !errorOverlay.classList.contains('hidden');

      // Map container & polygons behind overlay
      const interactivePaths = Array.from(document.querySelectorAll('path.leaflet-interactive'));

      // Side panel check
      const panelKode = document.getElementById('panel-kode')?.textContent.trim() || '';
      const panelStatus = document.getElementById('panel-status-label')?.textContent.trim() || '';
      const panelRasio = document.getElementById('panel-rasio-label')?.textContent.trim() || '';
      const panelPeriode = document.getElementById('panel-neraca-periode')?.textContent.trim() || '';
      const panelSupply = document.getElementById('panel-supply-formatted')?.textContent.trim() || '';
      const panelDemand = document.getElementById('panel-demand-formatted')?.textContent.trim() || '';
      const panelSelisih = document.getElementById('panel-selisih')?.textContent.trim() || '';
      const iconBox = document.getElementById('panel-status-icon-box')?.textContent.trim() || '';

      const sidePanelText = document.getElementById('side-panel-card')?.innerText || '';
      const hasStale105 = sidePanelText.includes('105,1%') || sidePanelText.includes('105.1%');
      const hasStaleFeb = /Februari\s*2026/i.test(sidePanelText);
      const hasStale18500 = sidePanelText.includes('18.500');

      return {
        isErrorOverlayVisible,
        mapInteractiveCount: interactivePaths.length,
        panelKode,
        panelStatus,
        panelRasio,
        panelPeriode,
        panelSupply,
        panelDemand,
        panelSelisih,
        iconBox,
        hasStale105,
        hasStaleFeb,
        hasStale18500,
      };
    });

    results.errorState = errorData;
    await errContext.close();

    // ── 4. GREP LEAK AUDIT (/peta outside modal & /wilayah/3302) ───────────────
    console.log(`\n[INFO] Running HTML Grep Leak Audit on /peta and /wilayah/3302...`);
    const grepContext = await browser.newContext();
    const grepPage = await grepContext.newPage();

    // Audit /peta
    await grepPage.goto(`${BASE_URL}/peta`, { waitUntil: 'load' });
    await grepPage.waitForTimeout(1000);
    const petaFullHtml = await grepPage.content();
    const modalStart = petaFullHtml.indexOf('id="modal-tentang-data"');
    const modalEnd = petaFullHtml.indexOf('vendor/leaflet/leaflet.js', modalStart);
    const petaOutsideModal = (modalStart !== -1 && modalEnd !== -1)
      ? petaFullHtml.slice(0, modalStart) + petaFullHtml.slice(modalEnd)
      : petaFullHtml;
    const modalMarkup = (modalStart !== -1 && modalEnd !== -1)
      ? petaFullHtml.slice(modalStart, modalEnd)
      : '';

    // Audit /wilayah/3302
    await grepPage.goto(`${BASE_URL}/wilayah/3302`, { waitUntil: 'load' });
    await grepPage.waitForTimeout(1000);
    const wilayahHtml = await grepPage.content();
    await grepContext.close();

    const countMatches = (text, regex) => {
      const m = text.match(regex);
      return m ? m.length : 0;
    };

    const grepAudit = {
      petaOutsideModal: {
        PRD: countMatches(petaOutsideModal, /PRD/g),
        Selimbang: countMatches(petaOutsideModal, /Selimbang/gi),
        harga: countMatches(petaOutsideModal, /harga/gi),
        deflasi: countMatches(petaOutsideModal, /deflasi/gi),
        XGBoost: countMatches(petaOutsideModal, /XGBoost/gi),
      },
      wilayah3302: {
        PRD: countMatches(wilayahHtml, /PRD/g),
        Selimbang: countMatches(wilayahHtml, /Selimbang/gi),
        harga: countMatches(wilayahHtml, /harga/gi),
        deflasi: countMatches(wilayahHtml, /deflasi/gi),
        XGBoost: countMatches(wilayahHtml, /XGBoost/gi),
      },
      modalMarkup: {
        PRD: countMatches(modalMarkup, /PRD/g),
        Selimbang: countMatches(modalMarkup, /Selimbang/gi),
        harga: countMatches(modalMarkup, /harga/gi),
        deflasi: countMatches(modalMarkup, /deflasi/gi),
        XGBoost: countMatches(modalMarkup, /XGBoost/gi),
        WGS84: countMatches(modalMarkup, /WGS84/gi),
      }
    };
    results.grepAudit = grepAudit;

    // ── 5. SAVE EVIDENCE REPORT ────────────────────────────────────────────────
    const evidencePath = path.join(OUTPUT_DIR, 'step11-evidence.json');
    fs.writeFileSync(evidencePath, JSON.stringify(results, null, 2), 'utf-8');
    console.log(`[SAVED] Evidence JSON: ${evidencePath}`);

    console.log('\n======================================================');
    console.log('STEP 11 CONSISTENCY & RELIABILITY - VERIFICATION SUMMARY');
    console.log('======================================================');
    console.log('1. Map Rule in Normal State:');
    console.log(`   - Total interactive polygons: ${results.normalState.interactiveCount} (expect > 400: ${results.normalState.interactiveCount > 400 ? 'PASS' : 'FAIL'})`);
    console.log(`   - Colored pilot polygons: ${results.normalState.coloredPilotCount} (expect >= 34: ${results.normalState.coloredPilotCount >= 34 ? 'PASS' : 'FAIL'})`);
    console.log(`   - Polygons outside container: ${results.normalState.outsideCount} (expect 0: ${results.normalState.outsideCount === 0 ? 'PASS' : 'FAIL'})`);
    console.log('\n2. Normal State Side Panel & Banner:');
    console.log(`   - Region Code: "${results.normalState.panelKode}" (expect "3302": ${results.normalState.panelKode === '3302' ? 'PASS' : 'FAIL'})`);
    console.log(`   - Period Format: "${results.normalState.panelPeriode}" (expect "Desember 2025 (Aktual)": ${results.normalState.panelPeriode === 'Desember 2025 (Aktual)' ? 'PASS' : 'FAIL'})`);
    console.log(`   - Banner Text: "${results.normalState.bannerText}"`);
    console.log('\n3. Modal Tentang Data Status Table vs Legend:');
    console.log(`   - Modal is visible: ${results.modalState.isVisible ? 'PASS' : 'FAIL'}`);
    console.log(`   - Table rows count: ${results.modalState.rows.length} (expect 5: ${results.modalState.rows.length === 5 ? 'PASS' : 'FAIL'})`);
    console.log('   - Table Rows Rendered:');
    results.modalState.rows.forEach(r => console.log(`     ${r.status} | ${r.rentang} | ${r.makna}`));
    console.log(`   - Leaks in Modal: PRD=${results.modalState.hasPRD}, WGS84=${results.modalState.hasWGS84}, Selimbang=${results.modalState.hasSelimbang}`);
    console.log('\n4. Failure State (API Blocked):');
    console.log(`   - Error Overlay Visible: ${results.errorState.isErrorOverlayVisible ? 'PASS' : 'FAIL'}`);
    console.log(`   - Rendered Map Polygons: ${results.errorState.mapInteractiveCount} (expect > 400: ${results.errorState.mapInteractiveCount > 400 ? 'PASS' : 'FAIL'})`);
    console.log(`   - Status Placeholder: "${results.errorState.panelStatus}" (expect "Data wilayah tidak dapat dimuat": ${results.errorState.panelStatus === 'Data wilayah tidak dapat dimuat' ? 'PASS' : 'FAIL'})`);
    console.log(`   - Side Panel Numbers: Supply="${results.errorState.panelSupply}", Demand="${results.errorState.panelDemand}", Selisih="${results.errorState.panelSelisih}", Rasio="${results.errorState.panelRasio}"`);
    console.log(`   - Stale Sample Check: 105.1%=${results.errorState.hasStale105 ? 'STALE DETECTED' : 'CLEAN'}, Feb 2026=${results.errorState.hasStaleFeb ? 'STALE DETECTED' : 'CLEAN'}, 18500=${results.errorState.hasStale18500 ? 'STALE DETECTED' : 'CLEAN'}`);
    console.log('\n5. HTML Grep Leak Audit:');
    console.log('   - /peta outside modal: ', JSON.stringify(results.grepAudit.petaOutsideModal));
    console.log('   - /wilayah/3302:       ', JSON.stringify(results.grepAudit.wilayah3302));
    console.log('   - modal markup:        ', JSON.stringify(results.grepAudit.modalMarkup));
    console.log('======================================================\n');

  } finally {
    await browser.close();
    if (serverProcess) {
      serverProcess.kill();
    }
  }
}

runVerification().catch(err => {
  console.error('[FATAL]', err);
  if (serverProcess) serverProcess.kill();
  process.exit(1);
});
