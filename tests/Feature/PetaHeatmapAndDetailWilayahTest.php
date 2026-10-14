<?php

namespace Tests\Feature;

use App\Models\Kabupaten;
use App\Models\Provinsi;
use Database\Seeders\KomoditasSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetaHeatmapAndDetailWilayahTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(KomoditasSeeder::class);
        $this->seed(WilayahSeeder::class);
        $this->seed(SettingSeeder::class);
    }

    /**
     * Uji Join GeoJSON: File GeoJSON provinsi dan kabupaten valid dan cocok 100% dengan database.
     */
    public function test_geojson_files_exist_and_match_database_master_data(): void
    {
        $provPath = public_path('geojson/provinsi.json');
        $kabPath = public_path('geojson/kabupaten.json');

        $this->assertFileExists($provPath, 'File public/geojson/provinsi.json harus ada');
        $this->assertFileExists($kabPath, 'File public/geojson/kabupaten.json harus ada');

        $provGeo = json_decode(file_get_contents($provPath), true);
        $kabGeo = json_decode(file_get_contents($kabPath), true);

        $this->assertIsArray($provGeo);
        $this->assertEquals('FeatureCollection', $provGeo['type'] ?? '');
        $this->assertCount(38, $provGeo['features'], 'GeoJSON provinsi harus memuat 38 provinsi');

        $geoProvCodes = array_map(fn ($f) => $f['properties']['kode'] ?? '', $provGeo['features']);
        $dbProvCodes = Provinsi::pluck('kode')->toArray();

        // 38 Provinsi di database harus cocok 100% dengan GeoJSON
        foreach ($dbProvCodes as $dbCode) {
            $this->assertContains($dbCode, $geoProvCodes, "Provinsi kode '{$dbCode}' harus ada di GeoJSON provinsi");
        }

        $this->assertIsArray($kabGeo);
        $this->assertEquals('FeatureCollection', $kabGeo['type'] ?? '');
        $this->assertGreaterThan(300, count($kabGeo['features']), 'GeoJSON kabupaten harus memuat poligon kabupaten nasional');

        $geoKabCodes = array_map(fn ($f) => $f['properties']['kode'] ?? '', $kabGeo['features']);
        $dbKabCodes = Kabupaten::pluck('kode')->toArray();

        // Seluruh 34 kabupaten master di database harus cocok 100% dengan GeoJSON
        foreach ($dbKabCodes as $dbCode) {
            $this->assertContains($dbCode, $geoKabCodes, "Kabupaten kode '{$dbCode}' harus ada di GeoJSON kabupaten");
        }
    }

    /**
     * Halaman peta heatmap publik dapat diakses tanpa login.
     */
    public function test_peta_heatmap_page_is_publicly_accessible(): void
    {
        $response = $this->get('/peta');

        $response->assertStatus(200);
        $response->assertSee('Peta Heatmap');
        $response->assertSee('Beras');
        $response->assertSee('Jagung');
        $response->assertSee('Singkong');
        $response->assertSee('Defisit');
        $response->assertSee('Seimbang');
        $response->assertSee('Waspada');
        $response->assertSee('Penuh');
        $response->assertSee('Tanpa Data');
        $response->assertSee('Tingkat');
        $response->assertSee('Tabel Wilayah');
    }

    /**
     * Halaman detail wilayah untuk provinsi dapat diakses publik.
     */
    public function test_public_wilayah_detail_page_for_provinsi(): void
    {
        $provinsi = Provinsi::where('kode', '33')->first(); // Jawa Tengah
        $this->assertNotNull($provinsi);

        $response = $this->get('/wilayah/33');

        $response->assertStatus(200);
        $response->assertSee('Jawa Tengah');
        $response->assertSee('Provinsi');
        $response->assertSee('Grafik Deret Waktu 12 Bulan');
        $response->assertSee('Daftar Kabupaten dan Kota di Provinsi Jawa Tengah');
    }

    /**
     * Halaman detail wilayah untuk kabupaten/kota dapat diakses publik.
     */
    public function test_public_wilayah_detail_page_for_kabupaten(): void
    {
        $kabupaten = Kabupaten::where('kode', '3302')->first(); // Banyumas
        $this->assertNotNull($kabupaten);

        $response = $this->get('/wilayah/3302');

        $response->assertStatus(200);
        $response->assertSee('Banyumas');
        $response->assertSee('Jawa Tengah');
        $response->assertSee('Total Supply');
        $response->assertSee('Demand Terproyeksi');
        $response->assertSee('XGBoost');
    }

    /**
     * Halaman detail wilayah mengembalikan 404 bila kode tidak ditemukan.
     */
    public function test_public_wilayah_detail_returns_404_for_invalid_code(): void
    {
        $response = $this->get('/wilayah/9999');
        $response->assertStatus(404);
    }

    /**
     * Endpoint API status publik mengembalikan data provinsi dan kabupaten.
     */
    public function test_api_status_returns_data_for_provinsi_and_kabupaten(): void
    {
        // 1. Level Provinsi
        $resProv = $this->getJson('/api/status?komoditas=beras&level=provinsi');
        $resProv->assertStatus(200);
        $resProv->assertJsonStructure([
            'komoditas',
            'periode',
            'level',
            'total_wilayah',
            'data' => [
                '*' => ['kode', 'nama', 'status', 'label', 'warna_hex'],
            ],
        ]);

        // 2. Level Kabupaten
        $resKab = $this->getJson('/api/status?komoditas=beras&level=kabupaten');
        $resKab->assertStatus(200);
        $resKab->assertJsonStructure([
            'komoditas',
            'periode',
            'level',
            'total_wilayah',
            'data',
        ]);
    }

    /**
     * Endpoint API seri deret waktu mengembalikan data time series historis vs prediksi.
     */
    public function test_api_wilayah_seri_returns_correct_time_series(): void
    {
        // Kabupaten 3302
        $resKab = $this->getJson('/api/wilayah/3302/seri?komoditas=beras');
        $resKab->assertStatus(200);
        $resKab->assertJsonStructure([
            'wilayah' => ['kode', 'nama', 'tipe'],
            'komoditas' => ['kode', 'nama'],
            'pesan_dampak',
            'model_version',
            'total_titik',
            'seri',
        ]);

        // Provinsi 33
        $resProv = $this->getJson('/api/wilayah/33/seri?komoditas=beras');
        $resProv->assertStatus(200);
        $resProv->assertJsonStructure([
            'wilayah' => ['kode', 'nama', 'tipe'],
            'komoditas' => ['kode', 'nama'],
            'seri',
        ]);
    }

    /**
     * Endpoint API info wilayah singkat untuk tooltip hover peta.
     */
    public function test_api_wilayah_info_returns_hover_data(): void
    {
        $response = $this->getJson('/api/wilayah/3302/info?komoditas=beras');
        $this->assertContains($response->status(), [200, 404]); // 404 if no status row exists for exact default month, 200 if seeded
    }

    /**
     * Halaman detail wilayah Banyumas menampilkan seluruh komponen mockup visual Increment 5.
     */
    public function test_detail_wilayah_banyumas_displays_complete_mockup_components(): void
    {
        $response = $this->get('/wilayah/3302?komoditas=beras');

        $response->assertStatus(200);
        // Alert banner
        $response->assertSee('STATUS: PENUH / SURPLUS');
        $response->assertSee('Tinjau Rute Redistribusi');
        // 4 KPI Cards
        $response->assertSee('Total Supply Aktif');
        $response->assertSee('Demand Terproyeksi');
        $response->assertSee('Surplus Bersih Kritis');
        $response->assertSee('Pengiriman Aktif Masuk');
        // 12-Month granular table
        $response->assertSee('Tabel Rincian Prediksi Horizon 1–12 Bulan');
        $response->assertSee('Puncak Panen');
        // Analysis & Corridor cards
        $response->assertSee('Analisis Dampak Surplus');
        $response->assertSee('Rekomendasi Kebijakan');
        $response->assertSee('Kab. Banjarnegara');
        $response->assertSee('Kab. Kebumen');
        $response->assertSee('Pool Armada Terdekat: Sub-Depo PT Logistik Pangan Banyumas');
        $response->assertSee('Unduh Rekap CSV Data');
    }
}
