<?php

namespace Tests\Feature;

use App\Models\Kabupaten;
use App\Models\Komoditas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class BrandNameTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Category E allow-list: identifier yang diizinkan memuat kata 'sipangan'
     * (misalnya domain email akun demo @sipangan.test).
     */
    protected array $categoryEAllowList = [
        '@sipangan.test',
        'sipangan.test',
    ];

    protected User $admin;

    protected User $distributor;

    protected User $bumdes;

    protected Kabupaten $banyumas;

    protected Komoditas $beras;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->banyumas = Kabupaten::where('kode', '3302')->firstOrFail();
        $this->beras = Komoditas::where('kode', 'beras')->firstOrFail();

        $this->admin = User::where('email', 'admin@sipangan.test')->firstOrFail();
        $this->distributor = User::where('email', 'distributor1@sipangan.test')->firstOrFail();
        $this->bumdes = User::where('email', 'bumdes.banyumas@sipangan.test')->firstOrFail();
    }

    /**
     * Helper asersi kepatuhan penamaan merek:
     * (a) Frasa merek lama "sipangan waspada" (case-insensitive) TIDAK boleh muncul di HTML.
     * (b) Nama merek baru "PRANALA PANGAN" (case-insensitive) WAJIB muncul pada header atau title halaman.
     * (c) Jika kata 'sipangan' muncul, harus berasal dari identifier yang ada pada Category E allow-list.
     */
    protected function assertBrandCompliance(TestResponse $response, string $pageName): void
    {
        $response->assertOk();
        $html = $response->getContent();

        // (a) Old brand phrase must not appear
        $this->assertStringNotContainsStringIgnoringCase(
            'sipangan waspada',
            $html,
            "Halaman {$pageName} masih memuat frasa merek lama 'sipangan waspada'!"
        );

        // (b) New brand name must appear in header/title
        $this->assertTrue(
            (bool) preg_match('/PRANALA.*PANGAN/i', $html),
            "Halaman {$pageName} tidak memuat nama merek baru 'PRANALA PANGAN'!"
        );

        // (c) Check any remaining 'sipangan' hits belong strictly to the Category E allow-list
        $cleanedHtml = $html;
        foreach ($this->categoryEAllowList as $allowed) {
            $cleanedHtml = str_ireplace($allowed, '', $cleanedHtml);
        }

        $this->assertStringNotContainsStringIgnoringCase(
            'sipangan',
            $cleanedHtml,
            "Halaman {$pageName} memuat kata 'sipangan' di luar Category E allow-list!"
        );
    }

    /**
     * Uji Halaman Tamu / Publik (Guest).
     */
    public function test_guest_public_pages_brand_compliance(): void
    {
        // 1. Root / redirect ke peta
        $respRoot = $this->get('/');
        $respRoot->assertRedirect('/peta');

        // 2. /peta
        $respPeta = $this->get('/peta');
        $this->assertBrandCompliance($respPeta, '/peta (Guest)');

        // 3. /wilayah/3302
        $respWilayah = $this->get('/wilayah/3302');
        $this->assertBrandCompliance($respWilayah, '/wilayah/3302 (Guest)');

        // 4. /login
        $respLogin = $this->get('/login');
        $this->assertBrandCompliance($respLogin, '/login');
    }

    /**
     * Uji Halaman Distributor.
     */
    public function test_distributor_pages_brand_compliance(): void
    {
        $this->actingAs($this->distributor);

        // 1. /peta
        $this->assertBrandCompliance($this->get('/peta'), '/peta (Distributor)');

        // 2. /wilayah/3302
        $this->assertBrandCompliance($this->get('/wilayah/3302'), '/wilayah/3302 (Distributor)');

        // 3. /pengiriman (Index tracking)
        $this->assertBrandCompliance($this->get('/pengiriman'), '/pengiriman');

        // 4. /pengiriman/buat (Form buat DO)
        $this->assertBrandCompliance($this->get('/pengiriman/buat'), '/pengiriman/buat');

        // 5. /pengiriman/1 (Detail shipment)
        $this->assertBrandCompliance($this->get('/pengiriman/1'), '/pengiriman/1');
    }

    /**
     * Uji Halaman BUMDes.
     */
    public function test_bumdes_pages_brand_compliance(): void
    {
        $this->actingAs($this->bumdes);

        // 1. /peta
        $this->assertBrandCompliance($this->get('/peta'), '/peta (BUMDes)');

        // 2. /wilayah/3302
        $this->assertBrandCompliance($this->get('/wilayah/3302'), '/wilayah/3302 (BUMDes)');

        // 3. /panen (Portal pelaporan hasil panen)
        $this->assertBrandCompliance($this->get('/panen'), '/panen');

        // 4. /jadwal-tanam (Rekomendasi jadwal tanam & simulasi)
        $this->assertBrandCompliance($this->get('/jadwal-tanam'), '/jadwal-tanam');
    }

    /**
     * Uji Halaman Admin.
     */
    public function test_admin_pages_brand_compliance(): void
    {
        $this->actingAs($this->admin);

        // 1. /peta & /wilayah/3302
        $this->assertBrandCompliance($this->get('/peta'), '/peta (Admin)');
        $this->assertBrandCompliance($this->get('/wilayah/3302'), '/wilayah/3302 (Admin)');

        // 2. Panel Admin: Pengguna
        $this->assertBrandCompliance($this->get('/admin/pengguna'), '/admin/pengguna');
        $this->assertBrandCompliance($this->get('/admin/pengguna/buat'), '/admin/pengguna/buat');
        $this->assertBrandCompliance($this->get('/admin/pengguna/'.$this->admin->id.'/edit'), '/admin/pengguna/{id}/edit');

        // 3. Panel Admin: Dataset, Forecast, Pengaturan
        $this->assertBrandCompliance($this->get('/admin/dataset'), '/admin/dataset');
        $this->assertBrandCompliance($this->get('/admin/forecast'), '/admin/forecast');
        $this->assertBrandCompliance($this->get('/admin/pengaturan'), '/admin/pengaturan');

        // 4. Akses modul operasional sebagai Admin
        $this->assertBrandCompliance($this->get('/pengiriman'), '/pengiriman (Admin)');
        $this->assertBrandCompliance($this->get('/panen'), '/panen (Admin)');
        $this->assertBrandCompliance($this->get('/jadwal-tanam'), '/jadwal-tanam (Admin)');
    }

    /**
     * Uji bahwa kata status 'Waspada' (Category G) tetap dipertahankan
     * pada peta dan legenda tanpa terpengaruh pergantian nama merek.
     */
    public function test_status_word_waspada_is_retained_on_map_and_legend(): void
    {
        $response = $this->get('/peta');
        $response->assertOk();

        // Memastikan label status level 'Waspada' tetap ada di legend & modal
        $response->assertSee('Waspada');
        $response->assertSee('▲ Waspada');
    }
}
