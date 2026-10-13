<?php

namespace Tests\Feature;

use App\Enums\ShipmentStatus;
use App\Enums\StatusLevel;
use App\Models\Kabupaten;
use App\Models\Komoditas;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\StatusWilayah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationAndVisibilityAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $distributor1;

    protected User $distributor2;

    protected User $bumdesBanyumas;

    protected User $bumdesCilacap;

    protected Kabupaten $banyumas;

    protected Kabupaten $cilacap;

    protected Komoditas $beras;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $roleAdmin = Role::where('kode', 'admin')->firstOrFail();
        $roleDistributor = Role::where('kode', 'distributor')->firstOrFail();
        $roleBumdes = Role::where('kode', 'bumdes')->firstOrFail();

        $this->banyumas = Kabupaten::where('kode', '3302')->firstOrFail();
        $this->cilacap = Kabupaten::where('kode', '3301')->firstOrFail();
        $this->beras = Komoditas::where('kode', 'beras')->firstOrFail();

        $this->admin = User::where('email', 'admin@sipangan.test')->firstOrFail();
        $this->distributor1 = User::where('email', 'distributor1@sipangan.test')->firstOrFail();
        $this->distributor2 = User::where('email', 'distributor2@sipangan.test')->firstOrFail();
        $this->bumdesBanyumas = User::where('email', 'bumdes.banyumas@sipangan.test')->firstOrFail();
        $this->bumdesCilacap = User::where('email', 'bumdes.cilacap@sipangan.test')->firstOrFail();
    }

    /**
     * UJI 1: Hak Akses & UI Tamu (Guest / Tanpa Login).
     * Guest hanya boleh melihat halaman publik (Peta, Detail Wilayah, API publik).
     */
    public function test_guest_access_and_navbar_visibility(): void
    {
        // 1. Halaman publik dapat diakses tanpa login
        $this->get('/')->assertRedirect(route('peta.index'));
        $this->get('/peta')->assertOk();
        $this->get("/wilayah/{$this->banyumas->kode}")->assertOk();
        $this->get('/api/status')->assertOk();
        $this->get('/api/periode')->assertOk();
        $this->getJson("/api/wilayah/{$this->banyumas->kode}/info?komoditas=beras")->assertStatus(404); // Respons JSON tanpa auth
        $this->get("/api/wilayah/{$this->banyumas->kode}/seri")->assertOk();
        $this->get("/api/wilayah/{$this->banyumas->kode}/cek")->assertOk();

        // 2. Navbar pada halaman publik TIDAK merender modul peran internal
        $respPeta = $this->get('/peta');
        $respPeta->assertSee('Peta Heatmap Nasional');
        $respPeta->assertSee('Detail Wilayah');
        $respPeta->assertDontSee('Portal Pengiriman');
        $respPeta->assertDontSee('Portal BUMDes');
        $respPeta->assertDontSee('Panel Admin');

        // Tombol aksi logistik pada peta tidak ditampilkan untuk guest
        $respPeta->assertDontSee('Disposisi Logistik');
        $respPeta->assertDontSee('Buat Rute Pengiriman Logistik');

        // Tombol aksi DO pada detail wilayah tidak menampilkan aksi langsung untuk guest
        $respDetail = $this->get("/wilayah/{$this->banyumas->kode}");
        $respDetail->assertDontSee('▶ Buat DO');
        $respDetail->assertDontSee('Panggil Operator Logistik');
        $respDetail->assertSee('Disposisi Mandat Distributor');

        // 3. Modul internal dialihkan ke login
        $this->get('/pengiriman')->assertRedirect(route('login'));
        $this->get('/pengiriman/buat')->assertRedirect(route('login'));
        $this->get('/panen')->assertRedirect(route('login'));
        $this->get('/jadwal-tanam')->assertRedirect(route('login'));
        $this->get('/admin/pengguna')->assertRedirect(route('login'));
        $this->get('/admin/dataset')->assertRedirect(route('login'));
        $this->get('/admin/forecast')->assertRedirect(route('login'));
        $this->get('/admin/pengaturan')->assertRedirect(route('login'));
    }

    /**
     * UJI 2: Hak Akses & UI Distributor.
     */
    public function test_distributor_access_and_navbar_visibility(): void
    {
        // 1. Navbar Distributor hanya menampilkan Pengiriman, bukan BUMDes atau Admin
        $resp = $this->actingAs($this->distributor1)->get('/pengiriman');
        $resp->assertOk();
        $resp->assertSee('Portal Pengiriman');
        $resp->assertDontSee('Portal BUMDes');
        $resp->assertDontSee('Panel Admin');
        $resp->assertSee('1. Buat Pengiriman Baru');

        // 2. Distributor dapat membuka form buat pengiriman
        $this->actingAs($this->distributor1)->get('/pengiriman/buat')->assertOk();

        // 3. Tombol aksi logistik pada peta dan detail wilayah terlihat untuk distributor
        $respPeta = $this->actingAs($this->distributor1)->get('/peta');
        $respPeta->assertSee('Disposisi Logistik');
        $respPeta->assertSee('Buat Rute Pengiriman Logistik');

        $respDetail = $this->actingAs($this->distributor1)->get("/wilayah/{$this->banyumas->kode}");
        $respDetail->assertSee('▶ Buat DO');
        $respDetail->assertSee('Panggil Operator Logistik');

        // 4. Distributor DITOLAK (403 Forbidden) saat mencoba mengakses modul BUMDes & Admin
        $this->actingAs($this->distributor1)->get('/panen')->assertForbidden();
        $this->actingAs($this->distributor1)->post('/panen', [
            'komoditas_id' => $this->beras->id,
            'periode' => '2026-01',
            'panen_kg' => 1000,
            'sisa_stok_kg' => 500,
        ])->assertForbidden();
        $this->actingAs($this->distributor1)->get('/panen/export-csv')->assertForbidden();
        $this->actingAs($this->distributor1)->get('/jadwal-tanam')->assertForbidden();

        $this->actingAs($this->distributor1)->get('/admin/pengguna')->assertForbidden();
        $this->actingAs($this->distributor1)->get('/admin/dataset')->assertForbidden();
        $this->actingAs($this->distributor1)->get('/admin/forecast')->assertForbidden();
        $this->actingAs($this->distributor1)->get('/admin/pengaturan')->assertForbidden();
    }

    /**
     * UJI 3: Hak Akses & UI BUMDes (Terkunci 1 Kabupaten).
     */
    public function test_bumdes_access_and_navbar_visibility(): void
    {
        // 1. Navbar BUMDes hanya menampilkan Portal BUMDes, bukan Pengiriman atau Admin
        $resp = $this->actingAs($this->bumdesBanyumas)->get('/panen');
        $resp->assertOk();
        $resp->assertSee('Portal BUMDes');
        $resp->assertDontSee('Portal Pengiriman');
        $resp->assertDontSee('Panel Admin');

        // BUMDes tidak melihat link mati pengiriman.create
        $resp->assertDontSee('Minta Disposisi Logistik');

        // Saat status wilayah PENUH, banner tampil dengan badge info non-actionable
        StatusWilayah::where('kabupaten_id', $this->banyumas->id)
            ->where('komoditas_id', $this->beras->id)
            ->whereDate('periode', '2026-01-01')
            ->update([
                'status' => StatusLevel::Penuh,
                'rasio' => 0.96,
            ]);
        $respPenuh = $this->actingAs($this->bumdesBanyumas)->get('/panen?periode=2026-01');
        $respPenuh->assertSee('Disposisi via Operator Terdaftar');
        $respPenuh->assertDontSee('Minta Disposisi Logistik');

        // 2. BUMDes dapat mengakses jadwal tanam
        $this->actingAs($this->bumdesBanyumas)->get('/jadwal-tanam')->assertOk();

        // 3. BUMDes DITOLAK (403 Forbidden) saat mencoba mengakses portal Distributor
        $this->actingAs($this->bumdesBanyumas)->get('/pengiriman')->assertForbidden();
        $this->actingAs($this->bumdesBanyumas)->get('/pengiriman/buat')->assertForbidden();
        $this->actingAs($this->bumdesBanyumas)->post('/pengiriman', [
            'no_resi' => 'RESI-ILEGAL-BUMDES',
            'komoditas_id' => $this->beras->id,
            'jumlah_kg' => 1000,
            'asal_kabupaten_id' => $this->banyumas->id,
            'tujuan_kabupaten_id' => $this->cilacap->id,
            'tanggal_kirim' => now()->toDateString(),
        ])->assertForbidden();

        // 4. BUMDes DITOLAK (403 Forbidden) saat mencoba mengakses panel Admin
        $this->actingAs($this->bumdesBanyumas)->get('/admin/pengguna')->assertForbidden();
        $this->actingAs($this->bumdesBanyumas)->get('/admin/dataset')->assertForbidden();
        $this->actingAs($this->bumdesBanyumas)->get('/admin/forecast')->assertForbidden();
        $this->actingAs($this->bumdesBanyumas)->get('/admin/pengaturan')->assertForbidden();
    }

    /**
     * UJI 4: Hak Akses & UI Admin (PRD 4.1 Matrix).
     * Admin melihat semua modul, tapi TIDAK membuat pengiriman (PRD: Buat pengiriman = Distributor).
     */
    public function test_admin_access_and_navbar_visibility(): void
    {
        // 1. Admin melihat seluruh menu navigasi di header
        $resp = $this->actingAs($this->admin)->get('/admin/pengguna');
        $resp->assertOk();
        $resp->assertSee('Portal Pengiriman');
        $resp->assertSee('Portal BUMDes');
        $resp->assertSee('Panel Admin');

        // 2. Admin dapat mengakses seluruh halaman admin
        $this->actingAs($this->admin)->get('/admin/pengguna')->assertOk();
        $this->actingAs($this->admin)->get('/admin/dataset')->assertOk();
        $this->actingAs($this->admin)->get('/admin/forecast')->assertOk();
        $this->actingAs($this->admin)->get('/admin/pengaturan')->assertOk();

        // 3. Admin dapat memantau dan menginput panen serta jadwal tanam
        $this->actingAs($this->admin)->get('/panen')->assertOk();
        $this->actingAs($this->admin)->get('/jadwal-tanam')->assertOk();

        // 4. Admin dapat melihat daftar pengiriman semua distributor
        $respShip = $this->actingAs($this->admin)->get('/pengiriman');
        $respShip->assertOk();
        // Admin TIDAK melihat tombol buat pengiriman baru
        $respShip->assertDontSee('1. Buat Pengiriman Baru');

        // 5. Admin DITOLAK (403) membuat pengiriman baru (PRD 4.1: Buat pengiriman = Distributor saja)
        $this->actingAs($this->admin)->get('/pengiriman/buat')->assertForbidden();
        $this->actingAs($this->admin)->post('/pengiriman', [
            'no_resi' => 'RESI-ADMIN-BUAT',
            'komoditas_id' => $this->beras->id,
            'jumlah_kg' => 1000,
            'asal_kabupaten_id' => $this->banyumas->id,
            'tujuan_kabupaten_id' => $this->cilacap->id,
            'tanggal_kirim' => now()->toDateString(),
        ])->assertForbidden();
    }

    /**
     * UJI 5: Isolasi Data Antar Distributor (Data Scoping).
     * Distributor 1 tidak boleh melihat, mencari, atau mengedit pengiriman Distributor 2.
     */
    public function test_distributor_data_scoping_isolation(): void
    {
        $shipmentDist2 = Shipment::create([
            'user_id' => $this->distributor2->id,
            'no_resi' => 'RESI-RAHASIA-DIST2',
            'komoditas_id' => $this->beras->id,
            'jumlah_kg' => 3000,
            'asal_kabupaten_id' => $this->cilacap->id,
            'tujuan_kabupaten_id' => $this->banyumas->id,
            'tanggal_kirim' => now()->toDateString(),
            'status' => ShipmentStatus::Dibuat,
        ]);

        // Distributor 1 dilarang melihat detail pengiriman Distributor 2 (403)
        $this->actingAs($this->distributor1)
            ->get("/pengiriman/{$shipmentDist2->id}")
            ->assertForbidden();

        // Distributor 1 dilarang memperbarui status/menambah event pengiriman Distributor 2 (403)
        $this->actingAs($this->distributor1)
            ->post("/pengiriman/{$shipmentDist2->id}/event", [
                'status' => ShipmentStatus::Diproses->value,
                'lokasi' => 'Titik Ilegal',
                'keterangan' => 'Manipulasi event',
            ])
            ->assertForbidden();

        // Distributor 1 mencari resi Distributor 2 tidak menemukan hasil
        $respSearch = $this->actingAs($this->distributor1)
            ->get('/pengiriman?q=RESI-RAHASIA-DIST2');
        $respSearch->assertOk();
        $this->assertCount(0, $respSearch->viewData('shipments'));
        $respSearch->assertSee('Belum ada data pengiriman terdaftar');

        // Admin dapat melihat dan mengoreksi status pengiriman Distributor 2
        $this->actingAs($this->admin)
            ->get("/pengiriman/{$shipmentDist2->id}")
            ->assertOk()
            ->assertSee('RESI-RAHASIA-DIST2');

        $this->actingAs($this->admin)
            ->post("/pengiriman/{$shipmentDist2->id}/event", [
                'status' => ShipmentStatus::Diproses->value,
                'lokasi' => 'Pusat Logistik Nasional',
                'keterangan' => 'Koreksi manifest oleh admin pusat',
            ])
            ->assertRedirect(route('pengiriman.show', $shipmentDist2->id));
    }

    /**
     * UJI 6: Pengikatan Data BUMDes ke 1 Kabupaten (Kabupaten Scoping).
     * BUMDes Banyumas tidak dapat memanipulasi atau mengekspor data kabupaten lain.
     */
    public function test_bumdes_kabupaten_scoping_strictness(): void
    {
        // 1. Percobaan input laporan dengan mengirimkan parameter kabupaten lain (Cilacap)
        $this->actingAs($this->bumdesBanyumas)
            ->post('/panen', [
                'kabupaten_id' => $this->cilacap->id, // Dicoba ubah ke Cilacap
                'komoditas_id' => $this->beras->id,
                'periode' => '2026-03',
                'panen_kg' => 999999,
                'sisa_stok_kg' => 888888,
            ])
            ->assertRedirect();

        // Server harus memaksakan kabupaten_id ke Banyumas (kabupaten akunnya)
        $this->assertDatabaseHas('harvest_reports', [
            'user_id' => $this->bumdesBanyumas->id,
            'kabupaten_id' => $this->banyumas->id,
            'panen_kg' => 999999,
        ]);
        $this->assertDatabaseMissing('harvest_reports', [
            'user_id' => $this->bumdesBanyumas->id,
            'kabupaten_id' => $this->cilacap->id,
        ]);

        // 2. Ekspor CSV oleh BUMDes Banyumas dengan query kabupaten_id Cilacap tetap menghasilkan file Banyumas
        $respExport = $this->actingAs($this->bumdesBanyumas)
            ->get("/panen/export-csv?kabupaten_id={$this->cilacap->id}");
        $respExport->assertOk();
        $this->assertStringContainsString('banyumas', strtolower($respExport->headers->get('content-disposition', '')));
        $this->assertStringNotContainsString('cilacap', strtolower($respExport->headers->get('content-disposition', '')));
    }
}
