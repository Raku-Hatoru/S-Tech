<?php

use App\Http\Controllers\Admin\DatasetController as AdminDatasetController;
use App\Http\Controllers\Admin\ForecastController as AdminForecastController;
use App\Http\Controllers\Admin\StatusRecalcController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PetaController;
use App\Http\Controllers\WilayahController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute Publik
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect()->route('peta.index');
})->name('home');

// Peta Heatmap — Publik (tanpa login)
Route::get('/peta', [PetaController::class, 'index'])->name('peta.index');

// Detail Wilayah — Publik (tanpa login)
Route::get('/wilayah/{kode}', [WilayahController::class, 'show'])->name('wilayah.detail');

/*
|--------------------------------------------------------------------------
| Autentikasi
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\HarvestReportController;
use App\Http\Controllers\PlantingController;
use App\Http\Controllers\ShipmentController;

/*
|--------------------------------------------------------------------------
| Distributor & Tracking (PRD F-07, F-08, Increment 6)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:distributor,admin'])->group(function () {
    Route::get('/pengiriman', [ShipmentController::class, 'index'])->name('pengiriman.index');
    Route::get('/pengiriman/buat', [ShipmentController::class, 'create'])->name('pengiriman.create');
    Route::post('/pengiriman', [ShipmentController::class, 'store'])->name('pengiriman.store');
    Route::get('/pengiriman/{id}', [ShipmentController::class, 'show'])->name('pengiriman.show');
    Route::post('/pengiriman/{id}/event', [ShipmentController::class, 'tambahEvent'])->name('pengiriman.event');
});

/*
|--------------------------------------------------------------------------
| BUMDes (PRD F-09, F-10, Increment 7)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:bumdes,admin'])->group(function () {
    Route::get('/panen', [HarvestReportController::class, 'index'])->name('panen.index');
    Route::post('/panen', [HarvestReportController::class, 'store'])->name('panen.store');
    Route::get('/panen/export-csv', [HarvestReportController::class, 'exportCsv'])->name('panen.export-csv');

    Route::get('/jadwal-tanam', [PlantingController::class, 'index'])->name('jadwal-tanam.index');
});

/*
|--------------------------------------------------------------------------
| Admin (PRD F-11, Increment 7)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Kelola Pengguna
    Route::get('/pengguna', [AdminUserController::class, 'index'])->name('pengguna.index');
    Route::get('/pengguna/buat', [AdminUserController::class, 'create'])->name('pengguna.create');
    Route::post('/pengguna', [AdminUserController::class, 'store'])->name('pengguna.store');
    Route::get('/pengguna/{pengguna}/edit', [AdminUserController::class, 'edit'])->name('pengguna.edit');
    Route::put('/pengguna/{pengguna}', [AdminUserController::class, 'update'])->name('pengguna.update');
    Route::post('/pengguna/{pengguna}/toggle-aktif', [AdminUserController::class, 'toggleAktif'])->name('pengguna.toggle-aktif');

    // Dataset Historis
    Route::get('/dataset', [AdminDatasetController::class, 'index'])->name('dataset.index');
    Route::post('/dataset', [AdminDatasetController::class, 'store'])->name('dataset.store');

    // Prediksi Forecast & Versi Model
    Route::get('/forecast', [AdminForecastController::class, 'index'])->name('forecast.index');
    Route::post('/forecast', [AdminForecastController::class, 'store'])->name('forecast.store');
    Route::post('/forecast/model-version/{modelVersion}/toggle', [AdminForecastController::class, 'toggleModelVersion'])->name('forecast.toggle-model');

    // Pengaturan & Ambang Batas (Increment 7)
    Route::get('/pengaturan', [AdminSettingController::class, 'index'])->name('pengaturan.index');
    Route::put('/pengaturan', [AdminSettingController::class, 'update'])->name('pengaturan.update');

    // Hitung Ulang Status Wilayah
    Route::post('/status/recalc', [StatusRecalcController::class, 'store'])->name('status.recalc');
});
