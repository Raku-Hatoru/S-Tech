#!/usr/bin/env python3
"""
SIPANGAN WASPADA — Baseline Seasonal Pipeline (Standard Library Only)
Model baseline berbasis rata-rata musiman historis (Historical Seasonal Average).
Dapat dijalankan langsung dengan Python 3 murni tanpa dependensi eksternal (pandas/numpy/xgboost).

Fitur:
- Perhitungan rolling balance stok pangan bulanan.
- Menghasilkan ml/outputs/forecasts.csv yang siap diimpor ke Laravel.
- Evaluasi metrik WAPE (Weighted Absolute Percentage Error).
"""

import os
import sys
import csv
import math
import argparse
from datetime import datetime
from collections import defaultdict


def calculate_wape(actuals, predictions):
    sum_actual = sum(actuals)
    if sum_actual == 0:
        return 0.0
    sum_err = sum(abs(a - p) for a, p in zip(actuals, predictions))
    return sum_err / sum_actual


def main():
    parser = argparse.ArgumentParser(description="Latih model baseline musiman & hasilkan forecasts.csv")
    parser.add_argument("--input", default="ml/data/processed/latest.csv", help="Path berkas CSV data historis")
    parser.add_argument("--output", default="ml/outputs/forecasts.csv", help="Path berkas CSV keluaran prediksi")
    parser.add_argument("--model-version", default="v1-2026Q1-baseline", help="Nama versi model")
    args = parser.parse_args()

    if not os.path.exists(args.input):
        print(f"❌ Berkas data {args.input} tidak ditemukan.")
        print("💡 Jalankan terlebih dahulu: php artisan data:export-ml")
        sys.exit(1)

    print(f"📂 Membaca dataset dari: {args.input}")
    records = []
    with open(args.input, mode="r", encoding="utf-8-sig") as f:
        reader = csv.DictReader(f)
        for row in reader:
            records.append(row)

    print(f"   Total baris data dibaca: {len(records):,}")

    # Struktur agregasi per (kode_kabupaten, komoditas, bulan)
    # Target: panen_kg, pasokan_masuk_kg, demand_kg
    seasonal_data = defaultdict(lambda: {
        'panen': defaultdict(list),
        'pasokan': defaultdict(list),
        'demand': defaultdict(list),
        'latest_record': None
    })

    # Evaluasi WAPE pada tahun 2025 menggunakan data latih <= 2024
    eval_actuals = {'panen': [], 'pasokan': [], 'demand': []}
    eval_preds = {'panen': [], 'pasokan': [], 'demand': []}

    # Urutkan berdasarkan periode
    records.sort(key=lambda r: (r['kode_kabupaten'], r['komoditas'], r['periode']))

    for row in records:
        kode_kab = str(row['kode_kabupaten']).strip().zfill(2)
        komoditas = row['komoditas'].strip().lower()
        periode = row['periode'].strip()
        tahun = int(periode[:4])
        bulan = int(periode[5:7])

        panen = float(row['panen_kg'] or 0.0)
        pasokan = float(row['pasokan_masuk_kg'] or 0.0)
        demand = float(row['demand_kg'] or 0.0)

        key = (kode_kab, komoditas)
        seasonal_data[key]['latest_record'] = row

        if tahun <= 2024:
            seasonal_data[key]['panen'][bulan].append(panen)
            seasonal_data[key]['pasokan'][bulan].append(pasokan)
            seasonal_data[key]['demand'][bulan].append(demand)
        elif tahun == 2025:
            # Uji prediksi baseline (rata-rata tahun sebelumnya)
            prev_panen = seasonal_data[key]['panen'][bulan]
            prev_pasokan = seasonal_data[key]['pasokan'][bulan]
            prev_demand = seasonal_data[key]['demand'][bulan]

            pred_p = sum(prev_panen) / len(prev_panen) if prev_panen else panen
            pred_pas = sum(prev_pasokan) / len(prev_pasokan) if prev_pasokan else pasokan
            pred_d = sum(prev_demand) / len(prev_demand) if prev_demand else demand

            eval_actuals['panen'].append(panen)
            eval_preds['panen'].append(pred_p)
            eval_actuals['pasokan'].append(pasokan)
            eval_preds['pasokan'].append(pred_pas)
            eval_actuals['demand'].append(demand)
            eval_preds['demand'].append(pred_d)

            # Tetap masukkan ke data latih untuk prediksi masa depan
            seasonal_data[key]['panen'][bulan].append(panen)
            seasonal_data[key]['pasokan'][bulan].append(pasokan)
            seasonal_data[key]['demand'][bulan].append(demand)

    print("\n📊 Hasil Evaluasi WAPE Model Baseline Musiman (Uji Validasi 2025):")
    for target in ['panen', 'pasokan', 'demand']:
        wape = calculate_wape(eval_actuals[target], eval_preds[target])
        print(f"  Target: {target:<18} | WAPE: {wape * 100:6.2f}%")

    # 3. Prediksi 12 Bulan ke Depan (Tahun 2026, Horizon 1–12)
    print("\n🚀 Menghasilkan Prediksi 12 Bulan ke Depan (Horizon 1–12)...")
    periode_dasar = '2025-12'
    created_at = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    forecast_rows = []

    for (kode_kab, komoditas), info in seasonal_data.items():
        last_rec = info['latest_record']
        last_supply = float(last_rec['supply_kg'] or 0.0)
        last_demand = float(last_rec['demand_kg'] or 0.0)

        sisa_terakhir = max(0.0, last_supply - last_demand)
        stok_awal_rolling = min(sisa_terakhir, last_demand * 1.5)

        for horizon in range(1, 13):
            target_month = horizon
            target_year = 2026
            periode_target = f"{target_year}-{target_month:02d}"

            list_p = info['panen'][target_month]
            list_pas = info['pasokan'][target_month]
            list_d = info['demand'][target_month]

            # Rata-rata musiman
            panen_pred = max(0.0, sum(list_p) / len(list_p) if list_p else 0.0)
            pasokan_pred = max(0.0, sum(list_pas) / len(list_pas) if list_pas else 0.0)
            demand_pred = max(1.0, sum(list_d) / len(list_d) if list_d else 100000.0)

            # Supply dihitung secara bergulir (BUKAN diprediksi langsung)
            supply_pred = panen_pred + pasokan_pred + stok_awal_rolling
            rasio_pred = round(supply_pred / demand_pred, 4)

            # Penentuan level status sesuai aturan PRD
            if rasio_pred < 0.70:
                status_pred = 'defisit'
            elif rasio_pred >= 1.00:
                status_pred = 'penuh'
            elif rasio_pred >= 0.90:
                status_pred = 'waspada'
            else:
                status_pred = 'seimbang'

            # Uncertainty interval melebar seiring bertambahnya horizon
            uncertainty = round(0.02 + (horizon * 0.015), 4)
            rasio_bawah = max(0.0, round(rasio_pred - uncertainty, 4))
            rasio_atas = round(rasio_pred + uncertainty, 4)

            forecast_rows.append({
                'kode_kabupaten': kode_kab,
                'komoditas': komoditas,
                'periode_dasar': periode_dasar,
                'periode_target': periode_target,
                'horizon_bulan': horizon,
                'panen_pred_kg': round(panen_pred, 2),
                'pasokan_masuk_pred_kg': round(pasokan_pred, 2),
                'stok_awal_pred_kg': round(stok_awal_rolling, 2),
                'supply_pred_kg': round(supply_pred, 2),
                'demand_pred_kg': round(demand_pred, 2),
                'rasio_pred': rasio_pred,
                'status_pred': status_pred,
                'rasio_bawah': rasio_bawah,
                'rasio_atas': rasio_atas,
                'versi_model': args.model_version,
                'dibuat_pada': created_at
            })

            # Rolling balance untuk bulan berikutnya
            sisa_bulan_ini = max(0.0, supply_pred - demand_pred)
            stok_awal_rolling = min(sisa_bulan_ini, demand_pred * 1.5)

    os.makedirs(os.path.dirname(args.output), exist_ok=True)
    fieldnames = [
        'kode_kabupaten', 'komoditas', 'periode_dasar', 'periode_target',
        'horizon_bulan', 'panen_pred_kg', 'pasokan_masuk_pred_kg',
        'stok_awal_pred_kg', 'supply_pred_kg', 'demand_pred_kg',
        'rasio_pred', 'status_pred', 'rasio_bawah', 'rasio_atas',
        'versi_model', 'dibuat_pada'
    ]

    with open(args.output, mode="w", encoding="utf-8", newline="") as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames)
        writer.writeheader()
        writer.writerows(forecast_rows)

    print(f"\n✅ Prediksi berhasil disimpan ke: {args.output}")
    print(f"   Total baris prediksi: {len(forecast_rows):,} baris")
    print(f"   Model Version: {args.model_version}")
    print("\n💡 Langkah selanjutnya: Impor hasil ini ke Laravel menggunakan:")
    print(f"   php artisan forecast:import {args.output}")
    print("   php artisan status:recalc")


if __name__ == '__main__':
    main()
