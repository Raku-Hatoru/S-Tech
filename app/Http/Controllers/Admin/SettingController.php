<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatasetImport;
use App\Models\ModelVersion;
use App\Models\Setting;
use App\Services\AuditService;
use App\Services\StatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * Tampilkan Pengaturan Sistem, Ambang Batas, Disclaimer, dan Riwayat Impor.
     */
    public function index(): View
    {
        $settings = [
            'ambang_defisit_max' => (float) Setting::nilai('ambang_defisit_max', 0.7000),
            'ambang_waspada_min' => (float) Setting::nilai('ambang_waspada_min', 0.9000),
            'ambang_penuh_min' => (float) Setting::nilai('ambang_penuh_min', 1.0000),
            'unit_switch_kg' => (int) Setting::nilai('unit_switch_kg', 2000),
            'teks_disclaimer' => Setting::nilai('teks_disclaimer', 'Sistem ini bukan layanan penjualan, hanya layanan pemantauan dan tracking.'),
            'pesan_dampak_surplus' => Setting::nilai('pesan_dampak_surplus', 'Pangan berisiko basi/terbuang dan menghasilkan emisi gas ke lingkungan; harga di tingkat petani dapat turun karena pasokan melimpah.'),
            'pesan_dampak_defisit' => Setting::nilai('pesan_dampak_defisit', 'Risiko kekurangan pangan di wilayah ini hingga kelaparan bila tidak dipenuhi.'),
        ];

        $modelVersions = ModelVersion::orderByDesc('is_aktif')->orderByDesc('id')->get();
        $recentImports = DatasetImport::with('user')->orderByDesc('id')->take(10)->get();

        return view('admin.pengaturan.index', compact('settings', 'modelVersions', 'recentImports'));
    }

    /**
     * Perbarui Ambang Batas dan Teks Sistem, lalu picu kalkulasi ulang status wilayah.
     */
    public function update(Request $request, StatusService $statusService): RedirectResponse
    {
        $validated = $request->validate([
            'ambang_defisit_max' => 'required|numeric|min:0.01|max:2.0',
            'ambang_waspada_min' => 'required|numeric|min:0.01|max:2.0|gt:ambang_defisit_max',
            'ambang_penuh_min' => 'required|numeric|min:0.01|max:2.0|gt:ambang_waspada_min',
            'unit_switch_kg' => 'required|integer|min:100|max:100000',
            'teks_disclaimer' => 'required|string|max:1000',
            'pesan_dampak_surplus' => 'required|string|max:1000',
            'pesan_dampak_defisit' => 'required|string|max:1000',
        ], [
            'ambang_waspada_min.gt' => 'Ambang Waspada harus lebih besar dari Ambang Defisit.',
            'ambang_penuh_min.gt' => 'Ambang Penuh harus lebih besar dari Ambang Waspada.',
        ]);

        $teksDisclaimer = strip_tags($validated['teks_disclaimer']);
        $pesanSurplus = strip_tags($validated['pesan_dampak_surplus']);
        $pesanDefisit = strip_tags($validated['pesan_dampak_defisit']);

        Setting::simpan('ambang_defisit_max', $validated['ambang_defisit_max'], 'decimal', 'Rasio < ini => Defisit');
        Setting::simpan('ambang_waspada_min', $validated['ambang_waspada_min'], 'decimal', 'Rasio >= ini dan < penuh => Waspada');
        Setting::simpan('ambang_penuh_min', $validated['ambang_penuh_min'], 'decimal', 'Rasio >= ini => Penuh');
        Setting::simpan('unit_switch_kg', $validated['unit_switch_kg'], 'integer', 'Batas tampilan kg => ton');
        Setting::simpan('teks_disclaimer', $teksDisclaimer, 'text', 'Muncul pada banner pengiriman');
        Setting::simpan('pesan_dampak_surplus', $pesanSurplus, 'text', 'Pesan dampak saat wilayah surplus');
        Setting::simpan('pesan_dampak_defisit', $pesanDefisit, 'text', 'Pesan dampak saat wilayah defisit');

        // Audit Logging (ISO 27001 A.8.15)
        AuditService::log('update_settings', 'settings', null, null, [
            'ambang_defisit_max' => $validated['ambang_defisit_max'],
            'ambang_waspada_min' => $validated['ambang_waspada_min'],
            'ambang_penuh_min' => $validated['ambang_penuh_min'],
            'unit_switch_kg' => $validated['unit_switch_kg'],
        ]);

        // Hitung ulang semua wilayah dengan ambang baru
        $totalDihitung = $statusService->recalcSemua();

        return redirect()->route('admin.pengaturan.index')
            ->with('sukses', "Pengaturan dan ambang batas berhasil diperbarui. Status wilayah telah dihitung ulang secara otomatis ({$totalDihitung} data poin diperbarui).");
    }
}
