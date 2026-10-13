<?php

namespace App\Models;

use App\Enums\StatusLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusWilayah extends Model
{
    use HasFactory;

    protected $table = 'status_wilayah';

    protected $fillable = [
        'kabupaten_id',
        'komoditas_id',
        'periode',
        'supply_kg',
        'demand_kg',
        'rasio',
        'status',
        'sumber',
        'stok_estimasi',
        'panen_koreksi_kg',
        'pengiriman_masuk_kg',
        'pengiriman_keluar_kg',
        'dihitung_pada',
    ];

    protected function casts(): array
    {
        return [
            'periode' => 'date',
            'supply_kg' => 'decimal:2',
            'demand_kg' => 'decimal:2',
            'rasio' => 'decimal:4',
            'status' => StatusLevel::class,
            'stok_estimasi' => 'boolean',
            'panen_koreksi_kg' => 'decimal:2',
            'pengiriman_masuk_kg' => 'decimal:2',
            'pengiriman_keluar_kg' => 'decimal:2',
            'dihitung_pada' => 'datetime',
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
}
