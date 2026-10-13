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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('no_resi', 60)->unique();
            $table->foreignId('komoditas_id')->constrained('komoditas')->restrictOnDelete();
            $table->decimal('jumlah_kg', 16, 2);
            $table->foreignId('asal_kabupaten_id')->constrained('kabupaten')->restrictOnDelete();
            $table->foreignId('tujuan_kabupaten_id')->constrained('kabupaten')->restrictOnDelete();
            $table->date('tanggal_kirim');
            $table->date('estimasi_tiba')->nullable();
            $table->enum('status', [
                'dibuat',
                'diterima_di_asal',
                'diproses',
                'dalam_perjalanan',
                'tiba_di_tujuan',
                'diterima',
                'dibatalkan',
            ])->default('dibuat');
            $table->enum('level_peringatan', ['defisit', 'seimbang', 'waspada', 'penuh', 'tanpa_data'])->nullable();
            $table->decimal('rasio_tujuan', 8, 4)->nullable();
            $table->boolean('konfirmasi_peringatan')->default(false);
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['tujuan_kabupaten_id', 'komoditas_id', 'tanggal_kirim'], 'idx_ship_tujuan');
            $table->index(['asal_kabupaten_id', 'komoditas_id', 'tanggal_kirim'], 'idx_ship_asal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
