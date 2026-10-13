<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuditService
{
    /**
     * Catat rekaman log aktivitas audit ke tabel audit_logs.
     *
     * @param  string  $aksi  Nama tindakan/aksi (misal: login_success, create_user)
     * @param  string  $tabel  Nama tabel target (misal: users, harvest_reports)
     * @param  int|null  $recordId  ID record yang dipengaruhi
     * @param  array<string, mixed>|null  $dataLama  Data sebelum perubahan
     * @param  array<string, mixed>|null  $dataBaru  Data setelah perubahan atau konteks metadata
     * @param  int|null  $userId  ID pengguna yang melakukan aksi (opsional, fallback ke Auth::id())
     */
    public static function log(
        string $aksi,
        string $tabel,
        ?int $recordId = null,
        ?array $dataLama = null,
        ?array $dataBaru = null,
        ?int $userId = null
    ): ?AuditLog {
        try {
            return AuditLog::create([
                'user_id' => $userId ?? Auth::id(),
                'aksi' => $aksi,
                'tabel' => $tabel,
                'record_id' => $recordId,
                'data_lama' => $dataLama,
                'data_baru' => $dataBaru,
                'ip' => request()->ip(),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Gagal mencatat audit log: '.$e->getMessage(), [
                'aksi' => $aksi,
                'tabel' => $tabel,
            ]);

            return null;
        }
    }
}
