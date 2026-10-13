<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Komoditas extends Model
{
    use HasFactory;

    protected $table = 'komoditas';

    protected $fillable = [
        'kode',
        'nama',
    ];

    /**
     * Relasi ke data historis komoditas ini.
     */
    public function dataHistoris(): HasMany
    {
        return $this->hasMany(DataHistoris::class, 'komoditas_id');
    }

    /**
     * Relasi ke prediksi komoditas ini.
     */
    public function forecasts(): HasMany
    {
        return $this->hasMany(Forecast::class, 'komoditas_id');
    }

    /**
     * Relasi ke status wilayah.
     */
    public function statusWilayah(): HasMany
    {
        return $this->hasMany(StatusWilayah::class, 'komoditas_id');
    }

    /**
     * Relasi ke pengiriman.
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'komoditas_id');
    }

    /**
     * Relasi ke laporan panen.
     */
    public function harvestReports(): HasMany
    {
        return $this->hasMany(HarvestReport::class, 'komoditas_id');
    }

    /**
     * Relasi ke rekomendasi tanam.
     */
    public function plantingRecommendations(): HasMany
    {
        return $this->hasMany(PlantingRecommendation::class, 'komoditas_id');
    }
}
