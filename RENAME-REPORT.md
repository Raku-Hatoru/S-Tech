# LAPORAN PERGANTIAN MEREK RESMI: PRANALA PANGAN (RENAME REPORT)

> **Catatan:** Dokumen ini merangkum proses migrasi dan penamaan ulang merek publik dari **"SIPANGAN WASPADA"** menjadi **"PRANALA PANGAN"** pada cabang kerja `chore/rename-pranala-pangan` secara terisolasi, aman, dan teruji tanpa regresi fungsional.

---

## 1. Tabel Klasifikasi Inventaris Merek (Step 1)

Pemindaian mendalam menggunakan pencarian *case-insensitive* terhadap seluruh berkas yang terlacak Git (`git ls-files`) menghasilkan klasifikasi sebagai berikut:

| Kategori | Deskripsi Singkat | Jumlah Baris/Hit | Berkas Terdampak |
|---|---|---|---|
| **A. USER-VISIBLE BRAND** | Teks antarmuka Blade, judul halaman, header logo, footer copyright, nama unduhan CSV, format pesan WhatsApp, dan log konsol browser | **11** | `app/Http/Controllers/PlantingController.php`<br>`resources/views/auth/login.blade.php`<br>`resources/views/layouts/app.blade.php`<br>`resources/views/peta/detail.blade.php`<br>`resources/views/peta/index.blade.php` |
| **B. CONFIG DEFAULT** | Konfigurasi default nama aplikasi (`config/app.php`, `.env.example`, `.gitignore`) | **3** | `.env.example`<br>`config/app.php`<br>`.gitignore` |
| **C. DOCS** | Dokumentasi resmi, PRD, pipeline ML, dan penambahan catatan pada dokumen riwayat audit | **20** | `README.md`<br>`docs/PRD.md` (`prd.md`)<br>`AUDIT.md`<br>`UI-REVIEW.md`<br>`MERGE-REPORT.md`<br>`REPO-SIZE.md`<br>`ml/README.md`<br>`ml/simplify_geojson.py`<br>`ml/train.py`<br>`ml/train_baseline.py`<br>`routes/api.php` |
| **D. TEST ASSERTION** | Skrip asersi integritas audit dan banner uji | **3** | `scripts/smoke_test.sh`<br>`scripts/verify_audit_integrity.sh` |
| **E. IDENTIFIER (UNTOUCHED)** | Identifier teknis yang dipertahankan: alamat email demo (`@sipangan.test`), pola regex cache key lama, dan variabel skrip sementara | **79** | `database/seeders/DemoUserSeeder.php`<br>`database/seeders/DemoHarvestReportSeeder.php`<br>`scripts/clutter-scan.mjs`<br>`scripts/verify-step14.mjs`<br>`scripts/verify-step15.mjs`<br>`scripts/verify-step16.mjs`<br>5 berkas pengujian `tests/Feature/*Test.php` |
| **F. ASSET (MANUAL)** | Berkas gambar biner mockup desain yang memuat teks nama lama pada nama file | **1** | `design/Halaman Login SIPANGAN WASPADA.png` |
| **G. STATUS WORD (KEEP)** | Kata tunggal **"Waspada"** sebagai level status neraca pangan (0.90 s.d. <1.00) — **TETAP DIPERTAHANKAN** | **583** | 43 berkas (Enums, Migrations, Seeders, Views, Chart, Legends, API, Tests) |

---

## 2. Rincian Perubahan Berdasarkan Kategori (Step 3)

### Kategori A: User-Visible Brand
1. **`resources/views/layouts/app.blade.php`**:
   - Tag `<title>` diubah menggunakan fallback: `{{ $title ?? config('app.name', 'PRANALA PANGAN') }}`.
   - Atribut title logo header: `PRANALA PANGAN (Prototipe)`.
   - Teks logo header: Kata pertama `PRANALA` (ditampilkan di semua ukuran layar) dan kata kedua `PANGAN` (ditampilkan mulai breakpoint `sm:` ke atas agar header mobile 390px tetap ringkas).
   - Teks hak cipta footer: `&copy; 2026 PRANALA PANGAN (Prototipe) • Sistem pemantauan dini pasokan pangan; bukan platform jual-beli.`
2. **`resources/views/auth/login.blade.php`**:
   - Tag `<title>`: `Masuk — {{ config('app.name', 'PRANALA PANGAN') }}`.
   - Logo teks header login: `PRANALA PANGAN`.
   - Footer halaman login: `&copy; 2026 PRANALA PANGAN • Sistem pemantauan dini pasokan pangan; bukan platform jual-beli.`
3. **`resources/views/peta/detail.blade.php`**:
   - Format penamaan unduhan berkas CSV diubah menjadi:
     `PRANALA_PANGAN_Rekap_12Bulan_{{ $kode }}_{{ $komoditas->kode }}.csv`.
4. **`resources/views/peta/index.blade.php`**:
   - Prefiks pesan diagnostik konsol JavaScript diperbarui: `[PRANALA PANGAN] Base map frame loading...` dan `[PRANALA PANGAN] Gagal memuat status data...`.
5. **`app/Http/Controllers/PlantingController.php`**:
   - Teks template pesan bagikan jadwal tanam ke WhatsApp:
     `Sumber: PRANALA PANGAN - BUMDes {$kabupaten->nama}`.

### Kategori B: Config Default
1. **`config/app.php`**:
   - Fallback `name` diperbarui dari `'Laravel'` menjadi `'PRANALA PANGAN'` via `env('APP_NAME', 'PRANALA PANGAN')`.
2. **`.env.example`**:
   - Nilai default baris 1 diubah: `APP_NAME="PRANALA PANGAN"`.
3. **`.gitignore`**:
   - Menambahkan aturan `/docs/ui-review/` secara eksplisit agar tangkapan layar verifikasi visual tidak mengotori repositori Git.

### Kategori C: Dokumentasi & Komentar Kode
1. **`README.md`**:
   - Menambahkan kop judul utama: `# PRANALA PANGAN` dan sub-judul `Sistem Pemantauan & Early-Warning Surplus Pangan Nasional (Sebelumnya dikenal sebagai SIPANGAN WASPADA)`.
2. **`docs/PRD.md` & `prd.md`**:
   - Judul dokumen diubah menjadi `# PRD: PRANALA PANGAN — Sistem Pemantauan & Early-Warning Surplus Pangan (Web)`.
   - Baris nama produk diubah menjadi `PRANALA PANGAN (sebelumnya SIPANGAN WASPADA)`.
3. **Dokumen Riwayat Audit (`AUDIT.md`, `UI-REVIEW.md`, `MERGE-REPORT.md`, `REPO-SIZE.md`)**:
   - Sesuai instruksi Step 3, isi dokumen riwayat **TIDAK diubah**, melainkan ditambahkan 1 baris catatan di baris paling atas:
     `Catatan: proyek ini kini bernama PRANALA PANGAN (sebelumnya SIPANGAN WASPADA).`
4. **Pipeline Machine Learning (`ml/README.md`, `ml/train.py`, `ml/train_baseline.py`, `ml/simplify_geojson.py`)**:
   - Header docstring diperbarui mencantumkan `PRANALA PANGAN`.
5. **`routes/api.php`**:
   - Header komentar blok rute diubah menjadi `API Routes (PRANALA PANGAN)`.

### Kategori D & Step 5: Pengujian & Skrip
1. **`database/seeders/DemoShipmentSeeder.php`**:
   - Nilai kolom keterangan riwayat pengiriman contoh diperbarui: `'Pengiriman didaftarkan di sistem PRANALA PANGAN.'`.
2. **`scripts/smoke_test.sh`**:
   - Banner terminal diperbarui menjadi `PRANALA PANGAN - SMOKE TEST (PRD Demo Flow Section 16)` dan penamaan file cookie sementara menggunakan prefiks `/tmp/pranala_cookies_*`.
3. **`tests/Feature/BrandNameTest.php` (BARU)**:
   - Dibuat suite pengujian fitur komprehensif (5 test methods, 97 assertions) yang memverifikasi kepatuhan nama merek untuk semua peran (Guest, Distributor, BUMDes, Administrator).

---

## 3. Daftar Hit yang Sengaja Tidak Diubah (Kategori E & F) Beserta Alasannya

Seluruh kemunculan kata `sipangan` yang tersisa pada repositori telah diperiksa dan disetujui untuk dipertahankan:

1. **Alamat Email Akun Demo (`*@sipangan.test`)**:
   - **Lokasi:** `database/seeders/DemoUserSeeder.php`, `database/seeders/DemoHarvestReportSeeder.php`, `tests/Feature/*Test.php`, `scripts/*.mjs`.
   - **Alasan:** Sesuai instruksi Kategori E, domain email akun demo (`admin@sipangan.test`, `distributor1@sipangan.test`, `bumdes.banyumas@sipangan.test`) adalah **identifier database** yang terikat dengan seeder dan puluhan assertions test unit/fitur. Mengubah domain email akan mematahkan integritas data yang ada tanpa memberikan manfaat bagi pengguna publik.
2. **Asersi Regex Kerahasiaan Email Demo (`scripts/verify_audit_integrity.sh:139`)**:
   - **Alasan:** Memastikan form login publik tetap bersih dan tidak membocorkan email akun demo.
3. **Pola Regex Cache Key Lama (`scripts/clutter-scan.mjs:268`)**:
   - **Alasan:** Skrip pemeliharaan perlu tetap membersihkan cache key lama yang masih berpola `sipangan-waspada-cache-*`.
4. **Berkas Biner Mockup Desain (`design/Halaman Login SIPANGAN WASPADA.png`)**:
   - **Alasan:** Berkas biner citra PNG tidak boleh diedit atau di-corrupt secara teks; pengguna dapat mengganti file ini secara manual jika diperlukan.

---

## 4. Analisis Dampak & Efek Samping (Step 4)

### A. Perubahan Derivasi Session Cookie & Cache Prefix
Laravel secara default menurunkan nama cookie sesi dan awalan kunci cache dari nilai `APP_NAME`:

| Konfigurasi | Rumus Derivasi | Nilai Lama (`SIPANGAN WASPADA`) | Nilai Baru (`PRANALA PANGAN`) |
|---|---|---|---|
| **Session Cookie** (`config/session.php`) | `Str::slug(APP_NAME) . '-session'` | `sipangan-waspada-session` | `pranala-pangan-session` |
| **Cache Key Prefix** (`config/cache.php`) | `Str::slug(APP_NAME) . '-cache-'` | `sipangan-waspada-cache-` | `pranala-pangan-cache-` |

> [!NOTE]
> **Konsekuensi Operasional:**  
> 1. Karena nama cookie sesi berganti menjadi `pranala-pangan-session`, seluruh pengguna yang sedang aktif akan diminta **login ulang satu kali**.
> 2. Kunci cache lama di tabel database/Redis dengan awalan `sipangan-waspada-cache-` menjadi yatim (*orphaned*) dan akan ditimpa secara aman saat `php artisan optimize:clear` dijalankan.  
> **Konfirmasi:** Hal ini sepenuhnya aman (*harmless*) dan merupakan perilaku standar saat aplikasi mengganti nama merek.

### B. Daftar Berkas yang Bergantung pada `config('app.name')` vs Hardcoded
- **Bergantung pada `config('app.name')`**:
  - `resources/views/layouts/app.blade.php`: Tag `<title>{{ $title ?? config('app.name', 'PRANALA PANGAN') }}</title>`.
  - `resources/views/auth/login.blade.php`: Tag `<title>Masuk — {{ config('app.name', 'PRANALA PANGAN') }}</title>`.
- **Memuat Nama Merek Secara Hardcoded**:
  - Header logo antarmuka (`PRANALA PANGAN`).
  - Teks penafian dan hak cipta di footer.
  - Nama file ekspor CSV detail wilayah.
  - Teks rujukan pesan WhatsApp BUMDes.

> [!IMPORTANT]
> Di server produksi, file `.env` memiliki baris `APP_NAME="SIPANGAN WASPADA"`. Saat kode ditarik (*pull*), tag `<title>` browser akan tetap membaca nilai `.env` tersebut sampai administrator mengubahnya menjadi `APP_NAME="PRANALA PANGAN"`.

---

## 5. Bukti Verifikasi Browser Nyata (Playwright Step 6)

Pengujian otomatis dijalankan menggunakan Chromium nyata (`/usr/bin/chromium`) melalui skrip `scripts/verify-rename.mjs` pada resolusi **1440x900 (Desktop)** dan **390x844 (Mobile)** untuk seluruh peran pengguna:

### Ringkasan Hasil Pengujian URL & Kepatuhan Merek

| Peran Pengguna | Jalur URL | Resolusi | Judul Halaman (`<title>`) | Frasa Lama (`sipangan waspada`) | Merek Baru (`PRANALA PANGAN`) | Status |
|---|---|---|---|---|---|---|
| **Guest** | `/peta` | 1440px & 390px | `PRANALA PANGAN` | **0** | **ADA** | **PASS** |
| **Guest** | `/wilayah/3302` | 1440px & 390px | `PRANALA PANGAN` | **0** | **ADA** | **PASS** |
| **Guest** | `/login` | 1440px & 390px | `Masuk — PRANALA PANGAN` | **0** | **ADA** | **PASS** |
| **Distributor** | `/peta` | 1440px & 390px | `PRANALA PANGAN` | **0** | **ADA** | **PASS** |
| **Distributor** | `/wilayah/3302` | 1440px & 390px | `PRANALA PANGAN` | **0** | **ADA** | **PASS** |
| **Distributor** | `/pengiriman` | 1440px & 390px | `PRANALA PANGAN` | **0** | **ADA** | **PASS** |
| **Distributor** | `/pengiriman/buat` | 1440px & 390px | `PRANALA PANGAN` | **0** | **ADA** | **PASS** |
| **Distributor** | `/pengiriman/1` | 1440px & 390px | `PRANALA PANGAN` | **0** | **ADA** | **PASS** |
| **BUMDes** | `/panen` | 1440px & 390px | `PRANALA PANGAN` | **0** | **ADA** | **PASS** |
| **BUMDes** | `/jadwal-tanam` | 1440px & 390px | `PRANALA PANGAN` | **0** | **ADA** | **PASS** |
| **Admin** | `/admin/pengguna` | 1440px & 390px | `PRANALA PANGAN` | **0** | **ADA** | **PASS** |
| **Admin** | `/admin/dataset` | 1440px & 390px | `PRANALA PANGAN` | **0** | **ADA** | **PASS** |
| **Admin** | `/admin/forecast` | 1440px & 390px | `PRANALA PANGAN` | **0** | **ADA** | **PASS** |
| **Admin** | `/admin/pengaturan`| 1440px & 390px | `PRANALA PANGAN` | **0** | **ADA** | **PASS** |

### Pengukuran Dimensi Header & Anti-Overlap

Pengukuran tata letak header dilakukan pada 4 viewport standar (`390px`, `768px`, `1024px`, `1440px`):

| Viewport | Teks Merek yang Dirender | Menu Navigasi Desktop | Horizontal Scroll | Evaluasi Layout |
|---|---|---|---|---|
| **390 px** | `"PRANALA"` | Sembunyi (Masuk Hamburger Menu) | **NONE (0px)** | Sangat lega, logo dan hamburger tidak berdesakan |
| **768 px** | `"PRANALA PANGAN"` | Tampak (`display: flex`) | **NONE (0px)** | Proporsional, chip dan menu berjarak aman |
| **1024 px** | `"PRANALA PANGAN"` | Tampak (`display: flex`) | **NONE (0px)** | Sempurna, jarak antar-menu lega |
| **1440 px** | `"PRANALA PANGAN"` | Tampak (`display: flex`) | **NONE (0px)** | Tampilan desktop standar pemerintah bersih & presisi |

### Tangkapan Layar yang Dihasilkan (`docs/ui-review/`)
Semua tangkapan layar telah tersimpan dan diabaikan dari komit Git:
- `docs/ui-review/rename-login-1440.png` & `docs/ui-review/rename-login-390.png`
- `docs/ui-review/rename-peta-1440.png` & `docs/ui-review/rename-peta-390.png`
- `docs/ui-review/rename-detail-1440.png` & `docs/ui-review/rename-detail-390.png`
- `docs/ui-review/rename-admin-1440.png` & `docs/ui-review/rename-admin-390.png`

---

## 6. Perbandingan Metrik Baseline Sebelum vs Sesudah

| Uji Verifikasi | Sebelum (*Baseline*) | Sesudah (*Rename*) | Keterangan |
|---|---|---|---|
| `php artisan test` | **100 passed**, 887 assertions | **105 passed**, 984 assertions | **+5 tes baru**, +97 assertions (`BrandNameTest`) |
| `./scripts/smoke_test.sh` | **11/11 PASS** (100%) | **11/11 PASS** (100%) | Semua rute demo HTTP 200/302 lulus |
| `./scripts/verify_audit_integrity.sh` | **100% PASS** | **100% PASS** | Tidak ada kebocoran resi atau akun demo |
| `vendor/bin/pint --test` | **Passed** | **Passed** | Kode 100% sesuai standar gaya Laravel Pint |
| `npm run build` | **Sukses** (525ms) | **Sukses** (389ms) | Aset CSS & JS terkompilasi bersih |

---

## 7. Hal yang Membutuhkan Keputusan Pengguna (*Decision Items*)

1. **Domain Email Akun Demo**:
   Saat ini akun demo menggunakan domain `@sipangan.test` (misalnya `admin@sipangan.test`). Jika ingin diubah ke `@pranalapangan.test`, hal ini memerlukan migrasi data user dan pembaruan pada 5 file test `AuthenticationAndRoleTest.php`. Rekomendasi saat ini: biarkan tetap `@sipangan.test` sebagai identifier lokal internal demo.
2. **Nama Model Machine Learning**:
   Model XGBoost di database saat ini bernama `v1-2026Q1-xgboost` (netral, tidak memuat nama brand). Tidak ada perubahan data yang diperlukan.
3. **Berkas Aset Mockup Desain**:
   Berkas `design/Halaman Login SIPANGAN WASPADA.png` memuat teks nama lama pada nama filenya. Anda dapat mengganti atau menghapus file tersebut jika folder `design/` ingin dibersihkan di masa mendatang.

---

## 8. Panduan Langkah Eksekusi Pengguna (Checklist Mandiri)

> [!CAUTION]
> **PENTING:** Jangan menjalankan perintah penggabungan (*merge*) atau dorong (*push*) secara otomatis sebelum memeriksa ringkasan di atas. Ikuti panduan perintah berikut:

### Langkah 1: Gabungkan Cabang Kerja di Komputer Lokal
Jalankan di terminal lokal Anda:
```bash
# 1. Beralih ke cabang utama
git switch main

# 2. Gabungkan cabang chore/rename-pranala-pangan dengan merge commit non-fast-forward
git merge --no-ff chore/rename-pranala-pangan -m "chore: merge controlled brand rename to PRANALA PANGAN"

# 3. Dorong ke GitHub repository
git push origin main
```

### Langkah 2: Pembaruan di Server Produksi
Jalankan perintah berikut melalui SSH di server produksi Anda:
```bash
# 1. Masuk ke direktori web root aplikasi
cd /path/ke/aplikasi/anda

# 2. Tarik commit terbaru dari GitHub
git pull origin main

# 3. Perbarui variabel APP_NAME pada file .env produksi
sed -i 's/APP_NAME="SIPANGAN WASPADA"/APP_NAME="PRANALA PANGAN"/' .env

# 4. Bersihkan dan optimalkan kembali cache Laravel
php artisan optimize:clear
php artisan optimize

# 5. Jika Anda menggunakan Cloudflare atau reverse proxy cache (Nginx cache),
#    lakukan "Purge Everything" pada dashboard CDN.
```
*(Setelah langkah ini, pengguna yang sedang login akan diminta memasukkan kembali email dan password mereka karena pembaruan cookie sesi).*
