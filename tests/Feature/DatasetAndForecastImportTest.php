<?php

namespace Tests\Feature;

use App\Models\Kabupaten;
use App\Models\ModelVersion;
use App\Models\User;
use App\Services\DataHistorisImporter;
use App\Services\ForecastImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatasetAndForecastImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
    }

    public function test_data_historis_importer_imports_valid_csv(): void
    {
        $csvContent = "kode_kabupaten,komoditas,periode,panen_kg,stok_awal_kg,pasokan_masuk_kg,supply_kg,demand_kg,curah_hujan_mm,jumlah_penduduk,luas_panen_ha,flag_estimasi,sumber_data\n"
            ."3302,beras,2024-03,15000000.00,5000000.00,,20000000.00,12000000.00,240.50,1820000,28000.00,0,BPS Jateng\n"
            ."3301,jagung,2024-03,8000000.00,2000000.00,500000.00,,5000000.00,190.20,1980000,15000.00,0,BPS Jateng\n";

        $tempPath = tempnam(sys_get_temp_dir(), 'test_hist_').'.csv';
        file_put_contents($tempPath, $csvContent);

        $importer = new DataHistorisImporter;
        $importLog = $importer->import($tempPath, null, 'test_historis.csv');

        unlink($tempPath);

        $this->assertEquals('selesai', $importLog->status);
        $this->assertEquals(2, $importLog->jumlah_baris);
        $this->assertEquals(2, $importLog->jumlah_berhasil);
        $this->assertEquals(0, $importLog->jumlah_gagal);

        $this->assertDatabaseHas('data_historis', [
            'periode' => '2024-03-01',
            'pasokan_masuk_kg' => '0.00', // Sesuai aturan: sel kosong diisi 0
            'panen_kg' => '15000000.00',
        ]);

        $this->assertDatabaseHas('data_historis', [
            'periode' => '2024-03-01',
            'supply_kg' => '10500000.00', // Dihitung otomatis: 8jt + 2jt + 500rb
        ]);
    }

    public function test_data_historis_importer_preserves_leading_zeros_and_records_failed_rows(): void
    {
        // Masukkan kabupaten buatan dengan kode berawalan nol '0101'
        $kab0101 = Kabupaten::create([
            'kode' => '0101',
            'provinsi_id' => 1,
            'nama' => 'Kabupaten Contoh Nol',
            'tipe' => 'kabupaten',
        ]);

        $csvContent = "kode_kabupaten,komoditas,periode,panen_kg,stok_awal_kg,pasokan_masuk_kg,supply_kg,demand_kg\n"
            ."0101,beras,2024-01,10000.00,2000.00,0,12000.00,9000.00\n" // valid, kode 0101
            ."9999,beras,2024-01,10000.00,2000.00,0,12000.00,9000.00\n" // gagal: kode tidak ada
            ."0101,gandum,2024-01,10000.00,2000.00,0,12000.00,9000.00\n" // gagal: komoditas gandum bukan beras/jagung/singkong
            ."0101,beras,2024-13,10000.00,2000.00,0,12000.00,9000.00\n"; // gagal: bulan 13 tidak valid

        $tempPath = tempnam(sys_get_temp_dir(), 'test_zero_').'.csv';
        file_put_contents($tempPath, $csvContent);

        $importer = new DataHistorisImporter;
        $importLog = $importer->import($tempPath, null, 'test_zero.csv');

        unlink($tempPath);

        $this->assertEquals(4, $importLog->jumlah_baris);
        $this->assertEquals(1, $importLog->jumlah_berhasil);
        $this->assertEquals(3, $importLog->jumlah_gagal);
        $this->assertNotNull($importLog->ringkasan_galat);
        $this->assertStringContainsString("Kode kabupaten '9999' tidak terdaftar", $importLog->ringkasan_galat);
        $this->assertStringContainsString("Komoditas 'gandum' tidak valid", $importLog->ringkasan_galat);
        $this->assertStringContainsString("Format periode '2024-13' tidak valid", $importLog->ringkasan_galat);

        // Pastikan kode 0101 utuh dan masuk ke basis data
        $this->assertDatabaseHas('data_historis', [
            'kabupaten_id' => $kab0101->id,
            'periode' => '2024-01-01',
            'panen_kg' => '10000.00',
        ]);
    }

    public function test_forecast_importer_imports_valid_forecast_csv(): void
    {
        $csvContent = "kode_kabupaten,komoditas,periode_dasar,periode_target,horizon_bulan,panen_pred_kg,pasokan_masuk_pred_kg,stok_awal_pred_kg,supply_pred_kg,demand_pred_kg,rasio_pred,status_pred,rasio_bawah,rasio_atas,versi_model,dibuat_pada\n"
            ."3302,beras,2025-12,2026-01,1,12000000.00,500000.00,4000000.00,16500000.00,15000000.00,1.1000,penuh,1.0500,1.1500,v1-2026Q1-test,2026-01-01 08:00:00\n"
            ."3301,jagung,2025-12,2026-02,2,3000000.00,200000.00,1000000.00,,5000000.00,,,0.7500,0.9200,v1-2026Q1-test,2026-01-01 08:00:00\n";

        $tempPath = tempnam(sys_get_temp_dir(), 'test_fore_').'.csv';
        file_put_contents($tempPath, $csvContent);

        $importer = new ForecastImporter;
        $importLog = $importer->import($tempPath, null, 'test_forecast.csv');

        unlink($tempPath);

        $this->assertEquals('selesai', $importLog->status);
        $this->assertEquals(2, $importLog->jumlah_baris);
        $this->assertEquals(2, $importLog->jumlah_berhasil);
        $this->assertEquals(0, $importLog->jumlah_gagal);

        // Pastikan ModelVersion dibuat otomatis
        $this->assertDatabaseHas('model_versions', [
            'nama' => 'v1-2026Q1-test',
            'is_aktif' => true,
        ]);

        $this->assertDatabaseHas('forecasts', [
            'periode_dasar' => '2025-12-01',
            'periode_target' => '2026-01-01',
            'horizon_bulan' => 1,
            'status_pred' => 'penuh',
        ]);

        // Baris kedua: supply dihitung otomatis = 3jt + 200rb + 1jt = 4.2jt.
        // Demand = 5jt -> rasio = 4.2 / 5.0 = 0.8400 -> status: seimbang (antara 0.70 dan 0.90)
        $this->assertDatabaseHas('forecasts', [
            'periode_dasar' => '2025-12-01',
            'periode_target' => '2026-02-01',
            'horizon_bulan' => 2,
            'supply_pred_kg' => '4200000.00',
            'rasio_pred' => '0.8400',
            'status_pred' => 'seimbang',
        ]);
    }

    public function test_artisan_data_import_historis_command_executes_successfully(): void
    {
        $csvContent = "kode_kabupaten,komoditas,periode,panen_kg,stok_awal_kg,pasokan_masuk_kg,supply_kg,demand_kg\n"
            ."3302,beras,2024-05,100000.00,20000.00,0,120000.00,90000.00\n";

        $tempPath = tempnam(sys_get_temp_dir(), 'cmd_hist_').'.csv';
        file_put_contents($tempPath, $csvContent);

        $this->artisan('data:import-historis', ['file' => $tempPath])
            ->assertSuccessful();

        unlink($tempPath);

        $this->assertDatabaseHas('data_historis', [
            'periode' => '2024-05-01',
            'panen_kg' => '100000.00',
        ]);
    }

    public function test_artisan_forecast_import_command_executes_successfully(): void
    {
        $csvContent = "kode_kabupaten,komoditas,periode_dasar,periode_target,horizon_bulan,panen_pred_kg,pasokan_masuk_pred_kg,stok_awal_pred_kg,supply_pred_kg,demand_pred_kg,rasio_pred,status_pred,versi_model\n"
            ."3302,beras,2025-12,2026-03,3,100000.00,10000.00,20000.00,130000.00,100000.00,1.3000,penuh,v1-artisan-test\n";

        $tempPath = tempnam(sys_get_temp_dir(), 'cmd_fore_').'.csv';
        file_put_contents($tempPath, $csvContent);

        $this->artisan('forecast:import', ['file' => $tempPath])
            ->assertSuccessful();

        unlink($tempPath);

        $this->assertDatabaseHas('forecasts', [
            'periode_dasar' => '2025-12-01',
            'periode_target' => '2026-03-01',
            'horizon_bulan' => 3,
            'status_pred' => 'penuh',
        ]);
    }

    public function test_admin_can_access_dataset_index(): void
    {
        $admin = User::where('email', 'admin@sipangan.test')->first();

        $response = $this->actingAs($admin)->get(route('admin.dataset.index'));

        $response->assertStatus(200);
        $response->assertSee('Dataset Historis Pangan');
        $response->assertSee('Unggah Dataset CSV');
    }

    public function test_non_admin_cannot_access_dataset_index(): void
    {
        $distributor = User::where('email', 'distributor1@sipangan.test')->first();

        $this->actingAs($distributor)
            ->get(route('admin.dataset.index'))
            ->assertStatus(403);
    }

    public function test_admin_can_upload_dataset_via_web(): void
    {
        $admin = User::where('email', 'admin@sipangan.test')->first();

        $csvContent = "kode_kabupaten,komoditas,periode,panen_kg,stok_awal_kg,pasokan_masuk_kg,supply_kg,demand_kg\n"
            ."3302,beras,2024-06,200000.00,30000.00,0,230000.00,150000.00\n";

        $file = UploadedFile::fake()->createWithContent('dataset_upload.csv', $csvContent);

        $response = $this->actingAs($admin)->post(route('admin.dataset.store'), [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('sukses');

        $this->assertDatabaseHas('data_historis', [
            'periode' => '2024-06-01',
            'panen_kg' => '200000.00',
        ]);

        $this->assertDatabaseHas('dataset_imports', [
            'user_id' => $admin->id,
            'tipe' => 'historis',
            'status' => 'selesai',
        ]);
    }

    public function test_admin_can_access_forecast_index(): void
    {
        $admin = User::where('email', 'admin@sipangan.test')->first();

        $response = $this->actingAs($admin)->get(route('admin.forecast.index'));

        $response->assertStatus(200);
        $response->assertSee('Hasil Prediksi XGBoost');
        $response->assertSee('Unggah Hasil Prediksi CSV');
    }

    public function test_non_admin_cannot_access_forecast_index(): void
    {
        $distributor = User::where('email', 'distributor1@sipangan.test')->first();

        $this->actingAs($distributor)
            ->get(route('admin.forecast.index'))
            ->assertStatus(403);
    }

    public function test_admin_can_upload_forecast_via_web(): void
    {
        $admin = User::where('email', 'admin@sipangan.test')->first();

        $csvContent = "kode_kabupaten,komoditas,periode_dasar,periode_target,horizon_bulan,panen_pred_kg,pasokan_masuk_pred_kg,stok_awal_pred_kg,supply_pred_kg,demand_pred_kg,rasio_pred,status_pred,versi_model\n"
            ."3302,beras,2025-12,2026-04,4,120000.00,15000.00,25000.00,160000.00,110000.00,1.4545,penuh,v1-web-test\n";

        $file = UploadedFile::fake()->createWithContent('forecast_upload.csv', $csvContent);

        $response = $this->actingAs($admin)->post(route('admin.forecast.store'), [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('sukses');

        $this->assertDatabaseHas('forecasts', [
            'periode_dasar' => '2025-12-01',
            'periode_target' => '2026-04-01',
            'horizon_bulan' => 4,
        ]);

        $this->assertDatabaseHas('dataset_imports', [
            'user_id' => $admin->id,
            'tipe' => 'forecast',
            'status' => 'selesai',
        ]);
    }

    public function test_admin_can_toggle_model_version(): void
    {
        $admin = User::where('email', 'admin@sipangan.test')->first();

        $v1 = ModelVersion::create(['nama' => 'v1-model', 'algoritma' => 'xgboost', 'is_aktif' => true]);
        $v2 = ModelVersion::create(['nama' => 'v2-model', 'algoritma' => 'xgboost', 'is_aktif' => false]);

        $response = $this->actingAs($admin)->post(route('admin.forecast.toggle-model', $v2));

        $response->assertRedirect();
        $response->assertSessionHas('sukses');

        $this->assertFalse((bool) $v1->fresh()->is_aktif);
        $this->assertTrue((bool) $v2->fresh()->is_aktif);
    }
}
