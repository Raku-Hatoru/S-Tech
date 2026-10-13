<?php

return [
    'unit_switch_kg' => (int) env('UNIT_SWITCH_KG', 2000),
    'ambang_defisit_max' => (string) env('STATUS_DEFISIT_MAX', '0.7000'),
    'ambang_waspada_min' => (string) env('STATUS_WASPADA_MIN', '0.9000'),
    'ambang_penuh_min' => (string) env('STATUS_PENUH_MIN', '1.0000'),
    'teks_disclaimer' => 'Sistem ini bukan layanan penjualan, hanya layanan pemantauan dan tracking.',
    'pesan_dampak_surplus' => 'Pangan berisiko basi/terbuang dan menghasilkan emisi gas ke lingkungan; harga di tingkat petani dapat turun karena pasokan melimpah.',
    'pesan_dampak_defisit' => 'Risiko kekurangan pangan di wilayah ini hingga kelaparan bila tidak dipenuhi.',

    // Kata sandi akun demo — wajib diganti di production via .env
    'demo_pass_admin' => env('DEMO_PASS_ADMIN', 'Admin1234'),
    'demo_pass_bumdes' => env('DEMO_PASS_BUMDES', 'Bumdes1234'),
    'demo_pass_distributor' => env('DEMO_PASS_DISTRIBUTOR', 'Distrib1234'),
];
