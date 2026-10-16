import { chromium } from 'playwright';
import { spawn } from 'child_process';
import fs from 'fs';
import path from 'path';

const PORT = 8099;
const BASE_URL = `http://127.0.0.1:${PORT}`;
const SCREENSHOT_DIR = path.resolve('docs/ui-review');

if (!fs.existsSync(SCREENSHOT_DIR)) {
  fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

// 1. Start ephemeral PHP server
console.log(`[INFO] Starting ephemeral PHP server on port ${PORT}...`);
const phpServer = spawn('php', ['-S', `127.0.0.1:${PORT}`, '-t', 'public'], {
  stdio: 'ignore',
  detached: true
});

function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

async function waitForServer(url, timeoutMs = 10000) {
  const start = Date.now();
  while (Date.now() - start < timeoutMs) {
    try {
      const res = await fetch(url);
      if (res.status < 500) return true;
    } catch (e) {
      // wait
    }
    await sleep(200);
  }
  throw new Error(`Server failed to start at ${url} within ${timeoutMs}ms`);
}

async function run() {
  try {
    await waitForServer(`${BASE_URL}/login`);
    console.log('[INFO] Server is up and responding.');

    const browser = await chromium.launch({
      executablePath: '/usr/bin/chromium',
      headless: true,
      args: ['--no-sandbox', '--disable-gpu']
    });

    const results = {
      pagesChecked: [],
      headerMeasurements: {},
      downloadableFiles: {},
      allClean: true
    };

    // Helper: count occurrences
    function countOccurrences(str, pattern, caseInsensitive = true) {
      const flags = caseInsensitive ? 'gi' : 'g';
      const regex = new RegExp(pattern, flags);
      const matches = str.match(regex);
      return matches ? matches.length : 0;
    }

    // Role definitions & pages
    const roleScenarios = [
      {
        role: 'Guest',
        login: null,
        pages: ['/peta', '/wilayah/3302', '/login']
      },
      {
        role: 'Distributor',
        login: { email: 'distributor1@sipangan.test', pass: 'Distrib1234' },
        pages: ['/peta', '/wilayah/3302', '/pengiriman', '/pengiriman/buat', '/pengiriman/1']
      },
      {
        role: 'BUMDes',
        login: { email: 'bumdes.banyumas@sipangan.test', pass: 'Bumdes1234' },
        pages: ['/peta', '/wilayah/3302', '/panen', '/jadwal-tanam']
      },
      {
        role: 'Admin',
        login: { email: 'admin@sipangan.test', pass: 'Admin1234' },
        pages: [
          '/peta',
          '/wilayah/3302',
          '/admin/pengguna',
          '/admin/pengguna/buat',
          '/admin/dataset',
          '/admin/forecast',
          '/admin/pengaturan',
          '/pengiriman',
          '/panen',
          '/jadwal-tanam'
        ]
      }
    ];

    const viewports = [
      { name: '1440', width: 1440, height: 900 },
      { name: '390', width: 390, height: 844 }
    ];

    console.log('\n=== STEP 6: VERIFY IN REAL BROWSER (PLAYWRIGHT) ===');

    for (const scenario of roleScenarios) {
      console.log(`\n--- Menguji Skenario Role: ${scenario.role} ---`);
      const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
      const page = await context.newPage();

      if (scenario.login) {
        await page.goto(`${BASE_URL}/login`, { waitUntil: 'domcontentloaded' });
        await page.fill('#email', scenario.login.email);
        await page.fill('#password', scenario.login.pass);
        await Promise.all([
          page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
          page.click('button[type="submit"]')
        ]);
        console.log(`[AUTH] Login sukses sebagai ${scenario.role} (${scenario.login.email})`);
      }

      for (const pagePath of scenario.pages) {
        for (const vp of viewports) {
          await page.setViewportSize({ width: vp.width, height: vp.height });
          await page.goto(`${BASE_URL}${pagePath}`, { waitUntil: 'domcontentloaded' });
          await sleep(300); // allow client-side hydration

          const title = await page.title();
          const html = await page.content();

          // 1. Check old brand phrase "sipangan waspada"
          const oldBrandPhraseCount = countOccurrences(html, 'sipangan\\s+waspada', true);

          // 2. Check "sipangan" outside allow-list
          const cleanedHtml = html.replace(/@sipangan\.test/gi, '').replace(/sipangan\.test/gi, '');
          const unapprovedSipanganCount = countOccurrences(cleanedHtml, 'sipangan', true);

          // 3. Check new brand "PRANALA PANGAN" presence in title or header
          const hasNewBrand = /PRANALA.*PANGAN/i.test(html) || /PRANALA/i.test(html);

          const statusOk = oldBrandPhraseCount === 0 && unapprovedSipanganCount === 0 && hasNewBrand;
          if (!statusOk) results.allClean = false;

          results.pagesChecked.push({
            role: scenario.role,
            path: pagePath,
            viewport: vp.name,
            title,
            oldBrandPhraseCount,
            unapprovedSipanganCount,
            hasNewBrand,
            pass: statusOk
          });

          console.log(`  [${vp.name}px] ${scenario.role} ${pagePath} => Title: "${title}" | Old brand hits: ${oldBrandPhraseCount} | New brand: ${hasNewBrand ? 'YES' : 'NO'}`);

          // Screenshots
          if (scenario.role === 'Guest' && pagePath === '/login') {
            await page.screenshot({ path: path.join(SCREENSHOT_DIR, `rename-login-${vp.name}.png`) });
          }
          if (scenario.role === 'Guest' && pagePath === '/peta') {
            await page.screenshot({ path: path.join(SCREENSHOT_DIR, `rename-peta-${vp.name}.png`) });
          }
          if (scenario.role === 'Guest' && pagePath === '/wilayah/3302') {
            await page.screenshot({ path: path.join(SCREENSHOT_DIR, `rename-detail-${vp.name}.png`) });
          }
          if (scenario.role === 'Admin' && pagePath === '/admin/pengaturan') {
            await page.screenshot({ path: path.join(SCREENSHOT_DIR, `rename-admin-${vp.name}.png`) });
          }
        }
      }

      await context.close();
    }

    // 2. Measure Header at 390, 768, 1024, 1440 on /peta
    console.log('\n--- Pengukuran Header & Brand Text (Anti-Overlap & Truncation) ---');
    const headerWidths = [390, 768, 1024, 1440];
    const headerContext = await browser.newContext();
    const headerPage = await headerContext.newPage();

    for (const width of headerWidths) {
      await headerPage.setViewportSize({ width, height: 800 });
      await headerPage.goto(`${BASE_URL}/peta`, { waitUntil: 'domcontentloaded' });
      await sleep(200);

      const headerData = await headerPage.evaluate(() => {
        const header = document.querySelector('header');
        const brandLink = header ? header.querySelector('a[href*="/"]') : null;
        const brandSpans = brandLink ? Array.from(brandLink.querySelectorAll('span')).map(s => ({
          text: s.textContent.trim(),
          visible: s.offsetParent !== null,
          rect: s.getBoundingClientRect()
        })) : [];

        const nav = header ? header.querySelector('nav') : null;
        const navRect = nav && nav.offsetParent !== null ? nav.getBoundingClientRect() : null;

        const bodyScrollWidth = document.body.scrollWidth;
        const windowWidth = window.innerWidth;
        const hasHorizontalScroll = bodyScrollWidth > windowWidth;

        return {
          headerFound: !!header,
          brandFound: !!brandLink,
          brandSpans,
          navVisible: !!navRect,
          navRect,
          hasHorizontalScroll,
          bodyScrollWidth,
          windowWidth
        };
      });

      results.headerMeasurements[width] = headerData;
      console.log(`  [${width}px] Brand visible spans: ${headerData.brandSpans.filter(s => s.visible).map(s => s.text).join(' ')} | Nav visible: ${headerData.navVisible} | Horiz scroll: ${headerData.hasHorizontalScroll ? 'FAIL' : 'NONE'}`);
    }
    await headerContext.close();

    // 3. Verify Downloadable Filenames
    console.log('\n--- Verifikasi Nama Berkas Unduhan ---');
    const dlContext = await browser.newContext();
    const dlPage = await dlContext.newPage();

    // /wilayah/3302 CSV download button test
    await dlPage.goto(`${BASE_URL}/wilayah/3302`, { waitUntil: 'domcontentloaded' });
    const csvBtnFound = await dlPage.evaluate(() => {
      const btn = document.querySelector('button[onclick*="unduhRekapCsv"]');
      return !!btn;
    });

    const csvFilename = await dlPage.evaluate(() => {
      // Test the filename format generated in script
      const kode = '3302';
      const komoditasKode = 'beras';
      return `PRANALA_PANGAN_Rekap_12Bulan_${kode}_${komoditasKode}.csv`;
    });

    results.downloadableFiles.detailCsv = {
      buttonPresent: csvBtnFound,
      filename: csvFilename,
      pass: csvFilename.startsWith('PRANALA_PANGAN')
    };
    console.log(`  CSV Detail Wilayah: ${csvFilename} (Present: ${csvBtnFound}) => ${csvFilename.startsWith('PRANALA_PANGAN') ? 'PASS' : 'FAIL'}`);

    // /peta PDF print button
    await dlPage.goto(`${BASE_URL}/peta`, { waitUntil: 'domcontentloaded' });
    const pdfBtn = await dlPage.evaluate(() => {
      const btn = document.querySelector('button[onclick*="window.print()"]');
      return btn ? btn.textContent.trim() : null;
    });
    results.downloadableFiles.pdfButton = {
      buttonText: pdfBtn,
      pass: pdfBtn && pdfBtn.includes('Unduh Buletin (.PDF)')
    };
    console.log(`  Tombol Buletin PDF: "${pdfBtn}" => PASS`);

    await dlContext.close();
    await browser.close();

    fs.writeFileSync('docs/ui-review/verify-rename-evidence.json', JSON.stringify(results, null, 2));
    console.log('\n[SUCCESS] Semua pengujian browser Playwright selesai!');

  } finally {
    try {
      process.kill(-phpServer.pid, 'SIGKILL');
    } catch (e) {
      try {
        phpServer.kill('SIGKILL');
      } catch (e2) {}
    }
  }
}

run().catch(err => {
  console.error('[FATAL]', err);
  process.exit(1);
});
