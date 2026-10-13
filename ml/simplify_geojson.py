#!/usr/bin/env python3
"""
Simplify & Export GeoJSON untuk SIPANGAN WASPADA Map
- Provinsi: Map ke 38 kode BPS/Kemendagri yang cocok 100% dengan database.
- Kabupaten: KDPKAB 'XX.YY' → kode 4-digit 'XXYY', dissolve per kabupaten.
- Koordinat dibulatkan ke 5 desimal agar payload ringan (< 5 detik muat).
- Output: public/geojson/provinsi.json, public/geojson/kabupaten.json
"""

import json
import os
from collections import defaultdict

BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
GEO_SRC = os.path.join(BASE_DIR, 'GeoJson-Indonesia-38-Provinsi')
DST = os.path.join(BASE_DIR, 'public', 'geojson')
os.makedirs(DST, exist_ok=True)

# 1. Peta Nama Provinsi ke Kode BPS (38 Provinsi Indonesia)
PROV_NAME_MAP = {
    'Aceh': ('11', 'Aceh'),
    'Sumatera Utara': ('12', 'Sumatera Utara'),
    'Sumatera Barat': ('13', 'Sumatera Barat'),
    'Riau': ('14', 'Riau'),
    'Jambi': ('15', 'Jambi'),
    'Sumatera Selatan': ('16', 'Sumatera Selatan'),
    'Bengkulu': ('17', 'Bengkulu'),
    'Lampung': ('18', 'Lampung'),
    'Kepulauan Bangka Belitung': ('19', 'Kepulauan Bangka Belitung'),
    'Kepulauan Riau': ('21', 'Kepulauan Riau'),
    'DKI Jakarta': ('31', 'DKI Jakarta'),
    'Jawa Barat': ('32', 'Jawa Barat'),
    'Jawa Tengah': ('33', 'Jawa Tengah'),
    'DI Yogyakarta': ('34', 'DI Yogyakarta'),
    'Daerah Istimewa Yogyakarta': ('34', 'DI Yogyakarta'),
    'Jawa Timur': ('35', 'Jawa Timur'),
    'Banten': ('36', 'Banten'),
    'Bali': ('51', 'Bali'),
    'Nusa Tenggara Barat': ('52', 'Nusa Tenggara Barat'),
    'Nusa Tenggara Timur': ('53', 'Nusa Tenggara Timur'),
    'Kalimantan Barat': ('61', 'Kalimantan Barat'),
    'Kalimantan Tengah': ('62', 'Kalimantan Tengah'),
    'Kalimantan Selatan': ('63', 'Kalimantan Selatan'),
    'Kalimantan Timur': ('64', 'Kalimantan Timur'),
    'Kalimantan Utara': ('65', 'Kalimantan Utara'),
    'Sulawesi Utara': ('71', 'Sulawesi Utara'),
    'Sulawesi Tengah': ('72', 'Sulawesi Tengah'),
    'Sulawesi Selatan': ('73', 'Sulawesi Selatan'),
    'Sulawesi Tenggara': ('74', 'Sulawesi Tenggara'),
    'Gorontalo': ('75', 'Gorontalo'),
    'Sulawesi Barat': ('76', 'Sulawesi Barat'),
    'Maluku': ('81', 'Maluku'),
    'Maluku Utara': ('82', 'Maluku Utara'),
    'Papua Barat': ('91', 'Papua Barat'),
    'Papua Barat Daya': ('92', 'Papua Barat Daya'),
    'Papua Selatan': ('93', 'Papua Selatan'),
    'Papua': ('94', 'Papua'),
    'Papua Tengah': ('95', 'Papua Tengah'),
    'Papua Pegunungan': ('96', 'Papua Pegunungan'),
}


def round_coords(coords, precision=5):
    """Bulatkan koordinat float untuk menghemat ukuran JSON tanpa kehilangan akurasi visual."""
    if not isinstance(coords, list):
        return round(float(coords), precision)
    if not coords:
        return []
    if isinstance(coords[0], (int, float)):
        return [round(float(c), precision) for c in coords]
    return [round_coords(c, precision) for c in coords]


def clean_geometry(geom):
    if not geom:
        return None
    return {
        'type': geom['type'],
        'coordinates': round_coords(geom['coordinates'], precision=5),
    }


def convert_kode_kab(kdpkab: str) -> str:
    """Convert 'XX.YY' to 4-digit string 'XXYY'."""
    parts = kdpkab.strip().split('.')
    if len(parts) == 2:
        code = parts[0].zfill(2) + parts[1].zfill(2)
    else:
        code = kdpkab.strip().replace('.', '').zfill(4)

    # Aliasing untuk kode Papua DOB agar sinkron dengan tabel master DB & sample CSV
    if code == '9101':
        return '9401'  # Merauke
    if code == '9171':
        return '9471'  # Kota Jayapura
    return code


# ── 1. Ekspor GeoJSON Provinsi ──────────────────────────────────────────────
prov_src = os.path.join(GEO_SRC, 'Provinsi', '38 Provinsi Indonesia - Provinsi.json')
with open(prov_src, encoding='utf-8') as f:
    prov_geo = json.load(f)

prov_out = {
    'type': 'FeatureCollection',
    'features': []
}

for feat in prov_geo['features']:
    props = feat['properties']
    raw_nama = str(props.get('PROVINSI', '')).strip()
    kode, nama = PROV_NAME_MAP.get(raw_nama, (str(props.get('KODE_PROV', '')).strip().zfill(2), raw_nama.title()))

    cleaned_geom = clean_geometry(feat.get('geometry'))
    if not cleaned_geom:
        continue

    prov_out['features'].append({
        'type': 'Feature',
        'properties': {
            'kode': kode,
            'nama': nama,
        },
        'geometry': cleaned_geom,
    })

# Urutkan berdasarkan kode provinsi
prov_out['features'].sort(key=lambda x: x['properties']['kode'])

dst_prov = os.path.join(DST, 'provinsi.json')
with open(dst_prov, 'w', encoding='utf-8') as f:
    json.dump(prov_out, f, ensure_ascii=False, separators=(',', ':'))
print(f'✅ Provinsi GeoJSON: {len(prov_out["features"])} features → {dst_prov} ({os.path.getsize(dst_prov) // 1024} KB)')


# ── 2. Ekspor GeoJSON Kabupaten ─────────────────────────────────────────────
kab_src = os.path.join(GEO_SRC, 'Kabupaten', '38 Provinsi Indonesia - Kabupaten.json')
with open(kab_src, encoding='utf-8') as f:
    kab_geo = json.load(f)

kab_groups = defaultdict(lambda: {'nama': '', 'kode_prov': '', 'geometries': []})

for feat in kab_geo['features']:
    props = feat['properties']
    kdpkab = str(props.get('KDPKAB', '') or '').strip()
    if not kdpkab:
        continue
    kode = convert_kode_kab(kdpkab)
    kode_prov = kode[:2]
    nama = str(props.get('WADMKK', '') or '').strip().title()
    geom = feat.get('geometry')
    if geom:
        kab_groups[kode]['nama'] = nama
        kab_groups[kode]['kode_prov'] = kode_prov
        kab_groups[kode]['geometries'].append(geom)


def merge_geometries(geometries):
    """Merge multiple geometries into a single MultiPolygon."""
    if not geometries:
        return None
    all_polys = []
    for geom in geometries:
        if geom is None:
            continue
        gtype = geom.get('type', '')
        coords = geom.get('coordinates', [])
        if gtype == 'Polygon':
            all_polys.append(coords)
        elif gtype == 'MultiPolygon':
            all_polys.extend(coords)
    if not all_polys:
        return None
    if len(all_polys) == 1:
        return {'type': 'Polygon', 'coordinates': round_coords(all_polys[0], 5)}
    return {'type': 'MultiPolygon', 'coordinates': round_coords(all_polys, 5)}


kab_out = {
    'type': 'FeatureCollection',
    'features': []
}

for kode, info in sorted(kab_groups.items()):
    merged = merge_geometries(info['geometries'])
    if merged is None:
        continue
    kab_out['features'].append({
        'type': 'Feature',
        'properties': {
            'kode': kode,
            'kode_prov': info['kode_prov'],
            'nama': info['nama'],
        },
        'geometry': merged,
    })

dst_kab = os.path.join(DST, 'kabupaten.json')
with open(dst_kab, 'w', encoding='utf-8') as f:
    json.dump(kab_out, f, ensure_ascii=False, separators=(',', ':'))
print(f'✅ Kabupaten GeoJSON: {len(kab_out["features"])} features → {dst_kab} ({os.path.getsize(dst_kab) // 1024} KB)')
