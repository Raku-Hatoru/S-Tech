<?php

namespace Tests\Feature;

use App\Enums\ShipmentStatus;
use App\Enums\StatusLevel;
use App\Models\Kabupaten;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_master_roles_are_seeded(): void
    {
        $this->assertDatabaseHas('roles', ['kode' => 'distributor']);
        $this->assertDatabaseHas('roles', ['kode' => 'bumdes']);
        $this->assertDatabaseHas('roles', ['kode' => 'admin']);
    }

    public function test_master_komoditas_are_seeded(): void
    {
        $this->assertDatabaseHas('komoditas', ['kode' => 'beras']);
        $this->assertDatabaseHas('komoditas', ['kode' => 'jagung']);
        $this->assertDatabaseHas('komoditas', ['kode' => 'singkong']);
    }

    public function test_master_wilayah_and_relations_work(): void
    {
        $banyumas = Kabupaten::where('kode', '3302')->first();
        $this->assertNotNull($banyumas);
        $this->assertEquals('Banyumas', $banyumas->nama);
        $this->assertEquals('33', $banyumas->provinsi->kode);
        $this->assertEquals('Jawa Tengah', $banyumas->provinsi->nama);
    }

    public function test_master_settings_and_helpers_work(): void
    {
        $this->assertEquals('0.7000', Setting::nilai('ambang_defisit_max'));
        $this->assertEquals('0.9000', Setting::nilai('ambang_waspada_min'));
        $this->assertEquals('1.0000', Setting::nilai('ambang_penuh_min'));
        $this->assertEquals(2000, Setting::nilai('unit_switch_kg'));
    }

    public function test_enums_work_as_expected(): void
    {
        $this->assertEquals('Penuh / Surplus', StatusLevel::Penuh->label());
        $this->assertEquals('#b91c1c', StatusLevel::Penuh->warnaHex());
        $this->assertEquals('Pengiriman Dibuat', ShipmentStatus::Dibuat->label());
        $this->assertTrue(ShipmentStatus::Diterima->isFinal());
        $this->assertFalse(ShipmentStatus::DalamPerjalanan->isFinal());
    }
}
