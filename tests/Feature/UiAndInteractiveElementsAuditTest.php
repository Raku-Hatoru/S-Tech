<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiAndInteractiveElementsAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Uji pembersihan elemen footer palsu yang bergaya tautan (cursor-pointer) tanpa handler/rute PRD.
     */
    public function test_no_fake_clickable_spans_in_footer_layout_and_login(): void
    {
        $responsePeta = $this->get('/peta');
        $responsePeta->assertStatus(200);
        $responsePeta->assertDontSee('Protokol BUMDes');
        $responsePeta->assertDontSee('SOP Distribusi');
        $responsePeta->assertDontSee('Status Integrasi API');
        $responsePeta->assertSee('Platform Pemantauan', false);

        $responseLogin = $this->get('/login');
        $responseLogin->assertStatus(200);
        $responseLogin->assertDontSee('Protokol Keamanan Data');
        $responseLogin->assertDontSee('SOP Verifikasi BUMDes');
        $responseLogin->assertDontSee('TLS 1.3');
        $responseLogin->assertSee('Sesi Terenkripsi', false);
    }

    /**
     * Uji validitas in-page anchor pada halaman detail wilayah.
     * Anchor href="#koridor-redistribusi" harus memiliki elemen target dengan id yang sama.
     */
    public function test_in_page_anchor_targets_exist(): void
    {
        $response = $this->get('/wilayah/3302');
        $response->assertStatus(200);

        $html = $response->getContent();
        $this->assertStringContainsString('href="#koridor-redistribusi"', $html);
        $this->assertStringContainsString('id="koridor-redistribusi"', $html);
    }

    /**
     * Uji bahwa seluruh halaman utama merender HTML yang valid tanpa tag rusak/stray.
     */
    public function test_all_rendered_pages_have_well_formed_html(): void
    {
        $distributor = User::whereHas('role', fn ($q) => $q->where('kode', 'distributor'))->first();
        $bumdes = User::whereHas('role', fn ($q) => $q->where('kode', 'bumdes'))->first();
        $admin = User::whereHas('role', fn ($q) => $q->where('kode', 'admin'))->first();

        $routes = [
            ['url' => '/peta', 'user' => null],
            ['url' => '/wilayah/3302', 'user' => null],
            ['url' => '/login', 'user' => null],
            ['url' => '/pengiriman', 'user' => $distributor],
            ['url' => '/pengiriman/buat', 'user' => $distributor],
            ['url' => '/panen', 'user' => $bumdes],
            ['url' => '/jadwal-tanam', 'user' => $bumdes],
            ['url' => '/admin/pengguna', 'user' => $admin],
            ['url' => '/admin/dataset', 'user' => $admin],
            ['url' => '/admin/forecast', 'user' => $admin],
            ['url' => '/admin/pengaturan', 'user' => $admin],
        ];

        foreach ($routes as $item) {
            $response = $item['user'] ? $this->actingAs($item['user'])->get($item['url']) : $this->get($item['url']);
            $this->assertEquals(200, $response->getStatusCode(), "Gagal memuat URL: {$item['url']}");

            $content = $response->getContent();

            // Cek tidak ada tag penutup stray (misal: "</div> >")
            $this->assertDoesNotMatchRegularExpression('/(<\/[a-z0-9]+>)\s*>/i', $content, "Ditemukan stray '>' di URL: {$item['url']}");

            // Cek tidak ada tautan mati href="#" atau href=""
            $this->assertDoesNotMatchRegularExpression('/<a\b[^>]*\bhref\s*=\s*["\'](#[^a-zA-Z0-9_-]|javascript:void\(0\);?|javascript:;|)["\']/i', $content, "Ditemukan dead href di URL: {$item['url']}");

            // Validasi DOM parsing
            libxml_use_internal_errors(true);
            libxml_clear_errors();
            $dom = new DOMDocument;
            $dom->loadHTML('<?xml encoding="utf-8" ?>'.$content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $errors = libxml_get_errors();
            libxml_clear_errors();

            $critical = [];
            foreach ($errors as $err) {
                if (str_contains($err->message, 'Tag svg invalid') ||
                    str_contains($err->message, 'Tag path invalid') ||
                    str_contains($err->message, 'Tag header invalid') ||
                    str_contains($err->message, 'Tag main invalid') ||
                    str_contains($err->message, 'Tag nav invalid') ||
                    str_contains($err->message, 'Tag footer invalid') ||
                    str_contains($err->message, 'Tag section invalid') ||
                    str_contains($err->message, 'Tag article invalid') ||
                    str_contains($err->message, 'htmlParseEntityRef: no name') ||
                    str_contains($err->message, 'Unexpected end tag : p')
                ) {
                    continue;
                }
                $critical[] = trim($err->message);
            }

            $this->assertEmpty($critical, "Ditemukan galat struktur HTML pada URL {$item['url']}: ".implode(', ', $critical));
        }
    }

    /**
     * Uji bahwa semua formulir POST/PUT memiliki token CSRF aktif.
     */
    public function test_all_forms_contain_csrf_tokens(): void
    {
        $distributor = User::whereHas('role', fn ($q) => $q->where('kode', 'distributor'))->first();
        $bumdes = User::whereHas('role', fn ($q) => $q->where('kode', 'bumdes'))->first();
        $admin = User::whereHas('role', fn ($q) => $q->where('kode', 'admin'))->first();

        $routes = [
            ['url' => '/login', 'user' => null],
            ['url' => '/pengiriman/buat', 'user' => $distributor],
            ['url' => '/panen', 'user' => $bumdes],
            ['url' => '/admin/pengguna/buat', 'user' => $admin],
            ['url' => '/admin/dataset', 'user' => $admin],
            ['url' => '/admin/forecast', 'user' => $admin],
            ['url' => '/admin/pengaturan', 'user' => $admin],
        ];

        foreach ($routes as $item) {
            $response = $item['user'] ? $this->actingAs($item['user'])->get($item['url']) : $this->get($item['url']);
            $html = $response->getContent();

            $dom = new DOMDocument;
            @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
            $xpath = new DOMXPath($dom);

            $forms = $xpath->query('//form');
            foreach ($forms as $form) {
                $method = strtoupper($form->getAttribute('method') ?: 'GET');
                if ($method === 'POST') {
                    $tokens = $xpath->query('.//input[@name="_token"]', $form);
                    $this->assertGreaterThan(0, $tokens->length, "Form tanpa token CSRF pada URL {$item['url']}");
                }
            }
        }
    }
}
