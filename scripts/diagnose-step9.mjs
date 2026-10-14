import { chromium } from 'playwright';
import { spawn } from 'child_process';

const PORT = '8099';
const BASE_URL = `http://127.0.0.1:${PORT}`;

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

async function runDiagnosis() {
  await startServerIfNeeded();

  const browser = await chromium.launch({
    executablePath: '/usr/bin/chromium',
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  });

  try {
    const context = await browser.newContext({
      viewport: { width: 1440, height: 900 }
    });
    const page = await context.newPage();

    // Track console and failed network requests
    page.on('console', msg => {
      if (msg.type() === 'error' || msg.type() === 'warning') {
        console.log(`[BROWSER ${msg.type().toUpperCase()}] ${msg.text()}`);
      }
    });

    page.on('requestfailed', req => {
      console.log(`[NET FAILED] ${req.method()} ${req.url()} - ${req.failure()?.errorText}`);
    });

    console.log(`[NAVIGATING] ${BASE_URL}/peta`);
    await page.goto(`${BASE_URL}/peta`, { waitUntil: 'networkidle' });

    // Wait 3 seconds for Leaflet map to load
    await page.waitForTimeout(3000);

    const diag = await page.evaluate(() => {
      const mapEl = document.getElementById('peta-map');
      const mapCard = document.getElementById('map-card');
      const header = document.querySelector('header');
      const modal = document.getElementById('modal-tentang-data');

      const mapStyles = mapEl ? window.getComputedStyle(mapEl) : null;
      const cardStyles = mapCard ? window.getComputedStyle(mapCard) : null;
      const headerStyles = header ? window.getComputedStyle(header) : null;
      const modalStyles = modal ? window.getComputedStyle(modal) : null;

      const mapRect = mapEl ? mapEl.getBoundingClientRect() : null;
      const cardRect = mapCard ? mapCard.getBoundingClientRect() : null;
      const headerRect = header ? header.getBoundingClientRect() : null;

      const polygons = Array.from(document.querySelectorAll('#peta-map path.leaflet-interactive'));
      const polygonBoxes = polygons.map(p => {
        const r = p.getBoundingClientRect();
        return {
          left: r.left,
          top: r.top,
          right: r.right,
          bottom: r.bottom,
          width: r.width,
          height: r.height,
          fill: p.getAttribute('fill'),
          stroke: p.getAttribute('stroke')
        };
      });

      // Check how many polygons intersect or lie outside mapRect
      let insideCount = 0;
      let outsideCount = 0;
      const outsidePolygons = [];

      if (mapRect) {
        for (const pb of polygonBoxes) {
          // Check if bounding box intersects mapRect
          const intersects = !(pb.right < mapRect.left || pb.left > mapRect.right || pb.bottom < mapRect.top || pb.top > mapRect.bottom);
          if (intersects) {
            insideCount++;
          } else {
            outsideCount++;
            outsidePolygons.push(pb);
          }
        }
      }

      // Check Leaflet internal panes
      const panes = Array.from(document.querySelectorAll('#peta-map .leaflet-pane')).map(pane => {
        const s = window.getComputedStyle(pane);
        return {
          className: pane.className,
          zIndex: s.zIndex,
          position: s.position,
        };
      });

      // Pilot polygons colored
      const coloredPilots = polygonBoxes.filter(pb => pb.fill && pb.fill.toLowerCase() !== '#64748b' && pb.fill.toLowerCase() !== '#3388ff');

      // Leaflet global object / map size
      let leafletAvailable = typeof window.L !== 'undefined';
      let mapObjDetails = null;
      // In Blade, let's see how map is stored
      if (window._leaflet_map) {
        mapObjDetails = {
          size: window._leaflet_map.getSize(),
          zoom: window._leaflet_map.getZoom(),
        };
      }

      return {
        leafletAvailable,
        mapObjDetails,
        mapStyles: mapStyles ? {
          position: mapStyles.position,
          overflow: mapStyles.overflow,
          zIndex: mapStyles.zIndex,
          isolation: mapStyles.isolation,
          width: mapStyles.width,
          height: mapStyles.height,
        } : null,
        cardStyles: cardStyles ? {
          position: cardStyles.position,
          overflow: cardStyles.overflow,
          zIndex: cardStyles.zIndex,
          isolation: cardStyles.isolation,
          width: cardStyles.width,
          height: cardStyles.height,
        } : null,
        headerStyles: headerStyles ? {
          position: headerStyles.position,
          zIndex: headerStyles.zIndex,
        } : null,
        modalStyles: modalStyles ? {
          position: modalStyles.position,
          zIndex: modalStyles.zIndex,
          display: modalStyles.display,
        } : null,
        mapRect,
        cardRect,
        headerRect,
        totalPolygons: polygons.length,
        coloredPilotsCount: coloredPilots.length,
        insideCount,
        outsideCount,
        outsidePolygonsSample: outsidePolygons.slice(0, 5),
        panes,
      };
    });

    console.log('\n=== DIAGNOSIS RESULT ===');
    console.log(JSON.stringify(diag, null, 2));

    await context.close();
  } finally {
    await browser.close();
    if (serverProcess) {
      serverProcess.kill();
      console.log('[INFO] Server stopped.');
    }
  }
}

runDiagnosis().catch(console.error);
