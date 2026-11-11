<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Role::truncate();
        Schema::enableForeignKeyConstraints();
        Role::create(['name' => 'SUPER-ADMIN']);
        Role::create(['name' => 'ADMIN']);
        Role::create(['name' => 'WORKSHOP']);
        Role::create(['name' => 'WAREHOUSE']);
        Role::create(['name' => 'OPERATOR']);
        Role::create(['name' => 'CHECKER']);
        Role::create(['name' => 'LEADER']);
        Role::create(['name' => 'SUPERVISOR']);
        Role::create(['name' => 'QUALITY']);
        Role::create(['name' => 'PE']);
        Role::create(['name' => 'STORE']);
        Role::create(['name' => 'PPIC']);
        Role::create(['name' => 'MAINTENANCE']);
        Role::create(['name' => 'MOULD-MAINTENANCE-LEADER']);
        Role::create(['name' => 'PRODUCTION']);
        Role::create(['name' => 'BUSINESS']);
        Role::create(['name' => 'ASSEMBLYPROCESS']);

    }
}
