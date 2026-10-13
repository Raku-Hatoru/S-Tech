<?php

namespace App\Console\Commands;

use App\Models\DataHistoris;
use App\Models\HarvestReport;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DataExportMlCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:export-ml {--output= : Path kustom berkas CSV hasil ekspor}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ekspor data historis dan laporan BUMDes ke ml/data/processed/latest.csv untuk training ML';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $defaultPath = base_path('ml/data/processed/latest.csv');
        $outputPath = $this->option('output') ?: $defaultPath;

        $dir = dirname($outputPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->info(' Memulai ekspor dataset untuk pipeline Machine Learning...');

        $fp = fopen($outputPath, 'w');
        if (! $fp) {
            $this->error("❌ Gagal membuka berkas untuk penulisan di: {$outputPath}");

            return self::FAILURE;
        }

        // Header CSV sesuai kontrak pipeline ML
        fputcsv($fp, [
            'kode_provinsi',
            'nama_provinsi',
            'kode_kabupaten',
            'nama_kabupaten',
            'tipe_kabupaten',
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

        // Preload harvest reports BUMDes untuk overlay data aktual jika ada
        $bumdesReports = HarvestReport::all()
            ->keyBy(fn ($r) => $r->kabupaten_id.'_'.$r->komoditas_id.'_'.Carbon::parse($r->periode)->format('Y-m'));

        $totalExported = 0;

        // Query data historis dengan chunk untuk hemat RAM
        DataHistoris::with(['kabupaten.provinsi', 'komoditas'])
            ->orderBy('periode')
            ->orderBy('kabupaten_id')
            ->chunk(1000, function ($records) use ($fp, $bumdesReports, &$totalExported) {
                foreach ($records as $record) {
                    $kab = $record->kabupaten;
                    $kom = $record->komoditas;
                    $prov = $kab?->provinsi;

                    $periodeStr = Carbon::parse($record->periode)->format('Y-m');
                    $key = $record->kabupaten_id.'_'.$record->komoditas_id.'_'.$periodeStr;

                    $panen = (float) $record->panen_kg;
                    $stokAwal = $record->stok_awal_kg !== null ? (float) $record->stok_awal_kg : null;
                    $sumber = $record->sumber_data ?: 'Historis';

                    // Cek jika ada laporan BUMDes aktual yang menimpa
                    if ($bumdesReports->has($key)) {
                        $report = $bumdesReports->get($key);
                        $panen = (float) $report->panen_kg;
                        $sumber = 'Laporan BUMDes (Aktual)';
                    }

                    $pasokanMasuk = (float) ($record->pasokan_masuk_kg ?? 0);
                    $supply = (float) ($record->supply_kg ?? ($panen + ($stokAwal ?? 0) + $pasokanMasuk));
                    $demand = (float) $record->demand_kg;

                    fputcsv($fp, [
                        $prov?->kode ?? '',
                        $prov?->nama ?? '',
                        (string) ($kab?->kode ?? ''),
                        $kab?->nama ?? '',
                        $kab?->tipe ?? 'kabupaten',
                        $kom?->kode ?? '',
                        $periodeStr,
                        $panen,
                        $stokAwal,
                        $pasokanMasuk,
                        $supply,
                        $demand,
                        $record->curah_hujan_mm !== null ? (float) $record->curah_hujan_mm : '',
                        $record->jumlah_penduduk ?? $kab?->jumlah_penduduk,
                        $record->luas_panen_ha !== null ? (float) $record->luas_panen_ha : '',
                        $record->flag_estimasi ? 1 : 0,
                        $sumber,
                    ]);

                    $totalExported++;
                }
            });

        fclose($fp);

        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['Lokasi Berkas', $outputPath],
                ['Total Baris Diekspor', number_format($totalExported)],
                ['Status', 'BERHASIL'],
            ]
        );

        $this->info("✅ Berkas siap digunakan untuk pelatihan model di Python ({$outputPath}).");

        return self::SUCCESS;
    }
}
