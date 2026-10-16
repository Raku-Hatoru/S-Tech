# Pipeline Machine Learning — PRANALA PANGAN

Dokumentasi pelatihan dan integrasi model Machine Learning untuk prediksi ketersediaan pangan di PRANALA PANGAN.

---

## 1. Arsitektur & Prinsip Desain

1. **Decoupled Architecture (Terpisah dari Web Server)**:
   - Laravel **tidak menjalankan training ML secara langsung** di dalam web request untuk mencegah memory exhaustion (OOM), HTTP timeout, dan pemblokiran resource server.
   - Pelatihan model dan inferensi multi-horizon dilakukan secara **offline di Python** di dalam folder `ml/`.

2. **3 Target Model Terpisah**:
   - Model melatih 3 target independen:
     - `panen_kg` (Prediksi hasil panen lokal)
     - `pasokan_masuk_kg` (Prediksi pasokan masuk antar-wilayah)
     - `demand_kg` (Prediksi kebutuhan konsumsi masyarakat)

3. **Perhitungan Supply Bergulir (*Rolling Balance*)**:
   - Sesuai **PRD Bagian 10** & **Rules Bagian 16**, `supply_pred_kg` **TIDAK diprediksi langsung** oleh model ML, melainkan dihitung bergulir:
     $$\text{supply\_pred} = \text{panen\_pred} + \text{pasokan\_masuk\_pred} + \text{stok\_awal\_pred}$$
   - Stok awal bergulir diambil dari sisa stok bulan sebelumnya:
     $$\text{stok\_awal\_pred}_{t} = \max(0, \text{supply\_pred}_{t-1} - \text{demand\_pred}_{t-1})$$

4. **Pencegahan Kebocoran Data (*Data Leakage*)**:
   - Prediksi dilakukan untuk horizon 1 hingga 12 bulan ke depan.
   - Fitur lag yang digunakan dibatasi $\ge 12$ bulan (bulan yang sama pada tahun sebelumnya) agar fitur pada saat inferensi selalu tersedia di masa lalu.

---

## 2. Struktur Direktori

```text
ml/
├── data/
│   └── processed/
│       └── latest.csv          # Dataset gabungan diekspor dari Laravel
├── models/                     # Model XGBoost biner tersimpan (.joblib)
├── outputs/
│   └── forecasts.csv           # File hasil prediksi siap impor ke Laravel
├── requirements.txt            # Dependensi Python (pandas, xgboost, scikit-learn)
├── train.py                    # Pipeline Machine Learning XGBoost
├── train_baseline.py           # Pipeline Baseline Musiman (Python Standard Library murni)
└── README.md                   # Dokumentasi ini
```

---

## 3. Alur Kerja End-to-End

### Langkah 1: Ekspor Dataset dari Laravel
Ekspor seluruh data historis dari tabel database ke format CSV yang dinormalisasi:
```bash
php artisan data:export-ml
```
*Berkas akan tersimpan di: `ml/data/processed/latest.csv` (berisi 3.672 baris data per kabupaten, komoditas, dan bulan).*

---

### Langkah 2: Pelatihan Model di Python

Anda dapat memilih salah satu dari dua metode di bawah ini:

#### Opsi A: Menggunakan Baseline Musiman (Langsung Tanpa Install pip)
Jika environment server Anda belum memiliki `xgboost` atau `pip`, jalankan skrip baseline:
```bash
python3 ml/train_baseline.py
```
*Menggunakan library bawaan Python (`csv`, `math`, `collections`). Menghasilkan prediksi dengan WAPE panen ~6.11%, demand ~2.92%.*

#### Opsi B: Menggunakan Model XGBoost Lengkap
Untuk melatih model regresi non-linear XGBoost dengan fitur musiman trigonometrik dan curah hujan:
1. Buat virtual environment dan pasang dependensi:
   ```bash
   python3 -m venv ml_env
   source ml_env/bin/activate
   pip install -r ml/requirements.txt
   ```
2. Jalankan pelatihan dan inferensi:
   ```bash
   python3 ml/train.py --input ml/data/processed/latest.csv --output ml/outputs/forecasts.csv --model-version v1-2026Q1-xgboost
   ```

Skrip akan:
- Membagi data (Train: 2023–2024, Test: 2025).
- Menghitung metrik evaluasi **WAPE** (Weighted Absolute Percentage Error), **MAE**, dan **RMSE** dibandingkan baseline.
- Menyimpan model di `ml/models/`.
- Menghasilkan prediksi 12 bulan (Januari–Desember 2026) dengan pita ketidakpastian (*uncertainty interval*) ke `ml/outputs/forecasts.csv`.

---

### Langkah 3: Impor Hasil Prediksi ke Laravel

Ada dua cara untuk mengimpor file `forecasts.csv`:

#### Cara 1: Menggunakan Artisan Command (CLI)
```bash
php artisan forecast:import ml/outputs/forecasts.csv
```

#### Cara 2: Melalui Antarmuka Web Admin
1. Buka browser dan login sebagai Admin (`/login`).
2. Masuk ke menu **Dataset & Prediksi** $\rightarrow$ **Kelola Model & Impor Forecast** (`/admin/forecast`).
3. Unggah file `ml/outputs/forecasts.csv` dan pilih versi model aktif.

---

### Langkah 4: Hitung Ulang Status Wilayah
Setelah data prediksi masuk, perbarui agregasi rasio kecukupan dan status peringatan dini seluruh wilayah:
```bash
php artisan status:recalc
```
Hasil prediksi langsung dapat diakses oleh publik di halaman `/peta` dan API `/api/status`.
