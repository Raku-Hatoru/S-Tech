<?php

namespace Tests\Feature;

use App\Enums\StatusLevel;
use App\Models\Kabupaten;
use App\Models\Komoditas;
use App\Models\StatusWilayah;
use App\Services\StatusService;
use Database\Seeders\KomoditasSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetaKpiDatabaseTest extends TestCase
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
     * Uji ringkasan statistik jujur StatusService cocok 100% dengan agregasi database riil.
     */
    public function test_ringkasan_wilayah_matches_direct_database_aggregations(): void
    {
        $statusService = app(StatusService::class);
        $beras = Komoditas::where('kode', 'beras')->firstOrFail();
        $periode = '2026-02-01';

        $kabupatens = Kabupaten::take(5)->get();

        // Bersihkan data eksisting pada periode uji jika ada
        StatusWilayah::where('komoditas_id', $beras->id)->whereDate('periode', $periode)->delete();

        // Buat 5 baris data terkontrol:
        // 2 penuh (rasio >= 1.0)
        // 2 waspada (0.90 <= rasio < 1.00)
        // 1 defisit (rasio < 0.70)
        StatusWilayah::create([
            'kabupaten_id' => $kabupatens[0]->id,
            'komoditas_id' => $beras->id,
            'periode' => $periode,
            'supply_kg' => 2000000.0,
            'demand_kg' => 1000000.0,
            'rasio' => 2.0,
            'status' => StatusLevel::Penuh,
            'sumber' => 'aktual',
            'dihitung_pada' => now(),
        ]);

        StatusWilayah::create([
            'kabupaten_id' => $kabupatens[1]->id,
            'komoditas_id' => $beras->id,
            'periode' => $periode,
            'supply_kg' => 1500000.0,
            'demand_kg' => 1000000.0,
            'rasio' => 1.5,
            'status' => StatusLevel::Penuh,
            'sumber' => 'aktual',
            'dihitung_pada' => now(),
        ]);

        StatusWilayah::create([
            'kabupaten_id' => $kabupatens[2]->id,
            'komoditas_id' => $beras->id,
            'periode' => $periode,
            'supply_kg' => 950000.0,
            'demand_kg' => 1000000.0,
            'rasio' => 0.95,
            'status' => StatusLevel::Waspada,
            'sumber' => 'aktual',
            'dihitung_pada' => now(),
        ]);

        StatusWilayah::create([
            'kabupaten_id' => $kabupatens[3]->id,
            'komoditas_id' => $beras->id,
            'periode' => $periode,
            'supply_kg' => 920000.0,
            'demand_kg' => 1000000.0,
            'rasio' => 0.92,
            'status' => StatusLevel::Waspada,
            'sumber' => 'aktual',
            'dihitung_pada' => now(),
        ]);

        StatusWilayah::create([
            'kabupaten_id' => $kabupatens[4]->id,
            'komoditas_id' => $beras->id,
            'periode' => $periode,
            'supply_kg' => 500000.0,
            'demand_kg' => 1000000.0,
            'rasio' => 0.50,
            'status' => StatusLevel::Defisit,
            'sumber' => 'aktual',
            'dihitung_pada' => now(),
        ]);

        $ringkasan = $statusService->ringkasanWilayah($beras->id, $periode);

        $this->assertEquals(5, $ringkasan['total_wilayah']);
        $this->assertEquals(2, $ringkasan['jumlah_penuh']);
        $this->assertEquals(2, $ringkasan['jumlah_waspada']);
        $this->assertEquals(1, $ringkasan['jumlah_defisit']);
        $this->assertEquals(0, $ringkasan['jumlah_seimbang']);
        $this->assertEquals(3, $ringkasan['jumlah_defisit_waspada']);

        // Total Supply: 2.000.000 + 1.500.000 + 950.000 + 920.000 + 500.000 = 5.870.000 kg (5.870,0 ton)
        $this->assertEquals(5870000.0, $ringkasan['total_supply_kg']);
        $this->assertEquals('5.870,0 ton', $ringkasan['total_supply_formatted']);

        // Total Demand: 5 x 1.000.000 = 5.000.000 kg (5.000,0 ton)
        $this->assertEquals(5000000.0, $ringkasan['total_demand_kg']);
        $this->assertEquals('5.000,0 ton', $ringkasan['total_demand_formatted']);

        // Selisih Bersih: 870.000 kg (+870,0 ton)
        $this->assertEquals(870000.0, $ringkasan['selisih_kg']);
        $this->assertEquals('+870,0 ton', $ringkasan['selisih_formatted']);

        // Persentase Selisih: (870.000 / 5.000.000) * 100 = 17.4% (+17,4%)
        $this->assertEquals('+17,4%', $ringkasan['selisih_persen_formatted']);

        // Rerata Rasio Wilayah Penuh: (2.0 + 1.5) / 2 = 1.75 => 175,0%
        $this->assertEquals('175,0%', $ringkasan['rerata_rasio_surplus_formatted']);

        // Total Defisit Riil: 1.000.000 - 500.000 = 500.000 kg (500,0 ton)
        $this->assertEquals(500000.0, $ringkasan['total_defisit_kg']);
        $this->assertEquals('500,0 ton', $ringkasan['total_defisit_formatted']);

        // Banner text generated
        $this->assertStringContainsString('2 Kabupaten Surplus/Penuh', $ringkasan['banner_text']);
        $this->assertStringContainsString('2 Waspada', $ringkasan['banner_text']);
        $this->assertStringContainsString('1 Defisit', $ringkasan['banner_text']);
        $this->assertStringContainsString('+870,0 ton', $ringkasan['banner_text']);
        $this->assertStringContainsString('+17,4%', $ringkasan['banner_text']);
    }

    /**
     * Uji halaman publik /peta merender metrik riil dan menghapus metrik palsu.
     */
    public function test_peta_page_renders_honest_database_kpis_and_omits_fake_metrics(): void
    {
        $statusService = app(StatusService::class);
        $beras = Komoditas::where('kode', 'beras')->firstOrFail();
        $periodeDefault = $statusService->getTanggalAktualTerakhir() ?? '2026-02-01';

        $response = $this->get('/peta');
        $response->assertStatus(200);

        // Assert metrik palsu dan terminologi harga tidak ada sama sekali di HTML
        $response->assertDontSee('82.4%');
        $response->assertDontSee('kapasitas gudang', false);
        $response->assertDontSee('Cold Storage', false);
        $response->assertDontSee('84.200');
        $response->assertDontSee('target pemenuhan', false);
        $response->assertDontSee('100% Terintegrasi BUMDes & Bulog', false);
        $response->assertDontSee('Akurasi Model Machine Learning', false);
        $response->assertDontSee('-14.2%');
        $response->assertDontSee('-14,2%');
        $response->assertDontSee('harga', false);
        $response->assertDontSee('deflasi', false);
        $response->assertDontSee('Estimasi Deflasi Produsen', false);

        // Assert metrik riil dan elemen ID konsisten ada
        $response->assertSee('id="kpi-total-supply"', false);
        $response->assertSee('id="kpi-demand-val"', false);
        $response->assertSee('id="kpi-jumlah-penuh"', false);
        $response->assertSee('id="kpi-jumlah-defisit-waspada"', false);
        $response->assertSee('id="banner-nasional-text"', false);
        $response->assertSee('Total Pasokan');
    }

    /**
     * Uji halaman /peta dan detail wilayah tidak memuat spekulasi harga atau estimasi deflasi.
     */
    public function test_peta_and_detail_pages_contain_no_price_predictions_or_deflation_terms(): void
    {
        $responsePeta = $this->get('/peta');
        $responsePeta->assertStatus(200);
        $responsePeta->assertDontSee('harga', false);
        $responsePeta->assertDontSee('deflasi', false);
        $responsePeta->assertDontSee('-14,2%');
        $responsePeta->assertDontSee('Estimasi Deflasi Produsen', false);

        $kabupaten = Kabupaten::where('kode', '3302')->first() ?? Kabupaten::first();
        if ($kabupaten) {
            $responseDetail = $this->get('/wilayah/'.$kabupaten->kode);
            $responseDetail->assertStatus(200);
            $responseDetail->assertDontSee('harga', false);
            $responseDetail->assertDontSee('deflasi', false);
            $responseDetail->assertDontSee('-14,2%');
            $responseDetail->assertDontSee('HPP', false);
        }
    }

    /**
     * Uji endpoint /api/status memuat ringkasan yang cocok dengan data riil.
     */
    public function test_api_status_returns_honest_ringkasan_payload(): void
    {
        $response = $this->getJson('/api/status?komoditas=beras&periode=2026-02&level=kabupaten');
        $response->assertStatus(200);

        $response->assertJsonStructure([
            'komoditas',
            'periode',
            'level',
            'total_wilayah',
            'ringkasan' => [
                'total_wilayah',
                'jumlah_penuh',
                'jumlah_seimbang',
                'jumlah_waspada',
                'jumlah_defisit',
                'total_supply_kg',
                'total_demand_kg',
                'selisih_kg',
                'selisih_formatted',
                'banner_text',
            ],
            'data',
        ]);

        $ringkasan = $response->json('ringkasan');
        $this->assertIsInt($ringkasan['jumlah_penuh']);
        $this->assertIsInt($ringkasan['jumlah_defisit']);
        $this->assertIsString($ringkasan['banner_text']);
    }
}
