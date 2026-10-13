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
        Schema::create('status_wilayah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kabupaten_id')->constrained('kabupaten')->restrictOnDelete();
            $table->foreignId('komoditas_id')->constrained('komoditas')->restrictOnDelete();
            $table->date('periode');
            $table->decimal('supply_kg', 16, 2)->nullable();
            $table->decimal('demand_kg', 16, 2)->nullable();
            $table->decimal('rasio', 8, 4)->nullable();
            $table->enum('status', ['defisit', 'seimbang', 'waspada', 'penuh', 'tanpa_data']);
            $table->enum('sumber', ['aktual', 'prediksi']);
            $table->boolean('stok_estimasi')->default(false);
            $table->decimal('panen_koreksi_kg', 16, 2)->nullable();
            $table->decimal('pengiriman_masuk_kg', 16, 2)->default(0);
            $table->decimal('pengiriman_keluar_kg', 16, 2)->default(0);
            $table->dateTime('dihitung_pada');
            $table->timestamps();

            $table->unique(['kabupaten_id', 'komoditas_id', 'periode'], 'uq_status');
            $table->index(['komoditas_id', 'periode', 'status'], 'idx_status_peta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_wilayah');
    }
};
