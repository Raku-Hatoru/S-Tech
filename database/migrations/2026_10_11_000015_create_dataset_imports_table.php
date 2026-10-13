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
        Schema::create('dataset_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('tipe', ['historis', 'forecast']);
            $table->string('nama_file', 190);
            $table->unsignedInteger('jumlah_baris')->default(0);
            $table->unsignedInteger('jumlah_berhasil')->default(0);
            $table->unsignedInteger('jumlah_gagal')->default(0);
            $table->enum('status', ['berjalan', 'selesai', 'gagal']);
            $table->text('ringkasan_galat')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dataset_imports');
    }
};
