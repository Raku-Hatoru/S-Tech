<?php

namespace Database\Seeders;

use App\Models\Kabupaten;
use App\Models\Provinsi;
use Illuminate\Database\Seeder;

class WilayahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Data Master Provinsi (38 Provinsi Indonesia)
        $provinsiData = [
            ['kode' => '11', 'nama' => 'Aceh'],
            ['kode' => '12', 'nama' => 'Sumatera Utara'],
            ['kode' => '13', 'nama' => 'Sumatera Barat'],
            ['kode' => '14', 'nama' => 'Riau'],
            ['kode' => '15', 'nama' => 'Jambi'],
            ['kode' => '16', 'nama' => 'Sumatera Selatan'],
            ['kode' => '17', 'nama' => 'Bengkulu'],
            ['kode' => '18', 'nama' => 'Lampung'],
            ['kode' => '19', 'nama' => 'Kepulauan Bangka Belitung'],
            ['kode' => '21', 'nama' => 'Kepulauan Riau'],
            ['kode' => '31', 'nama' => 'DKI Jakarta'],
            ['kode' => '32', 'nama' => 'Jawa Barat'],
            ['kode' => '33', 'nama' => 'Jawa Tengah'],
            ['kode' => '34', 'nama' => 'DI Yogyakarta'],
            ['kode' => '35', 'nama' => 'Jawa Timur'],
            ['kode' => '36', 'nama' => 'Banten'],
            ['kode' => '51', 'nama' => 'Bali'],
            ['kode' => '52', 'nama' => 'Nusa Tenggara Barat'],
            ['kode' => '53', 'nama' => 'Nusa Tenggara Timur'],
            ['kode' => '61', 'nama' => 'Kalimantan Barat'],
            ['kode' => '62', 'nama' => 'Kalimantan Tengah'],
            ['kode' => '63', 'nama' => 'Kalimantan Selatan'],
            ['kode' => '64', 'nama' => 'Kalimantan Timur'],
            ['kode' => '65', 'nama' => 'Kalimantan Utara'],
            ['kode' => '71', 'nama' => 'Sulawesi Utara'],
            ['kode' => '72', 'nama' => 'Sulawesi Tengah'],
            ['kode' => '73', 'nama' => 'Sulawesi Selatan'],
            ['kode' => '74', 'nama' => 'Sulawesi Tenggara'],
            ['kode' => '75', 'nama' => 'Gorontalo'],
            ['kode' => '76', 'nama' => 'Sulawesi Barat'],
            ['kode' => '81', 'nama' => 'Maluku'],
            ['kode' => '82', 'nama' => 'Maluku Utara'],
            ['kode' => '91', 'nama' => 'Papua Barat'],
            ['kode' => '92', 'nama' => 'Papua Barat Daya'],
            ['kode' => '93', 'nama' => 'Papua Selatan'],
            ['kode' => '94', 'nama' => 'Papua'],
            ['kode' => '95', 'nama' => 'Papua Tengah'],
            ['kode' => '96', 'nama' => 'Papua Pegunungan'],
        ];

        $provinsiMap = [];
        foreach ($provinsiData as $p) {
            $model = Provinsi::updateOrCreate(
                ['kode' => $p['kode']],
                ['nama' => $p['nama']]
            );
            $provinsiMap[$p['kode']] = $model->id;
        }

        // 2. Data Master Kabupaten/Kota Prioritas & Percontohan Nasional
        $kabupatenData = [
            // Jawa Tengah (33)
            ['provinsi_kode' => '33', 'kode' => '3301', 'nama' => 'Cilacap', 'tipe' => 'kabupaten', 'lat' => -7.5574, 'lng' => 109.0069, 'penduduk' => 1980000],
            ['provinsi_kode' => '33', 'kode' => '3302', 'nama' => 'Banyumas', 'tipe' => 'kabupaten', 'lat' => -7.4552, 'lng' => 109.1670, 'penduduk' => 1820000],
            ['provinsi_kode' => '33', 'kode' => '3303', 'nama' => 'Purbalingga', 'tipe' => 'kabupaten', 'lat' => -7.3188, 'lng' => 109.3497, 'penduduk' => 1020000],
            ['provinsi_kode' => '33', 'kode' => '3304', 'nama' => 'Banjarnegara', 'tipe' => 'kabupaten', 'lat' => -7.3619, 'lng' => 109.6749, 'penduduk' => 1040000],
            ['provinsi_kode' => '33', 'kode' => '3305', 'nama' => 'Kebumen', 'tipe' => 'kabupaten', 'lat' => -7.6698, 'lng' => 109.6517, 'penduduk' => 1380000],
            ['provinsi_kode' => '33', 'kode' => '3328', 'nama' => 'Tegal', 'tipe' => 'kabupaten', 'lat' => -7.0210, 'lng' => 109.1666, 'penduduk' => 1630000],
            ['provinsi_kode' => '33', 'kode' => '3329', 'nama' => 'Brebes', 'tipe' => 'kabupaten', 'lat' => -6.9833, 'lng' => 108.9667, 'penduduk' => 2010000],
            ['provinsi_kode' => '33', 'kode' => '3374', 'nama' => 'Semarang', 'tipe' => 'kota', 'lat' => -7.0051, 'lng' => 110.4381, 'penduduk' => 1680000],
            ['provinsi_kode' => '33', 'kode' => '3372', 'nama' => 'Surakarta', 'tipe' => 'kota', 'lat' => -7.5755, 'lng' => 110.8243, 'penduduk' => 522000],

            // Jawa Barat (32)
            ['provinsi_kode' => '32', 'kode' => '3201', 'nama' => 'Bogor', 'tipe' => 'kabupaten', 'lat' => -6.5518, 'lng' => 106.6291, 'penduduk' => 5427000],
            ['provinsi_kode' => '32', 'kode' => '3204', 'nama' => 'Bandung', 'tipe' => 'kabupaten', 'lat' => -7.0253, 'lng' => 107.5198, 'penduduk' => 3666000],
            ['provinsi_kode' => '32', 'kode' => '3212', 'nama' => 'Indramayu', 'tipe' => 'kabupaten', 'lat' => -6.4526, 'lng' => 108.2865, 'penduduk' => 1890000],
            ['provinsi_kode' => '32', 'kode' => '3215', 'nama' => 'Karawang', 'tipe' => 'kabupaten', 'lat' => -6.3227, 'lng' => 107.3376, 'penduduk' => 2480000],
            ['provinsi_kode' => '32', 'kode' => '3273', 'nama' => 'Bandung', 'tipe' => 'kota', 'lat' => -6.9175, 'lng' => 107.6191, 'penduduk' => 2450000],

            // DKI Jakarta (31)
            ['provinsi_kode' => '31', 'kode' => '3171', 'nama' => 'Jakarta Pusat', 'tipe' => 'kota', 'lat' => -6.1805, 'lng' => 106.8284, 'penduduk' => 1060000],
            ['provinsi_kode' => '31', 'kode' => '3173', 'nama' => 'Jakarta Barat', 'tipe' => 'kota', 'lat' => -6.1683, 'lng' => 106.7589, 'penduduk' => 2430000],
            ['provinsi_kode' => '31', 'kode' => '3174', 'nama' => 'Jakarta Selatan', 'tipe' => 'kota', 'lat' => -6.2615, 'lng' => 106.8106, 'penduduk' => 2230000],

            // Jawa Timur (35)
            ['provinsi_kode' => '35', 'kode' => '3507', 'nama' => 'Malang', 'tipe' => 'kabupaten', 'lat' => -8.1667, 'lng' => 112.6333, 'penduduk' => 2650000],
            ['provinsi_kode' => '35', 'kode' => '3509', 'nama' => 'Jember', 'tipe' => 'kabupaten', 'lat' => -8.2435, 'lng' => 113.6393, 'penduduk' => 2580000],
            ['provinsi_kode' => '35', 'kode' => '3510', 'nama' => 'Banyuwangi', 'tipe' => 'kabupaten', 'lat' => -8.3414, 'lng' => 114.2866, 'penduduk' => 1730000],
            ['provinsi_kode' => '35', 'kode' => '3522', 'nama' => 'Bojonegoro', 'tipe' => 'kabupaten', 'lat' => -7.2471, 'lng' => 111.7822, 'penduduk' => 1340000],
            ['provinsi_kode' => '35', 'kode' => '3524', 'nama' => 'Lamongan', 'tipe' => 'kabupaten', 'lat' => -7.1352, 'lng' => 112.3135, 'penduduk' => 1360000],
            ['provinsi_kode' => '35', 'kode' => '3578', 'nama' => 'Surabaya', 'tipe' => 'kota', 'lat' => -7.2575, 'lng' => 112.7521, 'penduduk' => 2880000],

            // DI Yogyakarta (34)
            ['provinsi_kode' => '34', 'kode' => '3404', 'nama' => 'Sleman', 'tipe' => 'kabupaten', 'lat' => -7.6896, 'lng' => 110.3429, 'penduduk' => 1220000],
            ['provinsi_kode' => '34', 'kode' => '3471', 'nama' => 'Yogyakarta', 'tipe' => 'kota', 'lat' => -7.7956, 'lng' => 110.3695, 'penduduk' => 373000],

            // Sumatera Utara (12)
            ['provinsi_kode' => '12', 'kode' => '1207', 'nama' => 'Deli Serdang', 'tipe' => 'kabupaten', 'lat' => 3.4217, 'lng' => 98.6853, 'penduduk' => 1930000],
            ['provinsi_kode' => '12', 'kode' => '1271', 'nama' => 'Medan', 'tipe' => 'kota', 'lat' => 3.5952, 'lng' => 98.6722, 'penduduk' => 2460000],

            // Nusa Tenggara Timur (53)
            ['provinsi_kode' => '53', 'kode' => '5311', 'nama' => 'Sumba Timur', 'tipe' => 'kabupaten', 'lat' => -9.8465, 'lng' => 120.2520, 'penduduk' => 260000],
            ['provinsi_kode' => '53', 'kode' => '5371', 'nama' => 'Kupang', 'tipe' => 'kota', 'lat' => -10.1772, 'lng' => 123.6070, 'penduduk' => 442000],

            // Papua / Papua Selatan (93/94)
            ['provinsi_kode' => '94', 'kode' => '9401', 'nama' => 'Merauke', 'tipe' => 'kabupaten', 'lat' => -8.4991, 'lng' => 140.4011, 'penduduk' => 231000],
            ['provinsi_kode' => '94', 'kode' => '9471', 'nama' => 'Jayapura', 'tipe' => 'kota', 'lat' => -2.5916, 'lng' => 140.6690, 'penduduk' => 362000],

            // Sulawesi Selatan (73)
            ['provinsi_kode' => '73', 'kode' => '7308', 'nama' => 'Bone', 'tipe' => 'kabupaten', 'lat' => -4.6852, 'lng' => 120.1554, 'penduduk' => 801000],
            ['provinsi_kode' => '73', 'kode' => '7371', 'nama' => 'Makassar', 'tipe' => 'kota', 'lat' => -5.1477, 'lng' => 119.4327, 'penduduk' => 1430000],

            // Lampung (18)
            ['provinsi_kode' => '18', 'kode' => '1802', 'nama' => 'Lampung Tengah', 'tipe' => 'kabupaten', 'lat' => -4.8694, 'lng' => 105.2667, 'penduduk' => 1470000],
        ];

        foreach ($kabupatenData as $k) {
            Kabupaten::updateOrCreate(
                ['kode' => $k['kode']],
                [
                    'provinsi_id' => $provinsiMap[$k['provinsi_kode']],
                    'nama' => $k['nama'],
                    'tipe' => $k['tipe'],
                    'lat' => $k['lat'],
                    'lng' => $k['lng'],
                    'jumlah_penduduk' => $k['penduduk'],
                ]
            );
        }
    }
}
