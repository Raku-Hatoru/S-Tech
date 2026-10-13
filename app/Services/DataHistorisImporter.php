<?php

namespace App\Services;

use App\Models\DatasetImport;
use App\Models\Kabupaten;
use App\Models\Komoditas;
use App\Models\Provinsi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DataHistorisImporter
{
    /**
     * Jumlah baris per batch upsert ke basis data.
     */
    protected const BATCH_SIZE = 500;

    /**
     * Jalankan impor dataset historis dari berkas CSV.
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
            'tipe' => 'historis',
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

        // Hapus BOM jika ada dan bersihkan whitespace
        $rawHeader[0] = preg_replace('/[\x{EF}\x{BB}\x{BF}]/u', '', $rawHeader[0]);
        $headers = array_map(fn ($h) => trim(strtolower((string) $h)), $rawHeader);

        $headerMap = array_flip($headers);

        $requiredColumns = ['kode_kabupaten', 'komoditas', 'periode'];
        $missingColumns = array_filter($requiredColumns, fn ($col) => ! isset($headerMap[$col]));

        if (! empty($missingColumns)) {
            fclose($handle);
            $importLog->update([
                'status' => 'gagal',
                'ringkasan_galat' => 'Kolom wajib tidak ditemukan dalam CSV: '.implode(', ', $missingColumns),
            ]);

            return $importLog;
        }

        // 2. Preload Master Data Lookup
        $komoditasMap = Komoditas::pluck('id', 'kode')->mapWithKeys(
            fn ($id, $kode) => [strtolower(trim($kode)) => $id]
        )->all();

        $kabupatenMap = Kabupaten::pluck('id', 'kode')->mapWithKeys(
            fn ($id, $kode) => [trim((string) $kode) => $id]
        )->all();

        $provinsiMap = Provinsi::pluck('id', 'kode')->mapWithKeys(
            fn ($id, $kode) => [trim((string) $kode) => $id]
        )->all();

        $totalRows = 0;
        $successCount = 0;
        $failedCount = 0;
        $errors = [];
        $batch = [];
        $rowNumber = 1; // Baris 1 adalah header

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $rowNumber++;

            // Abaikan baris kosong
            if (empty(array_filter($row, fn ($val) => trim((string) $val) !== ''))) {
                continue;
            }

            $totalRows++;
            $rowData = [];
            foreach ($headers as $index => $header) {
                $rowData[$header] = isset($row[$index]) ? trim((string) $row[$index]) : '';
            }

            // A. Validasi Kode Wilayah (harus teks, jaga awalan nol)
            $kodeKab = trim((string) ($rowData['kode_kabupaten'] ?? ''));
            if ($kodeKab === '') {
                $failedCount++;
                $errors[] = "Baris {$rowNumber}: 'kode_kabupaten' kosong.";

                continue;
            }

            // Jika kode kabupaten belum ada di master, cek apakah ada data master provinsi & nama kabupaten untuk auto-upsert
            if (! isset($kabupatenMap[$kodeKab])) {
                $kodeProv = trim((string) ($rowData['kode_provinsi'] ?? ''));
                $namaKab = trim((string) ($rowData['nama_kabupaten'] ?? ''));
                $namaProv = trim((string) ($rowData['nama_provinsi'] ?? ''));

                if ($kodeProv !== '' && $namaKab !== '') {
                    $provId = $provinsiMap[$kodeProv] ?? null;
                    if (! $provId) {
                        $prov = Provinsi::updateOrCreate(
                            ['kode' => $kodeProv],
                            ['nama' => $namaProv ?: 'Provinsi '.$kodeProv]
                        );
                        $provId = $prov->id;
                        $provinsiMap[$kodeProv] = $provId;
                    }

                    $kab = Kabupaten::updateOrCreate(
                        ['kode' => $kodeKab],
                        [
                            'provinsi_id' => $provId,
                            'nama' => $namaKab,
                            'tipe' => str_starts_with(strtolower($namaKab), 'kota') ? 'kota' : 'kabupaten',
                        ]
                    );
                    $kabupatenMap[$kodeKab] = $kab->id;
                } else {
                    $failedCount++;
                    $errors[] = "Baris {$rowNumber}: Kode kabupaten '{$kodeKab}' tidak terdaftar di master wilayah.";

                    continue;
                }
            }

            $kabupatenId = $kabupatenMap[$kodeKab];

            // B. Validasi Komoditas
            $komoditasKode = strtolower(trim((string) ($rowData['komoditas'] ?? '')));
            if (! isset($komoditasMap[$komoditasKode])) {
                $failedCount++;
                $errors[] = "Baris {$rowNumber}: Komoditas '{$komoditasKode}' tidak valid (hanya beras, jagung, singkong).";

                continue;
            }
            $komoditasId = $komoditasMap[$komoditasKode];

            // C. Validasi Periode (YYYY-MM)
            $rawPeriode = trim((string) ($rowData['periode'] ?? ''));
            if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $rawPeriode)) {
                $failedCount++;
                $errors[] = "Baris {$rowNumber}: Format periode '{$rawPeriode}' tidak valid (wajib YYYY-MM).";

                continue;
            }
            $periodeDate = $rawPeriode.'-01';

            // D. Parsing Nilai Angka
            $panenKg = $this->parseNullableDecimal($rowData['panen_kg'] ?? null, $rowNumber, 'panen_kg', $errors);
            $stokAwalKg = $this->parseNullableDecimal($rowData['stok_awal_kg'] ?? null, $rowNumber, 'stok_awal_kg', $errors);

            // Keputusan PRD/Rules: pasokan_masuk_kg kosong diisi 0
            $pasokanMasukKg = $this->parseNullableDecimal($rowData['pasokan_masuk_kg'] ?? null, $rowNumber, 'pasokan_masuk_kg', $errors);
            if ($pasokanMasukKg === null) {
                $pasokanMasukKg = 0.00;
            }

            // Supply: jika kosong, dihitung otomatis panen + stok_awal + pasokan_masuk
            $supplyKgInput = $this->parseNullableDecimal($rowData['supply_kg'] ?? null, $rowNumber, 'supply_kg', $errors);
            if ($supplyKgInput !== null) {
                $supplyKg = $supplyKgInput;
            } else {
                $supplyKg = ($panenKg ?? 0.0) + ($stokAwalKg ?? 0.0) + $pasokanMasukKg;
            }

            $demandKg = $this->parseNullableDecimal($rowData['demand_kg'] ?? null, $rowNumber, 'demand_kg', $errors);
            $curahHujanMm = $this->parseNullableDecimal($rowData['curah_hujan_mm'] ?? null, $rowNumber, 'curah_hujan_mm', $errors);

            $jumlahPendudukRaw = trim((string) ($rowData['jumlah_penduduk'] ?? ''));
            $jumlahPenduduk = ($jumlahPendudukRaw !== '' && is_numeric($jumlahPendudukRaw) && (int) $jumlahPendudukRaw >= 0)
                ? (int) $jumlahPendudukRaw
                : null;

            $luasPanenHa = $this->parseNullableDecimal($rowData['luas_panen_ha'] ?? null, $rowNumber, 'luas_panen_ha', $errors);

            $flagEstimasiRaw = trim((string) ($rowData['flag_estimasi'] ?? '0'));
            $flagEstimasi = in_array($flagEstimasiRaw, ['1', 'true', 'TRUE'], true) ? 1 : 0;

            $sumberData = ! empty($rowData['sumber_data']) ? substr(trim((string) $rowData['sumber_data']), 0, 150) : null;

            $now = Carbon::now()->toDateTimeString();

            $batch[] = [
                'kabupaten_id' => $kabupatenId,
                'komoditas_id' => $komoditasId,
                'periode' => $periodeDate,
                'panen_kg' => $panenKg,
                'stok_awal_kg' => $stokAwalKg,
                'pasokan_masuk_kg' => $pasokanMasukKg,
                'supply_kg' => $supplyKg,
                'demand_kg' => $demandKg,
                'curah_hujan_mm' => $curahHujanMm,
                'jumlah_penduduk' => $jumlahPenduduk,
                'luas_panen_ha' => $luasPanenHa,
                'flag_estimasi' => $flagEstimasi,
                'sumber_data' => $sumberData,
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

        // 3. Susun Ringkasan Hasil
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
     * Eksekusi upsert batch data historis ke database.
     *
     * @param  array<int, array<string, mixed>>  $batch
     */
    protected function flushBatch(array $batch): int
    {
        if (empty($batch)) {
            return 0;
        }

        try {
            DB::table('data_historis')->upsert(
                $batch,
                ['kabupaten_id', 'komoditas_id', 'periode'],
                [
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
                    'updated_at',
                ]
            );

            return count($batch);
        } catch (\Throwable $e) {
            Log::error('Gagal melakukan upsert batch data_historis: '.$e->getMessage());

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
