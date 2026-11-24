<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StoreUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timestamp = Carbon::now();
        $roleId = Role::where('name', 'STORE')->first()->id;

        for ($i = 1; $i <= 4; $i++) {
            $email = 'store0' . $i . '@daijo.co.id';

            User::updateOrCreate(
                ['email' => $email],
                [
                    'role_id' => $roleId,
                    'name' => 'Store 0' . $i,
                    'email' => $email,
                    'password' => Hash::make('Store'), // default password: Store
                    'remember_token' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]
            );
        }
    }
}
