<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Komoditas;
use App\Models\StatusWilayah;
use App\Services\NumberFormatter;
use App\Services\StatusService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StatusController extends Controller
{
    /**
     * Endpoint API publik untuk status neraca pangan per provinsi atau kabupaten.
     *
     * Parameter query:
     * - komoditas : kode komoditas (beras, jagung, singkong - default: beras)
     * - periode   : YYYY-MM (default: periode terbaru)
     * - level     : provinsi | kabupaten (default: provinsi)
     */
    public function index(Request $request, StatusService $statusService): JsonResponse
    {
        $request->validate([
            'komoditas' => ['sometimes', 'string', 'exists:komoditas,kode'],
            'periode' => ['sometimes', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'level' => ['sometimes', 'string', 'in:provinsi,kabupaten'],
        ]);

        $komoditasKode = strtolower((string) $request->input('komoditas', 'beras'));
        $komoditas = Komoditas::where('kode', $komoditasKode)->first();

        if (! $komoditas) {
            $komoditas = Komoditas::first();
        }

        // Tentukan periode default: bulan ini atau data terbaru yang tersedia
        $periodeInput = $request->input('periode');
        if (! $periodeInput) {
            $maxPeriode = $statusService->getTanggalAktualTerakhir();
            $periodeInput = $maxPeriode ? Carbon::parse($maxPeriode)->format('Y-m') : Carbon::now()->format('Y-m');
        }

        $level = strtolower((string) $request->input('level', 'provinsi'));
        $periodeDate = Carbon::parse($periodeInput)->format('Y-m-01');

        // Kunci cache berbasis versi agar invalidasi instan saat recalc
        $cacheVersion = $statusService->getCacheVersion();
        $cacheKey = "api_status:v{$cacheVersion}:{$komoditas->kode}:{$periodeInput}:{$level}";

        $result = Cache::remember($cacheKey, 3600, function () use ($statusService, $komoditas, $periodeInput, $periodeDate, $level) {
            if ($level === 'provinsi') {
                $provinces = $statusService->agregasiProvinsi($komoditas->id, $periodeDate);

                $data = $provinces->map(function ($row) {
                    return [
                        'kode' => $row->kode,
                        'nama' => $row->nama,
                        'total_supply_kg' => $row->total_supply_kg,
                        'total_demand_kg' => $row->total_demand_kg,
                        'total_supply_formatted' => NumberFormatter::tampilkan($row->total_supply_kg),
                        'total_demand_formatted' => NumberFormatter::tampilkan($row->total_demand_kg),
                        'jumlah_kabupaten' => $row->jumlah_kabupaten,
                        'rasio' => $row->rasio,
                        'rasio_persen' => NumberFormatter::formatPersen($row->rasio),
                        'status' => $row->status,
                        'label' => $row->label,
                        'warna_hex' => $row->warna_hex,
                        'warna_badge' => $row->warna_badge,
                    ];
                })->values()->all();
            } else {
                $kabupatens = StatusWilayah::with(['kabupaten.provinsi'])
                    ->where('komoditas_id', $komoditas->id)
                    ->whereDate('periode', $periodeDate)
                    ->get();

                $data = $kabupatens->map(function ($row) {
                    $supply = $row->supply_kg !== null ? (float) $row->supply_kg : null;
                    $demand = $row->demand_kg !== null ? (float) $row->demand_kg : null;
                    $rasio = $row->rasio !== null ? (float) $row->rasio : null;

                    return [
                        'kode' => (string) $row->kabupaten->kode,
                        'nama' => $row->kabupaten->nama,
                        'tipe' => $row->kabupaten->tipe,
                        'kode_provinsi' => (string) ($row->kabupaten->provinsi->kode ?? ''),
                        'nama_provinsi' => $row->kabupaten->provinsi->nama ?? '',
                        'lat' => $row->kabupaten->lat ? (float) $row->kabupaten->lat : null,
                        'lng' => $row->kabupaten->lng ? (float) $row->kabupaten->lng : null,
                        'supply_kg' => $supply,
                        'demand_kg' => $demand,
                        'supply_formatted' => NumberFormatter::tampilkan($supply),
                        'demand_formatted' => NumberFormatter::tampilkan($demand),
                        'rasio' => $rasio,
                        'rasio_persen' => NumberFormatter::formatPersen($rasio),
                        'status' => $row->status->value,
                        'label' => $row->status->label(),
                        'warna_hex' => $row->status->warnaHex(),
                        'warna_badge' => $row->status->warnaBadge(),
                        'sumber' => $row->sumber,
                        'stok_estimasi' => (bool) $row->stok_estimasi,
                    ];
                })->values()->all();
            }

            return [
                'komoditas' => [
                    'kode' => $komoditas->kode,
                    'nama' => $komoditas->nama,
                ],
                'periode' => $periodeInput,
                'level' => $level,
                'total_wilayah' => count($data),
                'data' => $data,
                'meta' => [
                    'dihitung_pada' => Carbon::now()->toIso8601String(),
                ],
            ];
        });

        return response()->json($result);
    }
}
