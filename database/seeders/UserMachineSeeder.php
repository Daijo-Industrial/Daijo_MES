<?php

namespace Database\Seeders;

use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserMachineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timestamp = Carbon::now();

        // Ganti sesuai role yang dibutuhkan
        $roleId = Role::where('name', 'OPERATOR')->first()->id;

        // List machine / username
        $machines = [
            '360A',
            '360B',
            '360C',
            '360D',
        ];

        foreach ($machines as $machine) {
            DB::table('users')->insert([
                'name' => $machine,
                'email' => strtolower($machine) . '@daijo.co.id',
                'role_id' => $roleId,
                'password' => Hash::make('daijo1234'),
                'remember_token' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }
    }
}
