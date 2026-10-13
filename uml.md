# UML & Flowchart: Sistem Pemantauan & Early-Warning Surplus Pangan (Web)

| Item | Detail |
|---|---|
| **Dokumen** | uml.md (pendamping `prd.md` v1.1, `infrastruktur.md`, `databaseschema.md`) |
| **Versi** | v1.0 – Draft |
| **Tanggal** | 11 Oktober 2026 |
| **Notasi** | **Mermaid** (tampil sebagai gambar di GitHub, VS Code dengan ekstensi Mermaid, Obsidian, dan Claude) |

> **Catatan notasi.** Mermaid mendukung langsung diagram kelas, urutan (sequence), dan state machine. Diagram lain (use case, komponen, deployment, paket, struktur komposit, komunikasi, timing, interaction overview) **tidak punya tipe bawaan** di Mermaid, sehingga digambar dengan `flowchart`/`gantt` yang **meniru notasi UML** sedekat mungkin. Semantik tetap UML, bentuk visual sedikit berbeda. Bila perlu notasi UML baku (mis. untuk laporan), diagram dapat dipindahkan ke draw.io atau PlantUML.
>
> **Konsistensi.** Semua diagram mengikuti PRD dan skema database. Data pada diagram objek adalah **contoh fiktif**.

## Daftar Diagram

| # | Diagram | Bagian |
|---|---|---|
| 1 | Use Case Diagram | 1 |
| 2 | Class Diagram (model domain dan lapisan aplikasi) | 2 |
| 3 | Object Diagram | 3 |
| 4 | Component Diagram | 4 |
| 5 | Deployment Diagram | 5 |
| 6 | Package Diagram | 6 |
| 7 | Composite Structure Diagram | 7 |
| 8 | Activity Diagram | 8 |
| 9 | Sequence Diagram | 9 |
| 10 | State Machine Diagram | 10 |
| 11 | Communication Diagram | 11 |
| 12 | Timing Diagram | 12 |
| 13 | Interaction Overview Diagram | 13 |
| 14 | Flowchart | 14 |

---

## 1. Use Case Diagram

**Aktor:** Pengunjung publik (tanpa login), Distributor, BUMDes, Admin, dan Pipeline ML (offline, dijalankan developer).

```mermaid
flowchart LR
    PUB(["Pengunjung publik"])
    DIS(["Distributor"])
    BUM(["BUMDes"])
    ADM(["Admin"])
    MLA(["Pipeline ML offline"])

    subgraph SISTEM["Sistem Pemantauan dan Early-Warning Surplus Pangan"]
        UC1(["Lihat peta heatmap"])
        UC2(["Filter komoditas dan bulan"])
        UC3(["Lihat detail wilayah"])
        UC4(["Lihat info dampak surplus atau defisit"])
        UC5(["Login"])
        UC6(["Buat pengiriman"])
        UC7(["Cek status wilayah tujuan"])
        UC8(["Tampilkan banner info kelebihan pasokan"])
        UC9(["Lihat dan cari pengiriman di dashboard"])
        UC10(["Perbarui status dan riwayat pengiriman"])
        UC11(["Input panen dan sisa stok"])
        UC12(["Lihat rekap panen"])
        UC13(["Lihat rekomendasi jadwal tanam dummy"])
        UC14(["Kelola pengguna"])
        UC15(["Unggah dataset historis"])
        UC16(["Impor hasil prediksi"])
        UC17(["Hitung ulang status wilayah"])
        UC18(["Atur ambang dan pengaturan"])
        UC19(["Koreksi status pengiriman"])
        UC20(["Ekspor data untuk ML"])
    end

    PUB --- UC1
    PUB --- UC3
    PUB --- UC4

    DIS --- UC5
    DIS --- UC6
    DIS --- UC9
    DIS --- UC10
    DIS --- UC1

    BUM --- UC5
    BUM --- UC11
    BUM --- UC12
    BUM --- UC13
    BUM --- UC1

    ADM --- UC5
    ADM --- UC14
    ADM --- UC15
    ADM --- UC16
    ADM --- UC17
    ADM --- UC18
    ADM --- UC19
    ADM --- UC20

    MLA --- UC20
    MLA --- UC16

    UC1 -.->|"include"| UC2
    UC1 -.->|"extend: pilih wilayah"| UC3
    UC3 -.->|"include"| UC4
    UC6 -.->|"include"| UC7
    UC8 -.->|"extend: status waspada atau penuh"| UC6
    UC11 -.->|"include"| UC17
    UC16 -.->|"include"| UC17
    UC15 -.->|"include"| UC17
    UC10 -.->|"include"| UC17
```

**Deskripsi singkat use case utama**

| Use case | Aktor | Prasyarat | Hasil |
|---|---|---|---|
| Lihat peta heatmap | Semua | Prediksi sudah diimpor | Peta berwarna menurut status; provinsi/kabupaten sesuai zoom |
| Buat pengiriman | Distributor | Login; nomor resi unik | Pengiriman tersimpan; banner info tampil bila tujuan Waspada/Penuh; **tidak diblokir** |
| Input panen dan sisa stok | BUMDes | Login; akun terikat ke satu kabupaten | Laporan bulanan tersimpan; sisa stok menjadi stok awal bulan berikutnya |
| Impor hasil prediksi | Admin | File `forecasts.csv` valid | Prediksi 12 bulan masuk database; status dihitung ulang |
| Atur ambang dan pengaturan | Admin | Login | Ambang status (default 0,70 / 0,90 / 1,00) dan teks pesan diperbarui |

---

## 2. Class Diagram

### 2.1 Model domain

```mermaid
classDiagram
    direction LR

    class Role {
        +int id
        +string kode
        +string nama
    }
    class User {
        +int id
        +int role_id
        +int kabupaten_id
        +string name
        +string email
        +string organisasi
        +bool is_active
        +isAdmin() bool
        +isBumdes() bool
        +isDistributor() bool
    }
    class Provinsi {
        +int id
        +string kode
        +string nama
    }
    class Kabupaten {
        +int id
        +int provinsi_id
        +string kode
        +string nama
        +string tipe
        +int jumlah_penduduk
    }
    class Komoditas {
        +int id
        +string kode
        +string nama
    }
    class DataHistoris {
        +date periode
        +decimal panen_kg
        +decimal stok_awal_kg
        +decimal pasokan_masuk_kg
        +decimal supply_kg
        +decimal demand_kg
        +decimal curah_hujan_mm
        +bool flag_estimasi
    }
    class ModelVersion {
        +int id
        +string nama
        +string algoritma
        +date tanggal_latih
        +json metrik
        +bool is_aktif
    }
    class Forecast {
        +date periode_dasar
        +date periode_target
        +int horizon_bulan
        +decimal panen_pred_kg
        +decimal pasokan_masuk_pred_kg
        +decimal stok_awal_pred_kg
        +decimal supply_pred_kg
        +decimal demand_pred_kg
        +decimal rasio_pred
        +StatusLevel status_pred
    }
    class StatusWilayah {
        +date periode
        +decimal supply_kg
        +decimal demand_kg
        +decimal rasio
        +StatusLevel status
        +string sumber
        +bool stok_estimasi
        +decimal pengiriman_masuk_kg
        +decimal pengiriman_keluar_kg
    }
    class Shipment {
        +int id
        +string no_resi
        +decimal jumlah_kg
        +date tanggal_kirim
        +ShipmentStatus status
        +StatusLevel level_peringatan
        +decimal rasio_tujuan
        +bool konfirmasi_peringatan
    }
    class ShipmentEvent {
        +string status
        +string lokasi
        +string keterangan
        +datetime waktu_kejadian
    }
    class HarvestReport {
        +date periode
        +decimal panen_kg
        +decimal sisa_stok_kg
        +date tanggal_survei
    }
    class PlantingRecommendation {
        +int bulan_tanam
        +int bulan_panen
        +string catatan
        +bool is_dummy
    }
    class Setting {
        +string kunci
        +string nilai
    }
    class DatasetImport {
        +string tipe
        +string nama_file
        +int jumlah_baris
        +int jumlah_gagal
        +string status
    }
    class StatusLevel {
        <<enumeration>>
        defisit
        seimbang
        waspada
        penuh
        tanpa_data
    }
    class ShipmentStatus {
        <<enumeration>>
        dibuat
        diterima_di_asal
        diproses
        dalam_perjalanan
        tiba_di_tujuan
        diterima
        dibatalkan
    }

    Role "1" --> "*" User : memiliki
    Kabupaten "0..1" --> "*" User : wilayah BUMDes
    Provinsi "1" --> "*" Kabupaten : berisi
    Kabupaten "1" --> "*" DataHistoris
    Komoditas "1" --> "*" DataHistoris
    Kabupaten "1" --> "*" Forecast
    Komoditas "1" --> "*" Forecast
    ModelVersion "0..1" --> "*" Forecast : menghasilkan
    Kabupaten "1" --> "*" StatusWilayah
    Komoditas "1" --> "*" StatusWilayah
    User "1" --> "*" Shipment : distributor
    Komoditas "1" --> "*" Shipment
    Kabupaten "1" --> "*" Shipment : asal
    Kabupaten "1" --> "*" Shipment : tujuan
    Shipment "1" *-- "*" ShipmentEvent : riwayat
    User "1" --> "*" ShipmentEvent : dicatat oleh
    User "1" --> "*" HarvestReport : BUMDes
    Kabupaten "1" --> "*" HarvestReport
    Komoditas "1" --> "*" HarvestReport
    Kabupaten "1" --> "*" PlantingRecommendation
    Komoditas "1" --> "*" PlantingRecommendation
    User "0..1" --> "*" DatasetImport : pengunggah
    StatusWilayah ..> StatusLevel
    Forecast ..> StatusLevel
    Shipment ..> StatusLevel
    Shipment ..> ShipmentStatus
```

### 2.2 Lapisan aplikasi (controller, layanan, kebijakan, perintah)

```mermaid
classDiagram
    direction TB

    class PetaController {
        +index()
        +apiStatus(komoditas, periode, level)
    }
    class WilayahController {
        +show(kode)
        +apiSeri(kode)
        +apiCek(kode, komoditas)
    }
    class ShipmentController {
        +index()
        +create()
        +store(request)
        +show(id)
        +tambahEvent(id, request)
    }
    class HarvestReportController {
        +index()
        +store(request)
        +update(id, request)
    }
    class PlantingController {
        +index()
    }
    class AdminController {
        +kelolaPengguna()
        +unggahDataset(file)
        +unggahForecast(file)
        +recalcStatus()
        +aturPengaturan(request)
    }
    class EnsureRole {
        +handle(request, roles)
    }
    class ShipmentPolicy {
        +view(user, shipment) bool
        +update(user, shipment) bool
    }
    class HarvestReportPolicy {
        +create(user, kabupaten) bool
        +update(user, laporan) bool
    }
    class StatusService {
        +hitungRasio(supply, demand) decimal
        +tentukanLevel(rasio) StatusLevel
        +cekWilayah(kabupaten, komoditas, periode) StatusWilayah
        +recalc(kabupaten, komoditas, periode)
        +recalcSemua()
        +agregasiProvinsi(komoditas, periode)
    }
    class ForecastImporter {
        +validasi(baris) bool
        +impor(file) DatasetImport
    }
    class DataHistorisImporter {
        +impor(file) DatasetImport
    }
    class NumberFormatter {
        +tampilkan(kg) string
    }
    class ImportDataHistoris {
        <<Artisan>>
        +handle()
    }
    class ImportForecast {
        <<Artisan>>
        +handle()
    }
    class ExportDataForMl {
        <<Artisan>>
        +handle()
    }
    class RecalcStatusWilayah {
        <<Artisan>>
        +handle()
    }

    PetaController ..> StatusService
    PetaController ..> NumberFormatter
    WilayahController ..> StatusService
    ShipmentController ..> StatusService
    ShipmentController ..> ShipmentPolicy
    HarvestReportController ..> StatusService
    HarvestReportController ..> HarvestReportPolicy
    AdminController ..> ForecastImporter
    AdminController ..> DataHistorisImporter
    AdminController ..> StatusService
    ImportForecast ..> ForecastImporter
    ImportDataHistoris ..> DataHistorisImporter
    RecalcStatusWilayah ..> StatusService
    ForecastImporter ..> StatusService
    DataHistorisImporter ..> StatusService
    ShipmentController ..> EnsureRole
    HarvestReportController ..> EnsureRole
    AdminController ..> EnsureRole
```

---

## 3. Object Diagram

Potret satu momen sistem (data **fiktif**): seorang distributor mengirim beras dari Cilacap ke Banyumas, yang pada Februari 2026 berstatus **Penuh**. BUMDes Banyumas sudah melapor panen bulan Januari.

```mermaid
classDiagram
    direction LR

    class roleDist["roleDist : Role"] {
        +kode = distributor
    }
    class roleBum["roleBum : Role"] {
        +kode = bumdes
    }
    class u1["u1 : User"] {
        +name = Budi Santoso contoh
        +organisasi = PT Contoh Pangan
    }
    class u2["u2 : User"] {
        +name = Pengurus BUMDes Banyumas contoh
        +organisasi = BUMDes Contoh
    }
    class kabAsal["kabAsal : Kabupaten"] {
        +kode = 3301
        +nama = Cilacap
    }
    class kabTuj["kabTuj : Kabupaten"] {
        +kode = 3302
        +nama = Banyumas
    }
    class kom1["kom1 : Komoditas"] {
        +kode = beras
    }
    class st1["st1 : StatusWilayah"] {
        +periode = 2026-02-01
        +supply_kg = 18500000
        +demand_kg = 17600000
        +rasio = 1.0511
        +status = penuh
        +stok_estimasi = false
    }
    class sh1["sh1 : Shipment"] {
        +no_resi = RESI-CONTOH-0001
        +jumlah_kg = 2500
        +status = dalam_perjalanan
        +level_peringatan = penuh
        +konfirmasi_peringatan = true
    }
    class ev1["ev1 : ShipmentEvent"] {
        +status = diproses
        +lokasi = Gudang Cilacap
    }
    class ev2["ev2 : ShipmentEvent"] {
        +status = dalam_perjalanan
        +lokasi = Perjalanan ke Banyumas
    }
    class hr1["hr1 : HarvestReport"] {
        +periode = 2026-01-01
        +panen_kg = 15200000
        +sisa_stok_kg = 3100000
    }

    roleDist --> u1
    roleBum --> u2
    u2 --> kabTuj : wilayah
    u1 --> sh1 : distributor
    kom1 --> sh1
    kabAsal --> sh1 : asal
    kabTuj --> sh1 : tujuan
    sh1 *-- ev1
    sh1 *-- ev2
    kabTuj --> st1
    kom1 --> st1
    u2 --> hr1 : pelapor
    kabTuj --> hr1
    kom1 --> hr1
    hr1 ..> st1 : sisa stok jadi stok awal Februari
```

Catatan: jumlah 2.500 kg ditampilkan di aplikasi sebagai **2,5 ton** (aturan tampilan: > 2.000 kg → ton, 1 desimal).

---

## 4. Component Diagram

```mermaid
flowchart LR
    subgraph KLIEN["Browser (klien)"]
        UI["Komponen Tampilan<br/>Blade + Tailwind"]
        MAP["Komponen Peta<br/>Leaflet"]
        CHT["Komponen Grafik<br/>Chart.js"]
    end

    subgraph APP["Aplikasi Laravel (server)"]
        MW["Middleware<br/>EnsureRole, CSRF"]
        CTRL["Controllers<br/>Peta, Wilayah, Shipment,<br/>HarvestReport, Planting, Admin"]
        POL["Policies"]
        SVC["Services<br/>StatusService, ForecastImporter,<br/>DataHistorisImporter, NumberFormatter"]
        ORM["Eloquent Models"]
        CMD["Artisan Commands<br/>import, export, recalc"]
        SCH["Scheduler"]
    end

    DB[("MySQL")]
    GEO["Berkas statis<br/>GeoJSON provinsi dan kabupaten"]
    STO["Penyimpanan berkas<br/>storage/app/imports"]

    subgraph MLP["Pipeline ML (offline, Python)"]
        PREP["prepare.py + features.py"]
        TRN["train.py + evaluate.py"]
        FCS["forecast.py"]
    end

    UI -->|"HTTP"| MW
    MAP -->|"GET /api/status"| MW
    CHT -->|"GET /api/wilayah/kode/seri"| MW
    MAP -->|"GET geojson"| GEO
    MW --> CTRL
    CTRL --> POL
    CTRL --> SVC
    SVC --> ORM
    ORM --> DB
    CMD --> SVC
    SCH --> CMD
    CTRL -->|"simpan CSV unggahan"| STO
    CMD -->|"baca CSV"| STO
    CMD -->|"data:export-ml"| PREP
    PREP --> TRN --> FCS
    FCS -->|"forecasts.csv"| STO
```

| Komponen | Antarmuka yang disediakan | Antarmuka yang dibutuhkan |
|---|---|---|
| StatusService | `hitungRasio`, `tentukanLevel`, `recalc` | Eloquent Models, Setting (ambang) |
| ForecastImporter | `impor(file)` | StatusService, DatasetImport |
| Pipeline ML | `forecasts.csv` | `latest.csv` hasil ekspor |
| GeoJSON statis | `/geojson/provinsi.json`, `/geojson/kabupaten.json` | — |

---

## 5. Deployment Diagram

```mermaid
flowchart TB
    subgraph PERANGKAT["Perangkat pengguna"]
        BRW["Browser<br/>pengunjung, distributor, BUMDes, admin"]
    end

    subgraph SERVER["Server hosting (CloudPanel)"]
        NGX["Nginx + SSL<br/>melayani HTTPS dan berkas statis"]
        PHP["PHP-FPM<br/>Aplikasi Laravel"]
        MYS[("MySQL<br/>basis data aplikasi")]
        CRN["Cron<br/>php artisan schedule:run tiap menit"]
        FIL["Sistem berkas<br/>public/geojson, public/build, storage/app/imports"]
    end

    subgraph LAPTOP["Laptop developer"]
        PYT["Python venv<br/>pandas, xgboost, scikit-learn"]
        NODE["Node + Vite<br/>build aset front-end"]
        GITL["Git lokal"]
    end

    REPO["Repo Git privat"]

    BRW -->|"HTTPS 443"| NGX
    NGX -->|"FastCGI"| PHP
    PHP -->|"TCP 3306 lokal"| MYS
    PHP --> FIL
    CRN --> PHP
    GITL -->|"push"| REPO
    REPO -->|"git pull saat deploy"| SERVER
    NODE -->|"public/build diunggah atau di-commit"| REPO
    PYT -->|"forecasts.csv diunggah admin"| NGX
```

| Node | Perangkat lunak / artefak | Catatan |
|---|---|---|
| Server CloudPanel | Nginx, PHP-FPM, Laravel, MySQL, cron | Satu situs PHP + satu database; document root `public/` |
| Laptop developer | Python (ML), Node (build), Git | Model dilatih offline; server tidak perlu Python |
| Repo Git privat | Kode sumber | `.env`, dataset, dan model tidak di-commit |

---

## 6. Package Diagram

```mermaid
flowchart TB
    subgraph PRESENTASI["Paket Presentasi"]
        VIEWS["resources/views<br/>Blade"]
        JS["resources/js<br/>peta, grafik"]
        GEOP["public/geojson"]
    end

    subgraph HTTP["Paket Http"]
        CTRLP["Controllers"]
        MIDP["Middleware"]
        REQP["Requests (validasi)"]
        POLP["Policies"]
    end

    subgraph SERVICE["Paket Services"]
        STAT["StatusService"]
        IMP["Importer"]
        FMT["NumberFormatter"]
    end

    subgraph DOMAIN["Paket Models (Domain)"]
        MOD["Eloquent Models"]
    end

    subgraph CONSOLE["Paket Console"]
        ART["Artisan Commands"]
    end

    subgraph DATA["Paket Database"]
        MIG["migrations"]
        SEED["seeders"]
    end

    subgraph MLPKG["Paket ML (Python)"]
        MLSRC["src: prepare, features, train, evaluate, forecast"]
        MLOUT["outputs: forecasts.csv, metrik.json"]
    end

    VIEWS -.->|"menampilkan"| CTRLP
    JS -.->|"memanggil API"| CTRLP
    CTRLP -.-> MIDP
    CTRLP -.-> REQP
    CTRLP -.-> POLP
    CTRLP -.-> STAT
    CTRLP -.-> IMP
    CTRLP -.-> FMT
    STAT -.-> MOD
    IMP -.-> MOD
    ART -.-> STAT
    ART -.-> IMP
    MOD -.-> MIG
    SEED -.-> MOD
    MLSRC -.->|"membaca latest.csv dari ekspor"| ART
    MLSRC -.-> MLOUT
    MLOUT -.->|"diimpor lewat"| IMP
```

---

## 7. Composite Structure Diagram

Struktur internal kelas **StatusService**: bagian-bagian (parts), port, dan konektor yang bekerja sama menghitung status satu wilayah, komoditas, dan bulan.

```mermaid
flowchart LR
    PIN["port masukan<br/>kabupaten, komoditas, periode"]
    POUT["port keluaran<br/>StatusWilayah"]

    subgraph SS["StatusService (kelas komposit)"]
        direction TB
        P1["part: SumberDasar<br/>pilih data aktual atau prediksi"]
        P2["part: StokResolver<br/>sisa survei BUMDes atau cadangan"]
        P3["part: PanenCorrector<br/>koreksi panen aktual vs prediksi"]
        P4["part: PengirimanAggregator<br/>pengiriman masuk dan keluar"]
        P5["part: RatioCalculator<br/>supply dibagi demand"]
        P6["part: LevelMapper<br/>rasio ke level status"]
    end

    PSET["port diperlukan<br/>Setting (ambang)"]
    PDB["port diperlukan<br/>Eloquent Models"]

    PIN --> P1
    P1 --> P2
    P2 --> P3
    P3 --> P4
    P4 --> P5
    P5 --> P6
    P6 --> POUT

    PSET -.-> P6
    PDB -.-> P1
    PDB -.-> P2
    PDB -.-> P3
    PDB -.-> P4
```

| Part | Tanggung jawab | Sumber data |
|---|---|---|
| SumberDasar | Memilih `data_historis` (aktual) atau `forecasts` terbaru (prediksi) menurut periode | data_historis, forecasts |
| StokResolver | Stok awal = sisa survei bulan sebelumnya; bila belum ada, hitungan cadangan `max(0, supply − demand)` dan tandai `stok_estimasi` | harvest_reports |
| PanenCorrector | `supply += panen_aktual − panen_prediksi` bila BUMDes sudah melapor | harvest_reports, forecasts |
| PengirimanAggregator | `supply += masuk − keluar` dari pengiriman non-batal pada bulan itu | shipments |
| RatioCalculator | `rasio = supply / demand`; `NULL` bila demand kosong atau 0 | — |
| LevelMapper | Memetakan rasio ke `defisit / seimbang / waspada / penuh / tanpa_data` | settings |

---

## 8. Activity Diagram

### 8.1 Distributor membuat pengiriman

```mermaid
flowchart TB
    subgraph DIS["Distributor"]
        D0(("Mulai"))
        D1["Buka form Buat Pengiriman"]
        D2["Isi nomor resi, komoditas, jumlah kg,<br/>asal, tujuan, tanggal kirim"]
        D3{"Lanjutkan setelah<br/>melihat banner?"}
        D4["Konfirmasi lanjut"]
        D5["Ubah tujuan atau batalkan"]
        D9["Lihat pengiriman di dashboard"]
        D10(("Selesai"))
    end

    subgraph SIS["Sistem"]
        S1["Ambil status wilayah tujuan"]
        S2{"Status tujuan?"}
        S3["Tampilkan banner info<br/>kelebihan pasokan, dampak,<br/>dan disclaimer bukan layanan penjualan"]
        S4["Tampilkan info wilayah membutuhkan pasokan"]
        S5["Tanpa banner"]
        S6{"Nomor resi unik<br/>dan data valid?"}
        S7["Tampilkan galat validasi"]
        S8["Simpan Shipment<br/>dengan level_peringatan"]
        S9["Hitung ulang status<br/>wilayah asal dan tujuan"]
    end

    D0 --> D1 --> D2 --> S1 --> S2
    S2 -->|"waspada atau penuh"| S3
    S2 -->|"defisit"| S4
    S2 -->|"seimbang atau tanpa data"| S5
    S3 --> D3
    D3 -->|"ya"| D4
    D3 -->|"tidak"| D5
    D5 --> D2
    S4 --> D4
    S5 --> D4
    D4 --> S6
    S6 -->|"tidak"| S7
    S7 --> D2
    S6 -->|"ya"| S8 --> S9 --> D9 --> D10
```

### 8.2 Siklus bulanan: laporan, data baru, prediksi

```mermaid
flowchart TB
    subgraph BUM["BUMDes"]
        B1(("Mulai"))
        B2["Isi laporan survei<br/>panen dan sisa stok akhir bulan"]
    end

    subgraph ADM["Admin"]
        A1["Unggah data bulan baru<br/>(demand, curah hujan, dll)"]
        A2["Jalankan ekspor data untuk ML"]
        A3["Unggah forecasts.csv"]
    end

    subgraph DEV["Pipeline ML (offline)"]
        M1["Siapkan data dan fitur"]
        M2{"Waktu retraining?<br/>kuartalan atau error melewati batas"}
        M3["Latih ulang XGBoost<br/>3 target: panen, pasokan masuk, demand"]
        M4["Prediksi 12 bulan"]
        M5["Hitung supply bergulir<br/>dari sisa stok terakhir"]
        M6["Tulis forecasts.csv"]
    end

    subgraph SIS["Sistem"]
        S1["Simpan laporan dan hitung ulang status<br/>bulan itu dan bulan berikutnya"]
        S2["Muat data baru ke data_historis"]
        S3["Gabungkan data_historis dan laporan BUMDes ke latest.csv"]
        S4["Validasi dan upsert ke forecasts"]
        S5["Hitung ulang status_wilayah dan hapus cache"]
        S6(("Peta terbarui"))
    end

    B1 --> B2 --> S1
    S1 --> A1
    A1 --> S2 --> A2 --> S3 --> M1 --> M2
    M2 -->|"ya"| M3 --> M4
    M2 -->|"tidak"| M4
    M4 --> M5 --> M6 --> A3 --> S4 --> S5 --> S6
```

### 8.3 BUMDes menginput panen dan sisa stok

```mermaid
flowchart TB
    subgraph BUM["BUMDes"]
        X0(("Mulai"))
        X1["Login"]
        X2["Buka form Panen dan Sisa Stok"]
        X3["Pilih komoditas dan bulan,<br/>isi panen kg dan sisa stok kg"]
        X9(("Selesai"))
    end

    subgraph SIS["Sistem"]
        Y1{"Akun terikat<br/>ke kabupaten ini?"}
        Y2["Tolak: akses ditolak"]
        Y3{"Sudah ada laporan<br/>bulan itu?"}
        Y4["Perbarui laporan"]
        Y5["Simpan laporan baru"]
        Y6["Hitung ulang status<br/>bulan itu dan bulan berikutnya"]
        Y7["Tampilkan rekap dan status kabupaten"]
    end

    X0 --> X1 --> X2 --> X3 --> Y1
    Y1 -->|"tidak"| Y2 --> X9
    Y1 -->|"ya"| Y3
    Y3 -->|"ya"| Y4 --> Y6
    Y3 -->|"tidak"| Y5 --> Y6
    Y6 --> Y7 --> X9
```

---

## 9. Sequence Diagram

### 9.1 Melihat peta (publik, tanpa login)

```mermaid
sequenceDiagram
    autonumber
    actor P as Pengunjung
    participant B as Browser (Leaflet)
    participant N as Nginx (berkas statis)
    participant C as PetaController
    participant S as StatusService
    participant DB as MySQL

    P->>B: Buka halaman utama
    B->>C: GET /
    C-->>B: Halaman peta
    B->>N: GET /geojson/provinsi.json
    N-->>B: GeoJSON provinsi
    B->>C: GET /api/status (level=provinsi, komoditas, periode)
    C->>S: agregasiProvinsi(komoditas, periode)
    S->>DB: SUM supply dan demand per provinsi dari status_wilayah
    DB-->>S: hasil agregasi
    S-->>C: rasio dan level per provinsi
    C-->>B: JSON status
    B-->>P: Peta berwarna per provinsi
    P->>B: Zoom mendekat
    alt zoom di atas ambang kabupaten
        B->>N: GET /geojson/kabupaten.json (dimuat saat dibutuhkan)
        N-->>B: GeoJSON kabupaten
        B->>C: GET /api/status (level=kabupaten, komoditas, periode)
        C->>DB: SELECT status_wilayah
        DB-->>C: baris status
        C-->>B: JSON status kabupaten
        B-->>P: Peta berwarna per kabupaten, abu-abu untuk tanpa data
    end
```

### 9.2 Login

```mermaid
sequenceDiagram
    autonumber
    actor U as Pengguna
    participant B as Browser
    participant A as AuthController
    participant DB as MySQL

    U->>B: Isi email dan kata sandi
    B->>A: POST /login
    A->>DB: Cari user dan cek hash kata sandi
    DB-->>A: data user dan role
    alt kredensial valid dan akun aktif
        A-->>B: Buat sesi dan redirect ke dashboard sesuai peran
    else tidak valid
        A-->>B: Pesan galat login
    end
```

### 9.3 Membuat pengiriman dengan banner info

```mermaid
sequenceDiagram
    autonumber
    actor D as Distributor
    participant B as Browser
    participant SC as ShipmentController
    participant W as WilayahController
    participant SS as StatusService
    participant DB as MySQL

    D->>B: Pilih komoditas dan kabupaten tujuan
    B->>W: GET /api/wilayah/:kode/cek?komoditas=
    W->>SS: cekWilayah(tujuan, komoditas, bulan)
    SS->>DB: baca status_wilayah
    DB-->>SS: rasio dan level
    SS-->>W: level dan pesan dampak
    W-->>B: JSON status tujuan
    alt level waspada atau penuh
        B-->>D: Banner info kelebihan pasokan + disclaimer bukan layanan penjualan
        D->>B: Konfirmasi lanjut
    else level defisit
        B-->>D: Info wilayah membutuhkan pasokan
    else level seimbang atau tanpa data
        B-->>D: Tanpa banner
    end
    D->>B: Kirim form pengiriman
    B->>SC: POST /pengiriman
    SC->>SC: Validasi (resi unik, jumlah kg, tujuan)
    alt validasi gagal
        SC-->>B: Galat validasi
    else validasi lolos
        SC->>DB: INSERT shipments (level_peringatan, konfirmasi_peringatan)
        SC->>SS: recalc(asal dan tujuan, komoditas, bulan)
        SS->>DB: UPSERT status_wilayah
        SC-->>B: Redirect ke dashboard pengiriman
        B-->>D: Pengiriman tampil dengan nomor resi
    end
```

### 9.4 Memperbarui status pengiriman (manual oleh distributor)

```mermaid
sequenceDiagram
    autonumber
    actor D as Distributor
    participant B as Browser
    participant SC as ShipmentController
    participant PO as ShipmentPolicy
    participant SS as StatusService
    participant DB as MySQL

    D->>B: Buka detail pengiriman dan tambah riwayat
    B->>SC: POST /pengiriman/:id/event
    SC->>PO: update(user, shipment)
    alt bukan pemilik dan bukan admin
        PO-->>SC: tolak
        SC-->>B: 403 akses ditolak
    else diizinkan
        PO-->>SC: izin
        SC->>DB: INSERT shipment_events dan UPDATE shipments.status
        opt status menjadi dibatalkan
            SC->>SS: recalc(asal dan tujuan)
            SS->>DB: UPSERT status_wilayah
        end
        SC-->>B: Riwayat diperbarui
        B-->>D: Status terbaru terlihat setelah halaman dimuat ulang
    end
```

### 9.5 BUMDes menginput panen dan sisa stok

```mermaid
sequenceDiagram
    autonumber
    actor U as BUMDes
    participant B as Browser
    participant HC as HarvestReportController
    participant PO as HarvestReportPolicy
    participant SS as StatusService
    participant DB as MySQL

    U->>B: Isi panen kg dan sisa stok kg
    B->>HC: POST /panen
    HC->>PO: create(user, kabupaten akun)
    PO-->>HC: izin hanya untuk kabupaten akun
    HC->>HC: Validasi (angka tidak negatif, periode valid)
    HC->>DB: UPSERT harvest_reports (kabupaten, komoditas, periode)
    HC->>SS: recalc(kabupaten, komoditas, bulan dan bulan berikutnya)
    SS->>DB: Baca stok survei, panen, pengiriman
    SS->>DB: UPSERT status_wilayah (stok_estimasi = false)
    HC-->>B: Rekap dan status terbaru
    B-->>U: Tampilan rekap panen
```

### 9.6 Admin mengimpor prediksi bulanan

```mermaid
sequenceDiagram
    autonumber
    actor A as Admin
    participant B as Browser
    participant AC as AdminController
    participant FI as ForecastImporter
    participant SS as StatusService
    participant DB as MySQL

    A->>B: Unggah forecasts.csv
    B->>AC: POST /admin/forecast
    AC->>AC: Simpan ke storage/app/imports (tidak publik)
    AC->>FI: impor(file)
    FI->>DB: INSERT dataset_imports (status berjalan)
    loop setiap baris CSV
        FI->>FI: Validasi kode kabupaten, komoditas, periode, horizon
        FI->>DB: UPSERT forecasts (kunci unik)
    end
    FI->>DB: UPDATE dataset_imports (selesai, jumlah berhasil dan gagal)
    AC->>SS: recalcSemua()
    SS->>DB: Isi ulang status_wilayah
    AC-->>B: Ringkasan impor dan versi model aktif
```

---

## 10. State Machine Diagram

### 10.1 Status Pengiriman (`shipments.status`)

```mermaid
stateDiagram-v2
    [*] --> dibuat : distributor menyimpan pengiriman
    dibuat --> diterima_di_asal : riwayat ditambah distributor
    diterima_di_asal --> diproses
    diproses --> dalam_perjalanan
    dalam_perjalanan --> tiba_di_tujuan
    tiba_di_tujuan --> diterima
    dibuat --> dibatalkan : dibatalkan
    diterima_di_asal --> dibatalkan
    diproses --> dibatalkan
    dalam_perjalanan --> dibatalkan
    diterima --> [*]
    dibatalkan --> [*]
```

Catatan: daftar status adalah **contoh gaya JNE** dan masih perlu dikonfirmasi. Perubahan status dilakukan **manual** oleh distributor; admin dapat mengoreksi.

### 10.2 Level Status Wilayah (`status_wilayah.status`)

```mermaid
stateDiagram-v2
    [*] --> hitung
    state hitung <<choice>>
    hitung --> tanpa_data : demand kosong atau nol
    hitung --> defisit : rasio di bawah 0.70
    hitung --> seimbang : rasio 0.70 sampai di bawah 0.90
    hitung --> waspada : rasio 0.90 sampai di bawah 1.00
    hitung --> penuh : rasio 1.00 atau lebih
    tanpa_data --> hitung : data atau prediksi diperbarui
    defisit --> hitung : data, laporan, atau pengiriman berubah
    seimbang --> hitung : data, laporan, atau pengiriman berubah
    waspada --> hitung : data, laporan, atau pengiriman berubah
    penuh --> hitung : data, laporan, atau pengiriman berubah
```

Ambang 0,70 / 0,90 / 1,00 adalah nilai awal dan dapat diubah Admin lewat tabel `settings`.

### 10.3 Impor Data (`dataset_imports.status`)

```mermaid
stateDiagram-v2
    [*] --> berjalan : admin atau CLI memulai impor
    berjalan --> selesai : semua baris diproses
    berjalan --> gagal : galat fatal atau file tidak valid
    selesai --> [*]
    gagal --> [*]
```

---

## 11. Communication Diagram

Skenario: **distributor menyimpan pengiriman**. Angka menunjukkan urutan pesan; angka bertingkat (2.1) menunjukkan pesan bersarang.

```mermaid
flowchart LR
    D(["aktor: Distributor"])
    SC["objek: ShipmentController"]
    PO["objek: ShipmentPolicy"]
    SS["objek: StatusService"]
    SW["objek: StatusWilayah"]
    SH["objek: Shipment"]

    D -->|"1: kirim form pengiriman"| SC
    SC -->|"2: otorisasi create"| PO
    SC -->|"3: cekWilayah(tujuan, komoditas, bulan)"| SS
    SS -->|"3.1: baca status"| SW
    SC -->|"4: simpan dengan level_peringatan"| SH
    SC -->|"5: recalc(asal dan tujuan)"| SS
    SS -->|"5.1: tulis ulang status"| SW
    SC -->|"6: redirect ke dashboard"| D
```

Skenario kedua: **BUMDes menyimpan laporan panen**.

```mermaid
flowchart LR
    U(["aktor: BUMDes"])
    HC["objek: HarvestReportController"]
    HP["objek: HarvestReportPolicy"]
    HR["objek: HarvestReport"]
    SS["objek: StatusService"]
    SW["objek: StatusWilayah"]

    U -->|"1: kirim form panen dan sisa stok"| HC
    HC -->|"2: otorisasi kabupaten akun"| HP
    HC -->|"3: upsert laporan"| HR
    HC -->|"4: recalc(bulan dan bulan berikutnya)"| SS
    SS -->|"4.1: baca laporan"| HR
    SS -->|"4.2: tulis status"| SW
    HC -->|"5: tampilkan rekap"| U
```

---

## 12. Timing Diagram

Mermaid tidak punya tipe timing diagram UML, sehingga **garis hidup (lifeline) digambar dengan Gantt**: setiap baris adalah satu objek, dan setiap batang adalah **state** objek itu pada rentang waktu tertentu. Tanggal hanya contoh.

### 12.1 Siklus bulanan (contoh bulan Februari 2026)

```mermaid
gantt
    title Timing siklus bulanan (contoh)
    dateFormat YYYY-MM-DD
    axisFormat %d
    section BUMDes
    Menyusun laporan survei         :b1, 2026-02-01, 4d
    Input panen dan sisa stok       :b2, after b1, 3d
    Menunggu bulan berikutnya       :b3, after b2, 21d
    section Admin
    Menyiapkan data bulan baru      :a1, 2026-02-05, 2d
    Ekspor data untuk ML            :a2, after a1, 1d
    Menunggu hasil prediksi         :a3, after a2, 2d
    Impor forecasts                 :a4, after a3, 1d
    Idle                            :a5, after a4, 18d
    section Pipeline ML
    Menyiapkan fitur                :m1, 2026-02-08, 1d
    Latih ulang bila waktunya       :m2, after m1, 1d
    Prediksi dan hitung supply      :m3, after m2, 1d
    section status_wilayah
    Memakai prediksi lama           :s1, 2026-02-01, 11d
    Hitung ulang                    :s2, after s1, 1d
    Memakai prediksi baru           :s3, after s2, 17d
    section Peta publik
    Menampilkan status lama         :p1, 2026-02-01, 11d
    Menampilkan status baru         :p2, after p1, 18d
```

### 12.2 Siklus hidup satu pengiriman (contoh)

```mermaid
gantt
    title Timing status satu pengiriman (contoh)
    dateFormat YYYY-MM-DD HH:mm
    axisFormat %d %H:%M
    section Status shipments
    dibuat              :c1, 2026-02-03 08:00, 3h
    diterima_di_asal    :c2, after c1, 5h
    diproses            :c3, after c2, 6h
    dalam_perjalanan    :c4, after c3, 20h
    tiba_di_tujuan      :c5, after c4, 4h
    diterima            :c6, after c5, 2h
    section Perhitungan supply tujuan
    Pengiriman dihitung sebagai masuk :d1, 2026-02-03 08:00, 40h
    section Aktor
    Distributor menambah riwayat      :milestone, e1, 2026-02-03 11:00, 0h
    Distributor menambah riwayat lagi :milestone, e2, 2026-02-03 16:00, 0h
    Distributor menambah riwayat akhir :milestone, e3, 2026-02-04 18:00, 0h
```

Catatan: kendala waktu (timing constraint) yang relevan: prediksi diperbarui **sekali per bulan**, laporan BUMDes **sekali per bulan per komoditas**, dan status pengiriman **terbarui saat halaman dimuat ulang** (tidak real-time/push).

---

## 13. Interaction Overview Diagram

Diagram alur tingkat tinggi yang menghubungkan beberapa interaksi. Kotak berawalan **ref** merujuk ke sequence diagram pada bagian 9.

```mermaid
flowchart TD
    S0(("Mulai"))
    R1["ref: sd Melihat peta (9.1)"]
    Q1{"Perlu fitur<br/>berbasis peran?"}
    R2["ref: sd Login (9.2)"]
    Q2{"Peran pengguna?"}

    R3["ref: sd Membuat pengiriman (9.3)"]
    R4["ref: sd Memperbarui status pengiriman (9.4)"]
    Q3{"Pengiriman<br/>sudah selesai?"}

    R5["ref: sd BUMDes input panen dan sisa stok (9.5)"]

    R6["ref: sd Admin impor prediksi (9.6)"]

    S9(("Selesai"))

    S0 --> R1 --> Q1
    Q1 -->|"tidak"| S9
    Q1 -->|"ya"| R2 --> Q2

    Q2 -->|"distributor"| R3 --> R4 --> Q3
    Q3 -->|"belum"| R4
    Q3 -->|"sudah"| S9

    Q2 -->|"BUMDes"| R5 --> S9

    Q2 -->|"admin"| R6 --> S9
```

---

## 14. Flowchart

### 14.1 Alur sistem secara keseluruhan

```mermaid
flowchart TD
    A(["Mulai"]) --> B["Siapkan dataset riil 2023-2025<br/>format dataset_template.csv"]
    B --> C["Validasi dan bersihkan data<br/>kode wilayah cocok dengan GeoJSON"]
    C --> D["Latih XGBoost offline<br/>panen, pasokan masuk, demand"]
    D --> E["Hitung supply bergulir<br/>dan prediksi 12 bulan"]
    E --> F["Hasilkan forecasts.csv"]
    F --> G["Admin impor ke web"]
    G --> H["Hitung status_wilayah"]
    H --> I["Peta heatmap tampil untuk semua orang"]

    I --> J{"Pengguna login<br/>dan memiliki peran?"}
    J -->|"distributor"| K["Buat pengiriman dan lacak resi"]
    J -->|"BUMDes"| L["Input panen dan sisa stok bulanan"]
    J -->|"admin"| M["Kelola data, pengguna, ambang"]
    J -->|"tidak login"| N["Lihat peta, detail, dan info dampak"]

    K --> H
    L --> H
    M --> G

    H --> O{"Sudah akhir bulan?"}
    O -->|"ya"| P["Ekspor data terbaru ke ML<br/>dan ulangi prediksi"]
    P --> D
    O -->|"tidak"| I
```

### 14.2 Algoritma penentuan status wilayah

```mermaid
flowchart TD
    A(["Mulai: kabupaten, komoditas, periode"]) --> B{"Periode sudah punya<br/>data aktual?"}
    B -->|"ya"| C["Ambil data_historis<br/>sumber = aktual"]
    B -->|"tidak"| D["Ambil forecast terbaru<br/>sumber = prediksi"]
    C --> E{"Ada laporan BUMDes<br/>bulan sebelumnya?"}
    D --> E
    E -->|"ya"| F["stok_awal = sisa_stok dari survei<br/>stok_estimasi = tidak"]
    E -->|"tidak"| G["stok_awal = max(0, supply - demand) bulan lalu<br/>stok_estimasi = ya"]
    F --> H["supply = panen + stok_awal + pasokan_masuk"]
    G --> H
    H --> I{"Ada laporan panen<br/>bulan ini?"}
    I -->|"ya"| J["supply = supply + panen_aktual - panen_prediksi"]
    I -->|"tidak"| K["Lewati koreksi panen"]
    J --> L["supply = supply + pengiriman_masuk - pengiriman_keluar"]
    K --> L
    L --> M{"demand kosong<br/>atau nol?"}
    M -->|"ya"| N["status = tanpa_data<br/>(abu-abu)"]
    M -->|"tidak"| O["rasio = supply / demand"]
    O --> P{"rasio?"}
    P -->|"kurang dari 0.70"| Q["status = defisit"]
    P -->|"0.70 sampai kurang dari 0.90"| R["status = seimbang"]
    P -->|"0.90 sampai kurang dari 1.00"| S["status = waspada"]
    P -->|"1.00 atau lebih"| T["status = penuh"]
    N --> U["Simpan ke status_wilayah"]
    Q --> U
    R --> U
    S --> U
    T --> U
    U --> V(["Selesai"])
```

### 14.3 Pipeline machine learning (offline)

```mermaid
flowchart TD
    A(["Mulai"]) --> B["Baca latest.csv<br/>data historis + laporan BUMDes"]
    B --> C["Validasi skema dan kode wilayah"]
    C --> D{"Ada sel kosong<br/>pada stok_awal?"}
    D -->|"ya"| E["Isi cadangan: max(0, supply - demand) bulan lalu"]
    D -->|"tidak"| F["Lanjut"]
    E --> G["Susun fitur: musim, lag minimal sepanjang horizon,<br/>rata-rata bergerak, curah hujan, kabupaten, komoditas, horizon"]
    F --> G
    G --> H{"Waktunya retraining?<br/>kuartalan atau error melewati batas"}
    H -->|"ya"| I["Latih XGBoost 3 target<br/>validasi waktu: latih 2023-2024, uji 2025"]
    I --> J["Evaluasi: WAPE, MAE, RMSE per horizon,<br/>bandingkan dengan baseline tahun lalu"]
    J --> K{"Lebih baik dari<br/>baseline?"}
    K -->|"tidak"| L["Tinjau fitur dan parameter"]
    L --> I
    K -->|"ya"| M["Simpan model dan metrik<br/>ke model_versions"]
    H -->|"tidak"| N["Pakai model aktif"]
    M --> O["Prediksi panen, pasokan masuk, demand<br/>untuk 12 bulan"]
    N --> O
    O --> P["Hitung supply bergulir<br/>dari sisa stok terakhir"]
    P --> Q["Hitung rasio dan status per bulan"]
    Q --> R["Tulis forecasts.csv"]
    R --> S(["Selesai"])
```

---

## 15. Catatan Konsistensi & Hal yang Perlu Dikonfirmasi

| Hal | Status |
|---|---|
| Daftar status pengiriman (diagram 10.1) | Contoh gaya JNE; perlu dikonfirmasi tim |
| Aturan pembatalan pengiriman (dari status mana boleh) | Diasumsikan boleh sebelum `diterima`; perlu dikonfirmasi |
| Ambang zoom pergantian provinsi → kabupaten | ± 7–8; disesuaikan saat uji peta |
| Definisi "pengiriman aktif" pada perhitungan supply | Saat ini semua pengiriman non-batal pada bulan itu |
| Penanda notasi | Diagram use case, komponen, deployment, paket, struktur komposit, komunikasi, timing, dan interaction overview digambar dengan Mermaid `flowchart`/`gantt` yang meniru notasi UML |
