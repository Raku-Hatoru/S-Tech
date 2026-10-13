<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatasetImport extends Model
{
    use HasFactory;

    protected $table = 'dataset_imports';

    protected $fillable = [
        'user_id',
        'tipe',
        'nama_file',
        'jumlah_baris',
        'jumlah_berhasil',
        'jumlah_gagal',
        'status',
        'ringkasan_galat',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_baris' => 'integer',
            'jumlah_berhasil' => 'integer',
            'jumlah_gagal' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
