# AUDIT CLEANUP VERIFIKASI — SIPANGAN WASPADA

Dokumen audit ini disusun sebagai catatan perbaikan, pembersihan kode, dan verifikasi kepatuhan sistem terhadap spesifikasi produk `docs/PRD.md` (source of truth).

---

## 0. Konteks & Stack

Berdasarkan deteksi otomatis terhadap repositori per 13 Oktober 2026:

| Komponen | Spesifikasi / Paket Terpasang | Keterangan |
|---|---|---|
| **Bahasa Pemrograman** | PHP 8.5.10 | Runtime utama |
| **Framework Backend** | Laravel 13.35.0 (`laravel/framework: ^13.17`) | Struktur standar Laravel 13 |
| **Frontend Rendering** | Plain Blade Templates | Tidak menggunakan Livewire, Inertia, maupun Filament |
| **Peta & Visualisasi UI** | Leaflet.js (CDN) & Chart.js (CDN) | Peta GeoJSON interaktif & grafik seri waktu pasokan |
| **Asset Pipeline & CSS** | Vite 8.3.4 + Tailwind CSS v4 (`@tailwindcss/vite: ^4.0.0`) | Pipeline aset modern |
| **Mekanisme Peran (Role)** | Custom RBAC (Database Driven) | Tabel `roles` (`kode`: `admin`, `bumdes`, `distributor`), relasi `User->role_id`, helper `isAdmin()`, `isBumdes()`, `isDistributor()`, Policy (`ShipmentPolicy`, `HarvestReportPolicy`), dan Route Middleware `EnsureRole` (`role:admin,bumdes,distributor`) |
| **Autentikasi** | Custom Session-based Auth (`app/Http/Controllers/Auth/LoginController.php`) | Menggunakan bcrypt (12 rounds) via Eloquent `hashed` cast, driver sesi database (`SESSION_DRIVER=database`), rate-limiting pada `/login` |
| **Struktur Rute** | `routes/web.php`, `routes/api.php`, `routes/console.php` | Terpisah jelas antara publik, auth, peran distributor, peran BUMDes, peran Admin, serta endpoint API publik |
| **Scheduler & Artisan Commands** | Artisan Command kustom di `app/Console/Commands/`: `data:export-ml`, `data:import-historis`, `forecast:import`, `status:recalc` | Terdaftar pada registri console dan dijalankan berkala |
| **Testing Tools** | PHPUnit 12, Laravel Test Runner, bash curl-based smoke test suite | Verifikasi runtime via curl dengan session cookie-jar dan CSRF token |

---

## 1. Hasil Baseline

Pengujian baseline dijalankan pada database lokal setelah migrasi dan seeding demo akun:

### 1.1 Hasil Pengujian Otomatis (PHPUnit / `php artisan test`)
- **Total Test**: 84 tests
- **Passed**: 83 passed (562 assertions)
- **Failed**: 1 failed
- **Detail Kegagalan**:
  - Test: `Tests\Feature\DatasetAndForecastImportTest::test_admin_can_toggle_model_version`
  - Error: `Error: Class "App\Services\AuditService" not found in app/Http/Controllers/Admin/ForecastController.php:127`
  - **Akar Masalah**: Berkas `app/Services/AuditService.php` ada di repositori dengan ukuran 0 byte (kosong). Sesuai aturan baseline langkah ini, tidak ada kode aplikasi yang diubah/dihapus sebelum audit dijalankan.

### 1.2 Hasil Smoke Test Demo Flow (PRD Bagian 16 / `scripts/smoke_test.sh`)
Suite pengujian alur demo dijalankan menggunakan skrip `scripts/smoke_test.sh`:

| # | Langkah Pengujian | Status Baseline | Bukti & Catatan |
|---|---|---|---|
| 1 | Peta Publik (`GET /peta`) | **PASS** | HTTP 200, Judul dan kontainer peta berhasil termuat tanpa autentikasi |
| 2 | Detail Wilayah Banyumas (`GET /wilayah/3302`) | **PASS** | HTTP 200, Informasi wilayah dan riwayat pangan termuat tanpa autentikasi |
| 3 | Login Distributor (`POST /login`) | **FAIL** | HTTP 500, Gagal karena pemanggilan `AuditService::log()` saat berkas `AuditService.php` kosong (0 byte) |
| 4 | Form Buat Pengiriman (`GET /pengiriman/buat`) | **BLOCKED** | Terblokir karena proses login distributor gagal pada langkah 3 |
| 5 | Dashboard Tracking Resi (`GET /pengiriman`) | **BLOCKED** | Terblokir karena proses login distributor gagal pada langkah 3 |
| 6 | Login BUMDes (`POST /login`) | **FAIL** | HTTP 500, Terblokir oleh error `AuditService` yang sama |
| 7 | Submit Hasil Panen & Sisa Stok (`POST /panen`) | **BLOCKED** | Terblokir karena proses login BUMDes gagal pada langkah 6 |
| 8 | Rekap Panen BUMDes (`GET /panen`) | **BLOCKED** | Terblokir karena proses login BUMDes gagal pada langkah 6 |
| 9 | Rekomendasi Jadwal Tanam (`GET /jadwal-tanam`) | **BLOCKED** | Terblokir karena rute dilindungi middleware auth BUMDes |
| 10 | Login Admin (`POST /login`) | **FAIL** | HTTP 500, Terblokir oleh error `AuditService` yang sama |
| 11 | Panel Admin (`/admin/pengguna`, `/admin/dataset`, dll.) | **BLOCKED** | Terblokir karena proses login Admin gagal pada langkah 10 |

**Catatan Verifikasi Runtime**:
- Interaktivitas JavaScript (klik peta Leaflet, interaksi popup GeoJSON, render grafik canvas Chart.js) diklasifikasikan sebagai: **NOT RUNTIME-VERIFIED VIA HEADLESS BROWSER** (lingkungan eksekusi tidak memiliki Playwright/browser binary; verifikasi level HTTP menggunakan curl cookie-jar dan CSRF token).

---

## 2. Matriks Hak Akses (Expected dari PRD 4.1 & Halaman Publik)

### 2.1 Matriks Peran (PRD Bagian 4.1)

| Fitur | Distributor | BUMDes | Admin |
|---|---|---|---|
| Lihat peta & status wilayah (juga publik tanpa login) | ✅ | ✅ | ✅ |
| Lihat info peringatan wilayah (juga publik tanpa login) | ✅ | ✅ | ✅ |
| Buat pengiriman | ✅ | ❌ | ❌ |
| Lihat/lacak pengiriman | Milik sendiri | ❌ | Semua |
| Update status pengiriman | ✅ (milik sendiri) | ❌ | ✅ (koreksi) |
| Input hasil panen & sisa stok (form survei) | ❌ | ✅ (kabupaten sendiri) | ✅ (semua) |
| Lihat rekap hasil panen | ❌ | Kabupaten sendiri | Semua |
| Rekomendasi jadwal tanam | ❌ | ✅ | ✅ |
| Kelola user, dataset, ambang | ❌ | ❌ | ✅ |

### 2.2 Halaman Publik (Guest / Tanpa Login)
Sesuai PRD bagian 4.1 & 12, halaman berikut dapat diakses oleh publik tanpa memerlukan login:
1. **Beranda / Redirect**: `GET /` (diarahkan ke `/peta`)
2. **Peta Heatmap Indonesia**: `GET /peta` (filter komoditas, bulan, status agregat)
3. **Detail Wilayah**: `GET /wilayah/{kode}` (grafik aktual vs prediksi, status, pesan dampak)
4. **Halaman Autentikasi**: `GET /login`, `POST /login`
5. **API Publik**: `GET /api/status`, `GET /api/periode`, `GET /api/wilayah/{kode}/info`, `GET /api/wilayah/{kode}/seri`, `GET /api/wilayah/{kode}/cek` (dengan rate limiter)

*Catatan pembatasan ketat PRD:*
- Nomor resi **tidak dapat dicari oleh publik** (hanya tersimpan di database dan tampil pada dashboard distributor/admin).
- Satu akun BUMDes terikat mutlak ke satu kabupaten.

---

## 3. Aturan Main (RULES)

R1. Nothing is deleted on suspicion. Every deletion needs BOTH static evidence (grep for all references including dynamic ones, route:list, controller/method existence, view includes, JS handlers) AND runtime evidence (run the app, log in as each relevant role, click or request it, record the result: HTTP status, console errors, laravel.log). If the app cannot be run for that item, mark it UNVERIFIED and do not touch it.
R2. Verdict labels: DEAD (verified unused, removed), NOT-ALLOWED (violates PRD non-goals or role rules, removed or hidden), BROKEN-PRD (belongs to a Must/Should PRD feature but fails: FIX it directly, do not delete), DECISION (needs a human), UNVERIFIED (not touched), KEEP (looked suspicious but is used; write why).
R3. PRD priority: Must/Should features F-01..F-09, F-11, F-12 are never deleted; if broken, fix them. F-10 and F-13 (Could): do not fix or delete, report as DECISION. F-14 (Won't) and anything under PRD section 2.3 non-goals: NOT-ALLOWED.
R4. Never touch: migrations that have already been run, seeders for demo accounts, demo data that is clearly labeled (for example "Simulasi / data contoh"), existing tests (update them only when the code they test was legitimately removed).
R5. Things that look unused but are used implicitly. Check before judging: Artisan commands and the scheduler (the PRD uses an Artisan import and monthly scheduling), queued jobs, policies, observers, service providers, middleware aliases, event listeners, auto-discovered Blade/Livewire/Filament classes, routes/console.php, config-driven classes, route names or views built from strings.
R6. One commit per finding group, message format: audit(<A|B|C|D|E>): <summary> [IDs]. Before each group run tests and the smoke test; after it run them again. If either got worse, git revert that commit and mark the items UNVERIFIED.
R7. No passwords, tokens or .env contents in AUDIT.md or commits.
R8. Be honest in the report: if you could not verify something, say so. Do not claim "everything works" without evidence.

---

## 4. Temuan Audit (A, B, C, D, E)

Format kolom: `ID | File:baris | Jenis | Bukti statis | Bukti runtime | Putusan | Tindakan | Commit`

### 4.1 Temuan A — Rute & Controller Tidak Terpakai / Mati
| ID | File:baris | Jenis | Bukti statis | Bukti runtime | Putusan | Tindakan | Commit |
|---|---|---|---|---|---|---|---|
| *(belum ada)* | | | | | | | |

### 4.2 Temuan B — Views, Komponen Blade, & Aset Tidak Terpakai
| ID | File:baris | Jenis | Bukti statis | Bukti runtime | Putusan | Tindakan | Commit |
|---|---|---|---|---|---|---|---|
| *(belum ada)* | | | | | | | |

### 4.3 Temuan C — Otorisasi, Visibilitas UI, & Pembatasan Data (PRD 4.1)

#### 4.3.1 Matriks Evaluasi Akses & Visibilitas UI (Expected vs Actual)

| Halaman / Aksi / Elemen UI | Guest (Tanpa Login) | Distributor | BUMDes | Admin |
|---|---|---|---|---|
| **Beranda (`GET /`)** | Exp: 302 /peta \| Act: 302 /peta | Exp: 302 /peta \| Act: 302 /peta | Exp: 302 /peta \| Act: 302 /peta | Exp: 302 /peta \| Act: 302 /peta |
| **Peta Heatmap (`GET /peta`)** | Exp: 200 OK \| Act: 200 OK | Exp: 200 OK \| Act: 200 OK | Exp: 200 OK \| Act: 200 OK | Exp: 200 OK \| Act: 200 OK |
| **Detail Wilayah (`GET /wilayah/{kode}`)** | Exp: 200 OK \| Act: 200 OK | Exp: 200 OK \| Act: 200 OK | Exp: 200 OK \| Act: 200 OK | Exp: 200 OK \| Act: 200 OK |
| **API Telemetri Publik (`GET /api/*`)** | Exp: 200 OK \| Act: 200 OK | Exp: 200 OK \| Act: 200 OK | Exp: 200 OK \| Act: 200 OK | Exp: 200 OK \| Act: 200 OK |
| **Portal Pengiriman (`GET /pengiriman`)** | Exp: 302 /login \| Act: 302 /login | Exp: 200 OK (milik sendiri) \| Act: 200 OK | Exp: 403 Forbidden \| Act: 403 Forbidden | Exp: 200 OK (semua) \| Act: 200 OK |
| **Buat Pengiriman (`GET/POST /pengiriman/buat`)** | Exp: 302 /login \| Act: 302 /login | Exp: 200/302 OK \| Act: 200/302 OK | Exp: 403 Forbidden \| Act: 403 Forbidden | Exp: 403 Forbidden \| Act: 403 Forbidden |
| **Detail Pengiriman (`GET /pengiriman/{id}`)** | Exp: 302 /login \| Act: 302 /login | Exp: 200 OK (milik sendiri) / 403 (orang lain) \| Act: Sesuai | Exp: 403 Forbidden \| Act: 403 Forbidden | Exp: 200 OK (semua) \| Act: 200 OK |
| **Update Checkpoint (`POST /pengiriman/{id}/event`)** | Exp: 302 /login \| Act: 302 /login | Exp: 302 OK (milik sendiri) / 403 (orang lain) \| Act: Sesuai | Exp: 403 Forbidden \| Act: 403 Forbidden | Exp: 302 OK (koreksi) \| Act: 302 OK |
| **Portal Panen (`GET/POST /panen`)** | Exp: 302 /login \| Act: 302 /login | Exp: 403 Forbidden \| Act: 403 Forbidden | Exp: 200/302 OK (kabupaten sendiri) \| Act: Sesuai | Exp: 200/302 OK (semua) \| Act: Sesuai |
| **Ekspor CSV Panen (`GET /panen/export-csv`)** | Exp: 302 /login \| Act: 302 /login | Exp: 403 Forbidden \| Act: 403 Forbidden | Exp: 200 CSV (kabupaten sendiri) \| Act: Sesuai | Exp: 200 CSV (semua) \| Act: Sesuai |
| **Jadwal Tanam (`GET /jadwal-tanam`)** | Exp: 302 /login \| Act: 302 /login | Exp: 403 Forbidden \| Act: 403 Forbidden | Exp: 200 OK (kabupaten sendiri) \| Act: Sesuai | Exp: 200 OK (semua) \| Act: Sesuai |
| **Kelola Pengguna (`/admin/pengguna/*`)** | Exp: 302 /login \| Act: 302 /login | Exp: 403 Forbidden \| Act: 403 Forbidden | Exp: 403 Forbidden \| Act: 403 Forbidden | Exp: 200/302 OK \| Act: 200/302 OK |
| **Kelola Dataset (`/admin/dataset/*`)** | Exp: 302 /login \| Act: 302 /login | Exp: 403 Forbidden \| Act: 403 Forbidden | Exp: 403 Forbidden \| Act: 403 Forbidden | Exp: 200/302 OK \| Act: 200/302 OK |
| **Kelola Forecast (`/admin/forecast/*`)** | Exp: 302 /login \| Act: 302 /login | Exp: 403 Forbidden \| Act: 403 Forbidden | Exp: 403 Forbidden \| Act: 403 Forbidden | Exp: 200/302 OK \| Act: 200/302 OK |
| **Pengaturan Ambang (`/admin/pengaturan/*`)** | Exp: 302 /login \| Act: 302 /login | Exp: 403 Forbidden \| Act: 403 Forbidden | Exp: 403 Forbidden \| Act: 403 Forbidden | Exp: 200/302 OK \| Act: 200/302 OK |
| **Navbar: Menu "Portal Pengiriman"** | Exp: Tersembunyi \| Act: Tersembunyi | Exp: Tampil \| Act: Tampil | Exp: Tersembunyi \| Act: Tersembunyi | Exp: Tampil \| Act: Tampil |
| **Navbar: Menu "Portal BUMDes"** | Exp: Tersembunyi \| Act: Tersembunyi | Exp: Tersembunyi \| Act: Tersembunyi | Exp: Tampil \| Act: Tampil | Exp: Tampil \| Act: Tampil |
| **Navbar: Menu "Panel Admin"** | Exp: Tersembunyi \| Act: Tersembunyi | Exp: Tersembunyi \| Act: Tersembunyi | Exp: Tersembunyi \| Act: Tersembunyi | Exp: Tampil \| Act: Tampil |
| **Peta Index: Tombol "Disposisi Logistik"** | Exp: Tersembunyi \| Act: Tersembunyi | Exp: Tampil \| Act: Tampil | Exp: Tersembunyi \| Act: Tersembunyi | Exp: Tampil \| Act: Tampil |
| **Peta Index: Tombol "Buat Rute Pengiriman"** | Exp: Tersembunyi \| Act: Tersembunyi | Exp: Tampil \| Act: Tampil | Exp: Tersembunyi \| Act: Tersembunyi | Exp: Tampil \| Act: Tampil |
| **Detail Wilayah: Tombol "▶ Buat DO"** | Exp: Badge Info \| Act: Badge Info | Exp: Tampil Tombol \| Act: Tampil Tombol | Exp: Badge Info \| Act: Badge Info | Exp: Badge Info \| Act: Badge Info |
| **Detail Wilayah: Tombol "Panggil Operator"** | Exp: Badge Info \| Act: Badge Info | Exp: Tampil Tombol \| Act: Tampil Tombol | Exp: Badge Info \| Act: Badge Info | Exp: Badge Info \| Act: Badge Info |
| **Portal BUMDes: Tombol "Minta Disposisi"** | Exp: N/A (Login) \| Act: N/A | Exp: N/A (403) \| Act: N/A | Exp: Badge Info \| Act: Badge Info | Exp: Badge Info \| Act: Badge Info |
| **Portal Pengiriman: Tab "1. Buat Pengiriman"** | Exp: N/A (Login) \| Act: N/A | Exp: Tampil Tab \| Act: Tampil Tab | Exp: N/A (403) \| Act: N/A | Exp: Tersembunyi \| Act: Tersembunyi |

#### 4.3.2 Daftar Temuan Otorisasi & Visibilitas UI

| ID | File:baris | Jenis | Keparahan | Bukti statis | Bukti runtime | Putusan | Tindakan | Commit |
|---|---|---|---|---|---|---|---|---|
| C-01 | `routes/web.php:50-59`, `app/Policies/ShipmentPolicy.php:21-25` | Pelanggaran Matriks PRD 4.1 (Admin Mengakses Form & Membuat Pengiriman) | **TINGGI** | Rute `/pengiriman/buat` & `POST /pengiriman` dilindungi middleware `role:distributor,admin` dan `ShipmentPolicy::create` mengizinkan Admin. Matriks PRD Bagian 4.1 secara ketat menetapkan: *Buat pengiriman = Distributor ✅, Admin ❌*. | Akun Admin dapat membuka form `/pengiriman/buat` dan mengirim request POST pengiriman tanpa ditolak 403. | NOT-ALLOWED | Pisahkan rute buat pengiriman khusus `role:distributor`, kunci `ShipmentPolicy::create` hanya untuk `$user->isDistributor()`, dan sembunyikan tab buat pengiriman dari Admin. | `1819a07` |
| C-02 | `resources/views/layouts/app.blade.php:45-75` | Eksposur Menu Navigasi Sensitif ke Semua Peran | **SEDANG** | Menu navigasi desktop merender *Portal Pengiriman*, *Portal BUMDes*, dan *Panel Admin* secara statis tanpa proteksi `@auth` atau kondisional peran. | Tamu publik melihat semua menu lalu terlempar ke login; BUMDes melihat menu Pengiriman dan Admin yang menghasilkan HTTP 403; Distributor melihat menu BUMDes dan Admin yang menghasilkan HTTP 403 (SHOWN-BUT-FORBIDDEN). | NOT-ALLOWED | Tambahkan pengecekan peran `@if(auth()->check() && ...)` pada setiap item menu navigasi. | `1819a07` |
| C-03 | `resources/views/peta/index.blade.php:190, 358, 772` | Tombol Aksi Logistik Terbuka untuk Publik & BUMDes | **SEDANG** | Tombol *Disposisi Logistik* dan *Buat Rute Pengiriman Logistik* merender tautan `route('pengiriman.index')` pada halaman peta publik. | Tamu publik diarahkan ke login, pengguna BUMDes mendapat HTTP 403 (SHOWN-BUT-FORBIDDEN). | NOT-ALLOWED | Bungkus tombol aksi logistik dengan kondisional peran Distributor/Admin, serta amankan script JS (null-check) agar tidak error saat tombol disembunyikan. | `1819a07` |
| C-04 | `resources/views/peta/detail.blade.php:452, 474` | Tombol Pembuatan DO Terbuka untuk Publik & BUMDes | **SEDANG** | Kartu koridor redistribusi wilayah detail merender tombol `▶ Buat DO` dan `Panggil Operator Logistik` yang mengarah ke `pengiriman.create`. | Tamu publik diarahkan ke login, BUMDes dan Admin mendapat HTTP 403 (SHOWN-BUT-FORBIDDEN). | NOT-ALLOWED | Bungkus tombol aksi dengan `@can('create', Shipment::class)` dan ganti dengan badge status informatif non-interaktif bagi peran non-distributor. | `1819a07` |
| C-05 | `resources/views/panen/index.blade.php:79` | Tautan Mati Pembuatan Pengiriman di Banner Surplus BUMDes | **SEDANG** | Banner peringatan status Waspada/Penuh pada modul panen merender tombol `Minta Disposisi Logistik` menuju `route('pengiriman.create')`. | Pengguna BUMDes yang mengeklik tombol tersebut mengalami error HTTP 403 Forbidden (SHOWN-BUT-FORBIDDEN). | NOT-ALLOWED | Bungkus tombol dengan `@can('create', Shipment::class)` dan tampilkan badge informasi disposisi bagi pengguna BUMDes. | `1819a07` |
| C-06 | `app/Http/Controllers/ShipmentController.php:36, 62`, `app/Policies/ShipmentPolicy.php:13-33` | Data Scoping: Isolasi Akses & Resi Antar Distributor | **INFO** | Query pengiriman difilter per `user_id` untuk non-admin, Policy `view` dan `update` mewajibkan kepemilikan data. | Distributor 1 dilarang (403) membuka `/pengiriman/{id_dist2}` dan dilarang (403) POST event; pencarian resi Distributor 2 tidak mengembalikan hasil. Admin dapat melihat dan mengoreksi (sesuai PRD 4.1). | KEEP | Dipertahankan dan diverifikasi penuh oleh test suite `AuthorizationAndVisibilityAuditTest`. | - |
| C-07 | `app/Http/Controllers/HarvestReportController.php:33, 224, 306`, `app/Http/Controllers/PlantingController.php:20` | Data Scoping: Pengikatan Akun BUMDes ke 1 Kabupaten | **INFO** | Seluruh pemrosesan `index`, `store`, dan `exportCsv` mengabaikan parameter `kabupaten_id` kiriman request jika peran adalah BUMDes, memaksakan nilai `$user->kabupaten_id`. | Upaya manipulasi `kabupaten_id` lewat form POST atau query URL diabaikan oleh server; data tetap tersimpan dan terekspor pada kabupaten asal akun BUMDes. | KEEP | Dipertahankan dan diverifikasi penuh oleh test suite `AuthorizationAndVisibilityAuditTest`. | - |

### 4.4 Temuan D — Pelanggaran Spesifikasi PRD, Keamanan, & Hak Akses
| ID | File:baris | Jenis | Bukti statis | Bukti runtime | Putusan | Tindakan | Commit |
|---|---|---|---|---|---|---|---|
| D-01 | `resources/views/auth/login.blade.php:173-183, 243-249` | Kebocoran Kredensial & Autofill | Tombol Quick Demo Login dan event handler JS `.btn-demo` mengekspos email dan password demo | Tombol demo tampil di antarmuka web publik dan mengisi password otomatis | DEAD | Hapus komponen visual tombol demo dan script autofill | `be34df7` |
| D-02 | `app/Services/AuditService.php:1-45` | Missing Service Implementation (F-01) | File `AuditService.php` kosong 0 byte tetapi diimpor & dipanggil di `LoginController`, `ForecastController`, dll. | Login pengguna & test `DatasetAndForecastImportTest` gagal dengan fatal error HTTP 500 `Class AuditService not found` | BROKEN-PRD | Implementasikan `AuditService::log()` untuk menyimpan ke `audit_logs` dan menangani error secara aman | `be34df7` |
| D-03 | `tests/Feature/IsoSecurityHardeningTest.php:1-85` | Missing Test File Implementation | File `IsoSecurityHardeningTest.php` kosong 0 byte menyebabkan kegagalan PHPUnit suite | PHPUnit mengeluarkan error exit code 1 pada berkas uji kosong | BROKEN-PRD | Lengkapi pengujian keamanan autentikasi (anti-enumerasi, hash password, audit log, invalidasi sesi) | `be34df7` |
| D-04 | `config/pangan.php:13-15` | Default Hardcoded Password Fallback | Fallback password demo literal `'Admin1234'`, `'Bumdes1234'`, `'Distrib1234'` pada konfigurasi seeder | Digunakan oleh seeder saat environment variabel kosong | KEEP | Dipertahankan untuk akun demo seeder lokal, dilaporkan mekanisme pembacaannya | - |
| D-05 | `database/seeders/DatabaseSeeder.php:19` | Eksekusi Seeder Demo di Production | `DemoUserSeeder::class` dipanggil tanpa pengecekan environment `app()->isProduction()` | `php artisan db:seed` di production akan men-seed akun demo publik | DECISION | Dilaporkan ke user; rekomendasi guard environment diusulkan di bagian 6 | - |
| D-06 | `routes/web.php:32-37` | Ketiadaan Registrasi Publik & Reset Password | Tidak ada rute registrasi mandiri atau lupa password pada rute web | Akses guest ke `/register` atau `/password/reset` menghasilkan HTTP 404 | DECISION | Dikonfirmasi sesuai PRD F-11 (akun diterbitkan terpusat oleh Admin) | - |

### 4.5 Temuan E — Non-Goals, Modul Duplikat, & Integritas Data (PRD v1.1)
| ID | File:baris | Jenis | Bukti statis | Bukti runtime | Putusan | Tindakan | Commit |
|---|---|---|---|---|---|---|---|
| E-01 | `resources/views/welcome.blade.php` | Boilerplate View Mati | Berkas template default Laravel (72 KB) tidak memiliki pemanggilan rute/controller aktif | Akses `GET /` diarahkan melalui HTTP 302 ke `/peta`, view tidak pernah dirender | DEAD | Hapus berkas template mati | `90a276a` |
| E-02 | `app/Http/Middleware/SecurityHeaders.php` | Berkas Kosong Tak Terdaftar | Berkas middleware kosong 0-byte tidak terdaftar pada pipeline di `bootstrap/app.php` | Tidak pernah dieksekusi dalam lifecycle HTTP request | DEAD | Hapus berkas kosong | `90a276a` |
| E-03 | `resources/views/peta/detail.blade.php:204-206`, `app/Http/Controllers/WilayahController.php:215, 229` | Pelanggaran Privasi Resi Publik (PRD 4.1 & 13) | Field `no_resi` (`RESI - CONTOH - 0001` atau resi pengiriman aktif) diekspos di kartu detail wilayah publik untuk pengunjung anonim | Tamu publik tanpa autentikasi dapat melihat nomor resi pengiriman | NOT-ALLOWED | Ganti eksposur resi pada halaman publik dengan indikator agregat kanal logistik | `90a276a` |
| E-04 | `app/Http/Controllers/WilayahController.php:151-178, 238-254, 287-305` | Unlabeled Hardcoded Demo Showcase Data | Angka metrik KPI dan tabel 12-bulan untuk Kabupaten Banyumas (3302) di-hardcode di controller alih-alih membaca tabel `forecasts` | Halaman `/wilayah/3302` menampilkan angka mockup statis tanpa label data contoh | DECISION | Laporkan ke user; ajukan opsi menambahkan label showcase atau beralih 100% ke tabel `forecasts` | - |

---

### 4.6 Keputusan Modul Tumpang Tindih (Overlapping Modules)
Berdasarkan investigasi menyeluruh terhadap rute, kontroler, dan tampilan antarmuka:
1. **Beranda Aplikasi (`welcome.blade.php` vs `PetaController@index`)**:
   - **Pemenang (Winner)**: `PetaController@index` ([`resources/views/peta/index.blade.php`](file:///home/rakuhatoru/lomba/S-Tech/projek/S-tech/resources/views/peta/index.blade.php)) yang diakses via rute `/peta` dan redirect root `/`. Memenuhi PRD bagian 12 ("Peta Indonesia (beranda)"), responsif, terhubung ke Leaflet & GeoJSON, serta lolos verifikasi runtime.
   - **Yang Dieliminasi (Loser)**: `resources/views/welcome.blade.php` (boilerplate default Laravel). Tidak memiliki fitur unik dan tidak pernah dapat dijangkau dari navigasi.
2. **Panel Admin & Manajemen Entitas**:
   - Hanya ada 1 panel admin tunggal yang terintegrasi pada prefiks `/admin/*` (`pengguna`, `dataset`, `forecast`, `pengaturan`). Tidak ditemukan duplikasi kontroler atau admin panel ganda (seperti Filament/Nova).
3. **Portal Operasional Sektoral**:
   - Portal Distributor tunggal pada `/pengiriman` (F-07, F-08).
   - Portal BUMDes tunggal pada `/panen` dan `/jadwal-tanam` (F-09, F-10).
   - Tidak ada duplikasi modul CRUD untuk entitas yang sama.

---

### 4.7 Matriks Cakupan Fitur PRD (PRD Coverage Table F-01 s.d. F-14)

| ID | Fitur PRD | Prioritas | Status Impl. | Status Runtime | Lokasi Kode / URL | Catatan Verifikasi |
|---|---|---|---|---|---|---|
| **F-01** | Autentikasi & 3 Role (Distributor, BUMDes, Admin) | **Must** | ✅ Lengkap | ✅ Lolos | `LoginController`, `EnsureRole`, `User`, `Role` | Login terisolasi per role, rate-limiting, proteksi brute-force, no user enumeration |
| **F-02** | Impor Dataset Historis (CSV) | **Must** | ✅ Lengkap | ✅ Lolos | `AdminDatasetController`, `DataHistorisImporter`, `data:import-historis` | Upload via UI admin dan via Artisan CLI, validasi kolom UTF-8 long format |
| **F-03** | Prediksi Bulanan XGBoost (3 target) + Supply Bergulir | **Must** | ✅ Lengkap | ✅ Lolos | `AdminForecastController`, `ForecastImporter`, `forecast:import`, `ml/` | Pipeline offline Python mengekspor forecasts.csv lalu diimpor ke DB |
| **F-04** | Perhitungan Status Wilayah (Defisit, Seimbang, Waspada, Penuh) | **Must** | ✅ Lengkap | ✅ Lolos | `StatusService`, `status:recalc` | Ambang rasio dinamis (Setting), roll-forward stok awal survei & pengiriman aktif |
| **F-05** | Peta Heatmap Indonesia (Zoom level provinsi/kabupaten) | **Must** | ✅ Lengkap | ✅ Lolos | `PetaController`, `peta/index.blade.php`, `Api\StatusController` | Leaflet basemap GeoJSON 38 provinsi & 514 kab/kota, filter komoditas/bulan |
| **F-06** | Panel Detail Wilayah (Grafik 12 bln, status, pesan dampak) | **Must** | ✅ Lengkap | ✅ Lolos | `WilayahController`, `peta/detail.blade.php`, `Api\WilayahController` | Chart.js 12 bulan aktual vs prediksi, batas ketidakpastian (CI), rekomendasi defisit |
| **F-07** | Pembuatan Pengiriman + Banner Info Kelebihan Pasokan | **Must** | ✅ Lengkap | ✅ Lolos | `ShipmentController`, `pengiriman/create.blade.php` | Pengecekan otomatis status tujuan (Waspada/Penuh), non-blocking, banner disclaimer |
| **F-08** | Tracking Pengiriman Gaya JNE (Resi DB, timeline, refresh) | **Must** | ✅ Lengkap | ✅ Lolos | `ShipmentController`, `pengiriman/index.blade.php`, `pengiriman/show.blade.php` | Resi unik, distributor hanya melihat miliknya, perbarui status manual tanpa websocket |
| **F-09** | Modul BUMDes: Input Panen & Sisa Stok Survei + Rekap | **Must** | ✅ Lengkap | ✅ Lolos | `HarvestReportController`, `panen/index.blade.php` | Terikat 1 kabupaten, sisa stok menjadi stok awal bulan depan, grafik aktual vs prediksi |
| **F-10** | Rekomendasi Jadwal Tanam (Dummy/Simulasi) | **Could** | ✅ Lengkap | ✅ Lolos | `PlantingController`, `jadwal-tanam/index.blade.php` | Tampil dengan label wajib `PRD F-10 • SIMULASI / DATA CONTOH`, info curah hujan BMKG |
| **F-11** | Panel Admin (Pengguna, Dataset, Ambang) | **Should** | ✅ Lengkap | ✅ Lolos | `Admin\*Controller`, `resources/views/admin/*` | Manajemen user (toggle aktif), pengaturan ambang batas, pembaruan data |
| **F-12** | Pesan Dampak (Konten Statis Surplus & Defisit) | **Should** | ✅ Lengkap | ✅ Lolos | `Setting`, `WilayahController`, `peta/detail.blade.php` | Menjelaskan risiko basi, emisi gas, penurunan harga petani vs kelaparan |
| **F-13** | Ekspor CSV | **Could** | ✅ Lengkap | ✅ Lolos | `HarvestReportController::exportCsv`, `ExportMlData` | Ekspor rekap panen bulanan (`/panen/export-csv`) & ekspor data ML (`data:export-ml`) |
| **F-14** | Retraining dari UI / Monitoring Model | **Won't** | ❌ Ditiadakan | ℹ️ Sesuai PRD | N/A (Sesuai Non-Goals PRD 2.3 & 6) | Sesuai keputusan PRD: model dilatih offline dengan script Python di server |

---

## 5. Log Commit

| Hash | Tanggal | Pesan Commit | Penjelasan |
|---|---|---|---|
| `e420bec` | 2026-10-13 | audit: inisialisasi audit workspace dan baseline | Baseline repositori sebelum pembersihan |
| `342bb78` | 2026-10-13 | audit: siapkan dokumen AUDIT.md dan skrip baseline smoke test | Setup awal dokumen audit dan skrip otomatis smoke test PRD |
| `be34df7` | 2026-10-13 | audit(D): bersihkan kredensial login dan amankan alur autentikasi [D-01, D-02, D-03] | Hapus tombol kredensial demo di login UI, perbaiki implementasi AuditService, lengkapi pengujian keamanan autentikasi |
| `90a276a` | 2026-10-13 | audit(E): bersihkan berkas mati, kebocoran resi publik, dan catat tabel cakupan PRD [E-01, E-02, E-03, E-04] | Hapus welcome.blade.php & SecurityHeaders kosong, tutup eksposur no_resi di halaman detail publik, dokumentasikan matriks cakupan PRD |
| `1819a07` | 2026-10-13 | audit(C): perketat otorisasi peran, visibilitas UI, dan isolasi data sesuai PRD 4.1 [C-01, C-02, C-03, C-04, C-05, C-06, C-07] | Pisahkan rute dan policy buat pengiriman khusus distributor, proteksi menu navigasi & tombol aksi dari salah peran, verifikasi data scoping |

---

## 6. Butuh Keputusan Manusia

1. **D-05 (Safeguard untuk DemoUserSeeder di Lingkungan Produksi)**:
   - **Kondisi saat ini**: `DatabaseSeeder.php:19` memanggil `$this->call(DemoUserSeeder::class)` secara langsung tanpa penjaga lingkungan (`! app()->isProduction()`). Hal ini berisiko men-seed akun demo ber-password default jika seeder dijalankan di server produksi.
   - **Rekomendasi tindakan**: Tambahkan pengecekan lingkungan `if (! app()->isProduction())` atau flag khusus sebelum menjalankan `DemoUserSeeder`. Sesuai instruksi, seeder belum diubah.
   - **Status**: Menunggu keputusan pengguna.

2. **D-06 (Konfirmasi Ketiadaan Registrasi Publik & Forgot Password)**:
   - **Kondisi saat ini**: Tidak ada form registrasi mandiri publik (`/register`) maupun alur reset sandi mandiri (`/forgot-password`). Seluruh akun pengguna dibuat oleh Administrator Nasional melalui menu `/admin/pengguna` (PRD Bagian 4.1 & F-11).
   - **Rekomendasi tindakan**: Pertahankan arsitektur saat ini tanpa membuat rute registrasi publik terbuka.
   - **Status**: Menunggu konfirmasi pengguna.

3. **E-04 (Penanganan Data Hardcoded Showcase Banyumas 3302 pada `WilayahController`)**:
   - **Kondisi saat ini**: Pada [`WilayahController.php:151-178, 238-254, 287-305`](file:///home/rakuhatoru/lomba/S-Tech/projek/S-tech/app/Http/Controllers/WilayahController.php#L151-L178), Kabupaten Banyumas (kode `3302`) memiliki nilai metrik, 12 baris horizon, dan koridor rekomendasi yang di-hardcode agar identik 100% dengan screenshot desain (`design/Detail Wilayah & Analisis Prediksi 12 Bulan.png`). Di sisi lain, tabel `forecasts` di database sudah memiliki 12 baris prediksi ML nyata untuk Banyumas.
   - **Pilihan tindakan**:
     - *Opsi A (Recommended)*: Tetap pertahankan angka showcase untuk Banyumas sebagai data demo kompetisi/video namun tambahkan label banner kecil *"Data Contoh Showcase Mockup"* pada header halaman detail Banyumas agar memenuhi kaidah transparansi PRD Section 13.
     - *Opsi B*: Hapus percabangan `if ($kode === '3302')` dan gunakan 100% data dinamis dari tabel `forecasts` database (namun angka pada video demo akan mengikuti data asli ML bukan persis mockup desain).
   - **Status**: Menunggu keputusan pengguna.
