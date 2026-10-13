<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'kode' => 'distributor',
                'nama' => 'Distributor Logistik Pangan',
            ],
            [
                'kode' => 'bumdes',
                'nama' => 'Pengurus BUMDes Wilayah',
            ],
            [
                'kode' => 'admin',
                'nama' => 'Administrator Nasional',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['kode' => $role['kode']],
                ['nama' => $role['nama']]
            );
        }
    }
}
