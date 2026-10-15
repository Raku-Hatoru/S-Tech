# LAPORAN OPTIMASI UKURAN REPOSITORI & CLONE CEPAT (REPO-SIZE.md)
**Proyek:** SIPANGAN WASPADA (Laravel 13, Tailwind v4, Vite, MySQL/SQLite)  
**Cabang Utama:** `main`  
**Waktu Eksekusi:** 15 Oktober 2026  
**Status Eksekusi:** SUKSES (Telah di-push ke remote `origin/main`)  

---

> [!WARNING]
> **PENGINGAT KEAMANAN PENTING (PRIVATE REPOSITORY):**  
> Repositori GitHub `Raku-Hatoru/S-Tech` **WAJIB DIATUR SEBAGAI PRIVATE REPOSITORY**, bukan publik. Hal ini dikarenakan seeder dan dokumentasi proyek memuat struktur akun demonstrasi (`admin@sipangan.test`, `distributor1@sipangan.test`, dsb.) serta arsitektur internal instansi.

---

## 1. Perbandingan Ukuran Repositori (Sebelum vs Sesudah)

Melalui pembersihan dan pengabaian (*ignoring & untracking*) artefak tangkapan layar review visual, dump HTML, dan aset kompilasi frontend (`public/build`), ukuran repositori untuk kloning dangkal (*shallow clone*) berhasil dipangkas secara drastis:

| Metrik | Sebelum Optimasi | Sesudah Optimasi | Penurunan / Penghematan |
|---|---|---|---|
| **Ukuran Pohon Berkas (`git archive HEAD`)** | **34.14 MB** (35,799,040 byte) | **7.54 MB** (7,905,280 byte) | **-26.60 MB (-77.9%)** |
| **Data Ditransfer saat Kloning (`git clone --depth 1`)** | ~31.5 MiB | **3.69 MiB** | **-88.3% bandwidth hemat** |
| **Waktu Kloning Dangkal dari GitHub (HTTPS)** | ~12–25 detik (jaringan biasa) | **1.69 detik** | **Super Cepat & Andal** |
| **Ukuran Total Direktori Hasil Kloning di Disk** | ~42 MB | **12 MB** | **-71.4% hemat disk** |

---

## 2. Tabel Klasifikasi Berkas Besar (> 300 KB) & Jalur Mencurigakan (Step 2)

Setiap berkas berukuran $\ge 300\text{ KB}$ dan jalur build dianalisis peruntukannya di runtime, build, maupun pengujian:

| Jalur (*Path*) | Ukuran | Klasifikasi | Bukti Penelusuran Kode (*Evidence*) | Tindakan (*Action*) |
|---|---|---|---|---|
| `docs/ui-review/*.png` (130 berkas) | ~25 MB | **ARTIFACT** | Gambar tangkapan layar audit UI; tidak diakses aplikasi saat runtime/uji | **UNTRACK & IGNORE** |
| `docs/ui-review/*.html` (10 berkas) | ~1.5 MB | **ARTIFACT** | Dump HTML hasil render Playwright; tidak diakses oleh kode runtime | **UNTRACK & IGNORE** |
| `docs/ui-review/axe-step*.json` (3 berkas >200 KB) | 678 KB | **ARTIFACT** | Dump json audit aksesibilitas lama (step 3 & step 8) | **UNTRACK & IGNORE** |
| `public/build/` (11 berkas aset) | 185 KB | **REBUILDABLE** | Aset hasil kompilasi Vite (`npm run build`); dapat dibuat ulang kapan saja | **UNTRACK & IGNORE** |
| `design/*.png` (6 berkas mockup) | 2.78 MB | **ARTIFACT** | Mockup desain awal dari pembuat repositori; ukuran per file < 800 KB | **KEEP** (Dipertahankan) |
| `database/data/dataset_historis_sample.csv` | 533.8 KB | **REQUIRED-FOR-BUILD-OR-TEST** | Digunakan generator seeder `database/data/generate_sample_datasets.php` | **KEEP** (Wajib Ada) |
| `public/geojson/kabupaten.json` | 492.6 KB | **REQUIRED-AT-RUNTIME** | Dimuat Leaflet di `resources/views/peta/index.blade.php` & diuji PHPUnit | **KEEP** (Wajib Ada) |
| `public/geojson/provinsi.json` | 450.0 KB | **REQUIRED-AT-RUNTIME** | Dimuat Leaflet di `resources/views/peta/index.blade.php` & diuji PHPUnit | **KEEP** (Wajib Ada) |
| `composer.lock` | 315.0 KB | **REQUIRED-FOR-BUILD-OR-TEST** | Kunci integritas dependensi PHP Composer | **KEEP** (Wajib Ada) |
| `public/vendor/leaflet/` | 175 KB | **REQUIRED-AT-RUNTIME** | Pustaka Leaflet CSS/JS/Marker lokal untuk peta mandiri tanpa koneksi CDN | **KEEP** (Wajib Ada) |

---

## 3. Ringkasan Konfigurasi `.gitignore` Akhir

Berkas `.gitignore` telah ditata ulang secara rapi, tanpa entri duplikat, dan dilengkapi komentar deskriptif bahasa Indonesia:
- **Dependensi**: Mengabaikan `/vendor` dan `/node_modules`.
- **Hasil Build Frontend**: Mengabaikan `/public/build`, `/public/hot`, `/public/storage`, `/public/fonts-manifest.dev.json`.
- **Kredensial & Rahasia**: Mengabaikan `.env`, `.env.*` (kecuali `!.env.example`), `.audit-credentials`, `/auth.json`.
- **Basis Data & Cadangan**: Mengabaikan `*.sqlite`, `*.sqlite-journal`, `*.sql`, `*.bundle`, `*.zip`, `*.tar.gz`.
- **Artefak UI & Audit**: Mengabaikan `/docs/ui-review/**/*.png`, `/docs/ui-review/**/*.html`, dan berkas dump json besar `axe-step3-*.json`, `axe-step8-*.json`. Namun tetap menjaga berkas bukti penting berukuran kecil (`step13-evidence.json`, `step16-evidence.json`, `declutter-*.json`, `*.md`).
- **Laravel Cache & Nested Rules**: Mempertahankan seluruh `.gitignore` bawaan Laravel di `storage/` dan `bootstrap/cache/`.

---

## 4. Hasil Pengujian Salinan Bersih (*Clean Clone Test* di `/tmp/verify-clone`)

Pengujian dilakukan dari repositori kosong yang dikloning dengan mode shallow (`git clone --depth 1 file://$PWD /tmp/verify-clone`):

1. **Instalasi Dependensi & Build:**
   - `composer install --no-interaction`: Sukses (114 paket terpasang, autoload teroptimasi).
   - `php artisan key:generate`: Sukses membuat application key baru.
   - `npm ci && npm run build`: Sukses mengompilasi aset Vite (durasi 1.01 detik).
2. **Migrasi & Seeding pada Scratch SQLite DB (`database/scratch_verify_clean_clone.sqlite`):**
   - 16 migrasi berjalan sempurna tanpa error.
   - Seluruh seeder master berjalan sukses.
3. **Pengujian PHPUnit Suite:**
   - **Hasil Uji Clean Clone:** `100 tests passed, 887 assertions` (durasi 9.55 detik).
   - **Hasil Uji Repositori Asli:** `100 tests passed, 887 assertions`.
   - **Kesimpulan:** Identik 100%, tidak ada dependensi atau berkas uji yang hilang.
4. **Smoke Test Demo Flow (`./scripts/smoke_test.sh`):**
   - **11/11 PASS** (Semua 11 skenario PRD Section 16 berstatus HTTP 200/302).
5. **Integritas Aturan Audit (`./scripts/verify_audit_integrity.sh`):**
   - **100% PASS** (Semua aturan hak akses, visibilitas peran, dan kerahasiaan resi publik terpenuhi).

---

## 5. Hasil Pemindaian Rahasia (*Secret Scan*) Sebelum Push

Pemindaian mendalam menggunakan ekspresi reguler terhadap seluruh berkas yang terlacak maupun komit yang akan didorong (*diff*):
- `APP_KEY=base64`: **0 terdeteksi** (hanya template kosong di `.env.example`).
- `ghp_`, `github_pat_`, `AKIA`, `sk-`: **0 token terdeteksi**.
- Kunci privat (`BEGIN PRIVATE KEY`): **0 terdeteksi**.
- Kredensial aktif database (`DB_PASSWORD` dengan nilai): **0 terdeteksi**.
- Berkas rahasia terlacak (`.env`, `.audit-credentials`): **0 berkas terlacak**.
- Berkas berukuran $\ge 50\text{ MB}$: **0 berkas**.

---

## 6. Hasil Eksekusi Push ke GitHub

- **Metode Push:** Kredensial sesi GitHub CLI terotentikasi (`Raku-Hatoru`).
- **Perintah:** `git push origin main` (Normal push, non-force).
- **Hasil:**
  ```
  To https://github.com/Raku-Hatoru/S-Tech.git
     09d8059..22cff87  main -> main
  ```
- **Verifikasi Sinkronisasi Hash:**
  - Local HEAD : `22cff8723968efa7abe61b1a5ffba5008d58d69c`
  - Remote origin/main : `22cff8723968efa7abe61b1a5ffba5008d58d69c`
  - Status : **SINKRON IDENTIK (100% UP-TO-DATE)**

---

## 7. Panduan Perintah yang Dijalankan di Server Produksi (CloudPanel)

Jalankan perintah berikut pada terminal server CloudPanel Anda untuk menarik kode bersih dengan sangat cepat:

```bash
# 1. Kloning shallow cepat ke direktori domain server
git clone --depth 1 -b main git@github.com:Raku-Hatoru/S-Tech.git domainanda.com

# 2. Masuk ke direktori aplikasi
cd domainanda.com

# 3. Salin file environment dan atur konfigurasi produksi
cp .env.example .env
# Edit .env: sesuaikan DB_*, APP_ENV=production, APP_DEBUG=false, SEED_DEMO_USERS=false

# 4. Pasang dependensi PHP
composer install --no-dev --optimize-autoloader

# 5. Buat application key
php artisan key:generate

# 6. Jalankan migrasi basis data
php artisan migrate --force

# 7. Optimalkan cache Laravel
php artisan optimize
```

> [!NOTE]
> **Kompilasi Frontend (`public/build`):**  
> Folder `public/build` kini tidak lagi disimpan di dalam Git agar repositori tetap ringan.
> - **Opsi A (Jika server memiliki Node.js & NPM):**  
>   Jalankan langsung di server:
>   ```bash
>   npm ci && npm run build
>   ```
> - **Opsi B (Jika server TIDAK memiliki Node.js):**  
>   Kompilasi aset di laptop lokal Anda (`npm run build`), lalu kirimkan folder `public/build/` ke server menggunakan `rsync`:
>   ```bash
>   rsync -avz --delete public/build/ user@server-ip:/path/ke/domainanda.com/public/build/
>   ```
