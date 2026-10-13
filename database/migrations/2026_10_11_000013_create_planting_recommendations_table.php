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
        Schema::create('planting_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kabupaten_id')->constrained('kabupaten')->restrictOnDelete();
            $table->foreignId('komoditas_id')->constrained('komoditas')->restrictOnDelete();
            $table->unsignedTinyInteger('bulan_tanam');
            $table->unsignedTinyInteger('bulan_panen');
            $table->string('catatan', 255)->nullable();
            $table->boolean('is_dummy')->default(true);
            $table->timestamps();

            $table->index(['kabupaten_id', 'komoditas_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planting_recommendations');
    }
};
