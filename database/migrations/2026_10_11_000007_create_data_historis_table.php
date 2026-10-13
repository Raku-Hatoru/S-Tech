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
        Schema::create('data_historis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kabupaten_id')->constrained('kabupaten')->restrictOnDelete();
            $table->foreignId('komoditas_id')->constrained('komoditas')->restrictOnDelete();
            $table->date('periode');
            $table->decimal('panen_kg', 16, 2)->nullable();
            $table->decimal('stok_awal_kg', 16, 2)->nullable();
            $table->decimal('pasokan_masuk_kg', 16, 2)->nullable();
            $table->decimal('supply_kg', 16, 2)->nullable();
            $table->decimal('demand_kg', 16, 2)->nullable();
            $table->decimal('curah_hujan_mm', 7, 2)->nullable();
            $table->unsignedBigInteger('jumlah_penduduk')->nullable();
            $table->decimal('luas_panen_ha', 12, 2)->nullable();
            $table->boolean('flag_estimasi')->default(false);
            $table->string('sumber_data', 150)->nullable();
            $table->timestamps();

            $table->unique(['kabupaten_id', 'komoditas_id', 'periode'], 'uq_historis');
            $table->index(['komoditas_id', 'periode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_historis');
    }
};
