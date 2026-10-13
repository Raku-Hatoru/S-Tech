# rules.md — Aturan Pengembangan Laravel

**Proyek:** Sistem Pemantauan & Early-Warning Surplus Pangan (Web)

| Item | Detail |
|---|---|
| **Sumber** | `prd.md` v1.1 · `databaseschema.md` v1.0 · `infrastruktur.md` v1.0 · `uml.md` v1.0 |
| **Tanggal** | 11 Oktober 2026 |
| **Target MVP** | 18 Oktober 2026 (prototipe + video demo) |
| **Tim** | 1 developer (web + ML), 1 ideation, 1 presenter |

> **Penanda.** Aturan **tanpa penanda** berasal langsung dari dokumen sumber.
> **[Saran]** = tambahan praktik Laravel dari penyusun rules ini, bukan keputusan tim.
> **[Cek]** = dokumen sumber belum final; jangan di-hardcode, buat mudah diubah atau tanyakan dulu.

---

## 0. Cara Memakai Dokumen Ini

1. File ini adalah ringkasan aturan yang harus dipatuhi saat menulis kode. Detail lengkap ada di empat dokumen sumber.
2. Bila dokumen sumber bertentangan: **aturan bisnis → `prd.md`**, **nama tabel & kolom → `databaseschema.md`**, **stack, struktur folder, deployment → `infrastruktur.md`**, **alur → `uml.md`**. Catat pertentangannya, jangan menebak diam-diam.
3. Jangan menambah fitur di luar F-01 s.d. F-13. Bila waktu habis, pangkas dengan urutan **Could → Should**, pertahankan **Must**.
4. Alur utama yang tidak boleh rusak: **peta → buat pengiriman → banner info → tracking resi**.
5. Jangan mengubah nama tabel, kolom, nilai status, atau path endpoint tanpa memperbarui dokumen sumber.

---

## 1. Ringkasan Produk & Stack

Web **pemantauan** keseimbangan supply–demand pangan (beras, jagung, singkong) per kabupaten/kota se-Indonesia. Prediksi bulanan 12 bulan ke depan, status wilayah (Defisit / Seimbang / Waspada / Penuh) tampil di peta heatmap. Distributor melacak pengiriman (gaya JNE), BUMDes menginput panen dan sisa stok. **Bukan** aplikasi jual-beli.

| Lapisan | Pilihan |
|---|---|
| Framework | **Laravel**, versi stabil terbaru yang cocok dengan PHP di server (umumnya PHP ≥ 8.2) **[Cek]** |
| Database | **MySQL** (atau MariaDB bila bawaan CloudPanel), InnoDB, `utf8mb4` **[Cek versi]** |
| Tampilan | Blade + Tailwind CSS |
| Build aset | Vite, **di-build di laptop**; server tidak punya Node |
| Peta | Leaflet + GeoJSON provinsi/kabupaten (tanpa tile basemap eksternal) |
| Grafik | Chart.js |
| Auth | Session auth bawaan Laravel; middleware `role` + Policy |
| Cache / sesi / antrean | `cache=database`, `session=database`, `queue=sync` (tanpa Redis, tanpa worker) |
| Penjadwalan | Laravel Scheduler, dipanggil cron tiap menit |
| ML | Python + XGBoost, **offline** di folder `ml/`, hasilnya CSV yang diimpor ke Laravel |
| Hosting | CloudPanel (Nginx + PHP-FPM), satu situs + satu database, **tanpa staging** |

---

## 2. Batasan Produk — JANGAN Dilanggar

- ❌ Tidak ada jual-beli, marketplace, pembayaran, atau transaksi apa pun.
- ❌ Tidak ada notifikasi di luar web: jangan pakai `Mail`, `Notification` (channel mail/SMS/WA/push), atau `Broadcasting`.
- ❌ Tidak ada data atau prediksi **harga**.
- ❌ Tidak ada real-time: tanpa websocket, tanpa polling, tanpa GPS live. Status pengiriman terbarui **saat halaman di-refresh**.
- ❌ Tidak ada integrasi API ekspedisi. Nomor resi diinput sendiri oleh distributor; status dan riwayat diisi manual.
- ❌ **Jangan memblokir pengiriman.** Wilayah Waspada/Penuh hanya memunculkan banner info; pengguna boleh lanjut setelah konfirmasi.
- ❌ Tidak ada registrasi mandiri (tidak ada route `/register`). Akun dibuat **Admin**. Petani tidak punya akun.
- ❌ Resi dan data pengiriman **tidak boleh publik**; tidak ada pencarian resi publik.
- ❌ Tidak ada aplikasi mobile native (cukup web responsif).
- ❌ Tidak ada retraining dari UI (F-14 = Won't). Jangan memanggil Python dari Laravel (`exec`, `Process`, dsb.).
- ❌ Tidak ada ketergantungan layanan eksternal saat runtime: tanpa tile peta pihak ketiga; aset front-end di-bundle lewat Vite, bukan CDN **[Saran]**.
- ❌ Jangan membuat tabel `planting_calendar` (jadwal tanam dibuat dummy).
- ❌ Jangan memperlakukan data demo/dummy seolah data asli; wajib diberi label.

---

## 3. Peran & Hak Akses

Kode role (tabel `roles.kode`): `distributor`, `bumdes`, `admin`.

| Fitur | Publik | Distributor | BUMDes | Admin |
|---|---|---|---|---|
| Peta, detail wilayah, info peringatan, grafik prediksi | ✅ | ✅ | ✅ | ✅ |
| Buat pengiriman | ❌ | ✅ | ❌ | ❌ |
| Lihat / lacak pengiriman | ❌ | milik sendiri | ❌ | semua |
| Tambah status/riwayat pengiriman | ❌ | milik sendiri | ❌ | ✅ (koreksi) |
| Input panen & sisa stok, rekap | ❌ | ❌ | kabupaten sendiri | semua |
| Rekomendasi jadwal tanam (dummy) | ❌ | ❌ | ✅ | ✅ |
| Kelola user, dataset, prediksi, ambang | ❌ | ❌ | ❌ | ✅ |

Aturan:
- **Satu akun BUMDes = satu kabupaten.** `users.kabupaten_id` wajib terisi untuk BUMDes (validasi di aplikasi saat Admin membuat/mengubah user).
- Untuk BUMDes, `kabupaten_id` **selalu diambil dari akun yang login**, tidak pernah dari input request. Hanya Admin yang boleh memilih kabupaten bebas.
- Login ditolak bila `users.is_active = false`.
- Otorisasi dua lapis: middleware `role:` untuk akses halaman, **Policy** untuk kepemilikan data (`ShipmentPolicy`, `HarvestReportPolicy`).
- Hak akses tidak boleh hanya disembunyikan di UI; selalu dicek di server.

---

## 4. Struktur Proyek & Konvensi

```
nama-proyek/
├─ app/
│  ├─ Console/Commands/    ImportDataHistoris, ImportForecast, ExportDataForMl, RecalcStatusWilayah
│  ├─ Enums/               StatusLevel, ShipmentStatus                       [Saran]
│  ├─ Http/
│  │  ├─ Controllers/      Peta, Wilayah, Pengiriman(Shipment), Panen(HarvestReport), JadwalTanam, Admin/*
│  │  ├─ Middleware/       EnsureRole
│  │  └─ Requests/         Form Request (semua validasi form)
│  ├─ Models/              User, Role, Provinsi, Kabupaten, Komoditas, DataHistoris, Forecast,
│  │                       StatusWilayah, Shipment, ShipmentEvent, HarvestReport,
│  │                       PlantingRecommendation, Setting, ModelVersion, DatasetImport
│  ├─ Policies/            ShipmentPolicy, HarvestReportPolicy
│  └─ Services/            StatusService, ForecastImporter, DataHistorisImporter, NumberFormatter
├─ database/               migrations, seeders
├─ resources/views/        layouts, peta, pengiriman, panen, admin
├─ resources/js/           app.js, peta.js, chart.js
├─ public/geojson/         provinsi.json, kabupaten.json (sudah disederhanakan)
├─ storage/app/imports/    CSV unggahan (TIDAK publik)
└─ ml/                     pipeline Python offline (data/, src/, models/, outputs/)
```

### Aturan penamaan & arsitektur
- **Controller tipis.** Logika bisnis ada di `Services`; validasi di Form Request; otorisasi di Policy/middleware.
- Nama tabel, kolom, dan path URL **tetap seperti di dokumen** (campuran Indonesia–Inggris sudah disengaja: master data berbahasa Indonesia tunggal `provinsi`, `kabupaten`, `komoditas`, `data_historis`, `status_wilayah`; sisanya `users`, `roles`, `forecasts`, `shipments`, `shipment_events`, `harvest_reports`, dst.).
- Nama method service mengikuti `uml.md`: `hitungRasio`, `tentukanLevel`, `cekWilayah`, `recalc`, `recalcSemua`, `agregasiProvinsi`.
- Teks antarmuka, pesan validasi, dan label **Bahasa Indonesia**. Komentar kode boleh Indonesia.
- Nilai status memakai **PHP backed enum** yang nilainya identik dengan kolom DB **[Saran]**:

```php
namespace App\Enums;

enum StatusLevel: string
{
    case Defisit   = 'defisit';
    case Seimbang  = 'seimbang';
    case Waspada   = 'waspada';
    case Penuh     = 'penuh';
    case TanpaData = 'tanpa_data';   // dipakai di status_wilayah & shipments.level_peringatan
}

enum ShipmentStatus: string
{
    case Dibuat = 'dibuat'; case DiterimaDiAsal = 'diterima_di_asal'; case Diproses = 'diproses';
    case DalamPerjalanan = 'dalam_perjalanan'; case TibaDiTujuan = 'tiba_di_tujuan';
    case Diterima = 'diterima'; case Dibatalkan = 'dibatalkan';
}
```

- Route role (alias `role` didaftarkan sesuai versi Laravel; di Laravel 11+ lewat `bootstrap/app.php`):

```php
Route::middleware(['auth', 'role:distributor'])->group(function () {
    Route::get('/pengiriman/buat', [ShipmentController::class, 'create'])->name('pengiriman.create');
    Route::post('/pengiriman', [ShipmentController::class, 'store'])->name('pengiriman.store');
});
Route::middleware(['auth', 'role:distributor,admin'])->get('/pengiriman', [ShipmentController::class, 'index']);
```

- **Jangan** memakai closure di file route (merusak `php artisan route:cache` di production).
- Endpoint `/api/*` ditaruh di `routes/web.php` dengan prefix `api` karena same-origin dan butuh sesi (mis. `/api/wilayah/{kode}/cek` untuk distributor) **[Saran]**.

---

## 5. Database, Migrasi & Eloquent

### 5.1 Aturan umum
- **Satuan: kg**, disimpan apa adanya. Konversi ke ton hanya saat tampilan.
- Kolom kg → `DECIMAL(16,2)`; rasio → `DECIMAL(8,4)`; `curah_hujan_mm` → `DECIMAL(7,2)`; `luas_panen_ha` → `DECIMAL(12,2)`.
- **`NULL` = data tidak diketahui; `0` = benar-benar nol.** Jangan mengubah `NULL` menjadi `0` kecuali `pasokan_masuk_kg` kosong saat impor → `0`.
- **Periode disimpan sebagai `DATE` tanggal 1** (`2026-02-01`), bukan teks `YYYY-MM`. Normalisasi dengan `->startOfMonth()`.
- **Kode wilayah disimpan sebagai teks** (`provinsi.kode CHAR(2)`, `kabupaten.kode CHAR(4)`) agar angka nol di depan tidak hilang, dan harus **sama persis** dengan GeoJSON dan CSV.
- Semua `foreignId` **wajib menyebut nama tabel**, karena nama tabel master bukan jamak Inggris:
  `$table->foreignId('kabupaten_id')->constrained('kabupaten')->restrictOnDelete();`
- Default FK `restrictOnDelete()`. Pengecualian: `shipment_events.shipment_id` → `cascadeOnDelete()`, `forecasts.model_version_id` → `nullOnDelete()`.
- Nilai ENUM huruf kecil, persis seperti di `databaseschema.md`.
- Urutan migrasi: `roles` → `provinsi` → `kabupaten` → `komoditas` → `users` (migrasi bawaan diubah: tambah `role_id`, `kabupaten_id`, `organisasi`, `telepon`, `is_active`) → `model_versions` → `data_historis` → `forecasts` → `status_wilayah` → `shipments` → `shipment_events` → `harvest_reports` → `planting_recommendations` → `settings` → `dataset_imports` → `audit_logs` (opsional). Atur nama/tanggal berkas migrasi agar urutan ini benar.
- Tabel bawaan Laravel tetap dipakai: `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `failed_jobs`.
- `audit_logs` prioritas Could; tunda bila waktu mepet.

### 5.2 Kunci unik (wajib ada, dipakai oleh `upsert`)

| Tabel | Unique key | Nama indeks |
|---|---|---|
| `data_historis` | `kabupaten_id, komoditas_id, periode` | `uq_historis` |
| `forecasts` | `kabupaten_id, komoditas_id, periode_dasar, periode_target` | `uq_forecast` |
| `status_wilayah` | `kabupaten_id, komoditas_id, periode` | `uq_status` |
| `harvest_reports` | `kabupaten_id, komoditas_id, periode` | `uq_laporan_bulanan` |
| `shipments` | `no_resi` | — |

### 5.3 Model Eloquent
- Set `protected $table` **eksplisit** untuk `Provinsi`, `Kabupaten`, `Komoditas`; dan juga `DataHistoris` serta `StatusWilayah` karena penjamakan otomatis Laravel salah untuk kata Indonesia **[Saran]**.
- Cast: `periode` / `periode_dasar` / `periode_target` → `date`; kolom `*_kg` → `decimal:2`; `rasio*` → `decimal:4`; kolom status → enum yang sesuai; kolom JSON (`metrik`, `parameter`) → `array`.
- Gunakan `$fillable` eksplisit; hindari `$guarded = []` **[Saran]**.
- Relasi wajib: `Shipment` → `user`, `komoditas`, `asal`, `tujuan`, `events`; `User` → `role`, `kabupaten`; `Kabupaten` → `provinsi`.
- `User` punya helper `isAdmin()`, `isBumdes()`, `isDistributor()`.
- `ShipmentEvent` ditampilkan urut `waktu_kejadian` **menurun** (terbaru di atas).
- `AppServiceProvider` (non-production): `Model::preventLazyLoading()` dan `Model::shouldBeStrict()` untuk menangkap N+1 sejak dini **[Saran]**.
- `upsert`/`insert` massal **tidak memicu event model**. Karena itu perhitungan ulang status dipanggil **eksplisit** dari controller/importer (lihat 6.3), bukan lewat Observer **[Saran]**.

---

## 6. Logika Inti

### 6.1 Rumus

```
stok_awal(t)  = sisa_stok laporan BUMDes (t−1)                 → stok_estimasi = false
                jika belum ada → max(0, supply(t−1) − demand(t−1))   → stok_estimasi = true
supply(t)     = panen(t) + stok_awal(t) + pasokan_masuk(t)
supply_tampil = supply(t)
                + (panen_aktual − panen_prediksi)        # hanya jika BUMDes sudah lapor panen bulan t
                + pengiriman_masuk − pengiriman_keluar   # dari shipments (non-dibatalkan, bulan tanggal_kirim)
rasio(t)      = supply_tampil / demand(t)                # NULL bila demand NULL atau 0 → tanpa_data
```

- **Pasokan masuk** = pasokan dari luar kabupaten/kota (bukan impor luar negeri).
- **Supply tidak diprediksi langsung.** Untuk periode prediksi, supply sudah dihitung bergulir oleh pipeline Python; Laravel hanya membaca `forecasts.*_pred_kg`.
- Pengiriman yang tercatat di aplikasi adalah **tambahan** di atas `pasokan_masuk` dasar dari model (hindari hitung ganda). Pengiriman keluar mengurangi kabupaten asal, pengiriman masuk menambah kabupaten tujuan.
- Urutan prioritas stok awal **[Cek]**: laporan BUMDes (t−1) → `data_historis.stok_awal_kg` bila tidak `NULL` → hitungan cadangan. Dokumen belum menyebut eksplisit prioritas `data_historis` vs laporan BUMDes pada bulan yang sama.

### 6.2 Ambang status (dari tabel `settings`, bukan hardcode)

| Status | Rasio | Perilaku |
|---|---|---|
| `defisit` | `< 0,70` | Info wilayah membutuhkan pasokan + dampak kekurangan |
| `seimbang` | `0,70 – < 0,90` | Tanpa peringatan |
| `waspada` | `0,90 – < 1,00` | Banner **kuning** |
| `penuh` | `≥ 1,00` | Banner **merah** + dampak kelebihan pasokan |
| `tanpa_data` | demand `NULL`/`0` | **Abu-abu**, tanpa banner |

Kunci `settings`: `ambang_defisit_max` (0.7000), `ambang_waspada_min` (0.9000), `ambang_penuh_min` (1.0000), `unit_switch_kg` (2000), `teks_disclaimer`, `pesan_dampak_surplus`, `pesan_dampak_defisit`.

```php
public function tentukanLevel(?string $rasio): StatusLevel
{
    if ($rasio === null) return StatusLevel::TanpaData;
    return match (true) {
        bccomp($rasio, Setting::nilai('ambang_defisit_max'), 4) < 0 => StatusLevel::Defisit,
        bccomp($rasio, Setting::nilai('ambang_waspada_min'), 4) < 0 => StatusLevel::Seimbang,
        bccomp($rasio, Setting::nilai('ambang_penuh_min'),   4) < 0 => StatusLevel::Waspada,
        default => StatusLevel::Penuh,
    };
}
```

- Warna: Waspada = kuning, Penuh = merah, Tanpa data = abu-abu (**ditetapkan PRD**). Warna Defisit dan Seimbang **belum ditetapkan [Cek]**. Definisikan semua warna di **satu tempat** (mis. method pada enum), jangan tersebar.
- Saat Admin mengubah ambang, validasi urutan `defisit_max < waspada_min < penuh_min` dan semuanya `> 0` **[Saran]**.

### 6.3 `StatusService` — satu-satunya sumber kebenaran status

- Semua perhitungan rasio/status **hanya** di `StatusService`. Jangan menduplikasi ambang di Blade, JavaScript, atau SQL `CASE` dengan angka tetap. JavaScript hanya **menampilkan** `status` dari API.
- Susun sebagai tahapan berurutan (sesuai Composite Structure di `uml.md`): `SumberDasar` → `StokResolver` → `PanenCorrector` → `PengirimanAggregator` → `RatioCalculator` → `LevelMapper`.
- **Sumber dasar:** periode ≤ data aktual terakhir → `data_historis` (`sumber = aktual`); setelahnya → `forecasts` dengan `periode_dasar` terbesar (`sumber = prediksi`). Definisikan "data aktual terakhir" (mis. `MAX(periode)` di `data_historis`) di satu tempat saja **[Saran]**.
- **Pengiriman dihitung** bila `status <> 'dibatalkan'` dan `tanggal_kirim` jatuh di bulan itu. Definisi "pengiriman aktif" final **[Cek]**; bungkus dalam satu method agar mudah diubah.
- **Agregasi provinsi:** `SUM(supply_kg) / NULLIF(SUM(demand_kg), 0)` dari `status_wilayah` yang `status <> 'tanpa_data'`, lalu level dipetakan lewat `tentukanLevel()` yang sama.
- Hitung ulang **harus dipanggil eksplisit** pada pemicu berikut:

| Pemicu | Cakupan hitung ulang |
|---|---|
| Impor `forecasts.csv` | Semua kabupaten × komoditas × bulan target |
| Impor `data_historis` | Periode yang terdampak |
| BUMDes simpan/ubah laporan | Kabupaten + komoditas + bulan itu **dan bulan berikutnya** |
| Pengiriman dibuat / diubah / dibatalkan | Kabupaten **asal dan tujuan**, bulan `tanggal_kirim` |
| Admin mengubah ambang | Semua |

- Setelah hitung ulang, **buang cache** `/api/status` terkait (lihat Bagian 11).
- Hasil ditulis dengan `upsert` ke `status_wilayah` (kunci `uq_status`) dan mengisi `dihitung_pada`.

### 6.4 `NumberFormatter` (tampilan kg/ton)

Data tetap kg. Aturan tampilan (peta, tooltip, tabel, grafik):

| Input (kg) | Tampilan |
|---|---|
| `1850` | `1.850 kg` |
| `2000` | `2.000 kg` (batas ≤ 2.000 masih kg) |
| `2500` | `2,5 ton` |
| `18300000` | `18.300,0 ton` |
| `NULL` | `—` / "Tidak ada data" (**bukan** `0 kg`) |

- Format Indonesia: **titik** untuk ribuan, **koma** untuk desimal. kg → bilangan bulat; ton → 1 desimal.
- Batas ambil dari `settings.unit_switch_kg`.
- Pada **satu grafik**, satuan sumbu ditentukan **sekali** dari nilai terbesar (tidak boleh campur kg dan ton).
- **Form input** (BUMDes, distributor) tetap dalam **kg**.
- Rasio ditampilkan sebagai persen format Indonesia, mis. `1.0511` → `105,11%`.
- Implementasi dengan `number_format($v, 1, ',', '.')`, **jangan** bergantung ekstensi `intl` (tidak ada di daftar ekstensi server) dan hati-hati bentrok nama dengan `\NumberFormatter` bawaan PHP; selalu `use App\Services\NumberFormatter;` **[Saran]**.

### 6.5 Perhitungan angka
- Nilai `decimal:2` dari Eloquent berupa **string**. Untuk penjumlahan/pembagian gunakan `bcmath` (`bcadd`, `bcdiv` dengan scale 4) atau agregasi SQL; jangan membandingkan float dengan `==` **[Saran]**.
- Kode wilayah adalah string. Kunci array numerik (`"3302"`) otomatis menjadi `int` di PHP, jadi cast ke `(string)` saat membandingkan dengan `===` **[Saran]**.

---

## 7. Aturan per Modul

### 7.1 Endpoint

**Publik (tanpa login)**

| Method | Path | Fungsi |
|---|---|---|
| GET | `/` | Peta heatmap + filter komoditas & bulan |
| GET | `/wilayah/{kode}` | Detail wilayah (grafik, status, dampak). `kode` provinsi 2 digit atau kabupaten 4 digit **[Cek]** |
| GET | `/api/status` | Query `komoditas`, `periode`, `level=provinsi\|kabupaten` |
| GET | `/api/wilayah/{kode}/seri` | Deret waktu aktual vs prediksi |
| GET | `/geojson/provinsi.json`, `/geojson/kabupaten.json` | File statis |

**Terautentikasi**

| Method | Path | Peran | Fungsi |
|---|---|---|---|
| GET/POST | `/login`, `/logout` | Semua | Masuk/keluar (logout wajib `POST`) |
| GET | `/pengiriman` | Distributor, Admin | Daftar (milik sendiri / semua) + cari resi |
| GET/POST | `/pengiriman/buat`, `/pengiriman` | Distributor | Buat pengiriman |
| GET | `/api/wilayah/{kode}/cek` | Distributor | Status tujuan untuk banner (non-blocking) |
| GET | `/pengiriman/{id}` | Pemilik, Admin | Detail & riwayat |
| POST | `/pengiriman/{id}/event` | Pemilik, Admin | Tambah status/riwayat manual |
| GET/POST | `/panen` (+ update) | BUMDes, Admin | Form panen & sisa stok, rekap |
| GET | `/jadwal-tanam` | BUMDes, Admin | Rekomendasi (dummy) |
| GET/POST | `/admin/pengguna` | Admin | Kelola akun |
| POST | `/admin/dataset`, `/admin/forecast` | Admin | Unggah & impor CSV |
| POST | `/admin/status/recalc` | Admin | Hitung ulang status |
| GET/POST | `/admin/pengaturan` | Admin | Ambang, disclaimer, pesan dampak |

Gunakan route model binding untuk `{id}` pengiriman. Endpoint publik hanya mengembalikan data **agregat per wilayah**; tidak boleh ada data user, resi, atau pengiriman.

### 7.2 Peta & detail wilayah (F-05, F-06)
- Zoom rendah → warna per **provinsi**; zoom tinggi → per **kabupaten/kota**. Ambang zoom ± 7–8 **[Cek]**; muat `kabupaten.json` secara *lazy*.
- Penggabungan `/api/status` ke GeoJSON dilakukan **di klien** berdasarkan **kode wilayah**.
- Wilayah tanpa data atau tidak muncul di hasil API → **abu-abu**.
- Ada legenda, filter komoditas & bulan, tooltip, dan klik → panel detail. Status satu kabupaten harus bisa dicapai ≤ 3 klik dari peta.
- **Fallback:** bila peta bermasalah, tampilkan tabel wilayah berwarna.
- Peta tanpa tile eksternal (hanya poligon GeoJSON). Target peta termuat **< 5 detik**.
- Panel detail menampilkan: grafik aktual vs prediksi (dengan rentang `rasio_bawah`/`rasio_atas` bila ada), status, pesan dampak, **versi model** dan tanggal pembaruan prediksi. Bulan yang jauh ditandai sebagai "gambaran".
- Beri penanda **"stok estimasi"** bila `status_wilayah.stok_estimasi = true`.
- Pesan dampak dibaca dari `settings` dan berupa penjelasan umum **tanpa klaim angka** kecuali ada sumber valid.

### 7.3 Pengiriman & Tracking (F-07, F-08)
1. Distributor memilih komoditas + kabupaten tujuan → klien memanggil `/api/wilayah/{kode}/cek`. Bulan yang dicek = bulan `tanggal_kirim` (default bulan berjalan) **[Saran]**.
2. Banner menurut level tujuan:
   - `waspada` / `penuh` → banner: status, rasio keterisian, pesan dampak, dan teks `settings.teks_disclaimer` ("Sistem ini bukan layanan penjualan, hanya layanan pemantauan dan tracking.").
   - `defisit` → info wilayah membutuhkan pasokan.
   - `seimbang` / `tanpa_data` → tanpa banner.
3. Saat `POST /pengiriman`: **hitung ulang level tujuan di server**. `level_peringatan` dan `rasio_tujuan` **tidak boleh** diambil dari input klien.
4. Bila level `waspada`/`penuh`, wajib `konfirmasi_peringatan = true` (sejalan dengan "boleh melanjutkan setelah mengonfirmasi"); di luar itu **tidak ada** pemblokiran **[Saran]**.
5. `no_resi`: diinput distributor, **unik** (validasi `unique:shipments,no_resi`), maks 60 karakter.
6. Setelah simpan: panggil `StatusService::recalc` untuk kabupaten **asal dan tujuan**, lalu redirect ke dashboard.
7. Tambah riwayat (`POST /pengiriman/{id}/event`): otorisasi `ShipmentPolicy::update` (pemilik atau admin) → dalam **satu transaksi DB** `INSERT shipment_events` + `UPDATE shipments.status`. Bila status menjadi `dibatalkan` → `recalc` asal & tujuan.
8. `shipment_events.status` (VARCHAR) menyimpan nilai `ShipmentStatus`; `dicatat_oleh` = user yang login.
9. Alur status (contoh gaya JNE, **[Cek]**): `dibuat → diterima_di_asal → diproses → dalam_perjalanan → tiba_di_tujuan → diterima`; `dibatalkan` boleh sebelum `diterima`. `diterima` dan `dibatalkan` adalah status akhir. Simpan aturan transisi di satu tempat agar mudah diubah; Admin boleh mengoreksi.
10. Distributor hanya melihat pengirimannya: **selalu** scope `where('user_id', auth()->id())`, termasuk saat mencari resi.

### 7.4 BUMDes: panen & sisa stok (F-09)
- Field: kabupaten (otomatis dari akun), komoditas, periode (bulan), `panen_kg`, `sisa_stok_kg` (sisa **akhir bulan**, hasil survei), `tanggal_survei` (opsional), `catatan` (opsional).
- **Satu laporan per bulan per komoditas per kabupaten**: simpan dengan `updateOrCreate`/`upsert` pada `(kabupaten_id, komoditas_id, periode)`; perubahan lewat edit.
- Validasi: angka ≥ 0, periode valid (diubah ke tanggal 1), komoditas valid.
- Otorisasi `HarvestReportPolicy`: BUMDes hanya untuk `kabupaten_id` akunnya; Admin semua.
- Sesudah simpan: `recalc` untuk bulan itu **dan bulan berikutnya**, lalu tampilkan rekap + status kabupaten.
- Rekap: tabel + grafik per bulan & komoditas, termasuk **panen aktual vs prediksi**.

### 7.5 Rekomendasi jadwal tanam (F-10, Could)
- **Dummy/simulasi.** Halaman wajib berlabel **"Simulasi / data contoh"**. Data dari `planting_recommendations` (`is_dummy = 1`).
- Tanpa perhitungan agronomi sungguhan. Tampilkan bulan tanam disarankan + bulan panen diharapkan per komoditas untuk kabupaten BUMDes.

### 7.6 Admin (F-11)
- Kelola user (buat, ubah, nonaktifkan lewat `is_active`), unggah dataset historis, impor prediksi, hitung ulang status, atur ambang & teks.
- Tampilkan **riwayat impor** (`dataset_imports`) dan **versi model aktif** (`model_versions.is_aktif`).
- Hanya satu `model_versions` yang `is_aktif = 1` pada satu waktu **[Saran]**.

---

## 8. Impor Data & Artisan Commands

| Command | Fungsi |
|---|---|
| `data:import-historis {file}` | CSV → `data_historis` (+ upsert `provinsi`, `kabupaten`) |
| `forecast:import {file}` | `forecasts.csv` → `forecasts` (+ `model_versions`) |
| `data:export-ml` | `data_historis` + laporan BUMDes → `ml/data/processed/latest.csv` |
| `status:recalc {--periode=}` | Isi ulang `status_wilayah` |

Aturan impor (berlaku untuk command **dan** halaman admin, lewat service yang sama):
- Impor besar dijalankan lewat **CLI**, bukan browser; proses dengan `chunk` untuk menghindari timeout.
- Pemetaan CSV → DB: `YYYY-MM` → `YYYY-MM-01`; sel kosong → `NULL`; `pasokan_masuk_kg` kosong → `0`; `komoditas` dicari lewat `komoditas.kode`; `kode_kabupaten` dicari lewat `kabupaten.kode`.
- Baca kolom kode wilayah **sebagai teks** (jangan `(int)`), agar `01xx` tidak kehilangan nol.
- Validasi per baris: `komoditas` ∈ {`beras`,`jagung`,`singkong`}; periode valid; `kode_kabupaten` ada di master; nilai tidak negatif; tidak ada duplikat kunci; bila `supply_kg` terisi harus sama dengan `panen + stok_awal + pasokan_masuk` (toleransi pembulatan kecil); `horizon_bulan` 1–12 untuk forecast.
- Gunakan **upsert** pada kunci unik. **Jangan menghapus** forecast lama (`periode_dasar` sebelumnya disimpan sebagai riwayat evaluasi).
- `versi_model` di CSV → `firstOrCreate` di `model_versions.nama`.
- Catat setiap impor di `dataset_imports`: `berjalan` → `selesai` / `gagal`, dengan `jumlah_baris`, `jumlah_berhasil`, `jumlah_gagal`, `ringkasan_galat` (contoh baris/kode yang gagal). Baris gagal tidak boleh menghentikan seluruh impor kecuali galat fatal.
- Setelah impor sukses → `recalc` sesuai tabel pemicu (6.3) → buang cache.
- **Unggahan CSV:** hanya Admin; validasi tipe (`csv,txt`) dan ukuran maksimum; simpan di `storage/app/imports` (disk privat, **bukan** `public`).

---

## 9. Frontend

- Blade + Tailwind; **responsif** untuk browser HP; seluruh teks Bahasa Indonesia. Nama bulan memakai locale `id` (`APP_LOCALE=id`).
- Keluaran Blade memakai `{{ }}` (escape otomatis). `{!! !!}` hanya untuk HTML yang sepenuhnya dikontrol aplikasi, **tidak pernah** untuk input pengguna.
- Setiap form: `@csrf`; request JS same-origin menyertakan token CSRF bila memakai method non-GET.
- JavaScript terpisah per fitur di `resources/js` (`peta.js`, `chart.js`, `app.js`), di-bundle Vite. Hasil `npm run build` (`public/build`) ikut di-deploy.
- Cek penggabungan GeoJSON ↔ `/api/status` **sejak awal** (uji join kode wilayah sebelum membangun fitur lain).
- Label wajib: **"stok estimasi"**, **"Simulasi / data contoh"**, versi model & tanggal pembaruan prediksi, dan penanda data demo.

---

## 10. Keamanan

| Aspek | Aturan |
|---|---|
| Transport | HTTPS wajib; paksa redirect HTTP→HTTPS (mis. `URL::forceHttps()` di production **[Saran]**) |
| Kata sandi | Hash bawaan Laravel (`Hash::make` / cast `hashed`). **Akun demo wajib diganti kata sandinya** sebelum dipublikasikan; jangan menaruh kata sandi lemah di server publik |
| CSRF / XSS | Proteksi bawaan Laravel; escaping Blade |
| Otorisasi | Middleware `role` + Policy; cek di server, bukan hanya UI |
| Mass assignment | `$fillable` eksplisit; `role_id`, `kabupaten_id`, `is_active` **tidak boleh** bisa diisi dari form non-admin |
| Data publik | Hanya peta, detail wilayah, agregat prediksi. **Resi dan pengiriman tidak publik** |
| Privasi | Data penjualan hanya tampil **agregat** per kabupaten; pengiriman & akun sensitif |
| Rate limit | `throttle` pada `/login` dan endpoint `/api/*` |
| Unggahan | Hanya Admin; batasi tipe & ukuran; simpan privat; validasi sebelum dimuat |
| Rahasia | `.env` tidak di-commit; `APP_DEBUG=false` di production; `.env` tidak boleh terjangkau publik (document root = `public/`) |
| Log | `LOG_CHANNEL=daily`; catat impor (`dataset_imports`) dan perubahan penting (opsional `audit_logs`) |

---

## 11. Performa & Cache

- Peta cukup **satu query sederhana** ke `status_wilayah` (hasil dihitung di muka). Jangan menghitung rasio saat request.
- Prediksi **tidak** dihitung saat halaman dibuka; web hanya membaca dari DB.
- Cache respons `/api/status` per kombinasi `(komoditas, periode, level)`; **dibuang** saat `status:recalc`, saat laporan BUMDes berubah, dan saat pengiriman dibuat/diubah/dibatalkan.
- Driver cache `database`/`file` **tidak mendukung `Cache::tags()`**. Pakai kunci berversi (mis. `status:{versi}:{komoditas}:{periode}:{level}` dengan `versi` dinaikkan saat invalidasi) atau `Cache::forget` per kunci **[Saran]**.
- Cache pembacaan `settings` (ambang) dan buang saat Admin menyimpan pengaturan **[Saran]**.
- Eager loading (`with()`) untuk relasi di daftar; pilih kolom yang dibutuhkan saja.
- GeoJSON sudah disederhanakan (± 1–5 MB), gzip aktif di Nginx, kabupaten dimuat lazy.
- Skala data (ringan untuk MySQL): `data_historis` ± 55 ribu baris, `forecasts` ± 18 ribu baris per pengiriman bulanan, `status_wilayah` ± 75 ribu baris.

---

## 12. Konfigurasi & Lingkungan

```dotenv
APP_NAME="<Nama Produk>"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<domain>
APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=<nama_db>
DB_USERNAME=<user_db>
DB_PASSWORD=<isi_sendiri>

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync
LOG_CHANNEL=daily

UNIT_SWITCH_KG=2000
STATUS_DEFISIT_MAX=0.70
STATUS_WASPADA_MIN=0.90
STATUS_PENUH_MIN=1.00
```

- Nilai ambang dan `UNIT_SWITCH_KG` di `.env` **hanya nilai awal untuk seeder**. Nilai yang berlaku ada di tabel `settings` (bisa diubah Admin).
- Panggil `env()` **hanya** di `config/*.php`; di tempat lain pakai `config()` (karena `config:cache` membuat `env()` bernilai `null`) **[Saran]**. Contoh: `config/pangan.php` membaca env dan dipakai `SettingSeeder`.
- Pesan validasi Bahasa Indonesia: publikasikan berkas bahasa (`php artisan lang:publish`) lalu sediakan `lang/id` **[Saran]**.
- `.gitignore` wajib memuat: `.env`, `ml/data`, `ml/models`, `storage/app/imports`.
- **Perhatian:** `.gitignore` bawaan Laravel mengabaikan `/public/build`. Karena server tidak punya Node, hapus baris itu dari `.gitignore` (atau unggah `public/build` manual saat deploy) **[Saran]**.
- Dataset mentah, `forecasts.csv`, `metrik.json`, dan model besar **tidak** di-commit dan dicadangkan di luar server.

---

## 13. Deployment (CloudPanel)

- Document root situs → folder **`public/`**. Pilih versi PHP sesuai syarat Laravel; ekstensi aktif: `mbstring`, `xml`, `curl`, `zip`, `pdo_mysql`, `bcmath`, `fileinfo`. Buat user database khusus (bukan root). Aktifkan SSL (Let's Encrypt).
- `storage/` dan `bootstrap/cache/` harus dapat ditulis oleh user PHP-FPM situs.

**Deploy pertama**
```bash
git clone <repo-privat> .
composer install --no-dev --optimize-autoloader
cp .env.example .env            # lalu isi sesuai Bagian 12
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force     # master data + akun dummy
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

**Update berikutnya**
```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
Bila aset berubah: `npm run build` di laptop, lalu commit/unggah `public/build`.

**Cron (user situs, tiap menit)**
```
* * * * * php /home/<user>/htdocs/<domain>/artisan schedule:run >> /dev/null 2>&1
```

- `mysqldump` manual sebelum deploy besar; ekspor dump terakhir sebelum demo/pengumpulan.
- Endpoint kesehatan `/up` tersedia pada versi Laravel yang mendukung **[Cek]**.
- Siapkan **video demo** dan dump database sebagai cadangan bila server bermasalah saat demo.

---

## 14. Seeder & Data Demo

| Seeder | Isi |
|---|---|
| `RoleSeeder` | `distributor`, `bumdes`, `admin` |
| `KomoditasSeeder` | `beras`, `jagung`, `singkong` |
| `WilayahSeeder` | Provinsi & kabupaten/kota dari master kode wilayah (**sama dengan GeoJSON**) |
| `SettingSeeder` | Ambang, `teks_disclaimer`, pesan dampak (nilai awal dari `config/pangan.php`) |
| `DemoUserSeeder` | 1 admin, 2–3 distributor, 3–5 BUMDes (tiap BUMDes terikat 1 kabupaten) — **dummy** |
| `DemoShipmentSeeder` | Beberapa pengiriman dummy + riwayat, termasuk **satu ke wilayah berstatus Penuh** agar banner terlihat di video demo |
| `PlantingRecommendationSeeder` | Data contoh jadwal tanam (dummy) |

- Seeder master (`Role`, `Komoditas`, `Wilayah`, `Setting`) bersifat **idempoten** (`updateOrCreate`/`upsert`) agar aman dijalankan ulang **[Saran]**.
- Seeder demo dipisah dari seeder master; kata sandi akun demo dibaca dari konfigurasi/`.env`, **bukan** ditulis lemah di kode **[Saran]**.

---

## 15. Pengujian

Waktu terbatas (1 developer, ± 7 hari): **uji yang paling berisiko dulu**, jangan mengejar cakupan penuh **[Saran]**.

1. **`StatusService::tentukanLevel`** — nilai batas: `0.6999`, `0.70`, `0.8999`, `0.90`, `0.9999`, `1.00`, dan `NULL` → `tanpa_data`.
2. **Rasio** — demand `NULL` dan `0` → `NULL`/`tanpa_data` (tanpa pembagian nol).
3. **`NumberFormatter`** — `1850`, `2000`, `2001`, `2500`, `18300000`, `NULL`.
4. **Stok awal** — pakai laporan BUMDes bila ada (`stok_estimasi=false`); cadangan bila tidak (`true`).
5. **Policy** — distributor tidak bisa melihat/mengubah pengiriman distributor lain; BUMDes tidak bisa menulis ke kabupaten lain; user non-admin tidak bisa mengakses `/admin/*`.
6. **Pengiriman** — resi duplikat ditolak; `level_peringatan` dihitung server (input klien diabaikan); pembatalan memicu hitung ulang asal & tujuan.
7. **Importer** — kode kabupaten tidak dikenal, duplikat, nilai negatif, `supply_kg` tidak cocok rumus, kode berawalan nol tetap terjaga.
8. **Akses publik** — `/`, `/wilayah/{kode}`, `/api/status`, `/api/wilayah/{kode}/seri` terbuka; `/pengiriman*` dan `/api/wilayah/{kode}/cek` mengalihkan ke login.

Jalankan tes pada database MySQL terpisah (`*_test`) bila memungkinkan agar perilaku `upsert`/ENUM sama dengan production **[Saran]**.

---

## 16. Kontrak dengan Pipeline ML (ringkas)

Laravel **tidak menjalankan ML**. Yang dijaga hanya kontrak berkas:

- **Keluar (Laravel → ML):** `php artisan data:export-ml` → `ml/data/processed/latest.csv` (`data_historis` + laporan BUMDes).
- **Masuk (ML → Laravel):** `forecasts.csv`, **1 baris = kabupaten × komoditas × bulan target**, 12 baris (horizon 1–12) per kabupaten × komoditas per run. Kolom: `kode_kabupaten`, `komoditas`, `periode_dasar`, `periode_target`, `horizon_bulan`, `panen_pred_kg`, `pasokan_masuk_pred_kg`, `stok_awal_pred_kg`, `supply_pred_kg`, `demand_pred_kg`, `rasio_pred`, `status_pred`, `rasio_bawah`, `rasio_atas` (opsional), `versi_model`, `dibuat_pada`.
- Format kolom CSV (`YYYY-MM`, kg, titik desimal, tanpa pemisah ribuan, UTF-8, koma) **tidak boleh diubah** sepihak; perubahan harus sinkron di skrip Python **dan** importer Laravel.
- Model: XGBoost, **3 target** (panen, pasokan masuk, demand), validasi waktu (latih 2023–2024, uji 2025). **Supply tidak dimodelkan langsung**, dihitung bergulir.
- Fitur hanya boleh memakai lag ≥ jarak horizon (cegah data leakage). Retraining kuartalan atau bila error melewati batas 2 bulan berturut-turut.
- Folder `ml/` hanya berisi kode; data & model tidak di-commit.

---

## 17. Checklist Sebelum Commit / PR

- [ ] Fitur masih dalam daftar F-01…F-13 dan tidak melanggar Bagian 2.
- [ ] Tidak ada closure di route; semua route yang butuh login memakai `auth` + `role:` yang tepat.
- [ ] Otorisasi dicek di server (middleware + Policy), bukan hanya disembunyikan di UI.
- [ ] Ambang, teks disclaimer, dan pesan dampak dibaca dari `settings`, tidak di-hardcode.
- [ ] Rasio/status hanya dihitung di `StatusService`; JavaScript tidak menghitung ulang.
- [ ] Setiap perubahan data yang memengaruhi status memanggil `recalc` + membuang cache.
- [ ] `NULL` dan `0` diperlakukan berbeda; kode wilayah tetap berupa string.
- [ ] Angka tampil lewat `NumberFormatter`; form input tetap kg.
- [ ] Data pengiriman/resi tidak bocor ke endpoint publik; query pengiriman ter-scope ke pemilik.
- [ ] Label "stok estimasi", "Simulasi / data contoh", dan penanda data demo tampil di tempatnya.
- [ ] Tidak ada `.env`, dataset, atau model yang ter-commit; `APP_DEBUG=false` untuk production.
- [ ] Migrasi dapat dijalankan dari nol (`migrate:fresh --seed`) tanpa galat di lokal.

---

## 18. Hal yang Belum Final **[Cek]** — Jangan Diasumsikan Diam-diam

Buat mudah diubah (konstanta/config/satu method) dan tanyakan ke tim:

1. **Daftar status pengiriman** final dan aturan transisi/pembatalan (saat ini contoh gaya JNE). itu tergantung jne nya karena kita hanya terpaku dari jne jadi mengikuti status dari jne
2. **Definisi "pengiriman aktif"** untuk perhitungan supply: semua non-batal pada bulan itu (usulan saat ini), atau hanya yang belum diterima? semua non-batal (saat ini)
3. **Prioritas stok awal** antara laporan BUMDes, `data_historis.stok_awal_kg`, dan hitungan cadangan (Bagian 6.1).laporan bumdes lebih prioritas
4. **Kelengkapan dataset 2023–2025** dan kabupaten tanpa data sama sekali (status `tanpa_data`/abu-abu) iya kalau belum ada datanya itu di kasih abu" aja.
5. **Kode wilayah** GeoJSON = dataset = tabel master (periksa sebelum impor).iya seperti itu
6. **Ambang zoom** pergantian provinsi → kabupaten (± 7–8).iya seperti itu
7. **Warna** status Defisit dan Seimbang (PRD hanya menetapkan kuning, merah, abu-abu).Merah, kuning dan hijau muda (buat yang seimbang)
8. Apakah tabel **`audit_logs`** dibuat atau ditunda. ditunda
9. **Versi Laravel/PHP/MySQL** di CloudPanel (cek di hari pertama). dijawab oleh ai agent
10. Format `kode` pada `/wilayah/{kode}` (provinsi 2 digit dan kabupaten 4 digit sekaligus?). iya seperti itu
11. `prd.md` v1.1 menyebut ada "2 pertanyaan kecil di bagian 19", tetapi bagian 19 tidak ada di berkas. Pastikan pertanyaan itu tidak mengubah aturan di atas. iya tidak merubah aturan apapun
