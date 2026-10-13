<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModelVersion extends Model
{
    use HasFactory;

    protected $table = 'model_versions';

    protected $fillable = [
        'nama',
        'algoritma',
        'tanggal_latih',
        'data_awal',
        'data_akhir',
        'metrik',
        'parameter',
        'catatan',
        'is_aktif',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_latih' => 'date',
            'data_awal' => 'date',
            'data_akhir' => 'date',
            'metrik' => 'array',
            'parameter' => 'array',
            'is_aktif' => 'boolean',
        ];
    }

    /**
     * Relasi ke hasil prediksi yang dihasilkan model ini.
     */
    public function forecasts(): HasMany
    {
        return $this->hasMany(Forecast::class, 'model_version_id');
    }
}
