<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductionNgTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ngTypes = [
            'BLACKDOT',
            'BUBLE',
            'CRACKING',
            'DENTED',
            'DIRTY',
            'DISCOLOR',
            'FLASHING',
            'FLOWMARK',
            'GASMARK',
            'PINMARK',
            'SCRATCH',
            'SHINING',
            'SHORTMOULD',
            'SINKMARK',
            'WEIGHTNG',
            'WELTLINE',
            'WHITEMARK',
            'PATAH PIN',
            'UNDERCUT',
        ];

        $now = Carbon::now();

        foreach ($ngTypes as $type) {
            DB::table('production_ng_types')->insert([
                'ng_type' => $type,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
