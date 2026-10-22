<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            [
                'code' => 'JKT',
                'name' => 'Jakarta Plant',
                'address' => 'Kawasan Berikat Nusantara (KBN), Jakarta',
                'is_main' => true,
                'is_active' => true,
            ],
            [
                'code' => 'KRW',
                'name' => 'Karawang Plant',
                'address' => 'Karawang, Jawa Barat',
                'is_main' => false,
                'is_active' => true,
            ],
        ];

        foreach ($branches as $branch) {
            Branch::updateOrCreate(
                ['code' => $branch['code']],
                $branch
            );
        }
    }
}
