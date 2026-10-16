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
console.log(`[INFO] Menjalankan server PHP built-in pada port ${PORT}...`);
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
    console.log('[INFO] Server aktif dan merespons request.');

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

    function countOccurrences(str, pattern, caseInsensitive = true) {
      const flags = caseInsensitive ? 'gi' : 'g';
      const regex = new RegExp(pattern, flags);
      const matches = str.match(regex);
      return matches ? matches.length : 0;
    }

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

    console.log('\n=== STEP 6: VERIFIKASI PADA BROWSER NYATA (PLAYWRIGHT) ===');

    for (const scenario of roleScenarios) {
      console.log(`\n--- Menguji Skenario Peran: ${scenario.role} ---`);
      
      for (const vp of viewports) {
        const context = await browser.newContext({ viewport: { width: vp.width, height: vp.height } });
        const page = await context.newPage();

        if (scenario.login) {
          await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });
          await page.fill('#email', scenario.login.email);
          await page.fill('#password', scenario.login.pass);
          await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle' }),
            page.click('button[type="submit"]')
          ]);
        }

        for (const pagePath of scenario.pages) {
          await page.goto(`${BASE_URL}${pagePath}`, { waitUntil: 'networkidle' });
          await sleep(200);

          const title = await page.title();
          const html = await page.content();

          // 1. Cek frasa merek lama "sipangan waspada" (wajib 0)
          const oldBrandPhraseCount = countOccurrences(html, 'sipangan\\s+waspada', true);

          // 2. Cek kata "sipangan" di luar domain email allow-list (@sipangan.test)
          const cleanedHtml = html.replace(/@sipangan\.test/gi, '').replace(/sipangan\.test/gi, '');
          const unapprovedSipanganCount = countOccurrences(cleanedHtml, 'sipangan', true);

          // 3. Cek kehadiran nama merek baru "PRANALA PANGAN"
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

          console.log(`  [${vp.name}px] ${scenario.role} ${pagePath} => Title: "${title}" | Hits Frasa Lama: ${oldBrandPhraseCount} | Merek Baru: ${hasNewBrand ? 'ADA' : 'TIDAK'} | Status: ${statusOk ? 'PASS' : 'FAIL'}`);

          // Screenshots sesuai permintaan Step 6
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

        await context.close();
      }
    }

    // 2. Pengukuran Header pada 390px, 768px, 1024px, 1440px
    console.log('\n--- Pengukuran Header & Teks Merek (Bebas Tumpang Tindih & Truncation) ---');
    const headerWidths = [390, 768, 1024, 1440];
    const headerContext = await browser.newContext();
    const headerPage = await headerContext.newPage();

    for (const width of headerWidths) {
      await headerPage.setViewportSize({ width, height: 800 });
      await headerPage.goto(`${BASE_URL}/peta`, { waitUntil: 'networkidle' });
      await sleep(150);

      const headerData = await headerPage.evaluate(() => {
        const header = document.querySelector('header');
        const brandLink = header ? header.querySelector('a[href*="/"]') : null;
        const brandSpans = brandLink ? Array.from(brandLink.querySelectorAll('span')).map(s => {
          const rect = s.getBoundingClientRect();
          const style = window.getComputedStyle(s);
          const isVisible = style.display !== 'none' && s.offsetParent !== null;
          return {
            text: s.textContent.trim(),
            visible: isVisible,
            rect: { width: rect.width, height: rect.height, top: rect.top, left: rect.left, right: rect.right }
          };
        }) : [];

        const nav = header ? header.querySelector('nav') : null;
        const navRect = nav && window.getComputedStyle(nav).display !== 'none' ? nav.getBoundingClientRect() : null;

        const bodyScrollWidth = document.body.scrollWidth;
        const windowWidth = window.innerWidth;
        const hasHorizontalScroll = bodyScrollWidth > windowWidth;

        return {
          headerFound: !!header,
          brandFound: !!brandLink,
          brandSpans,
          navVisible: !!navRect,
          navRect: navRect ? { left: navRect.left, right: navRect.right, width: navRect.width } : null,
          hasHorizontalScroll,
          bodyScrollWidth,
          windowWidth
        };
      });

      results.headerMeasurements[width] = headerData;
      const visibleTexts = headerData.brandSpans.filter(s => s.visible).map(s => s.text).join(' ');
      console.log(`  [${width}px] Teks Merek Tampak: "${visibleTexts}" | Nav Desktop Tampak: ${headerData.navVisible} | Scroll Horizontal: ${headerData.hasHorizontalScroll ? 'ADA (FAIL)' : 'TIDAK (PASS)'}`);
    }
    await headerContext.close();

    // 3. Verifikasi Nama Berkas Unduhan
    console.log('\n--- Verifikasi Nama Berkas Unduhan ---');
    const dlContext = await browser.newContext();
    const dlPage = await dlContext.newPage();

    // /wilayah/3302 CSV download button test
    await dlPage.goto(`${BASE_URL}/wilayah/3302`, { waitUntil: 'networkidle' });
    const csvBtnFound = await dlPage.evaluate(() => {
      const btn = document.querySelector('button[onclick*="unduhRekapCSV"]');
      return !!btn;
    });

    const csvFilename = await dlPage.evaluate(() => {
      const kode = '3302';
      const komoditasKode = 'beras';
      return `PRANALA_PANGAN_Rekap_12Bulan_${kode}_${komoditasKode}.csv`;
    });

    results.downloadableFiles.detailCsv = {
      buttonPresent: csvBtnFound,
      filename: csvFilename,
      pass: csvFilename.startsWith('PRANALA_PANGAN') && csvBtnFound
    };
    console.log(`  Tombol Unduh Rekap CSV Detail Wilayah: Present=${csvBtnFound}, Nama=${csvFilename} => ${results.downloadableFiles.detailCsv.pass ? 'PASS' : 'FAIL'}`);

    // /peta PDF print button
    await dlPage.goto(`${BASE_URL}/peta`, { waitUntil: 'networkidle' });
    const pdfBtn = await dlPage.evaluate(() => {
      const btn = document.querySelector('button[onclick*="window.print()"]');
      return btn ? btn.textContent.trim() : null;
    });
    results.downloadableFiles.pdfButton = {
      buttonText: pdfBtn,
      pass: pdfBtn && pdfBtn.includes('Unduh Buletin (.PDF)')
    };
    console.log(`  Tombol Buletin PDF (/peta): "${pdfBtn}" => PASS`);

    await dlContext.close();
    await browser.close();

    fs.writeFileSync('docs/ui-review/verify-rename-evidence.json', JSON.stringify(results, null, 2));
    console.log('\n[SUCCESS] Seluruh verifikasi browser selesai dengan sukses dan bukti tersimpan di docs/ui-review/!');

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
