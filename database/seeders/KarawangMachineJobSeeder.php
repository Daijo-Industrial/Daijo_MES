<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KarawangMachineJobSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timestamp = Carbon::now();

        $data = [];
        for ($id = 72; $id <= 83; $id++) {
            $data[] = [
                'user_id' => $id,
                'item_code' => null,       // bisa diisi kalau sudah ada mapping
                'shift' => null,
                'employee_name' => null,
            ];
        }

        DB::table('machine_jobs')->insert($data);
    }
}
