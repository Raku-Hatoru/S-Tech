<?php

use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\WilayahController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (SIPANGAN WASPADA)
|--------------------------------------------------------------------------
|
| Endpoint publik untuk peta heatmap dan panel grafik detail wilayah.
| Terbuka tanpa autentikasi, dengan rate limiting wajar.
|
*/

Route::middleware('throttle:60,1')->group(function () {
    // 1. Status Wilayah (Peta Heatmap & Filter)
    Route::get('/status', [StatusController::class, 'index'])->name('api.status');

    // 2. Deret Waktu Seri Historis vs Prediksi
    Route::get('/wilayah/{kode}/seri', [WilayahController::class, 'seri'])->name('api.wilayah.seri');

    // 3. Daftar periode yang tersedia (untuk selector peta)
    Route::get('/periode', [WilayahController::class, 'periodeList'])->name('api.periode');

    // 4. Info singkat wilayah (untuk tooltip hover)
    Route::get('/wilayah/{kode}/info', [WilayahController::class, 'info'])->name('api.wilayah.info');

    // 5. Cek status tujuan pengiriman (Banner Waspada/Penuh)
    Route::get('/wilayah/{kode}/cek', [WilayahController::class, 'cek'])->name('api.wilayah.cek');
});
