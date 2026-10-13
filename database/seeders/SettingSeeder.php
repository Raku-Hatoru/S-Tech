<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'kunci' => 'ambang_defisit_max',
                'nilai' => (string) config('pangan.ambang_defisit_max', '0.7000'),
                'tipe' => 'decimal',
                'keterangan' => 'Rasio pasokan/kebutuhan di bawah angka ini dinyatakan Defisit',
            ],
            [
                'kunci' => 'ambang_waspada_min',
                'nilai' => (string) config('pangan.ambang_waspada_min', '0.9000'),
                'tipe' => 'decimal',
                'keterangan' => 'Rasio pasokan/kebutuhan di atas angka ini dan di bawah penuh dinyatakan Waspada',
            ],
            [
                'kunci' => 'ambang_penuh_min',
                'nilai' => (string) config('pangan.ambang_penuh_min', '1.0000'),
                'tipe' => 'decimal',
                'keterangan' => 'Rasio pasokan/kebutuhan di atas atau sama dengan angka ini dinyatakan Penuh / Surplus',
            ],
            [
                'kunci' => 'unit_switch_kg',
                'nilai' => (string) config('pangan.unit_switch_kg', 2000),
                'tipe' => 'integer',
                'keterangan' => 'Ambang batas nilai kg ke tampilan ton pada antarmuka',
            ],
            [
                'kunci' => 'teks_disclaimer',
                'nilai' => config('pangan.teks_disclaimer', 'Sistem ini bukan layanan penjualan, hanya layanan pemantauan dan tracking.'),
                'tipe' => 'text',
                'keterangan' => 'Teks penafian resmi (disclaimer) pada banner peringatan pengiriman',
            ],
            [
                'kunci' => 'pesan_dampak_surplus',
                'nilai' => config('pangan.pesan_dampak_surplus', 'Pangan berisiko basi/terbuang dan menghasilkan emisi gas ke lingkungan; harga di tingkat petani dapat turun karena pasokan melimpah.'),
                'tipe' => 'text',
                'keterangan' => 'Penjelasan dampak risiko kelebihan pasokan bagi petani & lingkungan',
            ],
            [
                'kunci' => 'pesan_dampak_defisit',
                'nilai' => config('pangan.pesan_dampak_defisit', 'Risiko kekurangan pangan di wilayah ini hingga kelaparan bila tidak dipenuhi.'),
                'tipe' => 'text',
                'keterangan' => 'Penjelasan dampak risiko kekurangan pasokan bagi masyarakat',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::simpan(
                $setting['kunci'],
                $setting['nilai'],
                $setting['tipe'],
                $setting['keterangan']
            );
        }
    }
}
