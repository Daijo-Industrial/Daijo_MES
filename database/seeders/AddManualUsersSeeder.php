<?php

namespace Database\Seeders;

use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AddManualUsersSeeder extends Seeder
{
    public function run(): void
    {
        $timestamp = Carbon::now();

        $users = [
            [
                'name' => 'vincent',
                'email' => 'vincent@daijo.co.id',
                'role' => 'PRODUCTION',
            ],
            [
                'name' => 'asik',
                'email' => 'asik@daijo.co.id',
                'role' => 'PRODUCTION',
            ],
        ];

        foreach ($users as $user) {

            $password = $user['name'] . '123';

            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role_id' => Role::where('name', $user['role'])->first()->id,
                    'password' => Hash::make($password),
                    'remember_token' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]
            );
        }
    }
}
