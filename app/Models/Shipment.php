<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use App\Enums\StatusLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    use HasFactory;

    protected $table = 'shipments';

    protected $fillable = [
        'user_id',
        'no_resi',
        'komoditas_id',
        'jumlah_kg',
        'asal_kabupaten_id',
        'tujuan_kabupaten_id',
        'tanggal_kirim',
        'estimasi_tiba',
        'status',
        'level_peringatan',
        'rasio_tujuan',
        'konfirmasi_peringatan',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_kg' => 'decimal:2',
            'tanggal_kirim' => 'date',
            'estimasi_tiba' => 'date',
            'status' => ShipmentStatus::class,
            'level_peringatan' => StatusLevel::class,
            'rasio_tujuan' => 'decimal:4',
            'konfirmasi_peringatan' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function komoditas(): BelongsTo
    {
        return $this->belongsTo(Komoditas::class);
    }

    public function asal(): BelongsTo
    {
        return $this->belongsTo(Kabupaten::class, 'asal_kabupaten_id');
    }

    public function tujuan(): BelongsTo
    {
        return $this->belongsTo(Kabupaten::class, 'tujuan_kabupaten_id');
    }

    /**
     * Riwayat tracking/perjalanan urut waktu kejadian terbaru di atas.
     */
    public function events(): HasMany
    {
        return $this->hasMany(ShipmentEvent::class, 'shipment_id')->orderBy('waktu_kejadian', 'desc');
    }
}
