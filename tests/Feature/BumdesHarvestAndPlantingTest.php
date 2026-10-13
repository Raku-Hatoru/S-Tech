<?php

namespace Tests\Feature;

use App\Models\HarvestReport;
use App\Models\Kabupaten;
use App\Models\Komoditas;
use App\Models\Role;
use App\Models\Setting;
use App\Models\StatusWilayah;
use App\Models\User;
use Database\Seeders\KomoditasSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BumdesHarvestAndPlantingTest extends TestCase
{
    use RefreshDatabase;

    protected User $bumdesBanyumas;

    protected User $distributor;

    protected User $admin;

    protected Kabupaten $banyumas;

    protected Kabupaten $cilacap;

    protected Komoditas $beras;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(KomoditasSeeder::class);
        $this->seed(WilayahSeeder::class);
        $this->seed(SettingSeeder::class);

        $roleAdmin = Role::where('kode', 'admin')->firstOrFail();
        $roleBumdes = Role::where('kode', 'bumdes')->firstOrFail();
        $roleDistributor = Role::where('kode', 'distributor')->firstOrFail();

        $this->banyumas = Kabupaten::where('kode', '3302')->firstOrFail();
        $this->cilacap = Kabupaten::where('kode', '3301')->firstOrFail();
        $this->beras = Komoditas::where('kode', 'beras')->firstOrFail();

        $this->bumdesBanyumas = User::factory()->create([
            'role_id' => $roleBumdes->id,
            'kabupaten_id' => $this->banyumas->id,
            'organisasi' => 'BUMDes Makmur Banyumas',
            'is_active' => true,
        ]);

        $this->distributor = User::factory()->create([
            'role_id' => $roleDistributor->id,
            'organisasi' => 'PT Distrib Logistik',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'role_id' => $roleAdmin->id,
            'organisasi' => 'Badan Pangan Nasional',
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_bumdes_portal(): void
    {
        $responsePanen = $this->get(route('panen.index'));
        $responsePanen->assertRedirect(route('login'));

        $responseJadwal = $this->get(route('jadwal-tanam.index'));
        $responseJadwal->assertRedirect(route('login'));
    }

    public function test_distributor_cannot_access_bumdes_portal(): void
    {
        $response = $this->actingAs($this->distributor)->get(route('panen.index'));
        $response->assertForbidden();
    }

    public function test_bumdes_user_can_access_portal_with_locked_kabupaten(): void
    {
        $response = $this->actingAs($this->bumdesBanyumas)->get(route('panen.index'));

        $response->assertOk();
        $response->assertSee('Portal Manajemen Pangan BUMDes');
        $response->assertSee('Banyumas');
        $response->assertSee('ID: BUMDES-3302-AKTIF');
        $response->assertSee('Terkunci ke 1 Kabupaten sesuai regulasi ketahanan pangan');
    }

    public function test_bumdes_user_can_submit_harvest_report(): void
    {
        $periode = '2026-03';

        $payload = [
            'komoditas_id' => $this->beras->id,
            'periode' => $periode,
            'panen_kg' => 14500000,
            'sisa_stok_kg' => 2950000,
            'tanggal_survei' => '2026-03-31',
            'catatan' => 'Survei lumbung panen MT-1 gabungan Banyumas',
        ];

        $response = $this->actingAs($this->bumdesBanyumas)->post(route('panen.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('sukses');

        $report = HarvestReport::where('kabupaten_id', $this->banyumas->id)
            ->where('komoditas_id', $this->beras->id)
            ->whereDate('periode', '2026-03-01')
            ->first();

        $this->assertNotNull($report);
        $this->assertEquals(14500000, (float) $report->panen_kg);
        $this->assertEquals(2950000, (float) $report->sisa_stok_kg);
        $this->assertEquals('Survei lumbung panen MT-1 gabungan Banyumas', $report->catatan);
    }

    public function test_harvest_report_clears_stok_estimasi_for_next_month(): void
    {
        $periode = '2026-04-01';
        $nextPeriode = '2026-05-01';

        // Buat laporan untuk April 2026
        $payload = [
            'komoditas_id' => $this->beras->id,
            'periode' => '2026-04',
            'panen_kg' => 15000000,
            'sisa_stok_kg' => 3200000,
            'tanggal_survei' => '2026-04-30',
            'catatan' => 'Laporan survei April',
        ];

        $this->actingAs($this->bumdesBanyumas)->post(route('panen.store'), $payload);

        // Periksa status_wilayah bulan Mei 2026 (next month)
        $statusNext = StatusWilayah::where('kabupaten_id', $this->banyumas->id)
            ->where('komoditas_id', $this->beras->id)
            ->whereDate('periode', $nextPeriode)
            ->first();

        $this->assertNotNull($statusNext);
        // Sisa stok survei April (3.200.000 kg) menjadi stok awal Mei, sehingga stok_estimasi = false (0)
        $this->assertFalse((bool) $statusNext->stok_estimasi);
    }

    public function test_bumdes_user_cannot_spoof_different_kabupaten_in_report(): void
    {
        $payload = [
            'kabupaten_id' => $this->cilacap->id, // Mencoba kirim untuk Cilacap padahal BUMDes Banyumas
            'komoditas_id' => $this->beras->id,
            'periode' => '2026-06',
            'panen_kg' => 10000000,
            'sisa_stok_kg' => 2000000,
        ];

        $response = $this->actingAs($this->bumdesBanyumas)->post(route('panen.store'), $payload);
        $response->assertRedirect();

        // Database harus tersimpan ke Banyumas (kabupaten akun), BUKAN Cilacap
        $reportBanyumas = HarvestReport::where('kabupaten_id', $this->banyumas->id)
            ->whereDate('periode', '2026-06-01')
            ->first();
        $this->assertNotNull($reportBanyumas);

        $reportCilacap = HarvestReport::where('kabupaten_id', $this->cilacap->id)
            ->whereDate('periode', '2026-06-01')
            ->first();
        $this->assertNull($reportCilacap);
    }

    public function test_export_csv_returns_streamed_csv_response(): void
    {
        HarvestReport::create([
            'user_id' => $this->bumdesBanyumas->id,
            'kabupaten_id' => $this->banyumas->id,
            'komoditas_id' => $this->beras->id,
            'periode' => '2026-01-01',
            'panen_kg' => 15200000,
            'sisa_stok_kg' => 3100000,
            'catatan' => 'Uji CSV Banyumas',
        ]);

        $response = $this->actingAs($this->bumdesBanyumas)->get(route('panen.export-csv', [
            'komoditas_id' => $this->beras->id,
        ]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $content = $response->streamedContent();
        $this->assertStringContainsString('Bulan,Kabupaten,Komoditas', $content);
        $this->assertStringContainsString('Banyumas', $content);
        $this->assertStringContainsString('15200000', $content);
    }

    public function test_planting_recommendations_page_displays_simulation_label_and_items(): void
    {
        $response = $this->actingAs($this->bumdesBanyumas)->get(route('jadwal-tanam.index'));

        $response->assertOk();
        $response->assertSee('[SIMULASI / DATA CONTOH]');
        $response->assertSee('Beras (MT-2)');
        $response->assertSee('Teruskan Panduan ke Gapoktan');
    }

    public function test_admin_can_manage_settings_and_trigger_recalculation(): void
    {
        $responseGet = $this->actingAs($this->admin)->get(route('admin.pengaturan.index'));
        $responseGet->assertOk();
        $responseGet->assertSee('Konfigurasi Ambang Batas');

        $payload = [
            'ambang_defisit_max' => 0.6500,
            'ambang_waspada_min' => 0.8500,
            'ambang_penuh_min' => 0.9800,
            'unit_switch_kg' => 2500,
            'teks_disclaimer' => 'Disclaimer kustom pengujian admin.',
            'pesan_dampak_surplus' => 'Pesan surplus kustom pengujian.',
            'pesan_dampak_defisit' => 'Pesan defisit kustom pengujian.',
        ];

        $responsePut = $this->actingAs($this->admin)->put(route('admin.pengaturan.update'), $payload);

        $responsePut->assertRedirect(route('admin.pengaturan.index'));
        $responsePut->assertSessionHas('sukses');

        $this->assertEquals(0.6500, (float) Setting::nilai('ambang_defisit_max'));
        $this->assertEquals(0.8500, (float) Setting::nilai('ambang_waspada_min'));
        $this->assertEquals(0.9800, (float) Setting::nilai('ambang_penuh_min'));
        $this->assertEquals(2500, (int) Setting::nilai('unit_switch_kg'));
        $this->assertEquals('Disclaimer kustom pengujian admin.', Setting::nilai('teks_disclaimer'));
    }

    public function test_admin_can_submit_harvest_report_for_any_kabupaten(): void
    {
        $payload = [
            'kabupaten_id' => $this->cilacap->id,
            'komoditas_id' => $this->beras->id,
            'periode' => '2026-07',
            'panen_kg' => 11000000,
            'sisa_stok_kg' => 2100000,
            'catatan' => 'Laporan Cilacap oleh Admin',
        ];

        $response = $this->actingAs($this->admin)->post(route('panen.store'), $payload);
        $response->assertRedirect();

        $this->assertDatabaseHas('harvest_reports', [
            'kabupaten_id' => $this->cilacap->id,
            'user_id' => $this->admin->id,
            'catatan' => 'Laporan Cilacap oleh Admin',
        ]);
    }
}
