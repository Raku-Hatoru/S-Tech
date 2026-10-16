<?php

namespace App\Http\Controllers;

use App\Models\Kabupaten;
use App\Models\PlantingRecommendation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlantingController extends Controller
{
    /**
     * Tampilkan Halaman Rekomendasi Jadwal Tanam (PRD F-10 • Dummy / Simulasi).
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // 1. Tentukan Kabupaten (BUMDes dikunci ke kabupaten akunnya, Admin bebas pilih)
        if ($user->role?->kode === 'bumdes') {
            $kabupaten = $user->kabupaten ?? Kabupaten::where('kode', '3302')->firstOrFail();
            $semuaKabupaten = collect([$kabupaten]);
        } else {
            $kabupatenId = $request->input('kabupaten_id', $user->kabupaten_id);
            $kabupaten = $kabupatenId ? Kabupaten::find($kabupatenId) : Kabupaten::where('kode', '3302')->first();
            if (! $kabupaten) {
                $kabupaten = Kabupaten::firstOrFail();
            }
            $semuaKabupaten = Kabupaten::orderBy('nama')->get();
        }

        // 2. Ambil Rekomendasi Tanam dari Database
        $recommendations = PlantingRecommendation::with('komoditas')
            ->where('kabupaten_id', $kabupaten->id)
            ->get();

        // Nama-nama bulan Indonesia
        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        // 3. Jika belum ada di database, gunakan data simulasi standar sesuai mockup desain
        if ($recommendations->isEmpty()) {
            $recommendationsList = collect([
                (object) [
                    'komoditas_label' => 'Beras (MT-2)',
                    'varietas' => 'Varietas Inpari 32',
                    'waktu_tanam' => 'April 2026',
                    'perkiraan_panen' => 'Juli 2026',
                    'proyeksi_kebutuhan' => '17.500 ton',
                    'curah_hujan' => '180 mm (Mendukung)',
                    'catatan_agronomi' => 'Tunda penanaman serentak di blok selatan untuk seimbangkan pasokan harian.',
                ],
                (object) [
                    'komoditas_label' => 'Jagung',
                    'varietas' => 'Varietas Hibrida Bisi 18',
                    'waktu_tanam' => 'Mei 2026',
                    'perkiraan_panen' => 'Agustus 2026',
                    'proyeksi_kebutuhan' => '8.200 ton',
                    'curah_hujan' => '110 mm',
                    'catatan_agronomi' => 'Rotasi tanaman lahan kering pasca panen padi gadu untuk memutus siklus hama.',
                ],
                (object) [
                    'komoditas_label' => 'Singkong',
                    'varietas' => 'Varietas Manggu',
                    'waktu_tanam' => 'Maret 2026',
                    'perkiraan_panen' => 'November 2026',
                    'proyeksi_kebutuhan' => '4.100 ton',
                    'curah_hujan' => '220 mm',
                    'catatan_agronomi' => 'Penguatan cadangan pangan akhir tahun dan penyangga bahan olahan tapioka desa.',
                ],
            ]);
        } else {
            $recommendationsList = $recommendations->map(function ($rec) use ($namaBulan) {
                return (object) [
                    'komoditas_label' => ($rec->komoditas && $rec->komoditas->kode === 'beras') ? 'Beras (MT-2)' : ($rec->komoditas->nama ?? 'Komoditas'),
                    'varietas' => 'Varietas Unggul Lokal',
                    'waktu_tanam' => ($namaBulan[$rec->bulan_tanam] ?? 'Bulan '.$rec->bulan_tanam).' 2026',
                    'perkiraan_panen' => ($namaBulan[$rec->bulan_panen] ?? 'Bulan '.$rec->bulan_panen).' 2026',
                    'proyeksi_kebutuhan' => '12.000 ton',
                    'curah_hujan' => '160 mm (Normal)',
                    'catatan_agronomi' => $rec->catatan ?? 'Penyesuaian pola tanam sesuai kalender musim.',
                ];
            });
        }

        // 4. Susun Pesan Edaran WhatsApp Resmi untuk Gapoktan
        $waText = "📢 *EDARAN REKOMENDASI JADWAL TANAM PETANI* 📢\n"
            ."Wilayah: Kabupaten {$kabupaten->nama}\n"
            ."BUMDes Pangan Mitra Gapoktan\n"
            ."Status: [SIMULASI / DATA CONTOH SISTEM KETAHANAN PANGAN]\n\n"
            ."Guna mencegah penumpukan panen raya berlebih (spoilage shock) dan menjaga keseimbangan pasokan gabah di tingkat petani, disarankan rekomendasi jadwal tanam berikut:\n\n";

        foreach ($recommendationsList as $idx => $item) {
            $waText .= ($idx + 1).". *{$item->komoditas_label}* ({$item->varietas})\n"
                ."   • Waktu Tanam: {$item->waktu_tanam}\n"
                ."   • Perkiraan Panen: {$item->perkiraan_panen}\n"
                ."   • Proyeksi Kebutuhan: {$item->proyeksi_kebutuhan}\n"
                ."   • Catatan: {$item->catatan_agronomi}\n\n";
        }

        $waText .= "Sumber: SIPANGAN WASPADA - BUMDes {$kabupaten->nama}";
        $waLink = 'https://api.whatsapp.com/send?text='.urlencode($waText);

        return view('jadwal-tanam.index', compact(
            'kabupaten',
            'semuaKabupaten',
            'recommendationsList',
            'waLink'
        ));
    }
}
