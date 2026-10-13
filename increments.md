| # | Increment | Target tanggal | Prioritas |
|---|---|---|---|
| 1 | Fondasi proyek & database | 11–12 Okt | Must |
| 2 | Autentikasi & role | 12 Okt | Must |
| 3 | Impor data & prediksi | 12 Okt | Must |
| 4 | Mesin status & API publik | 13 Okt | Must |
| 5 | Peta heatmap & detail wilayah | 13–14 Okt | Must |
| 6 | Pengiriman & tracking | 15 Okt | Must |
| 7 | Modul BUMDes, jadwal tanam, panel admin | 16 Okt | Must / Could |
| 8 | Finalisasi, deploy, video demo | 17–18 Okt | Must |

## Increment 1: Fondasi proyek & database
- **Isi:** Install Laravel dan atur locale `id`, timezone `Asia/Jakarta`, serta `.gitignore` (termasuk `public/build`). Siapkan Tailwind + Vite dan layout dasar Blade. Cek versi PHP dan MySQL di CloudPanel. Buat 16 migrasi sesuai urutan di `databaseschema.md`, lalu model, enum, dan cast. Buat seeder master (`Role`, `Komoditas`, `Wilayah`, `Setting`).
- **Selesai bila:** `php artisan migrate:fresh --seed` jalan tanpa galat di lokal.

## Increment 2: Autentikasi & role
- **Isi:** Login/logout, middleware `EnsureRole`, penolakan akun `is_active = false`, rate limit login. Navigasi berbeda per role, plus `DemoUserSeeder`. Kelola pengguna versi dasar untuk Admin (buat dan nonaktifkan akun).
- **Selesai bila:** Tiga role bisa login, dan non-admin yang membuka `/admin/*` mendapat 403.

## Increment 3: Impor data & prediksi
- **Isi:** `DataHistorisImporter`, `ForecastImporter`, command `data:import-historis` dan `forecast:import`, serta logging ke `dataset_imports`. Tambah halaman unggah Admin (`/admin/dataset`, `/admin/forecast`).
- **Jalur paralel (ML):** bersihkan dataset 2023–2025, latih XGBoost, hasilkan `forecasts.csv`.
- **Jaga-jaga:** kalau model belum siap, buat `forecasts.csv` sementara dari baseline "bulan sama tahun lalu". Dengan begitu increment 4 sampai 6 tidak menunggu ML.
- **Selesai bila:** Data historis dan forecast masuk ke DB, kode berawalan nol utuh, dan baris yang gagal tercatat.

## Increment 4: Mesin status & API publik
- **Isi:** `StatusService` (rumus rasio, ambang dari `settings`, agregasi provinsi), `NumberFormatter`, dan `status:recalc`. Buat `/api/status` dan `/api/wilayah/{kode}/seri` dengan cache. Tulis tes batas ambang (0,70 / 0,90 / 1,00 / `NULL`).
- **Selesai bila:** API mengembalikan status per provinsi dan kabupaten, dan tes lolos.

## Increment 5: Peta heatmap & detail wilayah
Ini bagian paling berat, jadi mulai dari uji join kode GeoJSON.
- **Isi:** Sederhanakan GeoJSON dan uji join kode wilayah lebih dulu. Lalu Leaflet dengan layer provinsi, kabupaten dimuat lazy, filter komoditas dan bulan, legenda, tooltip, dan abu-abu untuk tanpa data. Panel detail dengan grafik Chart.js, pesan dampak, label "stok estimasi" dan versi model. Terakhir, fallback tabel wilayah.
- **Selesai bila:** Status satu kabupaten terlihat dalam ≤ 3 klik dan peta termuat < 5 detik.

## Increment 6: Pengiriman & tracking
Ini inti alur demo.
- **Isi:** Form buat pengiriman, endpoint `/api/wilayah/{kode}/cek`, dan banner Waspada/Penuh (non-blocking, dengan disclaimer). Server menghitung ulang `level_peringatan` sendiri. Dashboard distributor dengan pencarian resi, detail dengan riwayat, dan tambah event (status + riwayat dalam satu transaksi). `ShipmentPolicy`, plus `recalc` asal dan tujuan.
- **Selesai bila:** Alur peta → buat pengiriman → banner → lacak resi jalan, dan distributor lain tidak bisa melihat resi orang.

## Increment 7: Modul BUMDes, jadwal tanam, panel admin
- **Isi:** Form panen dan sisa stok (satu laporan per bulan, otomatis ke kabupaten akun), rekap tabel dan grafik, panen aktual vs prediksi. Hitung ulang bulan itu dan bulan berikutnya. Jadwal tanam dummy berlabel "Simulasi / data contoh". Panel admin untuk ambang, teks, riwayat impor, dan versi model.
- **Kalau waktu mepet:** potong jadwal tanam (Could) dan panel admin (Should) lebih dulu. Modul panen BUMDes tetap harus selesai.
- **Selesai bila:** Laporan BUMDes mengubah status kabupaten dan menghapus penanda "stok estimasi".

## Increment 8: Finalisasi, deploy, video demo
- **Isi:** `DemoShipmentSeeder`, termasuk satu pengiriman ke wilayah Penuh agar banner tampil di video. Lanjutkan dengan tes prioritas (policy, importer, pengiriman) dan rapikan tampilan HP. Pastikan label data demo ada, ganti kata sandi akun demo, lalu jalankan checklist di Bagian 17 `rules.md`. Deploy ke CloudPanel (SSL, cron, `public/build`), ekspor dump DB, dan rekam video demo. 18 Okt dipakai sebagai buffer.
- **Selesai bila:** Situs hidup di server, alur demo di PRD bagian 16 bisa direkam dari awal sampai akhir.