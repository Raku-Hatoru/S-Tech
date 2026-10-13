<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataHistoris extends Model
{
    use HasFactory;

    protected $table = 'data_historis';

    protected $fillable = [
        'kabupaten_id',
        'komoditas_id',
        'periode',
        'panen_kg',
        'stok_awal_kg',
        'pasokan_masuk_kg',
        'supply_kg',
        'demand_kg',
        'curah_hujan_mm',
        'jumlah_penduduk',
        'luas_panen_ha',
        'flag_estimasi',
        'sumber_data',
    ];

    protected function casts(): array
    {
        return [
            'periode' => 'date',
            'panen_kg' => 'decimal:2',
            'stok_awal_kg' => 'decimal:2',
            'pasokan_masuk_kg' => 'decimal:2',
            'supply_kg' => 'decimal:2',
            'demand_kg' => 'decimal:2',
            'curah_hujan_mm' => 'decimal:2',
            'jumlah_penduduk' => 'integer',
            'luas_panen_ha' => 'decimal:2',
            'flag_estimasi' => 'boolean',
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
