import { chromium } from 'playwright';
import { spawn } from 'child_process';

const PORT = '8099';
const BASE_URL = `http://127.0.0.1:${PORT}`;

const VIEWPORTS = [
  { name: 'Mobile (390x844)', width: 390, height: 844 },
  { name: 'Tablet Portrait (768x1024)', width: 768, height: 1024 },
  { name: 'Tablet Landscape (1024x768)', width: 1024, height: 768 },
];

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
  if (await isServerRunning()) return;
  serverProcess = spawn('php', ['-S', `127.0.0.1:${PORT}`, '-t', 'public'], { stdio: 'ignore' });
  for (let i = 0; i < 30; i++) {
    await new Promise(r => setTimeout(r, 200));
    if (await isServerRunning()) return;
  }
  throw new Error('Failed to start server');
}

async function run() {
  await startServerIfNeeded();
  const browser = await chromium.launch({
    executablePath: '/usr/bin/chromium',
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  });

  try {
    for (const vp of VIEWPORTS) {
      console.log(`\n========================================`);
      console.log(`CHECKING VIEWPORT: ${vp.name}`);
      console.log(`========================================`);

      const context = await browser.newContext({ viewport: { width: vp.width, height: vp.height } });
      const page = await context.newPage();
      await page.goto(`${BASE_URL}/peta`, { waitUntil: 'networkidle' });
      await page.waitForTimeout(1000);

      const audit = await page.evaluate(() => {
        const docWidth = document.documentElement.scrollWidth;
        const winWidth = window.innerWidth;
        const noHorizontalScroll = docWidth <= winWidth;

        // Map height check
        const mapEl = document.getElementById('peta-map');
        const mapHeight = mapEl ? mapEl.getBoundingClientRect().height : 0;
        const vh = window.innerHeight;
        const mapVhPct = (mapHeight / vh) * 100;

        // Interactive elements hit area check (buttons, links, selects, inputs)
        const interactives = Array.from(document.querySelectorAll('button, a, select, input'));
        const offenders = [];
        const compliant = [];

        for (const el of interactives) {
          // Ignore hidden elements
          const style = window.getComputedStyle(el);
          if (style.display === 'none' || style.visibility === 'hidden' || el.offsetParent === null) {
            continue;
          }
          const rect = el.getBoundingClientRect();
          if (rect.width === 0 && rect.height === 0) continue;

          const desc = el.getAttribute('id') || el.getAttribute('aria-label') || el.innerText.trim().slice(0, 30) || el.tagName.toLowerCase();
          const target = {
            tag: el.tagName.toLowerCase(),
            desc: desc.replace(/\s+/g, ' '),
            width: Math.round(rect.width * 10) / 10,
            height: Math.round(rect.height * 10) / 10,
          };

          if (rect.width < 44 || rect.height < 44) {
            offenders.push(target);
          } else {
            compliant.push(target);
          }
        }

        const overflowing = [];
        for (const el of document.querySelectorAll('*')) {
          const rect = el.getBoundingClientRect();
          if (rect.right > winWidth + 1) {
            overflowing.push({
              tag: el.tagName,
              id: el.id,
              cls: el.className ? String(el.className).slice(0, 50) : '',
              right: Math.round(rect.right),
              width: Math.round(rect.width)
            });
          }
        }

        return {
          docWidth,
          winWidth,
          noHorizontalScroll,
          overflowing: overflowing.slice(0, 8),
          mapHeight,
          mapVhPct,
          totalInteractives: offenders.length + compliant.length,
          offendersCount: offenders.length,
          offenders: offenders.slice(0, 25), // list top offenders
        };
      });

      console.log(`1. Horizontal Scroll Check:`);
      console.log(`   scrollWidth: ${audit.docWidth}px | windowWidth: ${audit.winWidth}px`);
      console.log(`   No horizontal scroll: ${audit.noHorizontalScroll ? 'PASS' : 'FAIL (Overflow by ' + (audit.docWidth - audit.winWidth) + 'px)'}`);
      if (!audit.noHorizontalScroll) {
        console.log(`   Overflowing elements sample:`, audit.overflowing);
      }

      console.log(`2. Map Height Check:`);
      console.log(`   Map height: ${audit.mapHeight}px (${audit.mapVhPct.toFixed(1)}% of viewport height ${vp.height}px)`);

      console.log(`3. Touch Target (>= 44x44px) Check:`);
      console.log(`   Total visible targets: ${audit.totalInteractives}`);
      console.log(`   Compliant: ${audit.totalInteractives - audit.offendersCount}`);
      console.log(`   Offenders (< 44px): ${audit.offendersCount}`);
      if (audit.offendersCount > 0) {
        console.log(`   Offenders sample:`);
        audit.offenders.forEach((o, i) => {
          console.log(`     [${i + 1}] <${o.tag}> "${o.desc}" -> ${o.width}x${o.height}px`);
        });
      }

      await context.close();
    }
  } finally {
    await browser.close();
    if (serverProcess) serverProcess.kill();
  }
}

run().catch(console.error);
