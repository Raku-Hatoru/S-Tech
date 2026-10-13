<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataHistoris;
use App\Models\DatasetImport;
use App\Services\AuditService;
use App\Services\DataHistorisImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DatasetController extends Controller
{
    /**
     * Tampilkan halaman kelola & riwayat impor dataset historis.
     */
    public function index(): View
    {
        $imports = DatasetImport::where('tipe', 'historis')
            ->with('user')
            ->latest()
            ->paginate(15);

        $totalRecords = DataHistoris::count();
        $totalKabupaten = DataHistoris::distinct('kabupaten_id')->count('kabupaten_id');
        $minPeriode = DataHistoris::min('periode');
        $maxPeriode = DataHistoris::max('periode');

        return view('admin.dataset.index', compact(
            'imports',
            'totalRecords',
            'totalKabupaten',
            'minPeriode',
            'maxPeriode'
        ));
    }

    /**
     * Unggah berkas CSV dataset historis dan jalankan importer.
     */
    public function store(Request $request, DataHistorisImporter $importer): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:51200'],
        ], [
            'file.required' => 'Pilih berkas CSV dataset historis untuk diunggah.',
            'file.mimes' => 'Format berkas harus CSV atau teks bertanda pemisah koma (.csv).',
            'file.max' => 'Ukuran berkas maksimum adalah 50 MB.',
        ]);

        $uploadedFile = $request->file('file');
        // Sanitasi nama berkas klien (A.8.28)
        $safeOriginalName = basename(preg_replace('/[^a-zA-Z0-9_\-\. ]/', '', $uploadedFile->getClientOriginalName()));
        if (empty($safeOriginalName)) {
            $safeOriginalName = 'dataset_import_'.now()->format('Ymd_His').'.csv';
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
            AuditService::log('import_dataset', 'dataset_imports', $importLog->id, null, [
                'nama_file' => $importLog->nama_file,
                'status' => $importLog->status,
                'jumlah_berhasil' => $importLog->jumlah_berhasil,
                'jumlah_gagal' => $importLog->jumlah_gagal,
            ]);

            if ($importLog->status === 'gagal') {
                return back()->with('galat', "Impor gagal: {$importLog->ringkasan_galat}");
            }

            $pesan = "Impor dataset '{$importLog->nama_file}' berhasil diproses. "
                ."{$importLog->jumlah_berhasil} baris berhasil masuk/diperbarui.";

            if ($importLog->jumlah_gagal > 0) {
                $pesan .= " ({$importLog->jumlah_gagal} baris dilewati karena format tidak sesuai).";
            }

            return back()->with('sukses', $pesan);
        } finally {
            // ISO 27001 A.8.8 & A.8.28: Hapus berkas sementara untuk mencegah kehabisan kapasitas disk (Disk Exhaustion DoS)
            Storage::disk('local')->delete($path);
        }
    }
}
