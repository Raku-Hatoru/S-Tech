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

        // Data tanggal aktual terakhir dari basis data riil (PRD 10.1 & PRD 2.3)
        $maxActual = $statusService->getTanggalAktualTerakhir();
        $maxActualYm = $maxActual ? Carbon::parse($maxActual)->format('Y-m') : '2025-12';

        // Periode default: data aktual terakhir
        $periodeDefault = $maxActualYm;

        // Bulan kalender berjalan saat ini
        $bulanBerjalan = Carbon::now()->format('Y-m');

        // Buat metadata periode (Aktual vs Proyeksi) berbasis data riil
        $periodesList = $periodes->map(function ($p) use ($maxActualYm) {
            $isAktual = $p <= $maxActualYm;

            return [
                'kode' => $p,
                'label' => Carbon::parse($p.'-01')->isoFormat('MMMM YYYY'),
                'tipe' => $isAktual ? 'Aktual' : 'Proyeksi',
                'is_aktual' => $isAktual,
            ];
        });

        // Daftar provinsi untuk panel fallback tabel dan pencarian
        $provinsiList = Provinsi::orderBy('nama')->get(['kode', 'nama']);

        // Ambang batas rasio status dibaca dinamis dari settings
        $ambangDefisit = (float) Setting::nilai('ambang_defisit_max', 0.70);
        $ambangWaspada = (float) Setting::nilai('ambang_waspada_min', 0.90);
        $ambangPenuh = (float) Setting::nilai('ambang_penuh_min', 1.00);

        // Komoditas default (beras atau komoditas pertama)
        $komoditasDefault = Komoditas::where('kode', 'beras')->first() ?? Komoditas::orderBy('nama')->first();
        $ringkasan = $komoditasDefault
            ? $statusService->ringkasanWilayah($komoditasDefault->id, $periodeDefault)
            : [];

        return view('peta.index', [
            'komoditasList' => $komoditasList,
            'komoditasDefault' => $komoditasDefault,
            'periodes' => $periodes,
            'periodesList' => $periodesList,
            'periodeDefault' => $periodeDefault,
            'bulanBerjalan' => $bulanBerjalan,
            'provinsiList' => $provinsiList,
            'ambangDefisit' => $ambangDefisit,
            'ambangWaspada' => $ambangWaspada,
            'ambangPenuh' => $ambangPenuh,
            'ringkasan' => $ringkasan,
        ]);
    }
}
