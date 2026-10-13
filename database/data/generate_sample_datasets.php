<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require_once __DIR__.'/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Kabupaten;
use App\Models\Komoditas;
use Illuminate\Contracts\Console\Kernel;

$kabupatens = Kabupaten::with('provinsi')->get();
$komoditasList = Komoditas::all();

if ($kabupatens->isEmpty() || $komoditasList->isEmpty()) {
    echo "Master kabupaten atau komoditas belum disemai. Jalankan seeder terlebih dahulu.\n";
    exit(1);
}

// 1. GENERATE DATASET HISTORIS (2023-01 s.d. 2025-12)
$historisFile = __DIR__.'/dataset_historis_sample.csv';
$fpHistoris = fopen($historisFile, 'w');

fputcsv($fpHistoris, [
    'kode_provinsi',
    'nama_provinsi',
    'kode_kabupaten',
    'nama_kabupaten',
    'komoditas',
    'periode',
    'panen_kg',
    'stok_awal_kg',
    'pasokan_masuk_kg',
    'supply_kg',
    'demand_kg',
    'curah_hujan_mm',
    'jumlah_penduduk',
    'luas_panen_ha',
    'flag_estimasi',
    'sumber_data',
]);

$startYear = 2023;
$endYear = 2025;

$rowsHistoris = 0;

foreach ($kabupatens as $kab) {
    $penduduk = $kab->jumlah_penduduk ?: 1500000;
    $isKota = $kab->tipe === 'kota';

    foreach ($komoditasList as $kom) {
        $kodeKom = $kom->kode;

        // Base demand bulanan per kapita (kg/orang/bulan)
        $konsumsiPerKapita = match ($kodeKom) {
            'beras' => 8.5,     // ~102 kg/tahun
            'jagung' => 2.0,    // bahan pangan & pakan
            'singkong' => 1.2,
            default => 5.0,
        };

        $baseDemand = $penduduk * $konsumsiPerKapita;

        // Base panen: kabupaten sentra pangan vs kota konsumen
        $basePanenFactor = match ($kodeKom) {
            'beras' => $isKota ? 0.05 : 1.35,
            'jagung' => $isKota ? 0.02 : 0.85,
            'singkong' => $isKota ? 0.01 : 0.70,
            default => 1.0,
        };

        // Buat variasi khusus wilayah surplus (misal Indramayu, Banyumas, Malang, Karawang, Cilacap)
        $isSentraSurplus = in_array($kab->kode, ['3212', '3215', '3301', '3302', '3507', '3509', '3522', '7308', '1802']);
        if ($isSentraSurplus) {
            $basePanenFactor *= 1.45;
        }

        $stokRolling = $baseDemand * 0.4;

        for ($year = $startYear; $year <= $endYear; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                $periode = sprintf('%04d-%02d', $year, $month);

                // Pola musiman panen (padi: puncak panen Maret–Mei & Juli–Agustus)
                $musimFactor = match ($month) {
                    3, 4, 5 => 1.70,   // Panen raya musim hujan
                    7, 8 => 1.25,      // Panen gadu kemarau
                    11, 12, 1 => 0.65, // Musim tanam / paceklik
                    default => 0.90,
                };

                // Pola curah hujan (musim hujan Okt–Apr, kemarau Mei–Sep)
                $curahHujan = match ($month) {
                    12, 1, 2 => rand(250, 420) + rand(0, 99) / 100,
                    3, 4 => rand(180, 310) + rand(0, 99) / 100,
                    5, 6 => rand(80, 160) + rand(0, 99) / 100,
                    7, 8, 9 => rand(20, 90) + rand(0, 99) / 100,
                    10, 11 => rand(150, 280) + rand(0, 99) / 100,
                    default => 180.0,
                };

                // Variasi random ±10%
                $noise = 0.90 + (rand(0, 200) / 1000);
                $panen = round($baseDemand * $basePanenFactor * $musimFactor * $noise, 2);

                // Kota konsumen menerima pasokan masuk dari luar wilayah
                $pasokanMasuk = $isKota
                    ? round($baseDemand * (0.80 + rand(0, 20) / 100), 2)
                    : round($baseDemand * (rand(0, 15) / 100), 2);

                $stokAwal = round($stokRolling, 2);
                $supply = round($panen + $stokAwal + $pasokanMasuk, 2);

                $demandNoise = 0.95 + (rand(0, 100) / 1000);
                $demand = round($baseDemand * $demandNoise, 2);

                // Sisa stok bergulir untuk bulan depan (stok = max(0, supply - demand))
                $sisaStok = max(0, $supply - $demand);
                // Batasi agar tidak meledak tak terkendali di daerah surplus (karena ada distribusi ke luar)
                $stokRolling = min($sisaStok, $baseDemand * 1.5);

                $luasPanen = round(($panen / 5200), 2); // asumsi produktivitas 5,2 ton/ha

                fputcsv($fpHistoris, [
                    $kab->provinsi->kode ?? substr($kab->kode, 0, 2),
                    $kab->provinsi->nama ?? 'Provinsi '.substr($kab->kode, 0, 2),
                    $kab->kode,
                    $kab->nama,
                    $kodeKom,
                    $periode,
                    $panen,
                    $stokAwal,
                    $pasokanMasuk,
                    $supply,
                    $demand,
                    $curahHujan,
                    $penduduk,
                    $luasPanen,
                    0, // bukan estimasi
                    'BPS & Satu Data Pertanian Daerah',
                ]);

                $rowsHistoris++;
            }
        }
    }
}
fclose($fpHistoris);
echo " Berhasil menghasilkan {$rowsHistoris} baris dataset historis di: {$historisFile}\n";

// 2. GENERATE FORECASTS PREDIKSI (2026-01 s.d. 2026-12)
$forecastFile = __DIR__.'/forecasts_sample.csv';
$fpForecast = fopen($forecastFile, 'w');

fputcsv($fpForecast, [
    'kode_kabupaten',
    'komoditas',
    'periode_dasar',
    'periode_target',
    'horizon_bulan',
    'panen_pred_kg',
    'pasokan_masuk_pred_kg',
    'stok_awal_pred_kg',
    'supply_pred_kg',
    'demand_pred_kg',
    'rasio_pred',
    'status_pred',
    'rasio_bawah',
    'rasio_atas',
    'versi_model',
    'dibuat_pada',
]);

$periodeDasar = '2025-12';
$rowsForecast = 0;
$versiModel = 'v1-2026Q1-xgboost';
$dibuatPada = '2026-01-02 08:00:00';

foreach ($kabupatens as $kab) {
    $penduduk = $kab->jumlah_penduduk ?: 1500000;
    $isKota = $kab->tipe === 'kota';

    foreach ($komoditasList as $kom) {
        $kodeKom = $kom->kode;

        $konsumsiPerKapita = match ($kodeKom) {
            'beras' => 8.5,
            'jagung' => 2.0,
            'singkong' => 1.2,
            default => 5.0,
        };

        $baseDemand = $penduduk * $konsumsiPerKapita;

        $basePanenFactor = match ($kodeKom) {
            'beras' => $isKota ? 0.05 : 1.35,
            'jagung' => $isKota ? 0.02 : 0.85,
            'singkong' => $isKota ? 0.01 : 0.70,
            default => 1.0,
        };

        $isSentraSurplus = in_array($kab->kode, ['3212', '3215', '3301', '3302', '3507', '3509', '3522', '7308', '1802']);
        if ($isSentraSurplus) {
            $basePanenFactor *= 1.45;
        }

        $stokPredRolling = $baseDemand * 0.45;

        for ($horizon = 1; $horizon <= 12; $horizon++) {
            $month = $horizon;
            $periodeTarget = sprintf('2026-%02d', $month);

            $musimFactor = match ($month) {
                3, 4, 5 => 1.65,
                7, 8 => 1.20,
                11, 12, 1 => 0.70,
                default => 0.92,
            };

            $panenPred = round($baseDemand * $basePanenFactor * $musimFactor, 2);
            $pasokanMasukPred = $isKota
                ? round($baseDemand * 0.85, 2)
                : round($baseDemand * 0.05, 2);

            $stokAwalPred = round($stokPredRolling, 2);
            $supplyPred = round($panenPred + $pasokanMasukPred + $stokAwalPred, 2);
            $demandPred = round($baseDemand, 2);

            $rasioPred = round($supplyPred / max(1, $demandPred), 4);

            $statusPred = match (true) {
                $rasioPred < 0.70 => 'defisit',
                $rasioPred >= 1.00 => 'penuh',
                $rasioPred >= 0.90 => 'waspada',
                default => 'seimbang',
            };

            // Rentang ketidakpastian semakin melebar seiring bertambahnya horizon
            $uncertaintyDelta = round(0.02 + ($horizon * 0.015), 4);
            $rasioBawah = max(0, round($rasioPred - $uncertaintyDelta, 4));
            $rasioAtas = round($rasioPred + $uncertaintyDelta, 4);

            // Bergulir untuk horizon berikutnya
            $sisaPred = max(0, $supplyPred - $demandPred);
            $stokPredRolling = min($sisaPred, $baseDemand * 1.4);

            fputcsv($fpForecast, [
                $kab->kode,
                $kodeKom,
                $periodeDasar,
                $periodeTarget,
                $horizon,
                $panenPred,
                $pasokanMasukPred,
                $stokAwalPred,
                $supplyPred,
                $demandPred,
                $rasioPred,
                $statusPred,
                $rasioBawah,
                $rasioAtas,
                $versiModel,
                $dibuatPada,
            ]);

            $rowsForecast++;
        }
    }
}
fclose($fpForecast);
echo " Berhasil menghasilkan {$rowsForecast} baris dataset forecast di: {$forecastFile}\n";
