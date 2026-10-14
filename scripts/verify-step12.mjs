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

async function inspectWidth(page, width, height, screenshotName) {
  await page.setViewportSize({ width, height });
  await page.goto(`${BASE_URL}/peta`, { waitUntil: 'load' });
  await page.waitForTimeout(2500);

  const shotPath = path.join(OUTPUT_DIR, screenshotName);
  await page.screenshot({ path: shotPath });
  console.log(`[SAVED] Screenshot: ${shotPath}`);

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
      if (el.offsetParent === null && el.offsetWidth === 0 && el.offsetHeight === 0) return;
      const style = window.getComputedStyle(el);
      if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') return;

      const hasText = el.innerText && el.innerText.trim().length > 0;
      if (!hasText) return;

      const isOverflowHidden = style.overflow === 'hidden' || style.overflowX === 'hidden' || style.textOverflow === 'ellipsis';
      const isScrollBigger = el.scrollWidth > el.clientWidth + 1;

      if (isOverflowHidden && isScrollBigger) {
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
    const interactiveCandidates = Array.from(document.querySelectorAll(
      'button, a[href], input, select, textarea, [role="button"]'
    )).filter(el => {
      if (el.offsetParent === null && el.offsetWidth === 0 && el.offsetHeight === 0) return false;
      const style = window.getComputedStyle(el);
      if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') return false;
      const r = el.getBoundingClientRect();
      return r.width > 0 && r.height > 0;
    });

    const overlaps = [];
    for (let i = 0; i < interactiveCandidates.length; i++) {
      for (let j = i + 1; j < interactiveCandidates.length; j++) {
        const a = interactiveCandidates[i];
        const b = interactiveCandidates[j];
        if (a.contains(b) || b.contains(a)) continue;

        const ra = a.getBoundingClientRect();
        const rb = b.getBoundingClientRect();

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
    { w: 1440, h: 900, name: 'desktop', shot: 'step12-1440.png' },
    { w: 1024, h: 768, name: 'tablet-landscape', shot: 'step12-1024.png' },
    { w: 768, h: 1024, name: 'tablet-portrait', shot: 'step12-768.png' },
    { w: 390, h: 844, name: 'mobile', shot: 'step12-390.png' }
  ];

  console.log('================================================================');
  console.log('STEP 12 VERIFICATION & EVIDENCE GENERATION (AFTER)');
  console.log('================================================================');

  let beforeData = {};
  if (fs.existsSync('docs/ui-review/step12-before.json')) {
    beforeData = JSON.parse(fs.readFileSync('docs/ui-review/step12-before.json', 'utf-8'));
  }

  const afterResults = {};
  for (const vp of widths) {
    const res = await inspectWidth(page, vp.w, vp.h, vp.shot);
    afterResults[vp.w] = res;
    const prev = beforeData[vp.w] || {};

    console.log(`\n── VIEWPORT ${vp.w}x${vp.h} (${vp.name}) ──`);
    console.log(`  1. Horizontal Scroll : ${res.hasHorizontalScroll ? `FAIL (${res.scrollWidth} > ${res.innerWidth})` : 'PASS (clean)'}`);
    console.log(`  2. Truncated Elements: Before=${prev.truncatedCount ?? '?'} -> After=${res.truncatedCount} (${res.truncatedCount === 0 ? 'PASS' : 'FAIL'})`);
    if (res.truncatedElements.length > 0) {
      res.truncatedElements.forEach(t => console.log(`     - [CUT] ${t.selector}: "${t.text}"`));
    }
    console.log(`  3. Overlapping Pairs : Before=${prev.overlapCount ?? '?'} -> After=${res.overlapCount} (${res.overlapCount === 0 ? 'PASS' : 'FAIL'})`);
    if (res.overlaps.length > 0) {
      res.overlaps.forEach(o => console.log(`     - [OVERLAP] ${o.elemA} overlaps with ${o.elemB}`));
    }
    console.log(`  4. Small Tap Targets (<44x44): Before=${prev.smallTargetCount ?? '?'} -> After=${res.smallTargetCount} (${res.smallTargetCount === 0 ? 'PASS' : 'FAIL'})`);
    if (res.smallTargets.length > 0) {
      res.smallTargets.forEach(s => console.log(`     - [SMALL] ${s.target} [${s.width}x${s.height} px]`));
    }
    console.log(`  5. Map Rule (Rule 2) :`);
    console.log(`     - Polygons: ${res.mapInteractiveCount} (expect > 400: ${res.mapInteractiveCount > 400 ? 'PASS' : 'FAIL'})`);
    console.log(`     - Colored Pilots: ${res.coloredPilotCount} (expect >= 34: ${res.coloredPilotCount >= 34 ? 'PASS' : 'FAIL'})`);
    console.log(`     - Outside Container: ${res.outsideCount} (expect 0: ${res.outsideCount === 0 ? 'PASS' : 'FAIL'})`);
    console.log(`  6. Heights           : Map Card=${res.mapCardHeight}px, Side Panel=${res.sideCardHeight}px`);
  }

  const finalEvidence = {
    before: beforeData,
    after: afterResults,
  };

  fs.writeFileSync('docs/ui-review/step12-evidence.json', JSON.stringify(finalEvidence, null, 2), 'utf-8');
  console.log('\n[SAVED] docs/ui-review/step12-evidence.json');

  await browser.close();
  if (serverProcess) serverProcess.kill();
}

run().catch(err => {
  console.error('[FATAL]', err);
  if (serverProcess) serverProcess.kill();
  process.exit(1);
});
