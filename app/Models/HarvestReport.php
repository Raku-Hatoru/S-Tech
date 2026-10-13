<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HarvestReport extends Model
{
    use HasFactory;

    protected $table = 'harvest_reports';

    protected $fillable = [
        'user_id',
        'kabupaten_id',
        'komoditas_id',
        'periode',
        'panen_kg',
        'sisa_stok_kg',
        'tanggal_survei',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'periode' => 'date',
            'panen_kg' => 'decimal:2',
            'sisa_stok_kg' => 'decimal:2',
            'tanggal_survei' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
