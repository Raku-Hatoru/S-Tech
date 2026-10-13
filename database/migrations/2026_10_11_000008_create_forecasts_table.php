<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kabupaten_id')->constrained('kabupaten')->restrictOnDelete();
            $table->foreignId('komoditas_id')->constrained('komoditas')->restrictOnDelete();
            $table->foreignId('model_version_id')->nullable()->constrained('model_versions')->nullOnDelete();
            $table->date('periode_dasar');
            $table->date('periode_target');
            $table->unsignedTinyInteger('horizon_bulan');
            $table->decimal('panen_pred_kg', 16, 2)->nullable();
            $table->decimal('pasokan_masuk_pred_kg', 16, 2)->nullable();
            $table->decimal('stok_awal_pred_kg', 16, 2)->nullable();
            $table->decimal('supply_pred_kg', 16, 2)->nullable();
            $table->decimal('demand_pred_kg', 16, 2)->nullable();
            $table->decimal('rasio_pred', 8, 4)->nullable();
            $table->enum('status_pred', ['defisit', 'seimbang', 'waspada', 'penuh'])->nullable();
            $table->decimal('rasio_bawah', 8, 4)->nullable();
            $table->decimal('rasio_atas', 8, 4)->nullable();
            $table->dateTime('dibuat_pada')->nullable();
            $table->timestamps();

            $table->unique(['kabupaten_id', 'komoditas_id', 'periode_dasar', 'periode_target'], 'uq_forecast');
            $table->index(['periode_dasar', 'komoditas_id', 'periode_target'], 'idx_forecast_baca');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forecasts');
    }
};
