<?php

namespace Database\Seeders;

use App\Models\Kabupaten;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder akun demo untuk kebutuhan evaluasi & video demo.
 *
 * Kata sandi dibaca dari environment / config agar tidak terlalu lemah.
 * Default lokal: lihat nilai di bawah (wajib diganti sebelum server publik).
 *
 * Seeder ini bersifat idempoten (updateOrCreate berdasarkan email).
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        // Jalankan seeder hanya bila flag SEED_DEMO_USERS bernilai true (PRD & Audit D-05)
        if (! filter_var(env('SEED_DEMO_USERS', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->command?->info('DemoUserSeeder dilewati karena flag SEED_DEMO_USERS tidak aktif (false).');

            return;
        }

        $adminPass = env('DEMO_PASS_ADMIN');
        $bumdesPass = env('DEMO_PASS_BUMDES');
        $distributorPass = env('DEMO_PASS_DISTRIBUTOR');

        if (app()->isProduction()) {
            if (empty($adminPass) || empty($bumdesPass) || empty($distributorPass)) {
                throw new \RuntimeException('Di lingkungan produksi, password akun demo wajib diset via environment variable DEMO_PASS_ADMIN, DEMO_PASS_BUMDES, dan DEMO_PASS_DISTRIBUTOR tanpa nilai fallback default.');
            }
        } else {
            $adminPass = $adminPass ?: config('pangan.demo_pass_admin', 'Admin1234');
            $bumdesPass = $bumdesPass ?: config('pangan.demo_pass_bumdes', 'Bumdes1234');
            $distributorPass = $distributorPass ?: config('pangan.demo_pass_distributor', 'Distrib1234');
        }

        $roleAdmin = Role::where('kode', 'admin')->firstOrFail();
        $roleBumdes = Role::where('kode', 'bumdes')->firstOrFail();
        $roleDistributor = Role::where('kode', 'distributor')->firstOrFail();

        // ── Admin ────────────────────────────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'admin@sipangan.test'],
            [
                'name' => 'Administrator Nasional (Demo)',
                'role_id' => $roleAdmin->id,
                'organisasi' => 'Badan Pangan Nasional',
                'telepon' => '021-12345678',
                'is_active' => true,
                'password' => Hash::make($adminPass),
            ]
        );

        // ── Distributor (2 akun) ─────────────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'distributor1@sipangan.test'],
            [
                'name' => 'Budi Santoso (Demo Distributor)',
                'role_id' => $roleDistributor->id,
                'organisasi' => 'PT Pangan Nusantara Mandiri',
                'telepon' => '081234567001',
                'is_active' => true,
                'password' => Hash::make($distributorPass),
            ]
        );

        User::updateOrCreate(
            ['email' => 'distributor2@sipangan.test'],
            [
                'name' => 'Sari Dewi (Demo Distributor)',
                'role_id' => $roleDistributor->id,
                'organisasi' => 'CV Logistik Pangan Jaya',
                'telepon' => '081234567002',
                'is_active' => true,
                'password' => Hash::make($distributorPass),
            ]
        );

        // ── BUMDes (5 akun, tiap BUMDes terikat ke 1 kabupaten) ─────────────
        $bumdesData = [
            [
                'email' => 'bumdes.banyumas@sipangan.test',
                'name' => 'Pengurus BUMDes Banyumas (Demo)',
                'organisasi' => 'BUMDes Makmur Sejahtera',
                'kode_kab' => '3302', // Banyumas, Jawa Tengah
            ],
            [
                'email' => 'bumdes.cilacap@sipangan.test',
                'name' => 'Pengurus BUMDes Cilacap (Demo)',
                'organisasi' => 'BUMDes Harapan Tani',
                'kode_kab' => '3301', // Cilacap, Jawa Tengah
            ],
            [
                'email' => 'bumdes.indramayu@sipangan.test',
                'name' => 'Pengurus BUMDes Indramayu (Demo)',
                'organisasi' => 'BUMDes Padi Emas',
                'kode_kab' => '3212', // Indramayu, Jawa Barat
            ],
            [
                'email' => 'bumdes.bogor@sipangan.test',
                'name' => 'Pengurus BUMDes Bogor (Demo)',
                'organisasi' => 'BUMDes Sawah Berkah',
                'kode_kab' => '3201', // Bogor, Jawa Barat
            ],
            [
                'email' => 'bumdes.malang@sipangan.test',
                'name' => 'Pengurus BUMDes Malang (Demo)',
                'organisasi' => 'BUMDes Tani Mandiri',
                'kode_kab' => '3507', // Malang, Jawa Timur
            ],
        ];

        foreach ($bumdesData as $data) {
            $kabupaten = Kabupaten::where('kode', $data['kode_kab'])->first();

            if (! $kabupaten) {
                // Skip jika kabupaten belum ada di master (misal WilayahSeeder belum lengkap)
                $this->command->warn("⚠  Kabupaten kode {$data['kode_kab']} tidak ditemukan, BUMDes {$data['email']} dilewati.");

                continue;
            }

            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'role_id' => $roleBumdes->id,
                    'kabupaten_id' => $kabupaten->id,
                    'organisasi' => $data['organisasi'],
                    'telepon' => '08'.rand(1000000000, 9999999999),
                    'is_active' => true,
                    'password' => Hash::make($bumdesPass),
                ]
            );
        }

        $this->command->info('✅  DemoUserSeeder selesai: 1 admin, 2 distributor, '.count($bumdesData).' BUMDes (lewati yang kabupatennya tidak ada).');
    }
}
