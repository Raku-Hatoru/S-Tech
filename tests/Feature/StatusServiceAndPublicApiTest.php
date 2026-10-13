<?php

namespace Tests\Feature;

use App\Enums\StatusLevel;
use App\Models\DataHistoris;
use App\Models\Forecast;
use App\Models\HarvestReport;
use App\Models\Kabupaten;
use App\Models\Komoditas;
use App\Models\Shipment;
use App\Models\StatusWilayah;
use App\Models\User;
use App\Services\NumberFormatter;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StatusServiceAndPublicApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Cache::flush();
    }

    public function test_status_service_tentukan_level_handles_threshold_boundaries(): void
    {
        $service = new StatusService;

        // 1. Di bawah 0.70 => Defisit
        $this->assertEquals(StatusLevel::Defisit, $service->tentukanLevel(0.6999));
        $this->assertEquals(StatusLevel::Defisit, $service->tentukanLevel(0.5000));
        $this->assertEquals(StatusLevel::Defisit, $service->tentukanLevel(0.0000));

        // 2. Batas 0.70 s.d. di bawah 0.90 => Seimbang
        $this->assertEquals(StatusLevel::Seimbang, $service->tentukanLevel(0.7000));
        $this->assertEquals(StatusLevel::Seimbang, $service->tentukanLevel(0.8000));
        $this->assertEquals(StatusLevel::Seimbang, $service->tentukanLevel(0.8999));

        // 3. Batas 0.90 s.d. di bawah 1.00 => Waspada
        $this->assertEquals(StatusLevel::Waspada, $service->tentukanLevel(0.9000));
        $this->assertEquals(StatusLevel::Waspada, $service->tentukanLevel(0.9500));
        $this->assertEquals(StatusLevel::Waspada, $service->tentukanLevel(0.9999));

        // 4. Batas 1.00 ke atas => Penuh / Surplus
        $this->assertEquals(StatusLevel::Penuh, $service->tentukanLevel(1.0000));
        $this->assertEquals(StatusLevel::Penuh, $service->tentukanLevel(1.5000));
        $this->assertEquals(StatusLevel::Penuh, $service->tentukanLevel(2.2000));

        // 5. Null => Tanpa Data
        $this->assertEquals(StatusLevel::TanpaData, $service->tentukanLevel(null));
    }

    public function test_status_service_hitung_rasio(): void
    {
        $service = new StatusService;

        $this->assertEquals(1.2000, $service->hitungRasio(120000.0, 100000.0));
        $this->assertEquals(0.8500, $service->hitungRasio(85000.0, 100000.0));
        $this->assertEquals(0.0000, $service->hitungRasio(0.0, 100000.0));
        $this->assertNull($service->hitungRasio(100000.0, 0.0));
        $this->assertNull($service->hitungRasio(100000.0, null));
        $this->assertNull($service->hitungRasio(null, 100000.0));
    }

    public function test_number_formatter_formats_kg_and_ton(): void
    {
        $this->assertEquals('1.850 kg', NumberFormatter::tampilkan(1850));
        $this->assertEquals('2.000 kg', NumberFormatter::tampilkan(2000));
        $this->assertEquals('2,5 ton', NumberFormatter::tampilkan(2500));
        $this->assertEquals('18.300,0 ton', NumberFormatter::tampilkan(18300000));
        $this->assertEquals('—', NumberFormatter::tampilkan(null));

        $this->assertEquals('105,11%', NumberFormatter::formatPersen(1.0511));
        $this->assertEquals('70,00%', NumberFormatter::formatPersen(0.70));
        $this->assertEquals('—', NumberFormatter::formatPersen(null));
    }

    public function test_status_service_cek_wilayah_and_recalc_pipeline(): void
    {
        $kabupaten = Kabupaten::where('kode', '3302')->first(); // Banyumas
        $komoditas = Komoditas::where('kode', 'beras')->first();

        // 1. Masukkan data historis
        DataHistoris::create([
            'kabupaten_id' => $kabupaten->id,
            'komoditas_id' => $komoditas->id,
            'periode' => '2024-03-01',
            'panen_kg' => 10000000,
            'stok_awal_kg' => 2000000,
            'pasokan_masuk_kg' => 500000,
            'supply_kg' => 12500000,
            'demand_kg' => 10000000,
        ]);

        $service = new StatusService;
        $status = $service->cekWilayah($kabupaten->id, $komoditas->id, '2024-03');

        $this->assertEquals('aktual', $status->sumber);
        $this->assertEquals(1.2500, (float) $status->rasio);
        $this->assertEquals(StatusLevel::Penuh, $status->status);
        $this->assertFalse((bool) $status->stok_estimasi);

        // 2. Tambahkan pengiriman keluar (Banyumas kirim ke Cilacap)
        $cilacap = Kabupaten::where('kode', '3301')->first();
        $userDist = User::where('email', 'distributor1@sipangan.test')->first();

        Shipment::create([
            'user_id' => $userDist->id,
            'no_resi' => 'RESI-TEST-STATUS-001',
            'komoditas_id' => $komoditas->id,
            'jumlah_kg' => 3000000, // 3 juta kg keluar dari Banyumas
            'asal_kabupaten_id' => $kabupaten->id,
            'tujuan_kabupaten_id' => $cilacap->id,
            'tanggal_kirim' => '2024-03-10',
            'status' => 'diproses',
        ]);

        // Hitung ulang setelah ada pengiriman keluar
        $statusSetelahKirim = $service->cekWilayah($kabupaten->id, $komoditas->id, '2024-03');

        // Supply berkurang: 12.5jt - 3jt = 9.5jt. Demand = 10jt. Rasio = 0.9500 (Waspada!)
        $this->assertEquals(9500000, (float) $statusSetelahKirim->supply_kg);
        $this->assertEquals(0.9500, (float) $statusSetelahKirim->rasio);
        $this->assertEquals(StatusLevel::Waspada, $statusSetelahKirim->status);
    }

    public function test_status_service_bumdes_survey_stock_removes_stok_estimasi(): void
    {
        $kabupaten = Kabupaten::where('kode', '3302')->first();
        $komoditas = Komoditas::where('kode', 'beras')->first();
        $userBumdes = User::where('email', 'bumdes.banyumas@sipangan.test')->first();

        // Periode prediksi (belum ada data historis)
        Forecast::create([
            'kabupaten_id' => $kabupaten->id,
            'komoditas_id' => $komoditas->id,
            'periode_dasar' => '2025-12-01',
            'periode_target' => '2026-05-01',
            'horizon_bulan' => 5,
            'panen_pred_kg' => 8000000,
            'stok_awal_pred_kg' => 1000000,
            'supply_pred_kg' => 9000000,
            'demand_pred_kg' => 10000000,
            'rasio_pred' => 0.9000,
            'status_pred' => 'waspada',
        ]);

        $service = new StatusService;
        $statusPred = $service->cekWilayah($kabupaten->id, $komoditas->id, '2026-05');

        $this->assertEquals('prediksi', $statusPred->sumber);
        $this->assertTrue((bool) $statusPred->stok_estimasi);

        // BUMDes melapor sisa stok bulan sebelumnya (2026-04)
        HarvestReport::create([
            'user_id' => $userBumdes->id,
            'kabupaten_id' => $kabupaten->id,
            'komoditas_id' => $komoditas->id,
            'periode' => '2026-04-01',
            'panen_kg' => 7000000,
            'sisa_stok_kg' => 3500000, // stok riil survei
        ]);

        $statusSetelahSurvei = $service->cekWilayah($kabupaten->id, $komoditas->id, '2026-05');

        // stok_estimasi kini false karena stok awal berasal dari survei BUMDes
        $this->assertFalse((bool) $statusSetelahSurvei->stok_estimasi);
    }

    public function test_artisan_status_recalc_command(): void
    {
        $kabupaten = Kabupaten::where('kode', '3302')->first();
        $komoditas = Komoditas::where('kode', 'beras')->first();

        DataHistoris::create([
            'kabupaten_id' => $kabupaten->id,
            'komoditas_id' => $komoditas->id,
            'periode' => '2024-03-01',
            'panen_kg' => 10000000,
            'stok_awal_kg' => 2000000,
            'supply_kg' => 12000000,
            'demand_kg' => 10000000,
        ]);

        $this->artisan('status:recalc')
            ->assertSuccessful();

        $this->assertDatabaseHas('status_wilayah', [
            'komoditas_id' => $komoditas->id,
        ]);
    }

    public function test_api_status_endpoint_returns_provinsi_and_kabupaten_data(): void
    {
        // Siapkan data status wilayah
        $service = new StatusService;
        $kabupaten = Kabupaten::where('kode', '3302')->first();
        $komoditas = Komoditas::where('kode', 'beras')->first();

        StatusWilayah::create([
            'kabupaten_id' => $kabupaten->id,
            'komoditas_id' => $komoditas->id,
            'periode' => '2024-03-01',
            'supply_kg' => 12000000,
            'demand_kg' => 10000000,
            'rasio' => 1.2000,
            'status' => StatusLevel::Penuh,
            'sumber' => 'aktual',
            'stok_estimasi' => false,
            'dihitung_pada' => now(),
        ]);

        $service->invalidateCache();

        // 1. Level Provinsi
        $responseProv = $this->getJson('/api/status?level=provinsi&komoditas=beras&periode=2024-03');
        $responseProv->assertStatus(200);
        $responseProv->assertJsonStructure([
            'komoditas' => ['kode', 'nama'],
            'periode',
            'level',
            'total_wilayah',
            'data' => [
                '*' => [
                    'kode',
                    'nama',
                    'total_supply_kg',
                    'total_demand_kg',
                    'total_supply_formatted',
                    'total_demand_formatted',
                    'rasio',
                    'rasio_persen',
                    'status',
                    'label',
                    'warna_hex',
                ],
            ],
            'meta' => ['dihitung_pada'],
        ]);

        // 2. Level Kabupaten
        $responseKab = $this->getJson('/api/status?level=kabupaten&komoditas=beras&periode=2024-03');
        $responseKab->assertStatus(200);
        $responseKab->assertJsonStructure([
            'komoditas' => ['kode', 'nama'],
            'periode',
            'level',
            'total_wilayah',
            'data' => [
                '*' => [
                    'kode',
                    'nama',
                    'tipe',
                    'kode_provinsi',
                    'nama_provinsi',
                    'supply_kg',
                    'demand_kg',
                    'supply_formatted',
                    'demand_formatted',
                    'rasio',
                    'rasio_persen',
                    'status',
                    'label',
                    'warna_hex',
                    'sumber',
                    'stok_estimasi',
                ],
            ],
        ]);

        $responseKab->assertJsonFragment([
            'kode' => '3302',
            'nama' => 'Banyumas',
            'status' => 'penuh',
        ]);
    }

    public function test_api_wilayah_seri_endpoint_returns_time_series_data(): void
    {
        $service = new StatusService;
        $kabupaten = Kabupaten::where('kode', '3302')->first();
        $komoditas = Komoditas::where('kode', 'beras')->first();

        StatusWilayah::create([
            'kabupaten_id' => $kabupaten->id,
            'komoditas_id' => $komoditas->id,
            'periode' => '2024-03-01',
            'supply_kg' => 12000000,
            'demand_kg' => 10000000,
            'rasio' => 1.2000,
            'status' => StatusLevel::Penuh,
            'sumber' => 'aktual',
            'stok_estimasi' => false,
            'dihitung_pada' => now(),
        ]);

        $service->invalidateCache();

        // 1. Seri Kabupaten (4 digit)
        $responseKab = $this->getJson('/api/wilayah/3302/seri?komoditas=beras');
        $responseKab->assertStatus(200);
        $responseKab->assertJsonStructure([
            'wilayah' => ['kode', 'nama', 'tipe'],
            'komoditas' => ['kode', 'nama'],
            'status_terbaru',
            'pesan_dampak',
            'model_version',
            'total_titik',
            'seri' => [
                '*' => [
                    'periode',
                    'supply_kg',
                    'demand_kg',
                    'rasio',
                    'rasio_persen',
                    'status',
                    'label',
                    'warna_hex',
                    'is_prediksi',
                    'stok_estimasi',
                ],
            ],
        ]);

        $responseKab->assertJsonFragment(['nama' => 'Banyumas']);

        // 2. Seri Provinsi (2 digit)
        $responseProv = $this->getJson('/api/wilayah/33/seri?komoditas=beras');
        $responseProv->assertStatus(200);
        $responseProv->assertJsonFragment(['nama' => 'Jawa Tengah']);

        // 3. Wilayah Tidak Dikenal
        $response404 = $this->getJson('/api/wilayah/9999/seri');
        $response404->assertStatus(404);
    }

    public function test_admin_can_trigger_status_recalc_via_web(): void
    {
        $admin = User::where('email', 'admin@sipangan.test')->first();

        $response = $this->actingAs($admin)->post(route('admin.status.recalc'));

        $response->assertRedirect();
        $response->assertSessionHas('sukses');
    }

    public function test_non_admin_cannot_trigger_status_recalc_via_web(): void
    {
        $distributor = User::where('email', 'distributor1@sipangan.test')->first();

        $response = $this->actingAs($distributor)->post(route('admin.status.recalc'));

        $response->assertStatus(403);
    }
}
