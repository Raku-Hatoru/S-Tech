<?php

namespace App\Console\Commands;

use App\Services\DataHistorisImporter;
use Illuminate\Console\Command;

class DataImportHistorisCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:import-historis {file : Path absolut atau relatif ke berkas CSV dataset historis}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Impor dataset historis bulanan pangan (2023–2025) ke tabel data_historis';

    /**
     * Execute the console command.
     */
    public function handle(DataHistorisImporter $importer): int
    {
        $fileArg = (string) $this->argument('file');
        $fullPath = str_starts_with($fileArg, '/') ? $fileArg : base_path($fileArg);

        if (! file_exists($fullPath)) {
            $this->error("❌ Berkas tidak ditemukan: {$fullPath}");

            return self::FAILURE;
        }

        $this->info(" Memulai impor dataset historis dari: {$fullPath}");

        $importLog = $importer->import($fullPath, null, basename($fullPath));

        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['Nama Berkas', $importLog->nama_file],
                ['Status', strtoupper($importLog->status)],
                ['Total Baris Dibaca', number_format($importLog->jumlah_baris)],
                ['Berhasil Masuk/Update', number_format($importLog->jumlah_berhasil)],
                ['Gagal / Ditolak', number_format($importLog->jumlah_gagal)],
            ]
        );

        if ($importLog->ringkasan_galat) {
            $this->warn("\n⚠ Catatan Galat / Peringatan:");
            $this->line($importLog->ringkasan_galat);
        }

        if ($importLog->status === 'gagal') {
            $this->error('❌ Impor gagal.');

            return self::FAILURE;
        }

        $this->info("✅ Impor selesai dengan sukses! (ID Log: {$importLog->id})");

        return self::SUCCESS;
    }
}
