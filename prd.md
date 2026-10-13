# PRD: Sistem Pemantauan & Early-Warning Surplus Pangan (Web)

| Item | Detail |
|---|---|
| **Nama produk** | [TBD] – ditentukan sendiri oleh tim |
| **Jenis produk** | Aplikasi web **pemantauan dan pelacakan** (bukan aplikasi jual-beli) |
| **Versi / status** | v1.1 – Siap dikerjakan (masih ada 2 pertanyaan kecil di bagian 19) |
| **Tanggal** | 10 Oktober 2026 |
| **Target MVP** | 18 Oktober 2026 (prototipe + video demo) |
| **Tim** | 1 software developer (juga mengerjakan model ML), 1 ideation/product, 1 presenter |

> **Legenda:** **[Keputusan]** = sudah diputuskan tim · **[Asumsi]** = saya simpulkan, perlu dikonfirmasi · **[TBD]** = belum ada jawaban · **[Rekomendasi]** = saran saya.

---

## 1. Ringkasan Produk

Website untuk **memantau keseimbangan pasokan (supply) dan kebutuhan (demand)** pangan per kabupaten/kota di seluruh Indonesia, **memprediksinya 12 bulan ke depan (diperbarui setiap bulan untuk koreksi, dengan gambaran tahunan)**, dan **menandai wilayah yang berisiko kelebihan pasokan** (berisiko basi/terbuang) lewat peta heatmap. Distributor dapat **melacak pengiriman** (gaya pelacakan JNE), dan BUMDes dapat **memantau serta menginput hasil panen** per kabupaten.

> **Penegasan produk [Keputusan]:** sistem ini **hanya layanan pemantauan dan tracking**. **Tidak ada penjualan, marketplace, pembayaran, maupun notifikasi** (email/WhatsApp/SMS/push). Semua informasi dan peringatan ditampilkan di dalam web.

- **Komoditas:** beras, jagung, singkong **[Keputusan]**
- **Cakupan:** nasional, level kabupaten/kota dengan agregasi provinsi **[Keputusan]**
- **Platform:** web saja **[Keputusan]**

## 2. Masalah & Tujuan

### 2.1 Masalah
Pasokan sering dikirim ke wilayah yang sudah kelebihan stok tanpa ada informasi yang mudah diakses. Akibatnya pangan menumpuk, basi, atau terbuang; harga di tingkat petani turun; dan wilayah lain yang kekurangan tidak terlayani. Belum ada konsep ini dalam bentuk sistem digital **[Keputusan, perlu diverifikasi lagi]**.

### 2.2 Tujuan produk
1. Memprediksi **panen, supply, dan demand** **12 bulan ke depan (resolusi bulanan, dikoreksi setiap bulan)** serta memberi gambaran tahunan, per kabupaten per komoditas **[Keputusan]**.
2. Menentukan status wilayah (**Defisit / Seimbang / Waspada / Penuh**) dan menampilkannya di **peta heatmap Indonesia** dengan level kabupaten/provinsi menyesuaikan zoom.
3. Menginformasikan kepada **semua pengunjung dan pengguna (tanpa perlu login)** bahwa suatu wilayah berisiko kelebihan pasokan, termasuk saat distributor memilih tujuan pengiriman.
4. Menyediakan **tracking pengiriman** bagi distributor.
5. Menyediakan **pemantauan hasil panen** bagi BUMDes (aktual vs prediksi panen) dan **rekomendasi jadwal tanam (simulasi/dummy)** yang disampaikan BUMDes kepada petani agar panen tiap bulan mencukupi kebutuhan.
6. Menjelaskan dampak surplus maupun defisit.

### 2.3 Non-goals **[Keputusan]**
- Tidak ada jual-beli, marketplace, pembayaran, atau layanan transaksi.
- Tidak ada notifikasi di luar web (email/WA/SMS/push) dan tidak ada fitur tambahan di luar pemantauan.
- Tidak ada data/prediksi harga.
- Tidak ada aplikasi mobile native (web responsif cukup).
- Tidak memblokir pengiriman; hanya memberi informasi/peringatan.
- Tidak real-time/push: prediksi diperbarui bulanan, status pengiriman terbarui saat halaman di-refresh **[Keputusan]**.
- Tidak ada GPS live tracking **[Asumsi]**.
- Petani **tidak** memiliki akun; BUMDes menjadi perantara **[Asumsi dari jawaban]**.

## 3. Success Metrics

### 3.1 Metrik model **[Rekomendasi, disetujui tim]**
| Metrik | Kegunaan | Target MVP |
|---|---|---|
| **WAPE** | Akurasi prediksi supply & demand | ≤ 20% (dikalibrasi setelah baseline) |
| **MAE / RMSE** | Besar error dalam kg | Dilaporkan |
| **Perbandingan baseline** (nilai bulan yang sama tahun lalu) | Membuktikan XGBoost lebih baik dari tebakan sederhana | Harus lebih baik |
| **Recall status Penuh** | Wilayah penuh tidak boleh terlewat | ≥ 0,80 |
| **Precision & F1 status** | Menekan peringatan palsu | Dilaporkan |

Validasi memakai pemisahan berdasarkan waktu. Dengan data 2023–2025: **latih 2023–2024, uji 2025**; model final dilatih ulang pada seluruh 2023–2025.

### 3.2 Metrik produk (demo) **[Asumsi]**
- Alur utama berjalan tanpa error: login → lihat peta → pilih wilayah → buat pengiriman → lihat info → lacak pengiriman.
- Status satu kabupaten dapat dilihat dalam ≤ 3 klik dari peta.

## 4. Pengguna & Peran

| Peran | Siapa | Kebutuhan utama |
|---|---|---|
| **Distributor** | Pihak yang mengirim pasokan **[Keputusan]** | Memantau kondisi wilayah tujuan, membuat dan melacak pengiriman |
| **BUMDes** | Pengelola BUMDes **[Keputusan]** | Menginput dan memantau hasil panen per kabupaten; menerima rekomendasi jadwal tanam untuk disampaikan ke petani |
| **Admin** | Pengelola sistem (harapannya pemerintah) **[Keputusan]** | Kelola pengguna, dataset, ambang, pembaruan prediksi |

### 4.1 Matriks hak akses
| Fitur | Distributor | BUMDes | Admin |
|---|---|---|---|
| Lihat peta & status wilayah (juga publik tanpa login) | ✅ | ✅ | ✅ |
| Lihat info peringatan wilayah (juga publik tanpa login) | ✅ | ✅ | ✅ |
| Buat pengiriman | ✅ | ❌ | ❌ |
| Lihat/lacak pengiriman | Milik sendiri | ❌ | Semua |
| Update status pengiriman | ✅ (milik sendiri) **[Keputusan]** | ❌ | ✅ (koreksi) **[Asumsi]** |
| Input hasil panen & sisa stok (form survei) | ❌ | ✅ (kabupaten sendiri) | ✅ (semua) |
| Lihat rekap hasil panen | ❌ | Kabupaten sendiri | Semua |
| Rekomendasi jadwal tanam | ❌ | ✅ | ✅ |
| Kelola user, dataset, ambang | ❌ | ❌ | ✅ |

Pembuatan akun: dibuat oleh Admin **[Asumsi]**. **Tidak semua halaman perlu login [Keputusan]:** peta, detail wilayah, info peringatan, dan grafik prediksi dapat dilihat publik; login hanya untuk fitur berbasis peran (pengiriman, panen, jadwal tanam, admin). **Resi tidak dapat dicari publik:** nomor resi tersimpan di database dan tampil di **dashboard distributor** **[Keputusan]**. **Satu akun BUMDes terikat ke satu kabupaten [Keputusan].**

## 5. User Stories

| ID | Sebagai | Saya ingin | Agar |
|---|---|---|---|
| US-01 | Distributor | melihat peta status wilayah per komoditas dan bulan | tahu wilayah mana yang penuh atau kekurangan |
| US-02 | Distributor | memilih tujuan saat membuat pengiriman dan melihat info jika wilayah itu kelebihan pasokan | mengambil keputusan dengan informasi lengkap |
| US-03 | Distributor | melacak pengiriman dengan nomor resi dan riwayat perjalanan | tahu posisi dan status barang |
| US-04 | BUMDes | mengisi form hasil panen dan **sisa stok (berdasarkan laporan survei)** per komoditas dan bulan | data panen dan stok wilayah tercatat sehingga prediksi lebih akurat |
| US-05 | BUMDes | melihat rekap panen kabupaten saya | memantau jumlah panen bulanan |
| US-06 | BUMDes | melihat rekomendasi jadwal tanam | menyampaikannya kepada petani agar panen tiap bulan mencukupi |
| US-07 | Semua pengguna | melihat penjelasan dampak surplus/defisit | memahami konsekuensinya |
| US-08 | Admin | mengunggah dataset dan memperbarui prediksi bulanan | menjaga prediksi tetap terkini |
| US-09 | Admin | mengatur ambang status dan mengelola akun | sistem sesuai kebijakan |

## 6. Fitur & Prioritas

| ID | Fitur | Prioritas |
|---|---|---|
| F-01 | Autentikasi & 3 role (Distributor, BUMDes, Admin) | **Must** |
| F-02 | Impor dataset historis | **Must** |
| F-03 | Prediksi bulanan **panen, pasokan masuk, dan demand** (XGBoost, 3 target) + **supply dihitung bergulir** (offline → impor ke DB) | **Must** |
| F-04 | Perhitungan status wilayah | **Must** |
| F-05 | **Peta heatmap Indonesia**: mode provinsi/kabupaten otomatis menurut zoom, filter komoditas & bulan, legenda, klik wilayah → panel detail | **Must** |
| F-06 | Panel detail wilayah: grafik aktual vs prediksi, status, pesan dampak | **Must** |
| F-07 | Pembuatan pengiriman + banner info kelebihan pasokan (non-blocking) | **Must** |
| F-08 | Tracking pengiriman gaya JNE di dashboard distributor (nomor resi tersimpan di DB, status terakhir, riwayat perjalanan; terbarui saat halaman di-refresh) | **Must** |
| F-09 | Modul BUMDes: form hasil panen & **sisa stok (laporan survei)** + rekap + perbandingan aktual vs prediksi panen | **Must** |
| F-10 | Rekomendasi jadwal tanam (**dummy/simulasi**: tampilan dan data contoh) | **Could** |
| F-11 | Panel admin (user, dataset, ambang) | **Should** |
| F-12 | Pesan dampak (konten statis) | **Should** |
| F-13 | Ekspor CSV | **Could** |
| F-14 | Retraining dari UI / monitoring model | **Won't (MVP)** |

### 6.1 Spesifikasi peta (F-05) **[Keputusan + Asumsi]**
- Basemap Indonesia dengan **GeoJSON** (provinsi + kabupaten/kota) **[Keputusan: perlu file GeoJSON]**.
- **Zoom rendah → warna per provinsi**, **zoom tinggi → per kabupaten/kota**.
- Warna sesuai status: Defisit, Seimbang, Waspada, Penuh. Wilayah tanpa data ditampilkan **abu-abu** **[Keputusan]**.
- Status provinsi = agregasi kabupaten **[Asumsi]**: total supply ÷ total demand seluruh kabupaten di provinsi itu.
- Filter: komoditas, bulan. Tooltip dan klik → panel detail.
- Fallback bila GeoJSON/performa bermasalah: tabel wilayah berwarna.

### 6.2 Acceptance criteria inti

**F-07 Pengiriman + info kelebihan pasokan**
- Setelah memilih komoditas dan kabupaten tujuan, tampil status wilayah.
- Status **Waspada/Penuh** → banner: status, rasio keterisian, pesan dampak, dan kalimat: *"Sistem ini bukan layanan penjualan, hanya layanan pemantauan dan tracking."* **[Keputusan]**.
- Pengguna **boleh melanjutkan** setelah mengonfirmasi **[Keputusan]**.
- Status **Defisit** → tampil info wilayah membutuhkan pasokan.
- Info kelebihan pasokan juga tampil di peta/panel detail untuk **semua pengguna**.
- Level status saat pengiriman dibuat disimpan.

**F-08 Tracking gaya JNE** **[Keputusan, detail status adaptasi saya]**
- Nomor resi **diinput sendiri oleh distributor** saat membuat pengiriman **[Keputusan]**; harus unik. Sistem **tidak** terhubung ke API ekspedisi, sehingga status dan riwayat perjalanan diisi manual oleh distributor **[Asumsi]**.
- Halaman tracking menampilkan: nomor resi, status terakhir, asal → tujuan, komoditas dan jumlah, distributor, estimasi tiba **[Asumsi]**, dan **riwayat perjalanan** (tanggal-jam, lokasi, keterangan) berurutan terbaru di atas.
- Contoh status **[Rekomendasi, sesuaikan]**: Pengiriman dibuat → Barang diterima di titik asal → Diproses → Dalam perjalanan → Tiba di kabupaten tujuan → Diterima. Pengiriman dibatalkan sebagai opsi.
- Distributor hanya melihat pengiriman miliknya.
- Status pengiriman **diperbarui oleh distributor** **[Keputusan]**; admin dapat mengoreksi **[Asumsi]**.
- Nomor resi **tersimpan di database** dan tampil di **dashboard distributor**; distributor dapat mencari resi di dashboardnya **[Keputusan]**.
- Status **terbarui saat halaman di-refresh** (tanpa websocket/push) **[Keputusan]**.
- Resi tidak dapat dicari publik **[Keputusan]**.

**F-09 Form hasil panen BUMDes**
- Field: kabupaten (otomatis dari akun), komoditas, periode (bulan), jumlah panen (**kg**), **sisa stok akhir bulan (kg) berdasarkan laporan survei** **[Keputusan]**, catatan/tanggal survei opsional.
- Panen aktual mengoreksi supply bulan tersebut. **Sisa stok yang diinput menjadi stok awal bulan berikutnya** dan menggantikan nilai hitungan otomatis **[Keputusan]**.
- Pelaporan dilakukan **setiap bulan, per komoditas, untuk sisa stok akhir bulan** **[Keputusan]**.
- Jika BUMDes belum melapor sisa stok, sistem memakai **hitungan otomatis** (supply − demand, minimal 0) sebagai cadangan dan menandainya **"stok estimasi"** di tampilan **[Keputusan]**.
- Rekap per bulan dan per komoditas dalam tabel dan grafik.

**F-10 Rekomendasi jadwal tanam (dummy)** **[Keputusan]**
- Fitur dibuat sebagai **simulasi**: tampilan dan data contoh, tanpa kalender tanam resmi dan tanpa perhitungan agronomi sungguhan.
- Tujuan yang ingin ditunjukkan: BUMDes menyampaikan jadwal tanam kepada petani agar panen tiap bulan mencukupi kebutuhan bulanan.
- Output contoh untuk BUMDes: tabel bulan tanam yang disarankan per komoditas untuk kabupatennya beserta bulan panen yang diharapkan.
- Halaman diberi label **"Simulasi / data contoh"** agar tidak dianggap rekomendasi resmi.
- Curah hujan nyata boleh ditampilkan sebagai informasi pendukung **[Asumsi]**.

## 7. Alur Pengguna Utama

**Alur A – Distributor membuat pengiriman**
1. Login → buka peta → pilih komoditas dan bulan.
2. Klik *Buat Pengiriman* → isi nomor resi, komoditas, jumlah (kg), asal, kabupaten tujuan.
3. Sistem menampilkan status tujuan; jika Waspada/Penuh, banner info muncul.
4. Konfirmasi dan simpan; nomor resi yang diinput tersimpan di database.
5. Nomor resi tersimpan di database dan muncul di dashboard distributor; refresh halaman untuk melihat status terbaru.

**Alur B – BUMDes**
1. Login → *Input Hasil Panen & Sisa Stok* → simpan.
2. Lihat rekap panen dan status kabupaten.
3. Buka *Rekomendasi Jadwal Tanam* → sampaikan ke petani.

**Alur C – Admin**
1. Unggah dataset bulan terbaru → jalankan pipeline model → impor hasil prediksi → peta terbarui.

## 8. Logika Inti: Status & Informasi

### 8.1 Definisi
- **Surplus berisiko** = pasokan melebihi kebutuhan sehingga berisiko basi/terbuang **[Keputusan]**.
- **Demand (kebutuhan)** = prediksi dari **penjualan sebelumnya**; **tidak ada data kapasitas penyimpanan** per kabupaten **[Keputusan]**.
- **Supply (pasokan)** = **gabungan semua persediaan**: hasil panen + sisa/stok dari bulan sebelumnya + pasokan masuk dari luar kabupaten/kota **[Keputusan]**. Rumus: `supply_kg = panen_kg + stok_awal_kg + pasokan_masuk_kg`.
- **Pasokan masuk** = pasokan yang datang dari **luar kabupaten/kota** (bukan impor luar negeri; kata "impor" pada draf sebelumnya hanya beda istilah) **[Keputusan]**.
- **Stok awal** = sisa bulan sebelumnya, **diinput BUMDes berdasarkan laporan survei** **[Keputusan]**. Hitungan otomatis (`supply − demand` bulan sebelumnya, minimal 0) hanya dipakai sebagai **cadangan** bila laporan belum ada **[Keputusan]**. Karena berasal dari survei, sisa stok sudah mencerminkan kondisi nyata, termasuk susut/basi.
- **Panen** = salah satu komponen supply; diinput BUMDes, diprediksi untuk monitoring (aktual vs prediksi) dan menjadi masukan perhitungan supply **[Asumsi]**.
- **Satuan data: kg per kabupaten** **[Keputusan]**.

### 8.2 Rumus **[Keputusan untuk stok; Asumsi untuk penyesuaian aplikasi]**
```
stok_awal(t)         = sisa_survei(t−1)              # diinput BUMDes (laporan survei)
                       jika belum ada → stok_akhir_hitung(t−1)  # cadangan, ditandai "estimasi"
supply(t)            = panen(t) + stok_awal(t) + pasokan_masuk(t)
stok_akhir_hitung(t) = max(0, supply(t) − demand(t))  # untuk prediksi/cadangan
rasio(t)             = supply(t) / demand(t)
```
Untuk prediksi, **panen, pasokan masuk, dan demand diprediksi model**, sedangkan **supply dihitung bergulir** dari sisa stok terakhir yang dilaporkan BUMDes (survei), bukan dimodelkan langsung.

Penyesuaian yang ditampilkan di aplikasi:
```
supply_tampil = supply(t)
                + (panen_aktual − panen_prediksi)         # jika BUMDes sudah input panen bulan itu
                + pengiriman_masuk_aktif − pengiriman_keluar_aktif
```
Input panen aktual **mengoreksi** supply bulan itu; pada pembaruan bulanan, pipeline menghitung ulang rangkaian stok ke depan. Pengiriman keluar **mengurangi** supply kabupaten asal dan pengiriman masuk **menambah** supply kabupaten tujuan **[Keputusan]**. Pengiriman yang dicatat di aplikasi dianggap **tambahan** di atas pasokan masuk dasar dari model, untuk menghindari hitung ganda **[Asumsi]**.

### 8.3 Status **[Keputusan: ambang disetujui]**
| Status | Rasio | Perilaku |
|---|---|---|
| Defisit | < 70% | Info wilayah membutuhkan pasokan + dampak kekurangan |
| Seimbang | 70% – < 90% | Tanpa peringatan |
| Waspada | 90% – < 100% | Banner kuning |
| Penuh | ≥ 100% | Banner merah + dampak kelebihan pasokan |

Ambang disimpan sebagai konfigurasi (Admin).

### 8.4 Pesan dampak **[Keputusan]**
| Kondisi | Dampak |
|---|---|
| **Surplus/Penuh** | Pangan berisiko basi/terbuang dan menghasilkan emisi gas ke lingkungan; harga di tingkat petani dapat turun karena pasokan melimpah |
| **Defisit** | Risiko kekurangan pangan di wilayah tersebut hingga kelaparan bila tidak dipenuhi |

Pesan berupa penjelasan umum tanpa klaim angka kecuali ada sumber valid.

## 9. Data

### 9.1 Status data **[penting]**
**Dataset memakai data riil seluruh Indonesia, sudah tersedia, periode 2023–2025 (±36 bulan)** **[Keputusan]**. Catatan: data penjualan per kabupaten per bulan kemungkinan tidak tersedia publik. Bila tidak ditemukan, kebutuhan (demand) dibangun dari data riil konsumsi per kapita × jumlah penduduk × pola musiman, dan data yang hanya tersedia tahunan perlu dipecah menjadi bulanan secara statistik. Penggunaan proxy ini **disetujui tim** **[Keputusan]**; metodenya sebaiknya dicantumkan jelas di aplikasi dan video **[Rekomendasi]**.

| Kebutuhan | Sumber yang direkomendasikan | Catatan |
|---|---|---|
| Panen & pasokan masuk (supply = panen + sisa sebelumnya + pasokan masuk dari luar kabupaten) | BPS/Kementan; portal Satu Data tiap kabupaten; Dinas Pertanian | Umumnya tahunan per kabupaten/kecamatan; bulanan terbatas (padi lewat KSA) |
| Penjualan (demand) | Kemungkinan tidak ada data publik per kabupaten per bulan | Proxy: konsumsi per kapita (Susenas) × jumlah penduduk × pola musiman; verifikasi ketersediaan |
| Curah hujan | Sudah ada per kabupaten per bulan **[Keputusan]** | Sumber asli: BMKG/portal data daerah |
| Penduduk | BPS | Untuk proxy demand |
| Batas wilayah | GeoJSON provinsi & kabupaten/kota | Perlu kode wilayah yang cocok dengan dataset |
| Kalender tanam | Tidak dibutuhkan | F-10 dibuat dummy **[Keputusan]** |

### 9.2 Format dataset (CSV) **[Rekomendasi, menunggu pengisian tim]**

File contoh: `dataset_template.csv` (berisi 4 baris fiktif, hanya untuk memperlihatkan format).

**Bentuk:** *long format*, **satu baris = satu kabupaten × satu komoditas × satu bulan**. Cakupan: sekitar 514 kabupaten/kota × 3 komoditas × 36 bulan (2023-01 s.d. 2025-12) ≈ 55 ribu baris bila lengkap.

| # | Kolom | Tipe | Wajib | Keterangan |
|---|---|---|---|---|
| 1 | `kode_provinsi` | teks | ✅ | Kode BPS/Kemendagri 2 digit, mis. `33`. Simpan sebagai teks agar angka nol di depan tidak hilang |
| 2 | `nama_provinsi` | teks | ✅ | |
| 3 | `kode_kabupaten` | teks | ✅ | Kode 4 digit, mis. `3302`. **Harus sama dengan kode di GeoJSON** |
| 4 | `nama_kabupaten` | teks | ✅ | |
| 5 | `komoditas` | teks | ✅ | Hanya `beras`, `jagung`, `singkong` (huruf kecil) |
| 6 | `periode` | teks | ✅ | Format `YYYY-MM`, mis. `2023-01` |
| 7 | `panen_kg` | angka | ✅ | Hasil panen bulan itu (**target prediksi 1**) |
| 8 | `stok_awal_kg` | angka | ✅ | Sisa bulan sebelumnya. **Data historis tersedia setiap bulan** **[Keputusan]**, jadi diisi dari data. Hanya jika ada sel kosong, dihitung otomatis (cadangan) = `supply − demand` bulan sebelumnya (minimal 0). Untuk bulan baru, diinput BUMDes lewat aplikasi (F-09) |
| 9 | `pasokan_masuk_kg` | angka | ✅ | Pasokan masuk dari luar kabupaten/kota pada bulan itu; isi 0 bila tidak ada **[Keputusan]**; jika seluruh datanya 0, model ini dilewati dan prediksinya 0 **[Asumsi]** (**target prediksi 3**) |
| 10 | `supply_kg` | angka | otomatis | Total persediaan = `panen_kg + stok_awal_kg + pasokan_masuk_kg`. **Boleh dikosongkan** (dihitung otomatis); **tidak diprediksi langsung**, melainkan dihitung bergulir |
| 11 | `demand_kg` | angka | ✅ | Kebutuhan bulan itu, berbasis penjualan; atau proxy konsumsi × penduduk jika penjualan tidak ada (**target prediksi 2**) |
| 12 | `curah_hujan_mm` | angka | ✅ | Curah hujan bulanan kabupaten (fitur model) |
| 13 | `jumlah_penduduk` | angka | opsional | Fitur dan bahan proxy demand |
| 14 | `luas_panen_ha` | angka | opsional | Fitur tambahan |
| 15 | `flag_estimasi` | 0/1 | opsional | `1` jika angka pada baris itu hasil estimasi/pemecahan statistik, `0` jika data asli |
| 16 | `sumber_data` | teks | opsional | Asal data baris itu (untuk audit) |

**Aturan pengisian**
1. Encoding **UTF-8**, pemisah kolom **koma**, desimal **titik** (`412.5`), **tanpa pemisah ribuan**, tanpa tanda satuan.
2. Semua besaran di file dan database dalam **kg** (bukan ton) **[Keputusan]**; aturan tampilan kg/ton di aplikasi ada di bagian 13.
3. **Sel kosong = data tidak diketahui**; angka **0 = benar-benar nol**. Jangan mengisi 0 untuk data yang hilang.
4. Tidak boleh ada duplikat kombinasi (`kode_kabupaten`, `komoditas`, `periode`).
5. Validasi saat pemuatan data: bila `supply_kg` diisi, harus sama dengan `panen_kg + stok_awal_kg + pasokan_masuk_kg` (toleransi pembulatan kecil); `komoditas` dan `periode` harus valid; `kode_kabupaten` harus ada di GeoJSON.
6. Kabupaten/komoditas dengan data tidak lengkap tetap boleh ada; di peta tampil **abu-abu** **[Keputusan]**.
7. Boleh dibagi per tahun (`dataset_2023.csv`, dst.) dengan kolom yang sama.
8. **Stok awal bulan pertama (2023-01)** tersedia dan **wajib diisi** pada baris 2023-01 **[Keputusan]**.
9. **Sisa stok dari survei:** data historis sisa stok tersedia tiap bulan **[Keputusan]**; isi pada `stok_awal_kg` bulan berikutnya. Sel yang kosong dihitung otomatis sebagai cadangan. Tidak ada parameter susut karena sisa stok survei sudah mencerminkan kondisi nyata **[Keputusan]**.

**Catatan data leakage:** pada saat memprediksi, model hanya boleh memakai informasi yang sudah diketahui. Karena `stok_awal_kg` bulan mendatang bergantung pada hasil bulan sebelumnya, fitur dari kolom ini hanya dipakai dalam bentuk **lag minimal sepanjang horizon prediksi**.

### 9.2b Format hasil prediksi (CSV untuk diimpor ke Laravel) **[Rekomendasi]**

File contoh: `forecasts_template.csv` (4 baris fiktif). Dihasilkan oleh pipeline Python tiap bulan, **satu baris = satu kabupaten × satu komoditas × satu bulan target**. Satu kali jalan menghasilkan 12 baris (horizon 1–12) per kabupaten × komoditas, sekitar 18 ribu baris.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `kode_kabupaten` | teks | Sama dengan dataset dan GeoJSON |
| `komoditas` | teks | `beras` / `jagung` / `singkong` |
| `periode_dasar` | teks | Bulan data terakhir yang dipakai model (`YYYY-MM`) |
| `periode_target` | teks | Bulan yang diprediksi (`YYYY-MM`) |
| `horizon_bulan` | angka | 1 sampai 12 |
| `panen_pred_kg` | angka | Prediksi panen |
| `pasokan_masuk_pred_kg` | angka | Prediksi pasokan masuk dari luar kabupaten/kota |
| `stok_awal_pred_kg` | angka | Sisa bulan sebelumnya hasil hitungan bergulir |
| `supply_pred_kg` | angka | `panen_pred + pasokan_masuk_pred + stok_awal_pred` (dihitung, bukan diprediksi langsung) |
| `demand_pred_kg` | angka | Prediksi demand |
| `rasio_pred` | angka | `supply_pred_kg / demand_pred_kg` (desimal, mis. `1.0511` = 105,11%) |
| `status_pred` | teks | `defisit` / `seimbang` / `waspada` / `penuh` sesuai ambang bagian 8.3 |
| `rasio_bawah`, `rasio_atas` | angka | Opsional: rentang ketidakpastian untuk grafik |
| `versi_model` | teks | Mis. `v1-2026Q1` |
| `dibuat_pada` | datetime | Waktu pipeline dijalankan |

Aturan: kunci unik (`kode_kabupaten`, `komoditas`, `periode_dasar`, `periode_target`); impor bulan baru **tidak menghapus** prediksi lama (disimpan sebagai riwayat untuk evaluasi aktual vs prediksi).

### 9.3 Entitas database (MySQL) **[Asumsi]**
`users`, `roles`, `provinsi`, `kabupaten`, `komoditas`, `data_historis`, `forecasts`, `shipments` (termasuk `no_resi`, `level_peringatan`), `shipment_events` (riwayat tracking), `harvest_reports` (panen_kg, sisa_stok_kg, tanggal/catatan survei), `planting_calendar`, `planting_recommendations`, `settings`, `model_versions`.

## 10. Spesifikasi Machine Learning

| Aspek | Keputusan |
|---|---|
| Algoritma | **XGBoost** **[Keputusan]** |
| Target | **Tiga target model** bulanan per kabupaten per komoditas: **panen, pasokan masuk, demand** (satu model XGBoost per target, pipeline yang sama dijalankan berulang). **Supply tidak dimodelkan langsung**, tetapi dihitung bergulir (bagian 8.2) **[Rekomendasi]** |
| Resolusi prediksi | **Bulanan** **[Keputusan]**, diperbarui setiap bulan |
| Horizon | **12 bulan ke depan** **[Keputusan]**, diperbarui **setiap bulan** untuk koreksi; ringkasan **tahunan** sebagai gambaran |
| Akurasi per horizon | Akurasi menurun pada bulan yang jauh; evaluasi WAPE **per horizon (1, 3, 6, 12 bulan)** dan tampilkan rentang ketidakpastian pada grafik **[Rekomendasi]** |
| Keterbatasan data | Histori hanya ±36 bulan: fitur lag 12 hanya terisi ±24 bulan terakhir dan hanya ada 2–3 siklus musiman. Mitigasi: satu model gabungan untuk semua kabupaten (pooling), fitur bulan/musim, curah hujan, jarak horizon; uji 2025 melawan baseline 2024 **[Rekomendasi]** |
| Pencegahan data leakage | Fitur (panen, stok awal, pasokan masuk, supply, demand) hanya dipakai dalam bentuk lag ≥ jarak horizon; curah hujan masa depan tidak dipakai kecuali dari prediksi BMKG **[Rekomendasi]** |
| Fitur | Bulan/musim, lag (≥ jarak horizon) dan rata-rata bergerak dari panen, stok awal, pasokan masuk, supply, demand; curah hujan, penduduk, kabupaten & komoditas |
| Pendekatan | Satu model per target (panen, supply, demand); kabupaten, komoditas, dan **jarak horizon** sebagai fitur (direct multi-horizon) |
| Konsistensi supply | Supply = panen + stok awal + pasokan masuk; stok awal bulan depan = sisa bulan ini dari **laporan survei BUMDes** (cadangan: supply − demand). Supply prediksi dihitung bergulir dari prediksi panen, pasokan masuk, dan demand, dimulai dari sisa stok terakhir yang dilaporkan. Sebagai pembanding opsional, latih model supply langsung dan pakai yang WAPE-nya lebih baik pada data uji 2025 **[Rekomendasi]** |
| Baseline | Nilai bulan yang sama tahun lalu |
| Evaluasi | WAPE, MAE, RMSE; precision/recall/F1 status; split berdasarkan waktu |
| Retraining | **Kuartalan** (tiap 3 bulan) + retraining tambahan bila error melewati batas 2 bulan berturut-turut **[Keputusan: ikut rekomendasi]**. Prediksi diperbarui bulanan memakai data terbaru |
| Deployment MVP | Dilatih **offline di Python**; hasil prediksi diekspor CSV dan diimpor ke Laravel |
| Catatan | Python di server mungkin dapat dijalankan lewat CloudPanel; verifikasi sebelum dipakai |

## 11. Arsitektur & Tech Stack

| Lapisan | Pilihan |
|---|---|
| Backend & web | **Laravel** **[Keputusan]** |
| Database | **MySQL** **[Keputusan]** |
| Hosting | Server dengan **CloudPanel** **[Keputusan]** (milik anggota tim) |
| Peta | Leaflet (atau MapLibre) + GeoJSON provinsi/kabupaten yang disederhanakan **[Rekomendasi]** |
| Grafik | Chart.js **[Asumsi]** |
| ML | Python (pandas, xgboost, scikit-learn), offline |
| Penjadwalan | Laravel Scheduler / cron untuk impor bulanan **[Asumsi]** |

```
Dataset (CSV) → Python: cleaning + XGBoost → forecasts.csv
                                             ↓ (impor Artisan)
                      Laravel + MySQL ← form BUMDes, pengiriman Distributor
                                             ↓
        Web: Peta heatmap · Detail wilayah · Pengiriman & Tracking · Panen · Jadwal Tanam
```

## 12. Struktur Halaman

| Halaman | Peran |
|---|---|
| Login | Semua |
| **Peta Indonesia (beranda)** + filter komoditas/bulan + panel detail | Semua |
| Detail Wilayah (grafik, status, dampak) | Semua |
| Buat Pengiriman | Distributor |
| Daftar Pengiriman & Lacak Resi | Distributor; Admin |
| Input Hasil Panen & Sisa Stok (Survei) & Rekap | BUMDes; Admin |
| Rekomendasi Jadwal Tanam | BUMDes; Admin |
| Admin: Pengguna, Dataset, Ambang, Impor Prediksi | Admin |

## 13. Kebutuhan Non-Fungsional

| Aspek | Kebutuhan |
|---|---|
| Performa | Peta termuat < 5 detik; GeoJSON disederhanakan agar ringan **[Asumsi]** |
| Pembaruan | Prediksi bulanan; status pengiriman terbarui saat halaman di-refresh (bukan push) |
| Keamanan | Password ter-hash, kontrol akses per peran, proteksi bawaan Laravel (CSRF/XSS), HTTPS |
| Privasi | Data pengiriman, akun, dan penjualan bersifat sensitif **[Keputusan]**: distributor hanya melihat pengirimannya; data penjualan hanya ditampilkan agregat |
| Usability | Bahasa Indonesia, responsif di browser HP |
| Format tampilan angka | Data tetap disimpan dalam **kg**. Tampilan di aplikasi (peta, tooltip, tabel, grafik): **≤ 2.000 kg ditampilkan dalam kg** (bilangan bulat, mis. `1.850 kg`); **> 2.000 kg ditampilkan dalam ton dengan 1 angka di belakang koma** (mis. `2.500 kg` → `2,5 ton`; `18.300.000 kg` → `18.300,0 ton`). Format Indonesia: titik untuk ribuan, koma untuk desimal **[Keputusan]**. Pada satu grafik, satuan sumbu ditentukan sekali dari nilai terbesar agar tidak bercampur kg dan ton **[Rekomendasi]**. Form input BUMDes dan distributor tetap dalam kg **[Asumsi]** |
| Format angka berat | Data disimpan dalam **kg**. Tampilan: **≤ 2.000 kg → ditampilkan dalam kg** (mis. 1.500 kg); **> 2.000 kg → ton dengan 1 angka di belakang koma** (mis. 2,5 ton; 12.345,6 ton). Format Indonesia: titik untuk ribuan, koma untuk desimal **[Keputusan]**. Input form tetap dalam kg **[Asumsi]** |
| Keterlacakan | Prediksi mencantumkan versi model dan tanggal pembaruan; data demo dilabeli |

## 14. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Waktu ±8 hari, 1 developer merangkap ML | MVP tak selesai | Fokus fitur Must; model offline; tunda yang Should/Could |
| **Dataset riil tidak lengkap (penjualan kabupaten bulanan hampir pasti tidak tersedia publik)** | Model tidak bisa dilatih/dievaluasi | Gunakan proxy dari data riil (konsumsi × penduduk), pecah data tahunan secara statistik, dan dokumentasikan metodenya secara jujur |
| Peta nasional kabupaten berat dan butuh kode wilayah cocok | Lambat/gagal tampil | Sederhanakan GeoJSON (mapshaper), cocokkan kode BPS/Kemendagri sejak awal, siapkan fallback tabel |
| Pesan dampak dianggap klaim berlebihan | Kredibilitas turun | Gunakan bahasa umum tanpa angka tanpa sumber |
| Definisi supply (panen + pengiriman) mudah dobel hitung | Rasio keliru | Tetapkan aturan: pengiriman keluar mengurangi asal, masuk menambah tujuan |
| Klaim "belum ada aplikasi serupa" keliru | Nilai pembeda lemah | Verifikasi; tekankan pembeda: peta prediksi per kabupaten + info saat memilih tujuan pengiriman |
| Python tidak bisa jalan di hosting | Retraining di server gagal | Latih lokal, impor CSV |
| Prediksi 12 bulan kurang akurat di bulan jauh | Status bulan jauh menyesatkan | Koreksi bulanan, evaluasi per horizon, tampilkan rentang ketidakpastian, tandai bulan jauh sebagai "gambaran" |
| Histori hanya 2023–2025 (±36 bulan) | Pola musiman dan lag 12 bulan terbatas; model rawan overfit | Model gabungan semua kabupaten, validasi waktu (latih 2023–2024, uji 2025), bandingkan dengan baseline, laporkan keterbatasan secara jujur |
| Laporan survei sisa stok BUMDes tidak lengkap atau terlambat | Stok awal kurang akurat, rasio meleset | Cadangan hitungan otomatis, tandai "stok estimasi" di tampilan, dorong pelaporan bulanan; untuk demo gunakan data dummy |

## 15. Rencana Kerja 10–18 Oktober 2026 **[Rekomendasi]**

| Tanggal | Fokus |
|---|---|
| **10 Okt** | Finalisasi PRD; audit dataset riil 2023–2025 yang sudah ada (kelengkapan, kode wilayah, outlier); kumpulkan GeoJSON dan kode wilayah |
| **11 Okt** | Bersihkan dan gabungkan dataset riil nasional; baseline + XGBoost untuk 3 target (panen, pasokan masuk, demand) + hitung supply bergulir; evaluasi; ekspor forecasts.csv |
| **12 Okt** | Setup Laravel, auth & role, migrasi MySQL, impor data & forecast |
| **13–14 Okt** | Peta heatmap (provinsi/kabupaten), panel detail, logika status |
| **15 Okt** | Pengiriman + banner info + tracking resi gaya JNE |
| **16 Okt** | Modul BUMDes (form panen, rekap); rekomendasi jadwal tanam (dummy); pesan dampak |
| **17 Okt** | Seed data dummy, uji alur, deploy ke CloudPanel, rekam video demo |
| **18 Okt** | Buffer & pengumpulan |

## 16. Deliverables **[Keputusan]**
1. **Prototipe web** yang berjalan (idealnya di-hosting).
2. **Video demo**. Alur: peta nasional → zoom provinsi → kabupaten Penuh → panel dampak → buat pengiriman ke kabupaten itu → banner info "bukan layanan penjualan" → alihkan ke kabupaten Defisit → lacak resi → sudut pandang BUMDes input panen → rekomendasi tanam.

Data akun, pengiriman, dan transaksi demo memakai **data dummy** **[Keputusan]**.

## 17. Catatan Peran Tim
- **Developer:** web, peta, integrasi, model XGBoost.
- **Ideation:** aturan bisnis, konten dampak, kalender tanam, penyusunan dataset demo, verifikasi sumber.
- **Presenter:** skenario & narasi video demo.

## 18. Catatan Tambahan
- Klaim "belum ada aplikasi serupa" sebaiknya diverifikasi sebelum dipakai di video/presentasi.
- Peta nasional level kabupaten dan data bulanan per kabupaten adalah bagian paling berat; prioritaskan sejak hari pertama.
