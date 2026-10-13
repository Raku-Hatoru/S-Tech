<?php

namespace Database\Seeders;

use App\Models\Komoditas;
use Illuminate\Database\Seeder;

class KomoditasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $komoditas = [
            [
                'kode' => 'beras',
                'nama' => 'Beras (Bulog / Medium / Premium)',
            ],
            [
                'kode' => 'jagung',
                'nama' => 'Jagung Pipil Kering',
            ],
            [
                'kode' => 'singkong',
                'nama' => 'Singkong / Ubi Kayu',
            ],
        ];

        foreach ($komoditas as $item) {
            Komoditas::updateOrCreate(
                ['kode' => $item['kode']],
                ['nama' => $item['nama']]
            );
        }
    }
}
