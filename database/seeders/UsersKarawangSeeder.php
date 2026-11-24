<?php

namespace Database\Seeders;

use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersKarawangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timestamp = Carbon::now();

        // role default untuk karawang, misalnya OPERATOR
        $roleId = Role::where('name', 'OPERATOR')->first()->id;

        $users = [
            'K2800A',
            'K2100A',
            'K1400A',
            'K1400B',
            'K1400C',
            'K0900A',
            'K0900B',
            'K0650A',
            'K0650B',
            'K0750A',
            'K0750B',
            'K0450A',
        ];

        foreach ($users as $username) {
            DB::table('users')->insert([
                'name' => $username,
                'email' => strtolower($username) . '@daijo.co.id',
                'role_id' => $roleId,
                'password' => Hash::make('daijo1234'), // default password
                'remember_token' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }
    }
}
