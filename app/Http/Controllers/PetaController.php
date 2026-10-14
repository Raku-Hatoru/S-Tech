<?php

namespace App\Http\Controllers;

use App\Models\Komoditas;
use App\Models\Provinsi;
use App\Models\Setting;
use App\Models\StatusWilayah;
use App\Services\StatusService;
use Carbon\Carbon;
use Illuminate\View\View;

class PetaController extends Controller
{
    /**
     * Halaman publik peta heatmap ketahanan pangan Indonesia.
     */
    public function index(StatusService $statusService): View
    {
        // Daftar komoditas untuk filter
        $komoditasList = Komoditas::orderBy('nama')->get(['kode', 'nama']);

        // Daftar periode tersedia (historis + prediksi), max 36 bulan terakhir
        $periodes = StatusWilayah::distinct()
            ->orderByDesc('periode')
            ->pluck('periode')
            ->map(fn ($p) => Carbon::parse($p)->format('Y-m'))
            ->unique()
            ->take(36)
            ->reverse()
            ->values();

        // Periode default: data aktual terakhir (bukan prediksi)
        $periodeDefault = $statusService->getTanggalAktualTerakhir()
            ? Carbon::parse($statusService->getTanggalAktualTerakhir())->format('Y-m')
            : Carbon::now()->format('Y-m');

        // Daftar provinsi untuk panel fallback tabel dan pencarian
        $provinsiList = Provinsi::orderBy('nama')->get(['kode', 'nama']);

        // Ambang batas rasio status dibaca dinamis dari settings
        $ambangDefisit = (float) Setting::nilai('ambang_defisit_max', 0.70);
        $ambangWaspada = (float) Setting::nilai('ambang_waspada_min', 0.90);
        $ambangPenuh = (float) Setting::nilai('ambang_penuh_min', 1.00);

        return view('peta.index', [
            'komoditasList' => $komoditasList,
            'periodes' => $periodes,
            'periodeDefault' => $periodeDefault,
            'provinsiList' => $provinsiList,
            'ambangDefisit' => $ambangDefisit,
            'ambangWaspada' => $ambangWaspada,
            'ambangPenuh' => $ambangPenuh,
        ]);
    }
}
