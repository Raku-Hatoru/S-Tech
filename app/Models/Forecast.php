<?php

namespace App\Models;

use App\Enums\StatusLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Forecast extends Model
{
    use HasFactory;

    protected $table = 'forecasts';

    protected $fillable = [
        'kabupaten_id',
        'komoditas_id',
        'model_version_id',
        'periode_dasar',
        'periode_target',
        'horizon_bulan',
        'panen_pred_kg',
        'pasokan_masuk_pred_kg',
        'stok_awal_pred_kg',
        'supply_pred_kg',
        'demand_pred_kg',
        'rasio_pred',
        'status_pred',
        'rasio_bawah',
        'rasio_atas',
        'dibuat_pada',
    ];

    protected function casts(): array
    {
        return [
            'periode_dasar' => 'date',
            'periode_target' => 'date',
            'horizon_bulan' => 'integer',
            'panen_pred_kg' => 'decimal:2',
            'pasokan_masuk_pred_kg' => 'decimal:2',
            'stok_awal_pred_kg' => 'decimal:2',
            'supply_pred_kg' => 'decimal:2',
            'demand_pred_kg' => 'decimal:2',
            'rasio_pred' => 'decimal:4',
            'status_pred' => StatusLevel::class,
            'rasio_bawah' => 'decimal:4',
            'rasio_atas' => 'decimal:4',
            'dibuat_pada' => 'datetime',
        ];
    }

    public function kabupaten(): BelongsTo
    {
        return $this->belongsTo(Kabupaten::class, 'kabupaten_id');
    }

    public function komoditas(): BelongsTo
    {
        return $this->belongsTo(Komoditas::class, 'komoditas_id');
    }

    public function modelVersion(): BelongsTo
    {
        return $this->belongsTo(ModelVersion::class, 'model_version_id');
    }
}
