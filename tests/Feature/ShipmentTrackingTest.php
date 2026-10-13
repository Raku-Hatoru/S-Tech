<?php

namespace Tests\Feature;

use App\Enums\ShipmentStatus;
use App\Enums\StatusLevel;
use App\Models\Kabupaten;
use App\Models\Komoditas;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Models\User;
use Database\Seeders\KomoditasSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected User $distributor1;

    protected User $distributor2;

    protected User $admin;

    protected User $bumdes;

    protected Komoditas $beras;

    protected Kabupaten $cilacap;

    protected Kabupaten $banyumas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(KomoditasSeeder::class);
        $this->seed(WilayahSeeder::class);
        $this->seed(SettingSeeder::class);

        $roleDistributor = Role::where('kode', 'distributor')->first();
        $roleAdmin = Role::where('kode', 'admin')->first();
        $roleBumdes = Role::where('kode', 'bumdes')->first();

        $this->distributor1 = User::factory()->create([
            'role_id' => $roleDistributor->id,
            'name' => 'Budi Santoso',
            'organisasi' => 'PT Pangan Mandiri',
            'is_active' => true,
        ]);

        $this->distributor2 = User::factory()->create([
            'role_id' => $roleDistributor->id,
            'name' => 'Sari Dewi',
            'organisasi' => 'CV Logistik Jaya',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'role_id' => $roleAdmin->id,
            'name' => 'Admin Nasional',
            'is_active' => true,
        ]);

        $this->cilacap = Kabupaten::where('kode', '3301')->first() ?? Kabupaten::first();
        $this->banyumas = Kabupaten::where('kode', '3302')->first() ?? Kabupaten::skip(1)->first();

        $this->bumdes = User::factory()->create([
            'role_id' => $roleBumdes->id,
            'kabupaten_id' => $this->banyumas->id,
            'name' => 'BUMDes Banyumas',
            'is_active' => true,
        ]);

        $this->beras = Komoditas::where('kode', 'beras')->first() ?? Komoditas::first();
    }

    /**
     * Tamu tanpa login dialihkan ke halaman login saat mengakses portal pengiriman.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/pengiriman');
        $response->assertRedirect('/login');

        $responseCreate = $this->get('/pengiriman/buat');
        $responseCreate->assertRedirect('/login');
    }

    /**
     * Akun BUMDes ditolak (403) saat mengakses portal distributor pengiriman.
     */
    public function test_bumdes_cannot_access_distributor_portal(): void
    {
        $response = $this->actingAs($this->bumdes)->get('/pengiriman');
        $response->assertStatus(403);

        $responseCreate = $this->actingAs($this->bumdes)->get('/pengiriman/buat');
        $responseCreate->assertStatus(403);
    }

    /**
     * Distributor dapat melihat halaman index dashboard pengiriman miliknya.
     */
    public function test_distributor_can_view_shipment_index_page(): void
    {
        Shipment::create([
            'user_id' => $this->distributor1->id,
            'no_resi' => 'RESI-TEST-001',
            'komoditas_id' => $this->beras->id,
            'jumlah_kg' => 2000,
            'asal_kabupaten_id' => $this->cilacap->id,
            'tujuan_kabupaten_id' => $this->banyumas->id,
            'tanggal_kirim' => now()->toDateString(),
            'status' => ShipmentStatus::Dibuat,
        ]);

        $response = $this->actingAs($this->distributor1)->get('/pengiriman');

        $response->assertStatus(200);
        $response->assertSee('Portal Manajemen Distribusi Pangan Logistik');
        $response->assertSee('RESI-TEST-001');
        $response->assertSee('Total Pengiriman');
        $response->assertSee('Dalam Perjalanan');
        $response->assertSee('Total Muatan Logistik');
    }

    /**
     * Distributor hanya melihat pengiriman miliknya, bukan milik distributor lain.
     */
    public function test_distributor_only_sees_their_own_shipments(): void
    {
        Shipment::create([
            'user_id' => $this->distributor1->id,
            'no_resi' => 'RESI-DIST1-EXCLUSIVE',
            'komoditas_id' => $this->beras->id,
            'jumlah_kg' => 2500,
            'asal_kabupaten_id' => $this->cilacap->id,
            'tujuan_kabupaten_id' => $this->banyumas->id,
            'tanggal_kirim' => now()->toDateString(),
            'status' => ShipmentStatus::Dibuat,
        ]);

        Shipment::create([
            'user_id' => $this->distributor2->id,
            'no_resi' => 'RESI-DIST2-SECRET',
            'komoditas_id' => $this->beras->id,
            'jumlah_kg' => 1800,
            'asal_kabupaten_id' => $this->cilacap->id,
            'tujuan_kabupaten_id' => $this->banyumas->id,
            'tanggal_kirim' => now()->toDateString(),
            'status' => ShipmentStatus::Dibuat,
        ]);

        // Distributor 1 mengecek index
        $response1 = $this->actingAs($this->distributor1)->get('/pengiriman');
        $response1->assertStatus(200);
        $response1->assertSee('RESI-DIST1-EXCLUSIVE');
        $response1->assertDontSee('RESI-DIST2-SECRET');

        // Distributor 2 mengecek index
        $response2 = $this->actingAs($this->distributor2)->get('/pengiriman');
        $response2->assertStatus(200);
        $response2->assertSee('RESI-DIST2-SECRET');
        $response2->assertDontSee('RESI-DIST1-EXCLUSIVE');
    }

    /**
     * Distributor dapat membuka formulir buat pengiriman baru.
     */
    public function test_distributor_can_view_create_shipment_form(): void
    {
        $response = $this->actingAs($this->distributor1)->get('/pengiriman/buat?tujuan=3302&komoditas=beras');

        $response->assertStatus(200);
        $response->assertSee('Formulir Dispatch Logistik Bahan Pokok');
        $response->assertSee('F-07: Entry Dispatch');
        $response->assertSee('Nomor Resi Unik (Waybill)');
        $response->assertSee('Pilihan Komoditas Pangan');
        $response->assertSee('Jumlah Pengiriman (kg)');
        $response->assertSee('Kabupaten Asal (Sumber Pasokan)');
        $response->assertSee('Kabupaten Tujuan (Penerima)');
        $response->assertSee('TELEMETRI KAPASITAS SILO / PASAR');
    }

    /**
     * Distributor dapat membuat pengiriman baru dan otomatis membuat event awal.
     */
    public function test_distributor_can_create_new_shipment(): void
    {
        $payload = [
            'no_resi' => 'RESI-BARU-2026',
            'komoditas_id' => $this->beras->id,
            'jumlah_kg' => 3000,
            'asal_kabupaten_id' => $this->cilacap->id,
            'tujuan_kabupaten_id' => $this->banyumas->id,
            'tanggal_kirim' => now()->toDateString(),
            'estimasi_tiba' => now()->addDays(2)->toDateString(),
            'catatan' => 'Muatan uji coba distribusi.',
            'konfirmasi_peringatan' => 1,
        ];

        $response = $this->actingAs($this->distributor1)->post('/pengiriman', $payload);

        $this->assertDatabaseHas('shipments', [
            'no_resi' => 'RESI-BARU-2026',
            'user_id' => $this->distributor1->id,
            'jumlah_kg' => 3000,
            'konfirmasi_peringatan' => 1,
        ]);

        $shipment = Shipment::where('no_resi', 'RESI-BARU-2026')->first();
        $this->assertNotNull($shipment);

        // Pastikan event initial otomatis dibuat
        $this->assertDatabaseHas('shipment_events', [
            'shipment_id' => $shipment->id,
            'status' => ShipmentStatus::Dibuat->value,
            'dicatat_oleh' => $this->distributor1->id,
        ]);

        $response->assertRedirect(route('pengiriman.show', $shipment->id));
    }

    /**
     * Validasi nomor resi unik menolak resi duplikat.
     */
    public function test_duplicate_no_resi_is_rejected(): void
    {
        Shipment::create([
            'user_id' => $this->distributor1->id,
            'no_resi' => 'RESI-DUPLIKAT',
            'komoditas_id' => $this->beras->id,
            'jumlah_kg' => 2000,
            'asal_kabupaten_id' => $this->cilacap->id,
            'tujuan_kabupaten_id' => $this->banyumas->id,
            'tanggal_kirim' => now()->toDateString(),
            'status' => ShipmentStatus::Dibuat,
        ]);

        $payload = [
            'no_resi' => 'RESI-DUPLIKAT',
            'komoditas_id' => $this->beras->id,
            'jumlah_kg' => 1500,
            'asal_kabupaten_id' => $this->cilacap->id,
            'tujuan_kabupaten_id' => $this->banyumas->id,
            'tanggal_kirim' => now()->toDateString(),
        ];

        $response = $this->actingAs($this->distributor1)->post('/pengiriman', $payload);
        $response->assertSessionHasErrors(['no_resi']);
    }

    /**
     * Distributor dapat melihat halaman detail pelacakan resi miliknya.
     */
    public function test_distributor_can_view_shipment_tracking_detail(): void
    {
        $shipment = Shipment::create([
            'user_id' => $this->distributor1->id,
            'no_resi' => 'RESI-CONTOH-0001',
            'komoditas_id' => $this->beras->id,
            'jumlah_kg' => 2500,
            'asal_kabupaten_id' => $this->cilacap->id,
            'tujuan_kabupaten_id' => $this->banyumas->id,
            'tanggal_kirim' => now()->subDay()->toDateString(),
            'estimasi_tiba' => now()->addDay()->toDateString(),
            'status' => ShipmentStatus::DalamPerjalanan,
            'level_peringatan' => StatusLevel::Penuh,
            'rasio_tujuan' => 1.0511,
            'konfirmasi_peringatan' => true,
        ]);

        ShipmentEvent::create([
            'shipment_id' => $shipment->id,
            'status' => ShipmentStatus::Dibuat->value,
            'lokasi' => 'Cilacap',
            'keterangan' => 'Pengiriman didaftarkan.',
            'waktu_kejadian' => now()->subDay(),
            'dicatat_oleh' => $this->distributor1->id,
        ]);

        $response = $this->actingAs($this->distributor1)->get("/pengiriman/{$shipment->id}");

        $response->assertStatus(200);
        $response->assertSee('RESI-CONTOH-0001');
        $response->assertSee('Dalam Perjalanan');
        $response->assertSee('Riwayat Perjalanan (Tracking Timeline)');
        $response->assertSee('Informasi Manifest Logistik');
        $response->assertSee('Perbarui Status');
    }

    /**
     * Distributor lain dilarang melihat detail pengiriman orang lain (403).
     */
    public function test_distributor_cannot_view_other_distributors_shipment_detail(): void
    {
        $shipment = Shipment::create([
            'user_id' => $this->distributor1->id,
            'no_resi' => 'RESI-RAHASIA-DIST1',
            'komoditas_id' => $this->beras->id,
            'jumlah_kg' => 2000,
            'asal_kabupaten_id' => $this->cilacap->id,
            'tujuan_kabupaten_id' => $this->banyumas->id,
            'tanggal_kirim' => now()->toDateString(),
            'status' => ShipmentStatus::Dibuat,
        ]);

        $response = $this->actingAs($this->distributor2)->get("/pengiriman/{$shipment->id}");
        $response->assertStatus(403);
    }

    /**
     * Admin dapat melihat detail pengiriman distributor mana pun.
     */
    public function test_admin_can_view_any_shipment_detail(): void
    {
        $shipment = Shipment::create([
            'user_id' => $this->distributor1->id,
            'no_resi' => 'RESI-DIST1-UNTUK-ADMIN',
            'komoditas_id' => $this->beras->id,
            'jumlah_kg' => 2000,
            'asal_kabupaten_id' => $this->cilacap->id,
            'tujuan_kabupaten_id' => $this->banyumas->id,
            'tanggal_kirim' => now()->toDateString(),
            'status' => ShipmentStatus::Dibuat,
        ]);

        $response = $this->actingAs($this->admin)->get("/pengiriman/{$shipment->id}");
        $response->assertStatus(200);
        $response->assertSee('RESI-DIST1-UNTUK-ADMIN');
    }

    /**
     * Distributor dapat memperbarui status pengiriman dan mencatat checkpoint baru.
     */
    public function test_distributor_can_update_status_and_add_checkpoint(): void
    {
        $shipment = Shipment::create([
            'user_id' => $this->distributor1->id,
            'no_resi' => 'RESI-UPDATE-EVENT',
            'komoditas_id' => $this->beras->id,
            'jumlah_kg' => 2500,
            'asal_kabupaten_id' => $this->cilacap->id,
            'tujuan_kabupaten_id' => $this->banyumas->id,
            'tanggal_kirim' => now()->toDateString(),
            'status' => ShipmentStatus::Dibuat,
        ]);

        $payload = [
            'status' => ShipmentStatus::TibaDiTujuan->value,
            'lokasi' => 'Gudang Induk Banyumas',
            'keterangan' => 'Truk tronton telah tiba dan siap bongkar muatan.',
        ];

        $response = $this->actingAs($this->distributor1)->post("/pengiriman/{$shipment->id}/event", $payload);

        $response->assertRedirect(route('pengiriman.show', $shipment->id));

        $shipment->refresh();
        $this->assertEquals(ShipmentStatus::TibaDiTujuan, $shipment->status);

        $this->assertDatabaseHas('shipment_events', [
            'shipment_id' => $shipment->id,
            'status' => ShipmentStatus::TibaDiTujuan->value,
            'lokasi' => 'Gudang Induk Banyumas',
            'keterangan' => 'Truk tronton telah tiba dan siap bongkar muatan.',
        ]);
    }
}
