<?php

namespace App\Services;

use App\Enums\StatusLevel;
use App\Models\DatasetImport;
use App\Models\Kabupaten;
use App\Models\Komoditas;
use App\Models\ModelVersion;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ForecastImporter
{
    /**
     * Ukuran batch upsert ke basis data.
     */
    protected const BATCH_SIZE = 500;

    /**
     * Jalankan impor hasil prediksi (forecast) dari berkas CSV.
     *
     * @param  string  $filePath  Path absolut ke berkas CSV
     * @param  int|null  $userId  ID pengguna yang mengunggah (null bila dari CLI)
     * @param  string|null  $originalFilename  Nama asli berkas
     */
    public function import(string $filePath, ?int $userId = null, ?string $originalFilename = null): DatasetImport
    {
        $filename = $originalFilename ?? basename($filePath);

        $importLog = DatasetImport::create([
            'user_id' => $userId,
            'tipe' => 'forecast',
            'nama_file' => $filename,
            'jumlah_baris' => 0,
            'jumlah_berhasil' => 0,
            'jumlah_gagal' => 0,
            'status' => 'berjalan',
            'ringkasan_galat' => null,
        ]);

        if (! file_exists($filePath) || ! is_readable($filePath)) {
            $importLog->update([
                'status' => 'gagal',
                'ringkasan_galat' => "Berkas '{$filename}' tidak dapat dibaca atau tidak ditemukan.",
            ]);

            return $importLog;
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $importLog->update([
                'status' => 'gagal',
                'ringkasan_galat' => "Gagal membuka pointer berkas '{$filename}'.",
            ]);

            return $importLog;
        }

        // 1. Baca Header
        $rawHeader = fgetcsv($handle, 0, ',');
        if (! $rawHeader) {
            fclose($handle);
            $importLog->update([
                'status' => 'gagal',
                'ringkasan_galat' => 'Berkas CSV kosong atau tidak memiliki baris header.',
            ]);

            return $importLog;
        }

        $rawHeader[0] = preg_replace('/[\x{EF}\x{BB}\x{BF}]/u', '', $rawHeader[0]);
        $headers = array_map(fn ($h) => trim(strtolower((string) $h)), $rawHeader);
        $headerMap = array_flip($headers);

        $requiredColumns = ['kode_kabupaten', 'komoditas', 'periode_dasar', 'periode_target', 'horizon_bulan'];
        $missingColumns = array_filter($requiredColumns, fn ($col) => ! isset($headerMap[$col]));

        if (! empty($missingColumns)) {
            fclose($handle);
            $importLog->update([
                'status' => 'gagal',
                'ringkasan_galat' => 'Kolom wajib tidak ditemukan dalam CSV forecast: '.implode(', ', $missingColumns),
            ]);

            return $importLog;
        }

        // 2. Preload Master Lookup
        $komoditasMap = Komoditas::pluck('id', 'kode')->mapWithKeys(
            fn ($id, $kode) => [strtolower(trim($kode)) => $id]
        )->all();

        $kabupatenMap = Kabupaten::pluck('id', 'kode')->mapWithKeys(
            fn ($id, $kode) => [trim((string) $kode) => $id]
        )->all();

        // Ambang batas dari tabel settings
        $ambangDefisitMax = (float) (Setting::nilai('ambang_defisit_max', '0.7000'));
        $ambangWaspadaMin = (float) (Setting::nilai('ambang_waspada_min', '0.9000'));
        $ambangPenuhMin = (float) (Setting::nilai('ambang_penuh_min', '1.0000'));

        $versionCache = [];

        $totalRows = 0;
        $successCount = 0;
        $failedCount = 0;
        $errors = [];
        $batch = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $rowNumber++;

            if (empty(array_filter($row, fn ($val) => trim((string) $val) !== ''))) {
                continue;
            }

            $totalRows++;
            $rowData = [];
            foreach ($headers as $index => $header) {
                $rowData[$header] = isset($row[$index]) ? trim((string) $row[$index]) : '';
            }

            // A. Validasi Kode Wilayah (harus string, awalan 0 dijaga)
            $kodeKab = trim((string) ($rowData['kode_kabupaten'] ?? ''));
            if ($kodeKab === '' || ! isset($kabupatenMap[$kodeKab])) {
                $failedCount++;
                $errors[] = "Baris {$rowNumber}: Kode kabupaten '{$kodeKab}' tidak terdaftar di master.";

                continue;
            }
            $kabupatenId = $kabupatenMap[$kodeKab];

            // B. Validasi Komoditas
            $komoditasKode = strtolower(trim((string) ($rowData['komoditas'] ?? '')));
            if (! isset($komoditasMap[$komoditasKode])) {
                $failedCount++;
                $errors[] = "Baris {$rowNumber}: Komoditas '{$komoditasKode}' tidak valid.";

                continue;
            }
            $komoditasId = $komoditasMap[$komoditasKode];

            // C. Validasi Periode Dasar & Periode Target (YYYY-MM)
            $rawPeriodeDasar = trim((string) ($rowData['periode_dasar'] ?? ''));
            if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $rawPeriodeDasar)) {
                $failedCount++;
                $errors[] = "Baris {$rowNumber}: Periode dasar '{$rawPeriodeDasar}' tidak valid (wajib YYYY-MM).";

                continue;
            }
            $periodeDasarDate = $rawPeriodeDasar.'-01';

            $rawPeriodeTarget = trim((string) ($rowData['periode_target'] ?? ''));
            if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $rawPeriodeTarget)) {
                $failedCount++;
                $errors[] = "Baris {$rowNumber}: Periode target '{$rawPeriodeTarget}' tidak valid (wajib YYYY-MM).";

                continue;
            }
            $periodeTargetDate = $rawPeriodeTarget.'-01';

            // D. Validasi Horizon Bulan (1–12)
            $horizonRaw = trim((string) ($rowData['horizon_bulan'] ?? ''));
            if (! is_numeric($horizonRaw) || (int) $horizonRaw < 1 || (int) $horizonRaw > 12) {
                $failedCount++;
                $errors[] = "Baris {$rowNumber}: Horizon bulan '{$horizonRaw}' tidak valid (harus 1–12).";

                continue;
            }
            $horizonBulan = (int) $horizonRaw;

            // E. Model Version
            $versiModel = trim((string) ($rowData['versi_model'] ?? ''));
            $modelVersionId = null;
            if ($versiModel !== '') {
                if (! isset($versionCache[$versiModel])) {
                    $modelVer = ModelVersion::firstOrCreate(
                        ['nama' => $versiModel],
                        [
                            'algoritma' => 'xgboost',
                            'catatan' => 'Dibuat otomatis saat impor forecast',
                            'is_aktif' => true,
                        ]
                    );
                    $versionCache[$versiModel] = $modelVer->id;
                }
                $modelVersionId = $versionCache[$versiModel];
            }

            // F. Parsing Nilai Prediksi
            $panenPred = $this->parseNullableDecimal($rowData['panen_pred_kg'] ?? null, $rowNumber, 'panen_pred_kg', $errors);
            $pasokanMasukPred = $this->parseNullableDecimal($rowData['pasokan_masuk_pred_kg'] ?? null, $rowNumber, 'pasokan_masuk_pred_kg', $errors);
            if ($pasokanMasukPred === null) {
                $pasokanMasukPred = 0.00;
            }

            $stokAwalPred = $this->parseNullableDecimal($rowData['stok_awal_pred_kg'] ?? null, $rowNumber, 'stok_awal_pred_kg', $errors);

            $supplyPredInput = $this->parseNullableDecimal($rowData['supply_pred_kg'] ?? null, $rowNumber, 'supply_pred_kg', $errors);
            if ($supplyPredInput !== null) {
                $supplyPred = $supplyPredInput;
            } else {
                $supplyPred = ($panenPred ?? 0.0) + $pasokanMasukPred + ($stokAwalPred ?? 0.0);
            }

            $demandPred = $this->parseNullableDecimal($rowData['demand_pred_kg'] ?? null, $rowNumber, 'demand_pred_kg', $errors);

            // Rasio Prediksi
            $rasioPredInput = $this->parseNullableDecimal($rowData['rasio_pred'] ?? null, $rowNumber, 'rasio_pred', $errors);
            if ($rasioPredInput !== null) {
                $rasioPred = round($rasioPredInput, 4);
            } elseif ($demandPred !== null && $demandPred > 0) {
                $rasioPred = round($supplyPred / $demandPred, 4);
            } else {
                $rasioPred = null;
            }

            // Status Prediksi (defisit, seimbang, waspada, penuh)
            $statusPredInput = strtolower(trim((string) ($rowData['status_pred'] ?? '')));
            if (in_array($statusPredInput, ['defisit', 'seimbang', 'waspada', 'penuh'], true)) {
                $statusPred = $statusPredInput;
            } elseif ($rasioPred !== null) {
                $statusPred = match (true) {
                    $rasioPred < $ambangDefisitMax => StatusLevel::Defisit->value,
                    $rasioPred >= $ambangPenuhMin => StatusLevel::Penuh->value,
                    $rasioPred >= $ambangWaspadaMin => StatusLevel::Waspada->value,
                    default => StatusLevel::Seimbang->value,
                };
            } else {
                $statusPred = null;
            }

            $rasioBawah = $this->parseNullableDecimal($rowData['rasio_bawah'] ?? null, $rowNumber, 'rasio_bawah', $errors);
            $rasioAtas = $this->parseNullableDecimal($rowData['rasio_atas'] ?? null, $rowNumber, 'rasio_atas', $errors);

            $dibuatPadaRaw = trim((string) ($rowData['dibuat_pada'] ?? ''));
            $dibuatPada = null;
            if ($dibuatPadaRaw !== '') {
                try {
                    $dibuatPada = Carbon::parse($dibuatPadaRaw)->toDateTimeString();
                } catch (\Throwable) {
                    $dibuatPada = Carbon::now()->toDateTimeString();
                }
            } else {
                $dibuatPada = Carbon::now()->toDateTimeString();
            }

            $now = Carbon::now()->toDateTimeString();

            $batch[] = [
                'kabupaten_id' => $kabupatenId,
                'komoditas_id' => $komoditasId,
                'model_version_id' => $modelVersionId,
                'periode_dasar' => $periodeDasarDate,
                'periode_target' => $periodeTargetDate,
                'horizon_bulan' => $horizonBulan,
                'panen_pred_kg' => $panenPred,
                'pasokan_masuk_pred_kg' => $pasokanMasukPred,
                'stok_awal_pred_kg' => $stokAwalPred,
                'supply_pred_kg' => $supplyPred,
                'demand_pred_kg' => $demandPred,
                'rasio_pred' => $rasioPred,
                'status_pred' => $statusPred,
                'rasio_bawah' => $rasioBawah,
                'rasio_atas' => $rasioAtas,
                'dibuat_pada' => $dibuatPada,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= self::BATCH_SIZE) {
                $successCount += $this->flushBatch($batch);
                $batch = [];
            }
        }

        fclose($handle);

        if (! empty($batch)) {
            $successCount += $this->flushBatch($batch);
        }

        $status = ($totalRows > 0 && $successCount === 0) ? 'gagal' : 'selesai';

        $ringkasan = '';
        if (! empty($errors)) {
            $previewCount = min(count($errors), 50);
            $ringkasan = implode("\n", array_slice($errors, 0, $previewCount));
            if (count($errors) > 50) {
                $ringkasan .= "\n... dan ".(count($errors) - 50).' baris galat lainnya.';
            }
        }

        $importLog->update([
            'jumlah_baris' => $totalRows,
            'jumlah_berhasil' => $successCount,
            'jumlah_gagal' => $failedCount,
            'status' => $status,
            'ringkasan_galat' => $ringkasan ?: null,
        ]);

        return $importLog;
    }

    /**
     * Eksekusi batch upsert forecasts ke database.
     *
     * @param  array<int, array<string, mixed>>  $batch
     */
    protected function flushBatch(array $batch): int
    {
        if (empty($batch)) {
            return 0;
        }

        try {
            DB::table('forecasts')->upsert(
                $batch,
                ['kabupaten_id', 'komoditas_id', 'periode_dasar', 'periode_target'],
                [
                    'model_version_id',
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
                    'dibuat_pada',
                    'updated_at',
                ]
            );

            return count($batch);
        } catch (\Throwable $e) {
            Log::error('Gagal melakukan upsert batch forecasts: '.$e->getMessage());

            return 0;
        }
    }

    /**
     * Parsing angka desimal non-negatif. Mengembalikan null jika sel kosong.
     *
     * @param  array<int, string>  $errors
     */
    protected function parseNullableDecimal(?string $value, int $rowNumber, string $colName, array &$errors): ?float
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (! is_numeric($trimmed)) {
            $errors[] = "Baris {$rowNumber}: Nilai kolom '{$colName}' ('{$trimmed}') bukan angka valid.";

            return null;
        }

        $floatVal = (float) $trimmed;
        if ($floatVal < 0) {
            $errors[] = "Baris {$rowNumber}: Nilai kolom '{$colName}' ('{$trimmed}') tidak boleh bernilai negatif.";

            return null;
        }

        return $floatVal;
    }
}
