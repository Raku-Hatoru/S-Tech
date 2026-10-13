<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatasetImport;
use App\Models\Forecast;
use App\Models\ModelVersion;
use App\Services\AuditService;
use App\Services\ForecastImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ForecastController extends Controller
{
    /**
     * Tampilkan halaman kelola model & riwayat impor forecast prediksi.
     */
    public function index(): View
    {
        $imports = DatasetImport::where('tipe', 'forecast')
            ->with('user')
            ->latest()
            ->paginate(15);

        $totalForecasts = Forecast::count();
        $minTarget = Forecast::min('periode_target');
        $maxTarget = Forecast::max('periode_target');
        $modelVersions = ModelVersion::withCount('forecasts')->latest()->get();
        $activeVersion = ModelVersion::where('is_aktif', true)->first();

        return view('admin.forecast.index', compact(
            'imports',
            'totalForecasts',
            'minTarget',
            'maxTarget',
            'modelVersions',
            'activeVersion'
        ));
    }

    /**
     * Unggah berkas CSV hasil prediksi XGBoost dan jalankan importer.
     */
    public function store(Request $request, ForecastImporter $importer): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:51200'],
        ], [
            'file.required' => 'Pilih berkas CSV hasil prediksi untuk diunggah.',
            'file.mimes' => 'Format berkas harus CSV (.csv).',
            'file.max' => 'Ukuran berkas maksimum adalah 50 MB.',
        ]);

        $uploadedFile = $request->file('file');
        // Sanitasi nama berkas klien (A.8.28)
        $safeOriginalName = basename(preg_replace('/[^a-zA-Z0-9_\-\. ]/', '', $uploadedFile->getClientOriginalName()));
        if (empty($safeOriginalName)) {
            $safeOriginalName = 'forecast_import_'.now()->format('Ymd_His').'.csv';
        }

        $path = $uploadedFile->store('imports');
        $fullPath = Storage::disk('local')->path($path);

        try {
            $importLog = $importer->import(
                $fullPath,
                auth()->id(),
                $safeOriginalName
            );

            // Audit Logging (ISO 27001 A.8.15)
            AuditService::log('import_forecast', 'dataset_imports', $importLog->id, null, [
                'nama_file' => $importLog->nama_file,
                'status' => $importLog->status,
                'jumlah_berhasil' => $importLog->jumlah_berhasil,
                'jumlah_gagal' => $importLog->jumlah_gagal,
            ]);

            if ($importLog->status === 'gagal') {
                return back()->with('galat', "Impor forecast gagal: {$importLog->ringkasan_galat}");
            }

            $pesan = "Impor forecast '{$importLog->nama_file}' berhasil diproses. "
                ."{$importLog->jumlah_berhasil} baris prediksi berhasil disimpan/diperbarui.";

            if ($importLog->jumlah_gagal > 0) {
                $pesan .= " ({$importLog->jumlah_gagal} baris dilewati karena format tidak sesuai).";
            }

            return back()->with('sukses', $pesan);
        } finally {
            // ISO 27001 A.8.8 & A.8.28: Hapus berkas sementara untuk mencegah kehabisan kapasitas disk
            Storage::disk('local')->delete($path);
        }
    }

    /**
     * Aktifkan versi model prediksi tertentu.
     */
    public function toggleModelVersion(ModelVersion $modelVersion): RedirectResponse
    {
        $oldActive = $modelVersion->is_aktif;

        // Nonaktifkan versi lain
        ModelVersion::where('id', '<>', $modelVersion->id)->update(['is_aktif' => false]);

        $modelVersion->update(['is_aktif' => ! $modelVersion->is_aktif]);

        // Audit Logging (ISO 27001 A.8.15)
        AuditService::log('toggle_model_version', 'model_versions', $modelVersion->id, [
            'is_aktif' => $oldActive,
        ], [
            'is_aktif' => $modelVersion->is_aktif,
            'nama' => $modelVersion->nama,
        ]);

        $pesan = $modelVersion->is_aktif
            ? "Versi model '{$modelVersion->nama}' kini diaktifkan sebagai model default tampilan publik."
            : "Versi model '{$modelVersion->nama}' dinonaktifkan.";

        return back()->with('sukses', $pesan);
    }
}
