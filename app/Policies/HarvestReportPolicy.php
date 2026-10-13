<?php

namespace App\Policies;

use App\Models\HarvestReport;
use App\Models\User;

class HarvestReportPolicy
{
    /**
     * Tentukan apakah pengguna dapat melihat daftar laporan panen.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role?->kode, ['admin', 'bumdes'], true);
    }

    /**
     * Tentukan apakah pengguna dapat melihat detail laporan panen tertentu.
     */
    public function view(User $user, HarvestReport $report): bool
    {
        if ($user->role?->kode === 'admin') {
            return true;
        }

        return $user->role?->kode === 'bumdes' && (int) $user->kabupaten_id === (int) $report->kabupaten_id;
    }

    /**
     * Tentukan apakah pengguna dapat membuat laporan panen.
     */
    public function create(User $user): bool
    {
        return in_array($user->role?->kode, ['admin', 'bumdes'], true);
    }

    /**
     * Tentukan apakah pengguna dapat memperbarui laporan panen.
     */
    public function update(User $user, HarvestReport $report): bool
    {
        if ($user->role?->kode === 'admin') {
            return true;
        }

        return $user->role?->kode === 'bumdes' && (int) $user->kabupaten_id === (int) $report->kabupaten_id;
    }

    /**
     * Tentukan apakah pengguna dapat menghapus laporan panen.
     */
    public function delete(User $user, HarvestReport $report): bool
    {
        if ($user->role?->kode === 'admin') {
            return true;
        }

        return $user->role?->kode === 'bumdes' && (int) $user->kabupaten_id === (int) $report->kabupaten_id;
    }
}
