<?php

namespace Database\Seeders;

use App\Enums\ShipmentStatus;
use App\Enums\StatusLevel;
use App\Models\Kabupaten;
use App\Models\Komoditas;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoShipmentSeeder extends Seeder
{
    public function run(): void
    {
        $distributor = User::where('email', 'distributor1@sipangan.test')->first()
            ?? User::whereHas('role', fn ($q) => $q->where('kode', 'distributor'))->first();

        if (! $distributor) {
            return;
        }

        $beras = Komoditas::where('kode', 'beras')->first() ?? Komoditas::first();
        $cilacap = Kabupaten::where('kode', '3301')->first();
        $banyumas = Kabupaten::where('kode', '3302')->first();
        $tegal = Kabupaten::where('kode', '3328')->first() ?? Kabupaten::where('kode', '3304')->first();

        if (! $beras || ! $cilacap || ! $banyumas) {
            return;
        }

        // 1. Showcase Shipment (Cilacap -> Banyumas, Resi RESI-CONTOH-0001)
        $shipment1 = Shipment::updateOrCreate(
            ['no_resi' => 'RESI-CONTOH-0001'],
            [
                'user_id' => $distributor->id,
                'komoditas_id' => $beras->id,
                'jumlah_kg' => 2500, // 2,5 ton
                'asal_kabupaten_id' => $cilacap->id,
                'tujuan_kabupaten_id' => $banyumas->id,
                'tanggal_kirim' => Carbon::now()->subDays(2)->format('Y-m-d'),
                'estimasi_tiba' => Carbon::now()->addDay()->format('Y-m-d'),
                'status' => ShipmentStatus::DalamPerjalanan,
                'level_peringatan' => StatusLevel::Penuh,
                'rasio_tujuan' => 1.0511,
                'konfirmasi_peringatan' => true,
                'catatan' => 'Muatan beras medium rantai logistik PT Pangan Nusantara Mandiri menuju Gudang Tambak Banyumas.',
            ]
        );

        // Events Riwayat untuk RESI-CONTOH-0001
        ShipmentEvent::firstOrCreate(
            ['shipment_id' => $shipment1->id, 'status' => ShipmentStatus::Dibuat->value],
            [
                'lokasi' => 'Pusat Dispatch Cilacap',
                'keterangan' => 'Pengiriman didaftarkan di sistem SIPANGAN WASPADA.',
                'waktu_kejadian' => Carbon::now()->subDays(2)->setHour(8)->setMinute(30),
                'dicatat_oleh' => $distributor->id,
            ]
        );

        ShipmentEvent::firstOrCreate(
            ['shipment_id' => $shipment1->id, 'status' => ShipmentStatus::DiterimaDiAsal->value],
            [
                'lokasi' => 'Gudang Penyangga Cilacap',
                'keterangan' => 'Barang diterima dan dimuat ke Truk Tronton #08.',
                'waktu_kejadian' => Carbon::now()->subDays(1)->setHour(10)->setMinute(15),
                'dicatat_oleh' => $distributor->id,
            ]
        );

        ShipmentEvent::firstOrCreate(
            ['shipment_id' => $shipment1->id, 'status' => ShipmentStatus::Diproses->value],
            [
                'lokasi' => 'Hub Transit Logistik Purwojati',
                'keterangan' => 'Inspeksi timbang dan verifikasi surat jalan karantina komoditas beras.',
                'waktu_kejadian' => Carbon::now()->subHours(14)->setMinute(0),
                'dicatat_oleh' => $distributor->id,
            ]
        );

        ShipmentEvent::firstOrCreate(
            ['shipment_id' => $shipment1->id, 'status' => ShipmentStatus::DalamPerjalanan->value],
            [
                'lokasi' => 'Jalur Arteri Selatan (Menuju Tambak Banyumas)',
                'keterangan' => 'Armada sedang melaju via Jalur Arteri Selatan dalam kondisi lancar.',
                'waktu_kejadian' => Carbon::now()->subHours(3)->setMinute(20),
                'dicatat_oleh' => $distributor->id,
            ]
        );

        // 2. Shipment Tambahan (Cilacap -> Tegal / Defisit)
        if ($tegal) {
            $shipment2 = Shipment::updateOrCreate(
                ['no_resi' => 'RESI-2026-TGL01'],
                [
                    'user_id' => $distributor->id,
                    'komoditas_id' => $beras->id,
                    'jumlah_kg' => 3500, // 3,5 ton
                    'asal_kabupaten_id' => $cilacap->id,
                    'tujuan_kabupaten_id' => $tegal->id,
                    'tanggal_kirim' => Carbon::now()->subDays(1)->format('Y-m-d'),
                    'estimasi_tiba' => Carbon::now()->addDays(2)->format('Y-m-d'),
                    'status' => ShipmentStatus::Diproses,
                    'level_peringatan' => StatusLevel::Defisit,
                    'rasio_tujuan' => 0.6500,
                    'konfirmasi_peringatan' => false,
                    'catatan' => 'Pasokan stabilisasi harga wilayah defisit.',
                ]
            );

            ShipmentEvent::firstOrCreate(
                ['shipment_id' => $shipment2->id, 'status' => ShipmentStatus::Dibuat->value],
                [
                    'lokasi' => 'Sub-Depo Cilacap',
                    'keterangan' => 'Manifest terbit untuk wilayah defisit prioritas.',
                    'waktu_kejadian' => Carbon::now()->subDays(1)->setHour(14),
                    'dicatat_oleh' => $distributor->id,
                ]
            );
        }
    }
}
