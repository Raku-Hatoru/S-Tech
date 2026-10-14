<?php

namespace App\Services;

use App\Enums\StatusLevel;
use App\Models\DataHistoris;
use App\Models\Forecast;
use App\Models\HarvestReport;
use App\Models\Kabupaten;
use App\Models\Komoditas;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\StatusWilayah;
use App\Support\IndoFormat;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatusService
{
    /**
     * Hitung rasio supply terhadap demand.
     * Mengembalikan null jika demand kosong atau nol.
     */
    public function hitungRasio(?float $supply, ?float $demand): ?float
    {
        if ($supply === null || $demand === null || $demand <= 0) {
            return null;
        }

        return round($supply / $demand, 4);
    }

    /**
     * Tentukan level status berdasarkan nilai rasio dan ambang batas di settings.
     *
     * Ambang default:
     * - rasio == null           => tanpa_data
     * - rasio < 0.7000          => defisit
     * - rasio >= 0.70 & < 0.90  => seimbang
     * - rasio >= 0.90 & < 1.00  => waspada
     * - rasio >= 1.0000         => penuh
     */
    public function tentukanLevel(?float $rasio): StatusLevel
    {
        if ($rasio === null) {
            return StatusLevel::TanpaData;
        }

        $ambangDefisitMax = (float) Setting::nilai('ambang_defisit_max', 0.7000);
        $ambangWaspadaMin = (float) Setting::nilai('ambang_waspada_min', 0.9000);
        $ambangPenuhMin = (float) Setting::nilai('ambang_penuh_min', 1.0000);

        return match (true) {
            $rasio < $ambangDefisitMax => StatusLevel::Defisit,
            $rasio < $ambangWaspadaMin => StatusLevel::Seimbang,
            $rasio < $ambangPenuhMin => StatusLevel::Waspada,
            default => StatusLevel::Penuh,
        };
    }

    /**
     * Dapatkan tanggal aktual terakhir yang ada di data_historis (format YYYY-MM-01).
     */
    public function getTanggalAktualTerakhir(): ?string
    {
        return Cache::remember('status_max_actual_date', 300, function () {
            $maxPeriode = DataHistoris::max('periode');

            return $maxPeriode ? Carbon::parse($maxPeriode)->format('Y-m-01') : null;
        });
    }

    /**
     * Hitung status satu wilayah, komoditas, dan periode tertentu.
     */
    public function cekWilayah(int $kabupatenId, int $komoditasId, string $periode): StatusWilayah
    {
        $periodeDate = Carbon::parse($periode)->format('Y-m-01');
        $maxActual = $this->getTanggalAktualTerakhir();

        $isAktual = ($maxActual !== null && $periodeDate <= $maxActual);
        $now = Carbon::now()->toDateTimeString();

        $panen = null;
        $stokAwal = null;
        $pasokanMasuk = 0.0;
        $supplyDasar = null;
        $demand = null;
        $sumber = $isAktual ? 'aktual' : 'prediksi';
        $stokEstimasi = false;
        $panenKoreksiKg = null;

        // 1. Sumber Dasar
        if ($isAktual) {
            $historis = DataHistoris::where('kabupaten_id', $kabupatenId)
                ->where('komoditas_id', $komoditasId)
                ->whereDate('periode', $periodeDate)
                ->first();

            if ($historis) {
                $panen = $historis->panen_kg !== null ? (float) $historis->panen_kg : null;
                $stokAwal = $historis->stok_awal_kg !== null ? (float) $historis->stok_awal_kg : null;
                $pasokanMasuk = $historis->pasokan_masuk_kg !== null ? (float) $historis->pasokan_masuk_kg : 0.0;
                $demand = $historis->demand_kg !== null ? (float) $historis->demand_kg : null;

                if ($historis->supply_kg !== null) {
                    $supplyDasar = (float) $historis->supply_kg;
                } elseif ($panen !== null) {
                    $supplyDasar = $panen + ($stokAwal ?? 0.0) + $pasokanMasuk;
                }
            }
        } else {
            // Periode mendatang -> cari forecast dengan periode_dasar terbaru
            $forecast = Forecast::where('kabupaten_id', $kabupatenId)
                ->where('komoditas_id', $komoditasId)
                ->whereDate('periode_target', $periodeDate)
                ->orderByDesc('periode_dasar')
                ->first();

            if ($forecast) {
                $panen = $forecast->panen_pred_kg !== null ? (float) $forecast->panen_pred_kg : null;
                $stokAwal = $forecast->stok_awal_pred_kg !== null ? (float) $forecast->stok_awal_pred_kg : null;
                $pasokanMasuk = $forecast->pasokan_masuk_pred_kg !== null ? (float) $forecast->pasokan_masuk_pred_kg : 0.0;
                $demand = $forecast->demand_pred_kg !== null ? (float) $forecast->demand_pred_kg : null;

                if ($forecast->supply_pred_kg !== null) {
                    $supplyDasar = (float) $forecast->supply_pred_kg;
                } elseif ($panen !== null) {
                    $supplyDasar = $panen + ($stokAwal ?? 0.0) + $pasokanMasuk;
                }
                $stokEstimasi = true;
            }
        }

        // 2. Stok Resolver (laporan survei BUMDes bulan t-1 jika ada)
        $prevPeriode = Carbon::parse($periodeDate)->subMonth()->format('Y-m-01');
        $laporanLalu = HarvestReport::where('kabupaten_id', $kabupatenId)
            ->where('komoditas_id', $komoditasId)
            ->whereDate('periode', $prevPeriode)
            ->first();

        if ($laporanLalu && $laporanLalu->sisa_stok_kg !== null) {
            $stokAwal = (float) $laporanLalu->sisa_stok_kg;
            $stokEstimasi = false;
            if ($panen !== null) {
                $supplyDasar = $panen + $stokAwal + $pasokanMasuk;
            }
        }

        // 3. Panen Corrector (jika bulan target prediksi sudah dilaporkan BUMDes)
        if (! $isAktual) {
            $laporanBulanIni = HarvestReport::where('kabupaten_id', $kabupatenId)
                ->where('komoditas_id', $komoditasId)
                ->whereDate('periode', $periodeDate)
                ->first();

            if ($laporanBulanIni && $laporanBulanIni->panen_kg !== null) {
                $panenAktual = (float) $laporanBulanIni->panen_kg;
                $panenKoreksiKg = $panenAktual - ($panen ?? 0.0);
                if ($supplyDasar !== null) {
                    $supplyDasar += $panenKoreksiKg;
                } else {
                    $supplyDasar = $panenAktual + ($stokAwal ?? 0.0) + $pasokanMasuk;
                }
                $stokEstimasi = false;
            }
        }

        // 4. Pengiriman Aggregator (shipments bulan tanggal_kirim yang aktif)
        $startBulan = Carbon::parse($periodeDate)->startOfMonth()->toDateString();
        $endBulan = Carbon::parse($periodeDate)->endOfMonth()->toDateString();

        $pengirimanMasuk = (float) Shipment::where('tujuan_kabupaten_id', $kabupatenId)
            ->where('komoditas_id', $komoditasId)
            ->whereBetween('tanggal_kirim', [$startBulan, $endBulan])
            ->where('status', '<>', 'dibatalkan')
            ->sum('jumlah_kg');

        $pengirimanKeluar = (float) Shipment::where('asal_kabupaten_id', $kabupatenId)
            ->where('komoditas_id', $komoditasId)
            ->whereBetween('tanggal_kirim', [$startBulan, $endBulan])
            ->where('status', '<>', 'dibatalkan')
            ->sum('jumlah_kg');

        $finalSupply = $supplyDasar !== null
            ? round($supplyDasar + $pengirimanMasuk - $pengirimanKeluar, 2)
            : null;

        // 5. Ratio & Level
        $rasio = $this->hitungRasio($finalSupply, $demand);
        $level = $this->tentukanLevel($rasio);

        $attributes = [
            'supply_kg' => $finalSupply,
            'demand_kg' => $demand,
            'rasio' => $rasio,
            'status' => $level,
            'sumber' => $sumber,
            'stok_estimasi' => $stokEstimasi,
            'panen_koreksi_kg' => $panenKoreksiKg,
            'pengiriman_masuk_kg' => $pengirimanMasuk,
            'pengiriman_keluar_kg' => $pengirimanKeluar,
            'dihitung_pada' => $now,
        ];

        $existing = StatusWilayah::where('kabupaten_id', $kabupatenId)
            ->where('komoditas_id', $komoditasId)
            ->whereDate('periode', $periodeDate)
            ->first();

        if ($existing) {
            $existing->update($attributes);

            return $existing;
        }

        return StatusWilayah::create(array_merge([
            'kabupaten_id' => $kabupatenId,
            'komoditas_id' => $komoditasId,
            'periode' => $periodeDate,
        ], $attributes));
    }

    /**
     * Hitung ulang status untuk satu kombinasi atau lingkup tertentu.
     */
    public function recalc(?int $kabupatenId = null, ?int $komoditasId = null, ?string $periode = null): void
    {
        if ($kabupatenId && $komoditasId && $periode) {
            $this->cekWilayah($kabupatenId, $komoditasId, $periode);
            $this->invalidateCache();

            return;
        }

        $kabupatenIds = $kabupatenId ? [$kabupatenId] : Kabupaten::pluck('id')->all();
        $komoditasIds = $komoditasId ? [$komoditasId] : Komoditas::pluck('id')->all();

        if ($periode) {
            $periodes = [Carbon::parse($periode)->format('Y-m-01')];
        } else {
            // Ambil seluruh periode unik dari data_historis & forecasts
            $periodes = DB::table('data_historis')
                ->select('periode')
                ->union(DB::table('forecasts')->select('periode_target as periode'))
                ->distinct()
                ->pluck('periode')
                ->map(fn ($p) => Carbon::parse($p)->format('Y-m-01'))
                ->unique()
                ->sort()
                ->values()
                ->all();
        }

        foreach ($kabupatenIds as $kId) {
            foreach ($komoditasIds as $komId) {
                foreach ($periodes as $p) {
                    $this->cekWilayah($kId, $komId, $p);
                }
            }
        }

        $this->invalidateCache();
    }

    /**
     * Hitung ulang seluruh status wilayah untuk seluruh data di sistem.
     * Menggunakan eksekusi bertahap yang efisien.
     */
    public function recalcSemua(): int
    {
        Cache::forget('status_max_actual_date');
        $maxActual = $this->getTanggalAktualTerakhir();
        $now = Carbon::now()->toDateTimeString();

        $ambangDefisitMax = (float) Setting::nilai('ambang_defisit_max', 0.7000);
        $ambangWaspadaMin = (float) Setting::nilai('ambang_waspada_min', 0.9000);
        $ambangPenuhMin = (float) Setting::nilai('ambang_penuh_min', 1.0000);

        // 1. Ambil data gabungan periode unik
        $periodes = DB::table('data_historis')
            ->select('periode')
            ->union(DB::table('forecasts')->select('periode_target as periode'))
            ->distinct()
            ->pluck('periode')
            ->map(fn ($p) => Carbon::parse($p)->format('Y-m-01'))
            ->unique()
            ->sort()
            ->values()
            ->all();

        $kabupatens = Kabupaten::pluck('id')->all();
        $komoditasList = Komoditas::pluck('id')->all();

        $totalRecalculated = 0;

        foreach ($periodes as $p) {
            foreach ($kabupatens as $kId) {
                foreach ($komoditasList as $cId) {
                    $this->cekWilayah($kId, $cId, $p);
                    $totalRecalculated++;
                }
            }
        }

        $this->invalidateCache();

        return $totalRecalculated;
    }

    /**
     * Agregasi status tingkat provinsi berdasarkan rasio tertimbang:
     * SUM(supply_kg) / SUM(demand_kg) per provinsi.
     *
     * @return Collection<int, object>
     */
    public function agregasiProvinsi(int $komoditasId, string $periode): Collection
    {
        $periodeDate = Carbon::parse($periode)->format('Y-m-01');

        $query = DB::table('status_wilayah as s')
            ->join('kabupaten as k', 'k.id', '=', 's.kabupaten_id')
            ->join('provinsi as p', 'p.id', '=', 'k.provinsi_id')
            ->where('s.komoditas_id', $komoditasId)
            ->whereDate('s.periode', $periodeDate)
            ->where('s.status', '<>', StatusLevel::TanpaData->value)
            ->select([
                'p.kode as kode',
                'p.nama as nama',
                DB::raw('SUM(s.supply_kg) as total_supply_kg'),
                DB::raw('SUM(s.demand_kg) as total_demand_kg'),
                DB::raw('COUNT(s.id) as jumlah_kabupaten'),
                DB::raw('SUM(s.supply_kg) / NULLIF(SUM(s.demand_kg), 0) AS rasio'),
            ])
            ->groupBy('p.kode', 'p.nama')
            ->get();

        return $query->map(function ($row) {
            $rasio = $row->rasio !== null ? round((float) $row->rasio, 4) : null;
            $statusLevel = $this->tentukanLevel($rasio);

            return (object) [
                'kode' => (string) $row->kode,
                'nama' => $row->nama,
                'total_supply_kg' => (float) $row->total_supply_kg,
                'total_demand_kg' => (float) $row->total_demand_kg,
                'jumlah_kabupaten' => (int) $row->jumlah_kabupaten,
                'rasio' => $rasio,
                'status' => $statusLevel->value,
                'label' => $statusLevel->label(),
                'warna_hex' => $statusLevel->warnaHex(),
                'warna_badge' => $statusLevel->warnaBadge(),
            ];
        });
    }

    /**
     * Hitung ringkasan statistik jujur wilayah pantau berdasarkan data riil status_wilayah.
     *
     * @return array<string, mixed>
     */
    public function ringkasanWilayah(int $komoditasId, string $periode): array
    {
        $periodeDate = Carbon::parse($periode)->format('Y-m-01');

        $rows = StatusWilayah::where('komoditas_id', $komoditasId)
            ->whereDate('periode', $periodeDate)
            ->get();

        $totalWilayah = $rows->count();

        $penuh = $rows->where('status', StatusLevel::Penuh);
        $seimbang = $rows->where('status', StatusLevel::Seimbang);
        $waspada = $rows->where('status', StatusLevel::Waspada);
        $defisit = $rows->where('status', StatusLevel::Defisit);
        $tanpaData = $rows->where('status', StatusLevel::TanpaData);

        $jumlahPenuh = $penuh->count();
        $jumlahSeimbang = $seimbang->count();
        $jumlahWaspada = $waspada->count();
        $jumlahDefisit = $defisit->count();
        $jumlahTanpaData = $tanpaData->count();

        $totalSupplyKg = (float) $rows->sum('supply_kg');
        $totalDemandKg = (float) $rows->sum('demand_kg');
        $selisihKg = $totalSupplyKg - $totalDemandKg;
        $selisihPersen = $totalDemandKg > 0 ? ($selisihKg / $totalDemandKg) * 100.0 : 0.0;

        $totalDefisitKg = (float) $defisit->sum(fn ($r) => max(0, (float) $r->demand_kg - (float) $r->supply_kg));
        $totalSurplusKg = (float) $penuh->sum(fn ($r) => max(0, (float) $r->supply_kg - (float) $r->demand_kg));

        $rerataRasioSurplus = $jumlahPenuh > 0 ? ((float) $penuh->avg('rasio')) * 100.0 : 0.0;
        $persenPenuh = $totalWilayah > 0 ? ($jumlahPenuh / $totalWilayah) * 100.0 : 0.0;
        $persenDefisitWaspada = $totalWilayah > 0 ? (($jumlahDefisit + $jumlahWaspada) / $totalWilayah) * 100.0 : 0.0;

        $bannerText = sprintf(
            'Status Pangan Nasional Terpantau: %d Kabupaten Surplus/Penuh, %d Waspada, %d Defisit. Selisih Bersih Pasokan: %s (%s di atas kebutuhan). Pantauan mencakup %d Kabupaten Percontohan.',
            $jumlahPenuh,
            $jumlahWaspada,
            $jumlahDefisit,
            IndoFormat::ton($selisihKg, 1, true),
            IndoFormat::percent($selisihPersen, 1, false, true),
            $totalWilayah
        );

        return [
            'total_wilayah' => $totalWilayah,
            'jumlah_penuh' => $jumlahPenuh,
            'jumlah_seimbang' => $jumlahSeimbang,
            'jumlah_waspada' => $jumlahWaspada,
            'jumlah_defisit' => $jumlahDefisit,
            'jumlah_tanpa_data' => $jumlahTanpaData,
            'jumlah_defisit_waspada' => $jumlahDefisit + $jumlahWaspada,
            'total_supply_kg' => $totalSupplyKg,
            'total_demand_kg' => $totalDemandKg,
            'selisih_kg' => $selisihKg,
            'selisih_persen' => $selisihPersen,
            'total_defisit_kg' => $totalDefisitKg,
            'total_surplus_kg' => $totalSurplusKg,
            'rerata_rasio_surplus' => $rerataRasioSurplus,
            'persen_penuh' => $persenPenuh,
            'persen_defisit_waspada' => $persenDefisitWaspada,

            // Formatted values untuk kemudahan konsumsi di Blade dan JSON
            'total_supply_formatted' => IndoFormat::ton($totalSupplyKg, 1),
            'total_demand_formatted' => IndoFormat::ton($totalDemandKg, 1),
            'selisih_formatted' => IndoFormat::ton($selisihKg, 1, true),
            'selisih_persen_formatted' => IndoFormat::percent($selisihPersen, 1, false, true),
            'total_defisit_formatted' => IndoFormat::ton($totalDefisitKg, 1),
            'rerata_rasio_surplus_formatted' => IndoFormat::percent($rerataRasioSurplus, 1),
            'persen_penuh_formatted' => IndoFormat::percent($persenPenuh, 1),
            'persen_defisit_waspada_formatted' => IndoFormat::percent($persenDefisitWaspada, 1),
            'banner_text' => $bannerText,
        ];
    }

    /**
     * Dapatkan versi cache status saat ini.
     */
    public function getCacheVersion(): int
    {
        return (int) Cache::get('status_cache_version', 1);
    }

    /**
     * Invalidasi seluruh cache status dengan menaikkan versi cache secara instan (O(1)).
     */
    public function invalidateCache(): void
    {
        Cache::forget('status_max_actual_date');
        $newVersion = ((int) Cache::get('status_cache_version', 1)) + 1;
        Cache::forever('status_cache_version', $newVersion);
    }
}
