import { chromium } from 'playwright';
import { AxeBuilder } from '@axe-core/playwright';
import { spawn } from 'child_process';
import fs from 'fs';
import path from 'path';

const PORT = process.env.PORT || '8099';
const BASE_URL = `http://127.0.0.1:${PORT}`;
const outputFile = process.argv[2] || 'docs/ui-review/axe-step3-before.json';

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
  if (running) return;

  console.log(`[INFO] Starting ephemeral PHP server on port ${PORT}...`);
  serverProcess = spawn('php', ['-S', `127.0.0.1:${PORT}`, '-t', 'public'], {
    stdio: 'ignore',
    detached: false,
  });

  for (let i = 0; i < 30; i++) {
    await new Promise((r) => setTimeout(r, 200));
    if (await isServerRunning()) return;
  }
  throw new Error(`Failed to start server at ${BASE_URL}`);
}

async function run() {
  await startServerIfNeeded();

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

    console.log(`[INFO] Loading ${BASE_URL}/peta at 1440x900...`);
    await page.goto(`${BASE_URL}/peta`, { waitUntil: 'networkidle' });

    // Wait for map and status data to populate
    try {
      await page.locator('#peta-map svg path.leaflet-interactive').first().waitFor({ state: 'attached', timeout: 15000 });
      await page.waitForTimeout(1500);
    } catch (e) {
      console.warn('[WARN] Wait timed out:', e.message);
    }

    // 1. Run Axe Accessibility Scan
    console.log('[INFO] Running axe-core scan...');
    const axeBuilder = new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']);

    const axeResults = await axeBuilder.analyze();

    // Contrast violations in axe
    const contrastViolations = axeResults.violations.filter((v) => v.id === 'color-contrast');
    console.log(`\n=== AXE SCAN SUMMARY ===`);
    console.log(`Total Violations: ${axeResults.violations.length}`);
    axeResults.violations.forEach((v) => {
      console.log(`- [${v.impact.toUpperCase()}] ${v.id}: ${v.description} (${v.nodes.length} nodes)`);
    });
    console.log(`Color-Contrast Violations: ${contrastViolations.length > 0 ? contrastViolations[0].nodes.length : 0} nodes`);

    // 2. DOM Text Node Audit: font-size < 12px & custom contrast calculation
    console.log('\n[INFO] Walking DOM for font-size < 12px and text contrast...');
    const domAudit = await page.evaluate(() => {
      function lum(r, g, b) {
        const a = [r, g, b].map((v) => {
          v /= 255;
          return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
        });
        return 0.2126 * a[0] + 0.7152 * a[1] + 0.0722 * a[2];
      }

      function parseRgba(str) {
        if (!str || str === 'transparent' || str === 'rgba(0, 0, 0, 0)') return null;
        const match = str.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)/);
        if (match) {
          return {
            r: parseInt(match[1]),
            g: parseInt(match[2]),
            b: parseInt(match[3]),
            a: match[4] !== undefined ? parseFloat(match[4]) : 1,
          };
        }
        try {
          const canvas = document.createElement('canvas');
          canvas.width = 1;
          canvas.height = 1;
          const ctx = canvas.getContext('2d');
          ctx.fillStyle = str;
          ctx.fillRect(0, 0, 1, 1);
          const data = ctx.getImageData(0, 0, 1, 1).data;
          return {
            r: data[0],
            g: data[1],
            b: data[2],
            a: data[3] / 255,
          };
        } catch (e) {
          return null;
        }
      }

      function getEffectiveBg(el) {
        let cur = el;
        while (cur && cur !== document.documentElement) {
          const style = getComputedStyle(cur);
          const bg = parseRgba(style.backgroundColor);
          if (bg && bg.a > 0.1) {
            return bg;
          }
          cur = cur.parentElement;
        }
        return { r: 255, g: 255, b: 255, a: 1 }; // default white
      }

      function contrastRatio(fg, bg) {
        const l1 = lum(fg.r, fg.g, fg.b);
        const l2 = lum(bg.r, bg.g, bg.b);
        return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
      }

      const elements = Array.from(document.querySelectorAll('body *'));
      const smallTextNodes = [];
      const lowContrastNodes = [];

      elements.forEach((el) => {
        // Skip hidden, scripts, styles, svg internals
        if (el.offsetParent === null && el.tagName !== 'BODY') return;
        if (['SCRIPT', 'STYLE', 'SVG', 'PATH'].includes(el.tagName)) return;

        // Check only direct text or leaf text containers
        const directText = Array.from(el.childNodes)
          .filter((n) => n.nodeType === Node.TEXT_NODE)
          .map((n) => n.textContent.trim())
          .join(' ')
          .trim();

        if (!directText) return;

        const style = getComputedStyle(el);
        const fontSizePx = parseFloat(style.fontSize);

        if (fontSizePx < 11.99) {
          smallTextNodes.push({
            tag: el.tagName.toLowerCase(),
            class: el.className,
            fontSize: `${fontSizePx.toFixed(1)}px`,
            text: directText.slice(0, 40),
          });
        }

        const fg = parseRgba(style.color);
        const bg = getEffectiveBg(el);
        if (fg && bg) {
          const ratio = contrastRatio(fg, bg);
          const isBold = parseInt(style.fontWeight) >= 700 || style.fontWeight === 'bold';
          const isLarge = fontSizePx >= 24 || (fontSizePx >= 18.66 && isBold);
          const req = isLarge ? 3.0 : 4.5;
          if (ratio < req - 0.05) {
            lowContrastNodes.push({
              tag: el.tagName.toLowerCase(),
              class: (el.className || '').slice(0, 60),
              text: directText.slice(0, 40),
              ratio: ratio.toFixed(2),
              required: req,
              color: style.color,
              fontSize: `${fontSizePx.toFixed(1)}px`,
            });
          }
        }
      });

      return {
        smallTextCount: smallTextNodes.length,
        smallTextNodes: smallTextNodes.slice(0, 30),
        lowContrastCount: lowContrastNodes.length,
        lowContrastNodes: lowContrastNodes.slice(0, 30),
      };
    });

    console.log(`\n=== DOM TEXT AUDIT RESULTS ===`);
    console.log(`Total Text Nodes with font-size < 12px: ${domAudit.smallTextCount}`);
    if (domAudit.smallTextCount > 0) {
      console.log('Sample small text nodes:');
      domAudit.smallTextNodes.forEach((n) => console.log(`  - [${n.fontSize}] <${n.tag} class="${n.class}">: "${n.text}"`));
    }

    console.log(`\nTotal Text Nodes with Contrast < 4.5:1: ${domAudit.lowContrastCount}`);
    if (domAudit.lowContrastCount > 0) {
      console.log('Sample low contrast nodes:');
      domAudit.lowContrastNodes.forEach((n) => console.log(`  - [${n.ratio}:1 < ${n.required}:1] "${n.text}" (<${n.tag} class="${n.class}">, color: ${n.color})`));
    }

    axeResults.domAudit = domAudit;
    const outputDir = path.dirname(outputFile);
    if (!fs.existsSync(outputDir)) {
      fs.mkdirSync(outputDir, { recursive: true });
    }
    fs.writeFileSync(outputFile, JSON.stringify(axeResults, null, 2), 'utf-8');
    console.log(`[SAVED] Axe results with domAudit saved to ${outputFile}`);

    await context.close();
  } finally {
    await browser.close();
    if (serverProcess) serverProcess.kill();
  }
}

run().catch((err) => {
  console.error('[ERROR]', err);
  if (serverProcess) serverProcess.kill();
  process.exit(1);
});
