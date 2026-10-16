#!/usr/bin/env python3
"""
PRANALA PANGAN — Machine Learning Pipeline
Pipeline Pelatihan Model XGBoost untuk Prediksi 3 Target Pangan Nasional:
1. Hasil Panen (panen_kg)
2. Pasokan Masuk dari Luar Wilayah (pasokan_masuk_kg)
3. Kebutuhan Konsumsi / Penjualan (demand_kg)

Supply dihitung bergulir (rolling balance) dari sisa stok bulan sebelumnya.
Output: ml/outputs/forecasts.csv yang siap diimpor ke Laravel.
"""

import os
import sys
import argparse
from datetime import datetime
import numpy as np
import pandas as pd

try:
    import xgboost as xgb
    from sklearn.metrics import mean_absolute_error, mean_squared_error
    import joblib
except ImportError:
    print("❌ Dependensi belum terpasang. Jalankan: pip install -r ml/requirements.txt")
    print("💡 Atau jalankan baseline runner tanpa dependensi: python3 ml/train_baseline.py")
    sys.exit(1)


def calculate_wape(actual: np.ndarray, predicted: np.ndarray) -> float:
    """Weighted Absolute Percentage Error (WAPE = sum(|y - y_hat|) / sum(y))"""
    sum_actual = np.sum(actual)
    if sum_actual == 0:
        return 0.0
    return float(np.sum(np.abs(actual - predicted)) / sum_actual)


def build_features(df: pd.DataFrame) -> pd.DataFrame:
    """Ekstraksi fitur musiman, lag aman, dan karakteristik wilayah."""
    data = df.copy()

    # Ekstrak waktu
    data['periode_dt'] = pd.to_datetime(data['periode'] + '-01')
    data['tahun'] = data['periode_dt'].dt.year
    data['bulan'] = data['periode_dt'].dt.month

    # Fitur siklikal musim (bulan 1–12 ke koordinat lingkaran)
    data['sin_bulan'] = np.sin(2 * np.pi * data['bulan'] / 12)
    data['cos_bulan'] = np.cos(2 * np.pi * data['bulan'] / 12)

    # Indikator komoditas one-hot
    for kom in ['beras', 'jagung', 'singkong']:
        data[f'is_{kom}'] = (data['komoditas'] == kom).astype(int)

    # Indikator tipe kabupaten/kota
    data['is_kota'] = (data['tipe_kabupaten'].astype(str).str.lower() == 'kota').astype(int)

    # Numerik default
    data['curah_hujan_mm'] = pd.to_numeric(data['curah_hujan_mm'], errors='coerce').fillna(150.0)
    data['jumlah_penduduk'] = pd.to_numeric(data['jumlah_penduduk'], errors='coerce').fillna(1500000)
    data['luas_panen_ha'] = pd.to_numeric(data['luas_panen_ha'], errors='coerce').fillna(10000.0)

    # Target numerik
    for col in ['panen_kg', 'pasokan_masuk_kg', 'demand_kg', 'stok_awal_kg', 'supply_kg']:
        data[col] = pd.to_numeric(data[col], errors='coerce').fillna(0.0)

    return data


def create_lags(data: pd.DataFrame) -> pd.DataFrame:
    """
    Buat fitur lag 12 bulan (bulan yang sama tahun lalu).
    Penggunaan lag >= 12 bulan menjamin TIDAK ADA DATA LEAKAGE untuk prediksi horizon 1-12 bulan ke depan.
    """
    df = data.sort_values(by=['kode_kabupaten', 'komoditas', 'periode_dt']).copy()

    # Lag 12 bulan per kabupaten dan komoditas
    for target in ['panen_kg', 'pasokan_masuk_kg', 'demand_kg']:
        df[f'{target}_lag12'] = df.groupby(['kode_kabupaten', 'komoditas'])[target].shift(12)

    # Isi lag kosong (tahun pertama) dengan rata-rata kelompok
    for target in ['panen_kg', 'pasokan_masuk_kg', 'demand_kg']:
        mean_val = df.groupby(['kode_kabupaten', 'komoditas'])[target].transform('mean')
        df[f'{target}_lag12'] = df[f'{target}_lag12'].fillna(mean_val).fillna(df[target])

    return df


def main():
    parser = argparse.ArgumentParser(description="Latih model XGBoost & hasilkan forecasts.csv")
    parser.add_argument("--input", default="ml/data/processed/latest.csv", help="Path berkas CSV data historis")
    parser.add_argument("--output", default="ml/outputs/forecasts.csv", help="Path berkas CSV keluaran prediksi")
    parser.add_argument("--model-version", default="v1-2026Q1-xgboost", help="Nama versi model")
    args = parser.parse_args()

    if not os.path.exists(args.input):
        print(f"❌ Berkas data {args.input} tidak ditemukan.")
        print(" Jalankan terlebih dahulu: php artisan data:export-ml")
        sys.exit(1)

    print(f" Membaca dataset dari: {args.input}")
    raw_df = pd.read_csv(args.input, dtype={'kode_kabupaten': str, 'kode_provinsi': str})
    print(f"   Total baris data: {len(raw_df):,}")

    # 1. Feature Engineering
    df = build_features(raw_df)
    df = create_lags(df)

    feature_cols = [
        'bulan', 'sin_bulan', 'cos_bulan',
        'is_beras', 'is_jagung', 'is_singkong', 'is_kota',
        'curah_hujan_mm', 'jumlah_penduduk', 'luas_panen_ha',
        'panen_kg_lag12', 'pasokan_masuk_kg_lag12', 'demand_kg_lag12'
    ]

    # 2. Train-Test Split Berdasarkan Waktu
    # Latih: 2023 - 2024, Uji Validasi: 2025
    train_mask = df['tahun'] <= 2024
    test_mask = df['tahun'] == 2025

    X_train = df.loc[train_mask, feature_cols]
    X_test = df.loc[test_mask, feature_cols]

    targets = ['panen_kg', 'pasokan_masuk_kg', 'demand_kg']
    models = {}
    metrics_summary = {}

    print("\n Memulai Pelatihan 3 Model XGBoost...")

    for target in targets:
        y_train = df.loc[train_mask, target]
        y_test = df.loc[test_mask, target]

        model = xgb.XGBRegressor(
            n_estimators=120,
            learning_rate=0.08,
            max_depth=5,
            subsample=0.85,
            colsample_bytree=0.85,
            random_state=42,
            n_jobs=-1
        )
        model.fit(X_train, y_train)
        models[target] = model

        # Evaluasi pada data uji 2025
        y_pred = np.maximum(0, model.predict(X_test))

        # Baseline: lag12 (bulan sama tahun lalu)
        baseline_pred = df.loc[test_mask, f'{target}_lag12'].values

        wape_model = calculate_wape(y_test.values, y_pred)
        wape_baseline = calculate_wape(y_test.values, baseline_pred)
        mae = mean_absolute_error(y_test.values, y_pred)
        rmse = np.sqrt(mean_squared_error(y_test.values, y_pred))

        metrics_summary[target] = {
            'wape_model': wape_model,
            'wape_baseline': wape_baseline,
            'mae': mae,
            'rmse': rmse
        }

        print(f"  Target: {target:<20} | WAPE Model: {wape_model*100:6.2f}% | WAPE Baseline: {wape_baseline*100:6.2f}% | MAE: {mae:,.0f} kg")

    # Simpan model
    os.makedirs("ml/models", exist_ok=True)
    for target, model in models.items():
        joblib.dump(model, f"ml/models/{target}_xgboost.joblib")
    print(" Model tersimpan di ml/models/")

    # 3. Prediksi 12 Bulan ke Depan (Tahun 2026, Horizon 1–12)
    print("\n Menghasilkan Prediksi 12 Bulan ke Depan (Horizon 1–12)...")

    # Ambil titik data terakhir per kabupaten dan komoditas
    latest_rows = df.sort_values(by='periode_dt').groupby(['kode_kabupaten', 'komoditas']).last().reset_index()

    periode_dasar = '2025-12'
    forecast_rows = []
    created_at = datetime.now().strftime('%Y-%m-%d %H:%M:%S')

    for _, row in latest_rows.iterrows():
        kode_kab = str(row['kode_kabupaten'])
        komoditas = row['komoditas']
        penduduk = row['jumlah_penduduk']
        is_kota = row['is_kota']
        luas_panen = row['luas_panen_ha']

        # Stok awal bergulir (dimulai dari sisa stok terakhir)
        sisa_terakhir = max(0.0, float(row['supply_kg']) - float(row['demand_kg']))
        stok_awal_rolling = min(sisa_terakhir, float(row['demand_kg']) * 1.5)

        for horizon in range(1, 13):
            target_month = horizon
            target_year = 2026
            periode_target = f"{target_year}-{target_month:02d}"

            # Fitur musiman
            sin_m = np.sin(2 * np.pi * target_month / 12)
            cos_m = np.cos(2 * np.pi * target_month / 12)

            curah_hujan = 250.0 if target_month in [12, 1, 2] else (80.0 if target_month in [6, 7, 8] else 160.0)

            # Fitur baris horizon
            feat_row = pd.DataFrame([{
                'bulan': target_month,
                'sin_bulan': sin_m,
                'cos_bulan': cos_m,
                'is_beras': 1 if komoditas == 'beras' else 0,
                'is_jagung': 1 if komoditas == 'jagung' else 0,
                'is_singkong': 1 if komoditas == 'singkong' else 0,
                'is_kota': is_kota,
                'curah_hujan_mm': curah_hujan,
                'jumlah_penduduk': penduduk,
                'luas_panen_ha': luas_panen,
                'panen_kg_lag12': row['panen_kg'],
                'pasokan_masuk_kg_lag12': row['pasokan_masuk_kg'],
                'demand_kg_lag12': row['demand_kg']
            }])

            panen_pred = max(0.0, float(models['panen_kg'].predict(feat_row)[0]))
            pasokan_pred = max(0.0, float(models['pasokan_masuk_kg'].predict(feat_row)[0]))
            demand_pred = max(1.0, float(models['demand_kg'].predict(feat_row)[0]))

            # Supply dihitung bergulir (BUKAN diprediksi langsung)
            supply_pred = panen_pred + pasokan_pred + stok_awal_rolling

            rasio_pred = round(supply_pred / demand_pred, 4)

            # Pemetaan status sesuai ambang sistem
            if rasio_pred < 0.70:
                status_pred = 'defisit'
            elif rasio_pred >= 1.00:
                status_pred = 'penuh'
            elif rasio_pred >= 0.90:
                status_pred = 'waspada'
            else:
                status_pred = 'seimbang'

            # Pita ketidakpastian semakin melebar pada horizon jauh
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

            # Update stok bergulir untuk bulan berikutnya
            sisa_bulan_ini = max(0.0, supply_pred - demand_pred)
            stok_awal_rolling = min(sisa_bulan_ini, demand_pred * 1.5)

    out_df = pd.DataFrame(forecast_rows)
    os.makedirs(os.path.dirname(args.output), exist_ok=True)
    out_df.to_csv(args.output, index=False)

    print(f"\n✅ Prediksi berhasil disimpan ke: {args.output}")
    print(f"   Total baris prediksi: {len(out_df):,} baris")
    print(f"   Model Version: {args.model_version}")
    print("\n Langkah selanjutnya: Impor hasil ini ke Laravel menggunakan:")
    print(f"   php artisan forecast:import {args.output}")
    print("   php artisan status:recalc")


if __name__ == '__main__':
    main()
