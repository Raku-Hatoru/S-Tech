<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kabupaten extends Model
{
    use HasFactory;

    protected $table = 'kabupaten';

    protected $fillable = [
        'provinsi_id',
        'kode',
        'nama',
        'tipe',
        'jumlah_penduduk',
        'lat',
        'lng',
    ];

    /**
     * Cast attributes.
     */
    protected function casts(): array
    {
        return [
            'jumlah_penduduk' => 'integer',
            'lat' => 'decimal:6',
            'lng' => 'decimal:6',
        ];
    }

    /**
     * Relasi ke Provinsi induk.
     */
    public function provinsi(): BelongsTo
    {
        return $this->belongsTo(Provinsi::class, 'provinsi_id');
    }

    /**
     * Pengguna BUMDes yang terikat ke kabupaten ini.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'kabupaten_id');
    }

    /**
     * Data historis untuk kabupaten ini.
     */
    public function dataHistoris(): HasMany
    {
        return $this->hasMany(DataHistoris::class, 'kabupaten_id');
    }

    /**
     * Prediksi untuk kabupaten ini.
     */
    public function forecasts(): HasMany
    {
        return $this->hasMany(Forecast::class, 'kabupaten_id');
    }

    /**
     * Status wilayah siap pakai untuk peta.
     */
    public function statusWilayah(): HasMany
    {
        return $this->hasMany(StatusWilayah::class, 'kabupaten_id');
    }

    /**
     * Pengiriman yang berasal dari kabupaten ini.
     */
    public function shipmentsAsal(): HasMany
    {
        return $this->hasMany(Shipment::class, 'asal_kabupaten_id');
    }

    /**
     * Pengiriman yang ditujukan ke kabupaten ini.
     */
    public function shipmentsTujuan(): HasMany
    {
        return $this->hasMany(Shipment::class, 'tujuan_kabupaten_id');
    }

    /**
     * Laporan panen BUMDes.
     */
    public function harvestReports(): HasMany
    {
        return $this->hasMany(HarvestReport::class, 'kabupaten_id');
    }

    /**
     * Rekomendasi jadwal tanam.
     */
    public function plantingRecommendations(): HasMany
    {
        return $this->hasMany(PlantingRecommendation::class, 'kabupaten_id');
    }
}
