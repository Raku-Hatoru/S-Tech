Catatan: proyek ini kini bernama PRANALA PANGAN (sebelumnya SIPANGAN WASPADA).

# LAPORAN PENGGABUNGAN KE CABANG UTAMA (MAINLINE MERGE REPORT)
**Proyek:** SIPANGAN WASPADA  
**Target Mainline:** `main` (dialihkan dari `master` melalui Step 6)  
**Sumber:** `ui/polish-20261014`  
**Tanggal:** 15 Oktober 2026  
**Penanda Rilis:** `mvp-20261015`  

---

## 1. Topologi Repositori & Strategi Penggabungan

### Topologi
Sebelum penggabungan dilakukan, pemetaan menyeluruh terhadap riwayat Git menunjukkan kondisi berikut:
- Titik awal audit (`master`) berada pada komit `e420bec` (*initial: baseline codebase before audit*).
- Cabang `master` tidak menerima komit baru sama sekali setelah `e420bec` (`git log e420bec..master` kosong).
- Garis keturunan riwayat berjalan linear dan searah:
  $$\text{master } (e420bec) \longrightarrow \text{audit/cleanup-20261013 } (164566a) \longrightarrow \text{main } (1506d00) \longrightarrow \text{ui/polish-20261014 } (e500ada)$$
- Pengujian leluhur (`git merge-base --is-ancestor master ui/polish-20261014`) mengembalikan kode keluar `0` (terbukti leluhur langsung).

### Strategi (Kasus A: Riwayat Linear)
Karena `master` merupakan leluhur langsung (*ancestor*) dari `ui/polish-20261014`, penggabungan dilakukan menggunakan mekanisme **Fast-Forward Only**:
```bash
git checkout master
git merge --ff-only ui/polish-20261014
```
Hasil: Tidak ada potensi konflik (*0 merge conflicts*). Riwayat komit tetap rapi, runtut, dan mempertahankan seluruh catatan audit serta pengoptimalan UI.

### Pengalihan Nama Cabang Utama (Step 6)
Sesuai arahan pemilik repositori (`MAINLINE_NAME = main`), cabang `master` dialihkan menjadi `main` menggunakan perintah:
```bash
git branch -M master main
```
Cabang `main` kini menunjuk langsung pada komit terverifikasi penuh (`mvp-20261015`), dan tidak ada lagi cabang lokal bernama `master`.

---

## 2. Jaring Pengaman (*Safety Nets*) yang Diterapkan

Sebelum dan selama proses penggabungan, jaring pengaman berikut dibuat untuk menjamin integritas kode:

1. **Tag Cadangan Git (Lokal):**
   - `backup/master-before-mainline` (diletakkan di `e420bec`, mengabadikan kondisi awal master).
   - `backup/audit-end` (diletakkan di `164566a`, akhir fase audit backend).
   - `backup/ui-polish-end` (diletakkan di `e500ada`, akhir fase pemolesan UI).
2. **Bundel Arsip Penuh di Luar Repositori:**
   - Berkas: `/home/rakuhatoru/lomba/S-Tech/projek/S-tech-backup-20261015.bundle`
   - Dibuat dengan: `git bundle create ../S-tech-backup-20261015.bundle --all`
   - Verifikasi: `git bundle verify` menyatakan berkas valid (*OK, 14 references, sha1*).
3. **Preservasi Cabang:**
   - Tidak ada cabang yang dihapus. Cabang `audit/cleanup-20261013` dan `ui/polish-20261014` tetap utuh di repositori.
4. **Tag Rilis Akhir:**
   - `mvp-20261015` disematkan pada ujung *mainline* `main`.

---

## 3. Konflik dan Keputusan Resolusi

Karena penggabungan dilakukan melalui skema *Fast-Forward*, **tidak terjadi konflik berkas (0 merge conflicts)**.

Seluruh keputusan arsitektur dan kepatuhan yang dihasilkan dari `AUDIT.md` dan pemolesan UI terjaga penuh:
- Hak akses berbasis peran (Tamu/Publik, Distributor, BUMDes, Administrator) tetap terlindungi ketat.
- Kerahasiaan nomor resi pengiriman pada halaman publik (`/peta` dan `/wilayah/{kode}`) terjaga 100%.
- Ketiadaan kredensial akun demo pada halaman `/login`.
- Eliminasi *micro-notes*, *helper text* birokratis, dan badge berlebih (decluttering) di seluruh portal.

---

## 4. Hasil Verifikasi Menyeluruh pada Cabang Utama (`main`)

Semua pengujian dijalankan langsung pada cabang utama `main`:

### A. Rangkaian Pengujian Otomatis (PHPUnit & Baseline Scripts)
| Uji / Skrip | Perintah | Status | Keterangan |
|---|---|---|---|
| **PHPUnit Suite** | `php artisan test --compact` | **PASS** | 100 test lolos, 887 asersi (durasi ~9.3–10.8 detik) |
| **Smoke Test Demo Flow** | `./scripts/smoke_test.sh` | **PASS (11/11)** | Alur demo PRD Section 16 (Peta, Login, DO, Panen, Admin) |
| **Integritas Aturan Audit** | `./scripts/verify_audit_integrity.sh` | **PASS (100%)** | Otentikasi, visibilitas tombol peran, kerahasiaan resi |
| **Pint Linter** | `vendor/bin/pint --test` | **PASS** | Format kode PHP standar Laravel Pint |
| **Daftar Rute** | `php artisan route:list` | **PASS** | 38 rute terdaftar sempurna tanpa exception |

### B. Status Penjadwalan & Perintah Impor
- `php artisan schedule:list`:
  ```
  INFO No scheduled tasks have been defined.
  ```
  *Catatan Kejujuran Teknis*: Pada PRD Section 5, cron/scheduler untuk impor bulanan dicatat sebagai `[Asumsi]`. Perintah impor pada kode basis (`data:import-historis` dan `forecast:import`) membutuhkan argumen parameter berkas `{file}` (contoh: `php artisan data:import-historis path/to/file.csv`). Oleh karena itu, kedua perintah ini dirancang untuk dipicu secara manual atau lewat skrip batch terarah, bukan cron scheduler tanpa parameter.
- `php artisan list | grep -i import`:
  - `data:import-historis` : Impor dataset historis bulanan pangan (2023–2025) ke tabel `data_historis`.
  - `forecast:import` : Impor hasil prediksi XGBoost (forecast bulanan) ke tabel `forecasts`.

### C. Pengujian Database Scratch (Migrate & Seed dari Nol)
Pengujian dilakukan pada database SQLite sementara di luar database kerja:
- **Nama Berkas Scratch**: `database/scratch_verify_step5.sqlite`
- **Eksekusi Migrasi & Seeder**:
  `DB_CONNECTION=sqlite DB_DATABASE=... php artisan migrate:fresh --seed`
  - 16 migrasi berjalan sukses.
  - 8 seeder (Role, Komoditas, Wilayah, Setting, DemoUser, DemoShipment, PlantingRecommendation, DemoHarvestReport) berjalan sukses.
- **Eksekusi Test Suite pada DB Scratch**:
  `DB_CONNECTION=sqlite DB_DATABASE=... php artisan test --compact`
  - Hasil: **100 passed, 887 assertions**.
- **Pembersihan**: Berkas scratch langsung dihapus setelah verifikasi selesai.

### D. Integritas Skema Migrasi Database
- `git diff --name-status e420bec..HEAD -- database/migrations`
- **Hasil**: **0 modifikasi, 0 berkas terhapus**. Seluruh skema migrasi awal dipertahankan secara utuh tanpa modifikasi destruktif.

### E. Verifikasi Browser Nyata (Playwright & Chromium Headless)
| Metrik / Halaman | Target Standar | Hasil Terukur | Status |
|---|---|---|---|
| **Poligon Peta (`/peta`)** | > 0 interactive path di `#peta-map` | **507 path interaktif** | **PASS** |
| **Tooltip Peta (`/peta`)** | Singleton (maks 1), hilang saat mouseleave | Maks 1 saat hover, 0 saat keluar | **PASS** |
| **Responsivitas (`/wilayah/3302`)** | 0 horizontal scroll (1440, 1024, 768, 390px) | 0px overflow pada 4 viewport | **PASS** |
| **Target Sentuh Mobile** | Minimum 44×44px | 0 elemen di bawah 44px | **PASS** |
| **Aksesibilitas Axe-core** | 0 violation pada `/wilayah/3302` | **0 violation, 0 contrast error** | **PASS** |
| **In-page Anchor** | Target `#koridor-redistribusi` ada & valid | Ada & dapat diklik | **PASS** |
| **Kerahasiaan Resi & Kata Terlarang** | 0 kata terlarang, 0 nomor resi di publik | 0 terdeteksi | **PASS** |

### F. Ringkasan Pengurangan Kepadatan Visual (*Decluttering*)
Hasil pemindaian sebelum (*before*) vs sesudah (*after*) pemolesan UI pada viewport 1440px:
- **Halaman Peta (`/peta`)**: 302 kata $\to$ 285 kata.
- **Detail Wilayah (`/wilayah/3302`)**: 313 kata $\to$ 199 kata (-36%), kedalaman DOM berkurang dari 4 menjadi 3.
- **Panel Admin Pengaturan (`/admin/pengaturan`)**: 264 kata $\to$ 134 kata (-49%), 5 helper text birokratis dihapus seluruhnya.
- **Login (`/login`)**: 65 kata $\to$ 50 kata, seluruh helper teks dan link kosong dibersihkan.

---

## 5. Analisis Risiko & Mitigasi

1. **Aset Frontend Produksi (Vite)**:
   - Aset CSS (`app-DgSiPpwo.css`) dan Javascript telah dikompilasi secara deterministik ke dalam folder `public/build/` dan tercatat dalam `manifest.json`.
   - *Mitigasi*: Berkas `public/hot` dipastikan tidak ada di repositori untuk mencegah browser memuat mode Vite HMR di server.
2. **Kredensial & Lingkungan Produksi**:
   - Berkas `.env` dan `.audit-credentials` tidak terlacak di Git (*untracked*).
   - Pengaturan produksi wajib mematikan `APP_DEBUG` dan mengaktifkan nilai salt sandi acak pada akun demo.

---

## 6. Panduan Tindakan Lanjutan untuk Pengguna (CloudPanel Deployment Checklist)

> [!IMPORTANT]
> Asisten **TIDAK** menjalankan perintah di lingkungan remote GitHub maupun server CloudPanel Anda. Silakan salin dan jalankan langkah-langkah berikut secara mandiri.

### A. Pengaturan Remote GitHub (Dijalankan di Mesin Lokal):
1. Dorong cabang `main` dan tag terbaru ke repositori remote:
   ```bash
   git push -u origin main
   git push origin --tags
   ```
2. Buka pengaturan GitHub di browser: **Settings > General > Default branch**, lalu ubah default branch dari `master` ke `main`.
3. Setelah default branch di GitHub berubah menjadi `main`, Anda dapat menghapus cabang `master` remote jika diinginkan:
   ```bash
   git push origin --delete master
   ```

### B. Langkah-langkah di Server CloudPanel:
1. **Masuk ke direktori aplikasi di server:**
   ```bash
   cd /path/ke/aplikasi/S-tech
   ```

2. **Perbarui remote dan beralih ke cabang `main`:**
   ```bash
   git fetch origin
   git branch -m master main
   git branch -u origin/main main
   git remote set-head origin -a
   git pull origin main
   ```

3. **Pasang dependensi PHP untuk lingkungan produksi:**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

4. **Kompilasi aset frontend untuk produksi:**
   ```bash
   npm ci && npm run build
   ```

5. **Jalankan migrasi database (jika diperlukan):**
   ```bash
   php artisan migrate --force
   ```

6. **Optimalkan cache aplikasi Laravel:**
   ```bash
   php artisan optimize
   ```

7. **Konfigurasi Berkas `.env` Produksi:**
   Pastikan berkas `.env` Anda memiliki konfigurasi keamanan berikut:
   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   SEED_DEMO_USERS=false
   ```
   *Jika membutuhkan kata sandi khusus untuk akun demo, tentukan variabel berikut:*
   ```dotenv
   DEMO_PASS_ADMIN="SandiKuatAdmin2026!"
   DEMO_PASS_DISTRIBUTOR="SandiKuatDistro2026!"
   DEMO_PASS_BUMDES="SandiKuatBumdes2026!"
   ```

8. **Perbarui Kata Sandi Akun yang Sudah Terdaftar:**
   Jika akun demo sudah sempat dibuat sebelumnya di database produksi, ubah kata sandinya menggunakan Tinker:
   ```bash
   php artisan tinker --execute 'App\Models\User::where("email", "admin@sipangan.test")->update(["password" => Hash::make(env("DEMO_PASS_ADMIN", "GantiDenganSandiBaru!"))]);'
   ```

---

## 7. Kesimpulan Akhir

Cabang `main` saat ini telah resmi menjadi cabang utama (*mainline*) proyek SIPANGAN WASPADA. Seluruh perbaikan audit teknis, keamanan, perampingan visual (*decluttering*), standarisasi tipografi, aksesibilitas WCAG AA, dan integritas data telah teruji 100% lulus tanpa regresi fungsional.
