<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatusLevel;
use App\Http\Controllers\Controller;
use App\Models\Forecast;
use App\Models\Kabupaten;
use App\Models\Komoditas;
use App\Models\ModelVersion;
use App\Models\Provinsi;
use App\Models\Setting;
use App\Models\StatusWilayah;
use App\Services\NumberFormatter;
use App\Services\StatusService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WilayahController extends Controller
{
    /**
     * Endpoint API publik untuk deret waktu historis vs prediksi per wilayah.
     *
     * @param  string  $kode  Kode provinsi (2 digit) atau kabupaten (4 digit)
     */
    public function seri(string $kode, Request $request, StatusService $statusService): JsonResponse
    {
        $komoditasKode = strtolower((string) $request->input('komoditas', 'beras'));
        $komoditas = Komoditas::where('kode', $komoditasKode)->first() ?? Komoditas::first();

        // 1. Identifikasi Wilayah (Provinsi atau Kabupaten)
        $isProvinsi = strlen($kode) === 2;
        $kabupaten = null;
        $provinsi = null;

        if ($isProvinsi) {
            $provinsi = Provinsi::where('kode', $kode)->first();
            if (! $provinsi) {
                return response()->json(['message' => "Provinsi dengan kode '{$kode}' tidak ditemukan."], 404);
            }
            $wilayahInfo = [
                'kode' => $provinsi->kode,
                'nama' => $provinsi->nama,
                'tipe' => 'provinsi',
            ];
        } else {
            $kabupaten = Kabupaten::with('provinsi')->where('kode', $kode)->first();
            if (! $kabupaten) {
                return response()->json(['message' => "Kabupaten/Kota dengan kode '{$kode}' tidak ditemukan."], 404);
            }
            $wilayahInfo = [
                'kode' => $kabupaten->kode,
                'nama' => $kabupaten->nama,
                'tipe' => $kabupaten->tipe,
                'kode_provinsi' => $kabupaten->provinsi->kode ?? null,
                'nama_provinsi' => $kabupaten->provinsi->nama ?? null,
            ];
        }

        // 2. Caching
        $cacheVersion = $statusService->getCacheVersion();
        $cacheKey = "api_seri:v{$cacheVersion}:{$kode}:{$komoditas->kode}";

        $result = Cache::remember($cacheKey, 3600, function () use ($statusService, $komoditas, $isProvinsi, $provinsi, $kabupaten, $wilayahInfo) {
            $maxActual = $statusService->getTanggalAktualTerakhir();
            $seriData = [];

            if (! $isProvinsi) {
                // Tingkat Kabupaten
                $statusList = StatusWilayah::where('kabupaten_id', $kabupaten->id)
                    ->where('komoditas_id', $komoditas->id)
                    ->orderBy('periode')
                    ->get();

                // Ambil rentang forecast untuk rasio_bawah & rasio_atas
                $forecastList = Forecast::where('kabupaten_id', $kabupaten->id)
                    ->where('komoditas_id', $komoditas->id)
                    ->get()
                    ->keyBy(fn ($f) => Carbon::parse($f->periode_target)->format('Y-m'));

                foreach ($statusList as $s) {
                    $periodeKey = Carbon::parse($s->periode)->format('Y-m');
                    $isPrediksi = ($s->sumber === 'prediksi');

                    $fc = $forecastList->get($periodeKey);

                    $seriData[] = [
                        'periode' => $periodeKey,
                        'supply_kg' => $s->supply_kg !== null ? (float) $s->supply_kg : null,
                        'demand_kg' => $s->demand_kg !== null ? (float) $s->demand_kg : null,
                        'supply_formatted' => NumberFormatter::tampilkan($s->supply_kg !== null ? (float) $s->supply_kg : null),
                        'demand_formatted' => NumberFormatter::tampilkan($s->demand_kg !== null ? (float) $s->demand_kg : null),
                        'rasio' => $s->rasio !== null ? (float) $s->rasio : null,
                        'rasio_persen' => NumberFormatter::formatPersen($s->rasio !== null ? (float) $s->rasio : null),
                        'status' => $s->status->value,
                        'label' => $s->status->label(),
                        'warna_hex' => $s->status->warnaHex(),
                        'warna_badge' => $s->status->warnaBadge(),
                        'is_prediksi' => $isPrediksi,
                        'stok_estimasi' => (bool) $s->stok_estimasi,
                        'rasio_bawah' => $fc && $fc->rasio_bawah !== null ? (float) $fc->rasio_bawah : null,
                        'rasio_atas' => $fc && $fc->rasio_atas !== null ? (float) $fc->rasio_atas : null,
                    ];
                }
            } else {
                // Tingkat Provinsi (Agregasi)
                $query = DB::table('status_wilayah as s')
                    ->join('kabupaten as k', 'k.id', '=', 's.kabupaten_id')
                    ->where('k.provinsi_id', $provinsi->id)
                    ->where('s.komoditas_id', $komoditas->id)
                    ->where('s.status', '<>', StatusLevel::TanpaData->value)
                    ->select([
                        's.periode',
                        DB::raw('SUM(s.supply_kg) as total_supply'),
                        DB::raw('SUM(s.demand_kg) as total_demand'),
                        DB::raw('SUM(s.supply_kg) / NULLIF(SUM(s.demand_kg), 0) AS rasio'),
                        DB::raw('MAX(s.sumber) as sumber'),
                    ])
                    ->groupBy('s.periode')
                    ->orderBy('s.periode')
                    ->get();

                foreach ($query as $row) {
                    $periodeKey = Carbon::parse($row->periode)->format('Y-m');
                    $rasio = $row->rasio !== null ? round((float) $row->rasio, 4) : null;
                    $statusLevel = $statusService->tentukanLevel($rasio);
                    $isPrediksi = ($row->sumber === 'prediksi');

                    $seriData[] = [
                        'periode' => $periodeKey,
                        'supply_kg' => (float) $row->total_supply,
                        'demand_kg' => (float) $row->total_demand,
                        'supply_formatted' => NumberFormatter::tampilkan((float) $row->total_supply),
                        'demand_formatted' => NumberFormatter::tampilkan((float) $row->total_demand),
                        'rasio' => $rasio,
                        'rasio_persen' => NumberFormatter::formatPersen($rasio),
                        'status' => $statusLevel->value,
                        'label' => $statusLevel->label(),
                        'warna_hex' => $statusLevel->warnaHex(),
                        'warna_badge' => $statusLevel->warnaBadge(),
                        'is_prediksi' => $isPrediksi,
                        'stok_estimasi' => false,
                        'rasio_bawah' => null,
                        'rasio_atas' => null,
                    ];
                }
            }

            // Ambil status titik terakhir/terbaru
            $titikTerakhir = end($seriData) ?: null;

            // Pesan dampak sesuai status rasio
            $pesanDampak = match (true) {
                $titikTerakhir && $titikTerakhir['status'] === StatusLevel::Penuh->value => Setting::nilai('pesan_dampak_surplus', 'Pangan berisiko basi atau terbuang dan menghasilkan emisi gas rumah kaca bila pasokan melebihi kapasitas serap wilayah.'),
                $titikTerakhir && $titikTerakhir['status'] === StatusLevel::Defisit->value => Setting::nilai('pesan_dampak_defisit', 'Risiko kekurangan pangan di wilayah ini hingga kelaparan bila pasokan tidak segera dipenuhi.'),
                $titikTerakhir && $titikTerakhir['status'] === StatusLevel::Waspada->value => 'Pasokan mendekati kapasitas penuh. Waspadai potensi penumpukan stok komoditas.',
                default => 'Pasokan pangan wilayah terpantau dalam kisaran seimbang dan mencukupi kebutuhan konsumsi.',
            };

            $activeModel = ModelVersion::where('is_aktif', true)->first();

            return [
                'wilayah' => $wilayahInfo,
                'komoditas' => [
                    'kode' => $komoditas->kode,
                    'nama' => $komoditas->nama,
                ],
                'status_terbaru' => $titikTerakhir,
                'pesan_dampak' => $pesanDampak,
                'model_version' => [
                    'nama' => $activeModel?->nama ?? 'v1-2026Q1-xgboost',
                    'algoritma' => strtoupper($activeModel?->algoritma ?? 'XGBoost'),
                    'tanggal_latih' => $activeModel?->tanggal_latih?->format('d/m/Y') ?? null,
                ],
                'total_titik' => count($seriData),
                'seri' => $seriData,
            ];
        });

        return response()->json($result);
    }

    /**
     * Daftar periode (YYYY-MM) yang tersedia di database, diurutkan terbaru dulu.
     */
    public function periodeList(Request $request): JsonResponse
    {
        $komoditasKode = strtolower((string) $request->input('komoditas', 'beras'));
        $komoditas = Komoditas::where('kode', $komoditasKode)->first() ?? Komoditas::first();

        $cacheVersion = (new StatusService)->getCacheVersion();
        $cacheKey = "api_periode:v{$cacheVersion}:{$komoditas->kode}";

        $data = Cache::remember($cacheKey, 3600, function () use ($komoditas) {
            return StatusWilayah::where('komoditas_id', $komoditas->id)
                ->distinct()
                ->orderByDesc('periode')
                ->pluck('periode')
                ->map(fn ($p) => Carbon::parse($p)->format('Y-m'))
                ->unique()
                ->values()
                ->all();
        });

        return response()->json([
            'komoditas' => $komoditas->kode,
            'total' => count($data),
            'periodes' => $data,
        ]);
    }

    /**
     * Info ringkasan terbaru status per wilayah (untuk tooltip peta).
     *
     * @param  string  $kode  Kode provinsi (2 digit) atau kabupaten (4 digit)
     */
    public function info(string $kode, Request $request, StatusService $statusService): JsonResponse
    {
        $komoditasKode = strtolower((string) $request->input('komoditas', 'beras'));
        $periodeInput = $request->input('periode');
        $komoditas = Komoditas::where('kode', $komoditasKode)->first() ?? Komoditas::first();

        if (! $periodeInput) {
            $maxPeriode = $statusService->getTanggalAktualTerakhir();
            $periodeInput = $maxPeriode ? Carbon::parse($maxPeriode)->format('Y-m') : Carbon::now()->format('Y-m');
        }

        $periodeDate = Carbon::parse($periodeInput)->format('Y-m-01');

        $isProvinsi = strlen($kode) === 2;

        if ($isProvinsi) {
            $provinsi = Provinsi::where('kode', $kode)->first();
            if (! $provinsi) {
                return response()->json(['message' => "Provinsi '{$kode}' tidak ditemukan."], 404);
            }

            // Agregasi sederhana untuk provinsi
            $row = DB::table('status_wilayah as s')
                ->join('kabupaten as k', 'k.id', '=', 's.kabupaten_id')
                ->where('k.provinsi_id', $provinsi->id)
                ->where('s.komoditas_id', $komoditas->id)
                ->whereDate('s.periode', $periodeDate)
                ->where('s.status', '<>', StatusLevel::TanpaData->value)
                ->selectRaw('SUM(s.supply_kg) AS total_supply, SUM(s.demand_kg) AS total_demand, COUNT(*) AS jumlah_kab')
                ->first();

            if (! $row || ! $row->total_demand) {
                return response()->json(['message' => 'Data tidak tersedia.'], 404);
            }

            $rasio = round((float) $row->total_supply / (float) $row->total_demand, 4);
            $statusLevel = $statusService->tentukanLevel($rasio);

            return response()->json([
                'kode' => $kode,
                'nama' => $provinsi->nama,
                'tipe' => 'provinsi',
                'rasio' => $rasio,
                'rasio_persen' => NumberFormatter::formatPersen($rasio),
                'status' => $statusLevel->value,
                'label' => $statusLevel->label(),
                'warna_hex' => $statusLevel->warnaHex(),
                'supply_formatted' => NumberFormatter::tampilkan((float) $row->total_supply),
                'demand_formatted' => NumberFormatter::tampilkan((float) $row->total_demand),
                'jumlah_kabupaten' => (int) $row->jumlah_kab,
            ]);
        } else {
            $kabupaten = Kabupaten::with('provinsi')->where('kode', $kode)->first();
            if (! $kabupaten) {
                return response()->json(['message' => "Kabupaten '{$kode}' tidak ditemukan."], 404);
            }

            $statusRow = StatusWilayah::where('kabupaten_id', $kabupaten->id)
                ->where('komoditas_id', $komoditas->id)
                ->whereDate('periode', $periodeDate)
                ->first();

            if (! $statusRow) {
                return response()->json(['message' => 'Data tidak tersedia.'], 404);
            }

            return response()->json([
                'kode' => $kode,
                'nama' => $kabupaten->nama,
                'tipe' => $kabupaten->tipe,
                'kode_provinsi' => $kabupaten->provinsi->kode ?? '',
                'nama_provinsi' => $kabupaten->provinsi->nama ?? '',
                'rasio' => $statusRow->rasio !== null ? (float) $statusRow->rasio : null,
                'rasio_persen' => NumberFormatter::formatPersen($statusRow->rasio !== null ? (float) $statusRow->rasio : null),
                'status' => $statusRow->status->value,
                'label' => $statusRow->status->label(),
                'warna_hex' => $statusRow->status->warnaHex(),
                'supply_formatted' => NumberFormatter::tampilkan($statusRow->supply_kg !== null ? (float) $statusRow->supply_kg : null),
                'demand_formatted' => NumberFormatter::tampilkan($statusRow->demand_kg !== null ? (float) $statusRow->demand_kg : null),
                'stok_estimasi' => (bool) $statusRow->stok_estimasi,
                'sumber' => $statusRow->sumber,
            ]);
        }
    }

    /**
     * Endpoint cek status wilayah tujuan pengiriman (F-07, PRD 8.3 & 8.4, UML 9.3).
     * Digunakan saat form pengiriman memilih kabupaten tujuan untuk menentukan banner peringatan.
     *
     * @param  string  $kode  Kode kabupaten tujuan (4 digit)
     */
    public function cek(string $kode, Request $request, StatusService $statusService): JsonResponse
    {
        $komoditasKode = strtolower((string) $request->input('komoditas', 'beras'));
        $komoditas = Komoditas::where('kode', $komoditasKode)->first() ?? Komoditas::first();

        $kabupaten = Kabupaten::with('provinsi')->where('kode', $kode)->first();
        if (! $kabupaten) {
            return response()->json(['message' => "Kabupaten dengan kode '{$kode}' tidak ditemukan."], 404);
        }

        // Tentukan periode yang dicek: bulan tanggal_kirim atau bulan aktual terakhir
        $tanggalKirim = $request->input('tanggal_kirim') ?? $request->input('periode');
        if ($tanggalKirim) {
            $periodeDate = Carbon::parse($tanggalKirim)->format('Y-m-01');
        } else {
            $maxPeriode = $statusService->getTanggalAktualTerakhir();
            $periodeDate = $maxPeriode ? Carbon::parse($maxPeriode)->format('Y-m-01') : Carbon::now()->format('Y-m-01');
        }

        $statusWilayah = $statusService->cekWilayah($kabupaten->id, $komoditas->id, $periodeDate);

        $statusLevel = $statusWilayah?->status ?? StatusLevel::TanpaData;
        $rasio = $statusWilayah?->rasio !== null ? (float) $statusWilayah->rasio : null;

        // Tentukan banner & tindakan sesuai PRD 8.3 & rules 7.3
        $tampilkanBanner = in_array($statusLevel, [StatusLevel::Waspada, StatusLevel::Penuh, StatusLevel::Defisit]);
        $tipeBanner = match ($statusLevel) {
            StatusLevel::Penuh => 'danger',
            StatusLevel::Waspada => 'warning',
            StatusLevel::Defisit => 'info',
            default => 'none',
        };

        $pesanDampak = match ($statusLevel) {
            StatusLevel::Penuh => Setting::nilai('pesan_dampak_surplus', 'Pangan berisiko basi atau terbuang dan menghasilkan emisi gas rumah kaca bila pasokan melebihi kapasitas serap wilayah.'),
            StatusLevel::Waspada => 'Pasokan di wilayah tujuan mendekati batas jenuh (90%–100%). Waspadai potensi penumpukan stok.',
            StatusLevel::Defisit => Setting::nilai('pesan_dampak_defisit', 'Wilayah tujuan sedang defisit pasokan (<70%). Pasokan pangan sangat dibutuhkan untuk menjaga keseimbangan pasokan dan konsumsi masyarakat.'),
            default => null,
        };

        $disclaimer = Setting::nilai('teks_disclaimer', 'Sistem ini bukan layanan penjualan komersial, melainkan platform pemantauan, tracing, dan mitigasi kelebihan suplai logistik pangan nasional.');

        return response()->json([
            'kode' => $kabupaten->kode,
            'nama' => $kabupaten->nama,
            'tipe' => $kabupaten->tipe,
            'provinsi' => $kabupaten->provinsi->nama ?? '',
            'komoditas' => $komoditas->kode,
            'periode' => Carbon::parse($periodeDate)->format('Y-m'),
            'status' => $statusLevel->value,
            'label' => $statusLevel->label(),
            'warna_hex' => $statusLevel->warnaHex(),
            'rasio' => $rasio,
            'rasio_persen' => NumberFormatter::formatPersen($rasio),
            'tampilkan_banner' => $tampilkanBanner,
            'tipe_banner' => $tipeBanner,
            'pesan_dampak' => $pesanDampak,
            'disclaimer' => $disclaimer,
            'supply_formatted' => NumberFormatter::tampilkan($statusWilayah?->supply_kg !== null ? (float) $statusWilayah->supply_kg : null),
            'demand_formatted' => NumberFormatter::tampilkan($statusWilayah?->demand_kg !== null ? (float) $statusWilayah->demand_kg : null),
        ]);
    }
}
