<?php

namespace Database\Seeders;

use App\Models\Kabupaten;
use App\Models\Komoditas;
use App\Models\PlantingRecommendation;
use Illuminate\Database\Seeder;

class PlantingRecommendationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banyumas = Kabupaten::where('kode', '3302')->first();
        if (! $banyumas) {
            return;
        }

        $beras = Komoditas::where('kode', 'beras')->first() ?? Komoditas::first();
        $jagung = Komoditas::where('kode', 'jagung')->first() ?? $beras;
        $singkong = Komoditas::where('kode', 'singkong')->first() ?? $beras;

        $items = [
            [
                'kabupaten_id' => $banyumas->id,
                'komoditas_id' => $beras->id,
                'bulan_tanam' => 4, // April
                'bulan_panen' => 7, // Juli
                'catatan' => 'Tunda penanaman serentak di blok selatan untuk seimbangkan pasokan harian.',
                'is_dummy' => true,
            ],
            [
                'kabupaten_id' => $banyumas->id,
                'komoditas_id' => $jagung->id,
                'bulan_tanam' => 5, // Mei
                'bulan_panen' => 8, // Agustus
                'catatan' => 'Rotasi tanaman lahan kering pasca panen padi gadu untuk memutus siklus hama.',
                'is_dummy' => true,
            ],
            [
                'kabupaten_id' => $banyumas->id,
                'komoditas_id' => $singkong->id,
                'bulan_tanam' => 3, // Maret
                'bulan_panen' => 11, // November
                'catatan' => 'Penguatan cadangan pangan akhir tahun dan penyangga bahan olahan tapioka desa.',
                'is_dummy' => true,
            ],
        ];

        foreach ($items as $data) {
            PlantingRecommendation::updateOrCreate(
                [
                    'kabupaten_id' => $data['kabupaten_id'],
                    'komoditas_id' => $data['komoditas_id'],
                    'bulan_tanam' => $data['bulan_tanam'],
                ],
                $data
            );
        }
    }
}
