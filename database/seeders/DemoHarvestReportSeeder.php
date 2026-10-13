<?php

namespace Database\Seeders;

use App\Models\HarvestReport;
use App\Models\Kabupaten;
use App\Models\Komoditas;
use App\Models\User;
use App\Services\StatusService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoHarvestReportSeeder extends Seeder
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

        $bumdesUser = User::where('email', 'bumdes.banyumas@sipangan.test')->first() ?? User::where('kabupaten_id', $banyumas->id)->first();
        if (! $bumdesUser) {
            return;
        }

        $beras = Komoditas::where('kode', 'beras')->first() ?? Komoditas::first();

        $laporan = [
            [
                'periode' => '2025-11-01',
                'panen_kg' => 12450000,
                'sisa_stok_kg' => 2650000,
                'tanggal_survei' => '2025-11-30',
                'catatan' => 'Survei pasca panen MT-3 gabungan Gapoktan Banyumas',
            ],
            [
                'periode' => '2025-12-01',
                'panen_kg' => 13900000,
                'sisa_stok_kg' => 2800000,
                'tanggal_survei' => '2025-12-31',
                'catatan' => 'Survei akhir kuartal IV lumbung Banyumas',
            ],
            [
                'periode' => '2026-01-01',
                'panen_kg' => 15200000,
                'sisa_stok_kg' => 3100000,
                'tanggal_survei' => '2026-01-31',
                'catatan' => 'Survei gabungan 12 lumbung desa BUMDes Banyumas',
            ],
        ];

        $statusService = app(StatusService::class);

        foreach ($laporan as $item) {
            HarvestReport::updateOrCreate(
                [
                    'kabupaten_id' => $banyumas->id,
                    'komoditas_id' => $beras->id,
                    'periode' => $item['periode'],
                ],
                [
                    'user_id' => $bumdesUser->id,
                    'panen_kg' => $item['panen_kg'],
                    'sisa_stok_kg' => $item['sisa_stok_kg'],
                    'tanggal_survei' => $item['tanggal_survei'],
                    'catatan' => $item['catatan'],
                ]
            );

            // Recalculate status
            $statusService->recalc($banyumas->id, $beras->id, $item['periode']);
            $nextP = Carbon::parse($item['periode'])->addMonth()->format('Y-m-01');
            $statusService->recalc($banyumas->id, $beras->id, $nextP);
        }
    }
}
