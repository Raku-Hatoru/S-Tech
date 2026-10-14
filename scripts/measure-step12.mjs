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
    return;
  }
  console.log(`[INFO] Starting ephemeral PHP server on port ${PORT}...`);
  serverProcess = spawn('php', ['-S', `127.0.0.1:${PORT}`, '-t', 'public'], { stdio: 'ignore' });
  for (let i = 0; i < 30; i++) {
    await new Promise(r => setTimeout(r, 200));
    if (await isServerRunning()) {
      return;
    }
  }
  throw new Error(`Failed to start server at ${BASE_URL}`);
}

async function inspectWidth(page, width, height) {
  await page.setViewportSize({ width, height });
  await page.goto(`${BASE_URL}/peta`, { waitUntil: 'load' });
  // Wait for map and status data
  await page.waitForTimeout(2500);

  const evaluation = await page.evaluate(() => {
    // 1. Horizontal scroll check
    const hasHorizontalScroll = document.documentElement.scrollWidth > window.innerWidth;
    const scrollWidth = document.documentElement.scrollWidth;
    const innerWidth = window.innerWidth;

    // 2. Truncated visible text elements
    // Criteria: visible element where scrollWidth > clientWidth with overflow hidden or text-overflow ellipsis
    const truncatedElements = [];
    const allEls = document.querySelectorAll('body *');
    allEls.forEach(el => {
      // Must be visible
      if (el.offsetParent === null && el.offsetWidth === 0 && el.offsetHeight === 0) return;
      const style = window.getComputedStyle(el);
      if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') return;

      // Only inspect elements with direct text or inline text
      const hasText = el.innerText && el.innerText.trim().length > 0;
      if (!hasText) return;

      const isOverflowHidden = style.overflow === 'hidden' || style.overflowX === 'hidden' || style.textOverflow === 'ellipsis';
      const isScrollBigger = el.scrollWidth > el.clientWidth + 1; // 1px threshold for rounding

      if (isOverflowHidden && isScrollBigger) {
        // Find identifier
        const id = el.id ? `#${el.id}` : '';
        const cls = el.className && typeof el.className === 'string' ? `.${el.className.trim().split(/\s+/).slice(0, 3).join('.')}` : '';
        const tag = el.tagName.toLowerCase();
        const textSample = el.innerText.trim().slice(0, 40);
        truncatedElements.push({
          selector: `${tag}${id}${cls}`,
          text: textSample,
          scrollWidth: el.scrollWidth,
          clientWidth: el.clientWidth,
        });
      }
    });

    // 3. Overlapping interactive elements
    // Elements: button, a, input, select, textarea, [role="button"], [tabindex]:not([tabindex="-1"])
    const interactiveCandidates = Array.from(document.querySelectorAll(
      'button, a[href], input, select, textarea, [role="button"]'
    )).filter(el => {
      if (el.offsetParent === null && el.offsetWidth === 0 && el.offsetHeight === 0) return false;
      const style = window.getComputedStyle(el);
      if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') return false;
      const r = el.getBoundingClientRect();
      return r.width > 0 && r.height > 0;
    });

    // Filter out ancestors/descendants
    const overlaps = [];
    for (let i = 0; i < interactiveCandidates.length; i++) {
      for (let j = i + 1; j < interactiveCandidates.length; j++) {
        const a = interactiveCandidates[i];
        const b = interactiveCandidates[j];
        if (a.contains(b) || b.contains(a)) continue;

        const ra = a.getBoundingClientRect();
        const rb = b.getBoundingClientRect();

        // Calculate intersection
        const left = Math.max(ra.left, rb.left);
        const top = Math.max(ra.top, rb.top);
        const right = Math.min(ra.right, rb.right);
        const bottom = Math.min(ra.bottom, rb.bottom);

        if (right - left > 2 && bottom - top > 2) {
          const aId = a.id ? `#${a.id}` : (a.innerText?.trim().slice(0, 20) || a.tagName);
          const bId = b.id ? `#${b.id}` : (b.innerText?.trim().slice(0, 20) || b.tagName);
          overlaps.push({
            elemA: `${a.tagName.toLowerCase()}(${aId})`,
            elemB: `${b.tagName.toLowerCase()}(${bId})`,
            overlapBox: { x: Math.round(left), y: Math.round(top), w: Math.round(right - left), h: Math.round(bottom - top) }
          });
        }
      }
    }

    // 4. Tap target sizes (< 44x44)
    const smallTargets = [];
    interactiveCandidates.forEach(el => {
      // Ignore hidden mobile nav if closed
      const r = el.getBoundingClientRect();
      if (r.width < 43.5 || r.height < 43.5) {
        const id = el.id ? `#${el.id}` : '';
        const txt = el.innerText?.trim().slice(0, 25) || el.getAttribute('aria-label') || el.title || el.tagName;
        smallTargets.push({
          target: `${el.tagName.toLowerCase()}${id} ("${txt}")`,
          width: Math.round(r.width * 10) / 10,
          height: Math.round(r.height * 10) / 10,
        });
      }
    });

    // 5. Map rule checks
    const mapEl = document.getElementById('map-card');
    const mapBox = mapEl ? mapEl.getBoundingClientRect() : null;
    const interactivePaths = Array.from(document.querySelectorAll('path.leaflet-interactive'));
    let outsideCount = 0;
    let coloredPilotCount = 0;
    const defaultGray = 'rgb(100, 116, 139)';

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

    // 6. Height comparison: map card vs side panel
    const sideCard = document.getElementById('side-panel-card');
    const mapCardHeight = mapEl ? Math.round(mapEl.getBoundingClientRect().height) : 0;
    const sideCardHeight = sideCard ? Math.round(sideCard.getBoundingClientRect().height) : 0;

    return {
      hasHorizontalScroll,
      scrollWidth,
      innerWidth,
      truncatedCount: truncatedElements.length,
      truncatedElements,
      overlapCount: overlaps.length,
      overlaps,
      smallTargetCount: smallTargets.length,
      smallTargets,
      mapInteractiveCount: interactivePaths.length,
      coloredPilotCount,
      outsideCount,
      mapCardHeight,
      sideCardHeight,
    };
  });

  return evaluation;
}

async function run() {
  await startServerIfNeeded();
  const browser = await chromium.launch({
    executablePath: process.env.CHROMIUM_PATH || '/usr/bin/chromium',
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  });

  const page = await browser.newPage();
  const widths = [
    { w: 1440, h: 900, name: 'desktop' },
    { w: 1024, h: 768, name: 'tablet-landscape' },
    { w: 768, h: 1024, name: 'tablet-portrait' },
    { w: 390, h: 844, name: 'mobile' }
  ];

  console.log('================================================================');
  console.log('STEP 12 MEASUREMENT — BEFORE AUDIT REPORT');
  console.log('================================================================');

  const beforeResults = {};
  for (const vp of widths) {
    const res = await inspectWidth(page, vp.w, vp.h);
    beforeResults[vp.w] = res;
    console.log(`\n── VIEWPORT ${vp.w}x${vp.h} (${vp.name}) ──`);
    console.log(`  Horizontal Scroll : ${res.hasHorizontalScroll ? `YES (${res.scrollWidth} > ${res.innerWidth})` : 'NO (clean)'}`);
    console.log(`  Truncated Elements: ${res.truncatedCount}`);
    res.truncatedElements.forEach(t => console.log(`    - ${t.selector}: "${t.text}" (scroll:${t.scrollWidth} > client:${t.clientWidth})`));
    console.log(`  Overlapping Pairs : ${res.overlapCount}`);
    res.overlaps.forEach(o => console.log(`    - ${o.elemA} overlaps with ${o.elemB} at [${o.overlapBox.x},${o.overlapBox.y},${o.overlapBox.w}x${o.overlapBox.h}]`));
    console.log(`  Small Tap Targets (<44x44): ${res.smallTargetCount}`);
    res.smallTargets.forEach(s => console.log(`    - ${s.target} [${s.width}x${s.height} px]`));
    console.log(`  Map Rule: Polygons=${res.mapInteractiveCount}, Colored=${res.coloredPilotCount}, Outside=${res.outsideCount}`);
    console.log(`  Height: mapCard=${res.mapCardHeight}px, sidePanel=${res.sideCardHeight}px`);
  }

  fs.writeFileSync('docs/ui-review/step12-before.json', JSON.stringify(beforeResults, null, 2), 'utf-8');
  await browser.close();
  if (serverProcess) serverProcess.kill();
  console.log('\n[SAVED] docs/ui-review/step12-before.json');
}

run().catch(err => {
  console.error('[FATAL]', err);
  if (serverProcess) serverProcess.kill();
  process.exit(1);
});
