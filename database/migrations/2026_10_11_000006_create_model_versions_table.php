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
        Schema::create('model_versions', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 60)->unique();
            $table->string('algoritma', 30)->default('xgboost');
            $table->date('tanggal_latih')->nullable();
            $table->date('data_awal')->nullable();
            $table->date('data_akhir')->nullable();
            $table->json('metrik')->nullable();
            $table->json('parameter')->nullable();
            $table->text('catatan')->nullable();
            $table->boolean('is_aktif')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_versions');
    }
};
