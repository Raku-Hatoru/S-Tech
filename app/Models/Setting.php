<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $table = 'settings';

    protected $fillable = [
        'kunci',
        'nilai',
        'tipe',
        'keterangan',
    ];

    /**
     * Ambil nilai pengaturan berdasarkan kunci dengan fallback default dan caching.
     */
    public static function nilai(string $kunci, mixed $default = null): mixed
    {
        return Cache::remember("setting:{$kunci}", 3600, function () use ($kunci, $default) {
            $setting = static::where('kunci', $kunci)->first();
            if (! $setting) {
                return $default;
            }

            return match ($setting->tipe) {
                'integer' => (int) $setting->nilai,
                'decimal', 'float' => (float) $setting->nilai,
                'boolean' => filter_var($setting->nilai, FILTER_VALIDATE_BOOLEAN),
                default => $setting->nilai,
            };
        });
    }

    /**
     * Simpan atau perbarui nilai setting dan hapus cache terkait.
     */
    public static function simpan(string $kunci, mixed $nilai, string $tipe = 'string', ?string $keterangan = null): self
    {
        $setting = static::updateOrCreate(
            ['kunci' => $kunci],
            [
                'nilai' => (string) $nilai,
                'tipe' => $tipe,
                'keterangan' => $keterangan,
            ]
        );

        Cache::forget("setting:{$kunci}");

        return $setting;
    }
}
