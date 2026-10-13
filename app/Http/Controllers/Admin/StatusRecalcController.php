<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StatusRecalcController extends Controller
{
    /**
     * Pemicu hitung ulang status wilayah dari panel admin.
     */
    public function store(Request $request, StatusService $statusService): RedirectResponse
    {
        $periode = $request->input('periode');

        if ($periode && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periode)) {
            $statusService->recalc(null, null, $periode);
            $pesan = "Perhitungan ulang status wilayah untuk periode {$periode} berhasil dijalankan.";
        } else {
            $total = $statusService->recalcSemua();
            $pesan = "Perhitungan ulang seluruh status wilayah ({$total} titik) berhasil dijalankan dan cache telah dibersihkan.";
        }

        return back()->with('sukses', $pesan);
    }
}
