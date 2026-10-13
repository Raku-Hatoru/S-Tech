<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Halaman root redirect ke /peta (peta heatmap publik).
     */
    public function test_root_redirects_to_peta(): void
    {
        $response = $this->get('/');
        $response->assertRedirect(route('peta.index'));
    }

    /**
     * Halaman peta heatmap dapat diakses publik tanpa login.
     */
    public function test_peta_heatmap_dapat_diakses_publik(): void
    {
        $response = $this->get('/peta');
        $response->assertStatus(200);
        $response->assertSee('Peta Heatmap');
    }
}
