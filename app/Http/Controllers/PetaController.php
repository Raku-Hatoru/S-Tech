<?php

namespace App\Http\Controllers;

use App\Enums\StatusLevel;
use App\Models\Kabupaten;
use App\Models\Komoditas;
use App\Models\ModelVersion;
use App\Models\Provinsi;
use App\Models\Setting;
use App\Models\StatusWilayah;
use App\Services\NumberFormatter;
use App\Services\StatusService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        return view('peta.index', [
            'komoditasList' => $komoditasList,
            'periodes' => $periodes,
            'periodeDefault' => $periodeDefault,
            'provinsiList' => $provinsiList,
        ]);
    }

    /**
     * Halaman detail wilayah (provinsi atau kabupaten/kota) publik.
     * Menampilkan grafik 12 bulan aktual vs prediksi, status, pesan dampak, dan versi model.
     *
     * @param  string  $kode  Kode provinsi (2 digit) atau kabupaten (4 digit)
     */
    public function detail(string $kode, Request $request, StatusService $statusService): View
    {
        $komoditasKode = strtolower((string) $request->query('komoditas', 'beras'));
        $komoditas = Komoditas::where('kode', $komoditasKode)->first() ?? Komoditas::first();
        $komoditasList = Komoditas::orderBy('nama')->get(['kode', 'nama']);

        $isProvinsi = strlen($kode) === 2;
        $provinsi = null;
        $kabupaten = null;
        $daftarKabupaten = collect();

        if ($isProvinsi) {
            $provinsi = Provinsi::where('kode', $kode)->firstOrFail();
            $namaWilayah = $provinsi->nama;
            $tipeWilayah = 'Provinsi';
            $kodeProvinsi = $provinsi->kode;
            $namaProvinsiInduk = null;

            // Daftar kabupaten di bawah provinsi ini
            $daftarKabupaten = Kabupaten::where('provinsi_id', $provinsi->id)
                ->orderBy('nama')
                ->get();
        } else {
            $kabupaten = Kabupaten::with('provinsi')->where('kode', $kode)->firstOrFail();
            $namaWilayah = $kabupaten->nama;
            $tipeWilayah = $kabupaten->tipe === 'kota' ? 'Kota' : 'Kabupaten';
            $kodeProvinsi = $kabupaten->provinsi->kode ?? null;
            $namaProvinsiInduk = $kabupaten->provinsi->nama ?? null;
        }

        // Tentukan periode default (aktual terakhir)
        $tanggalAktual = $statusService->getTanggalAktualTerakhir();
        $periodeTerbaru = $tanggalAktual
            ? Carbon::parse($tanggalAktual)->format('Y-m-01')
            : Carbon::now()->startOfMonth()->format('Y-m-01');

        // Ringkasan status terkini
        $statusTerbaru = null;
        if (! $isProvinsi) {
            $statusRow = StatusWilayah::where('kabupaten_id', $kabupaten->id)
                ->where('komoditas_id', $komoditas->id)
                ->whereDate('periode', $periodeTerbaru)
                ->first();

            // Jika periode aktual belum ada data, ambil periode terakhir apa pun
            if (! $statusRow) {
                $statusRow = StatusWilayah::where('kabupaten_id', $kabupaten->id)
                    ->where('komoditas_id', $komoditas->id)
                    ->orderByDesc('periode')
                    ->first();
            }

            if ($statusRow) {
                $statusTerbaru = [
                    'status' => $statusRow->status->value,
                    'label' => $statusRow->status->label(),
                    'warna_hex' => $statusRow->status->warnaHex(),
                    'warna_badge' => $statusRow->status->warnaBadge(),
                    'rasio' => $statusRow->rasio !== null ? (float) $statusRow->rasio : null,
                    'rasio_persen' => NumberFormatter::formatPersen($statusRow->rasio !== null ? (float) $statusRow->rasio : null),
                    'supply_formatted' => NumberFormatter::tampilkan($statusRow->supply_kg !== null ? (float) $statusRow->supply_kg : null),
                    'demand_formatted' => NumberFormatter::tampilkan($statusRow->demand_kg !== null ? (float) $statusRow->demand_kg : null),
                    'stok_estimasi' => (bool) $statusRow->stok_estimasi,
                    'periode' => Carbon::parse($statusRow->periode)->format('Y-m'),
                    'sumber' => $statusRow->sumber,
                ];
            }
        } else {
            // Agregasi provinsi
            $row = DB::table('status_wilayah as s')
                ->join('kabupaten as k', 'k.id', '=', 's.kabupaten_id')
                ->where('k.provinsi_id', $provinsi->id)
                ->where('s.komoditas_id', $komoditas->id)
                ->whereDate('s.periode', $periodeTerbaru)
                ->where('s.status', '<>', StatusLevel::TanpaData->value)
                ->selectRaw('SUM(s.supply_kg) AS total_supply, SUM(s.demand_kg) AS total_demand, COUNT(*) AS jumlah_kab')
                ->first();

            if ($row && $row->total_demand > 0) {
                $rasio = round((float) $row->total_supply / (float) $row->total_demand, 4);
                $level = $statusService->tentukanLevel($rasio);
                $statusTerbaru = [
                    'status' => $level->value,
                    'label' => $level->label(),
                    'warna_hex' => $level->warnaHex(),
                    'warna_badge' => $level->warnaBadge(),
                    'rasio' => $rasio,
                    'rasio_persen' => NumberFormatter::formatPersen($rasio),
                    'supply_formatted' => NumberFormatter::tampilkan((float) $row->total_supply),
                    'demand_formatted' => NumberFormatter::tampilkan((float) $row->total_demand),
                    'stok_estimasi' => false,
                    'periode' => Carbon::parse($periodeTerbaru)->format('Y-m'),
                    'jumlah_kab' => (int) $row->jumlah_kab,
                    'sumber' => 'agregat',
                ];
            }
        }

        // Pesan dampak sesuai status rasio
        $statusValue = $statusTerbaru['status'] ?? StatusLevel::TanpaData->value;
        $pesanDampak = match ($statusValue) {
            StatusLevel::Penuh->value => Setting::nilai('pesan_dampak_surplus', 'Pangan berisiko basi/terbuang dan menghasilkan emisi gas ke lingkungan; harga di tingkat petani dapat turun karena pasokan melimpah.'),
            StatusLevel::Defisit->value => Setting::nilai('pesan_dampak_defisit', 'Risiko kekurangan pangan di wilayah ini hingga kelaparan bila pasokan tidak segera dipenuhi.'),
            StatusLevel::Waspada->value => 'Pasokan mendekati kapasitas penuh. Waspadai potensi penumpukan stok komoditas.',
            StatusLevel::Seimbang->value => 'Pasokan pangan wilayah terpantau dalam kisaran seimbang dan mencukupi kebutuhan konsumsi masyarakat.',
            default => 'Data pasokan wilayah belum lengkap atau belum terlaporkan.',
        };

        $disclaimer = Setting::nilai('teks_disclaimer', 'Sistem ini bukan layanan penjualan, hanya layanan pemantauan dan tracking pasokan pangan.');
        $activeModel = ModelVersion::where('is_aktif', true)->first();

        return view('peta.detail', [
            'kode' => $kode,
            'isProvinsi' => $isProvinsi,
            'provinsi' => $provinsi,
            'kabupaten' => $kabupaten,
            'namaWilayah' => $namaWilayah,
            'tipeWilayah' => $tipeWilayah,
            'kodeProvinsi' => $kodeProvinsi,
            'namaProvinsiInduk' => $namaProvinsiInduk,
            'komoditas' => $komoditas,
            'komoditasList' => $komoditasList,
            'statusTerbaru' => $statusTerbaru,
            'pesanDampak' => $pesanDampak,
            'disclaimer' => $disclaimer,
            'daftarKabupaten' => $daftarKabupaten,
            'modelVersion' => [
                'nama' => $activeModel?->nama ?? 'v1-2026Q1-xgboost',
                'algoritma' => strtoupper($activeModel?->algoritma ?? 'XGBoost Regressor'),
                'tanggal_latih' => $activeModel?->tanggal_latih?->format('d/m/Y') ?? '02/01/2026',
            ],
        ]);
    }
}
