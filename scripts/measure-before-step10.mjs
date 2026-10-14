import { chromium } from 'playwright';
import { spawn } from 'child_process';

const PORT = '8099';
const BASE_URL = `http://127.0.0.1:${PORT}`;

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
  if (await isServerRunning()) return;
  serverProcess = spawn('php', ['-S', `127.0.0.1:${PORT}`, '-t', 'public'], { stdio: 'ignore' });
  for (let i = 0; i < 30; i++) {
    await new Promise(r => setTimeout(r, 200));
    if (await isServerRunning()) return;
  }
  throw new Error('Failed to start server');
}

async function measure() {
  await startServerIfNeeded();
  const browser = await chromium.launch({
    executablePath: '/usr/bin/chromium',
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  });

  try {
    const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await context.newPage();

    await page.goto(`${BASE_URL}/peta`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(2000);

    const counts = await page.evaluate(() => {
      function countTextNodes(container) {
        if (!container) return 0;
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
          texts.push(walker.currentNode.textContent.trim().slice(0, 40));
        }
        return { count, sample: texts };
      }

      // Areas
      const headerEl = document.querySelector('main .flex.flex-col.lg\\:flex-row') || document.querySelector('h1')?.closest('.flex');
      const filterEl = document.getElementById('filter-container');
      const bannerEl = document.getElementById('banner-nasional');
      const mapCardEl = document.getElementById('map-card');
      const sidePanelEl = document.getElementById('side-panel-card');
      const kpiEl = document.querySelector('main .grid.grid-cols-1.sm\\:grid-cols-2.lg\\:grid-cols-4');
      const footerEl = document.querySelector('footer');

      // First screen visibility check (within 900px viewport without scroll)
      const vh = 900;
      function checkInFirstScreen(el) {
        if (!el) return false;
        const r = el.getBoundingClientRect();
        return r.top < vh && r.bottom > 0;
      }

      return {
        header: countTextNodes(headerEl),
        filter: countTextNodes(filterEl),
        banner: countTextNodes(bannerEl),
        mapCard: countTextNodes(mapCardEl),
        sidePanel: countTextNodes(sidePanelEl),
        kpi: countTextNodes(kpiEl),
        footer: countTextNodes(footerEl),
        firstScreen: {
          header: checkInFirstScreen(headerEl),
          filter: checkInFirstScreen(filterEl),
          banner: checkInFirstScreen(bannerEl),
          mapCard: checkInFirstScreen(mapCardEl),
          sidePanel: checkInFirstScreen(sidePanelEl),
          mapCardTop: mapCardEl ? mapCardEl.getBoundingClientRect().top : null,
          mapCardBottom: mapCardEl ? mapCardEl.getBoundingClientRect().bottom : null,
          visibleMapHeightIn900: mapCardEl ? Math.max(0, Math.min(vh, mapCardEl.getBoundingClientRect().bottom) - Math.max(0, mapCardEl.getBoundingClientRect().top)) : 0,
        }
      };
    });

    console.log('=== BEFORE STEP 10 MEASUREMENT ===');
    console.log(JSON.stringify(counts, null, 2));

    await context.close();
  } finally {
    await browser.close();
    if (serverProcess) serverProcess.kill();
  }
}

measure().catch(console.error);
