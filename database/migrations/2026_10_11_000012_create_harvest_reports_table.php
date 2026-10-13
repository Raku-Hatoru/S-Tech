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
        Schema::create('harvest_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('kabupaten_id')->constrained('kabupaten')->restrictOnDelete();
            $table->foreignId('komoditas_id')->constrained('komoditas')->restrictOnDelete();
            $table->date('periode');
            $table->decimal('panen_kg', 16, 2);
            $table->decimal('sisa_stok_kg', 16, 2);
            $table->date('tanggal_survei')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['kabupaten_id', 'komoditas_id', 'periode'], 'uq_laporan_bulanan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('harvest_reports');
    }
};
