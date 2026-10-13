<?php

namespace Tests\Feature;

use App\Http\Controllers\PetaController;
use App\Http\Controllers\WilayahController;
use App\Models\AuditLog;
use App\Models\DataHistoris;
use App\Models\DatasetImport;
use App\Models\Forecast;
use App\Models\HarvestReport;
use App\Models\Kabupaten;
use App\Models\Komoditas;
use App\Models\ModelVersion;
use App\Models\PlantingRecommendation;
use App\Models\Provinsi;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Models\StatusWilayah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class UnusedCodeAndBackendAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Uji penghapusan metode duplikat mati PetaController@detail (A-01).
     * PetaController hanya boleh memiliki metode index; rute /wilayah/{kode} ditangani oleh WilayahController@show.
     */
    public function test_peta_controller_only_handles_index_and_has_no_dead_detail_method(): void
    {
        $ref = new ReflectionClass(PetaController::class);
        $this->assertTrue($ref->hasMethod('index'), 'PetaController harus memiliki metode index');
        $this->assertFalse($ref->hasMethod('detail'), 'Metode mati PetaController@detail harus sudah dihapus (A-01)');

        $route = Route::getRoutes()->getByName('wilayah.detail');
        $this->assertNotNull($route);
        $this->assertEquals(WilayahController::class.'@show', $route->getActionName());
    }

    /**
     * Uji bahwa seluruh rute aplikasi yang terdaftar memiliki controller dan aksi yang valid.
     */
    public function test_all_registered_routes_have_valid_callable_actions(): void
    {
        $routes = Route::getRoutes();

        foreach ($routes as $route) {
            $action = $route->getActionName();

            // Abaikan closure routes atau vendor routes
            if ($action === 'Closure' || str_contains($action, 'vendor\\') || str_contains($action, 'vendor/')) {
                continue;
            }

            if (str_contains($action, '@')) {
                [$controller, $method] = explode('@', $action);
                $this->assertTrue(class_exists($controller), "Kelas controller {$controller} untuk rute {$route->uri()} harus ada");
                $this->assertTrue(method_exists($controller, $method), "Metode {$method} pada {$controller} untuk rute {$route->uri()} harus ada");
            }
        }
    }

    /**
     * Uji bahwa tidak ada metode publik controller yang menjadi 'orphan' (tidak terikat ke rute mana pun).
     */
    public function test_no_orphan_controller_public_actions_exist(): void
    {
        $files = array_merge(
            glob(app_path('Http/Controllers/*.php')),
            glob(app_path('Http/Controllers/**/*.php'))
        );

        $routedActions = [];
        foreach (Route::getRoutes() as $route) {
            $routedActions[] = $route->getActionName();
        }

        $orphans = [];
        foreach ($files as $file) {
            $class = 'App\\'.str_replace([app_path().'/', '/', '.php'], ['', '\\', ''], $file);
            if (! class_exists($class)) {
                continue;
            }

            $ref = new ReflectionClass($class);
            if ($ref->isAbstract()) {
                continue;
            }

            foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $class) {
                    continue;
                }
                if (str_starts_with($method->getName(), '__')) {
                    continue;
                }

                $action = $class.'@'.$method->getName();
                if (! in_array($action, $routedActions)) {
                    $orphans[] = $action;
                }
            }
        }

        $this->assertEmpty($orphans, 'Tidak boleh ada metode publik controller yang orphan: '.implode(', ', $orphans));
    }

    /**
     * Uji keberadaan dan kesiapan seluruh 16 model entitas PRD Bagian 9.3.
     */
    public function test_all_sixteen_prd_entities_exist_and_query_database(): void
    {
        $prdModels = [
            User::class,
            Role::class,
            Provinsi::class,
            Kabupaten::class,
            Komoditas::class,
            ModelVersion::class,
            DataHistoris::class,
            Forecast::class,
            StatusWilayah::class,
            Shipment::class,
            ShipmentEvent::class,
            HarvestReport::class,
            PlantingRecommendation::class,
            Setting::class,
            DatasetImport::class,
            AuditLog::class,
        ];

        foreach ($prdModels as $modelClass) {
            $this->assertTrue(class_exists($modelClass), "Model {$modelClass} harus ada sesuai PRD 9.3");
            $count = $modelClass::count();
            $this->assertIsInt($count, "Model {$modelClass} harus dapat mengakses tabel database");
        }
    }
}
