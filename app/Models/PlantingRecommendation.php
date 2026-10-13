<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlantingRecommendation extends Model
{
    use HasFactory;

    protected $table = 'planting_recommendations';

    protected $fillable = [
        'kabupaten_id',
        'komoditas_id',
        'bulan_tanam',
        'bulan_panen',
        'catatan',
        'is_dummy',
    ];

    protected function casts(): array
    {
        return [
            'bulan_tanam' => 'integer',
            'bulan_panen' => 'integer',
            'is_dummy' => 'boolean',
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
