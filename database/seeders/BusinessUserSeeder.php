<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BusinessUserSeeder extends Seeder
{
    public function run(): void
    {
        $timestamp = Carbon::now();

        $roleId = Role::where('name', 'BUSINESS')->first()->id;

        $names = [
            'SUHANDI',
            'SRIYATI',
            'TINA',
            'ANIK',
            'ANDRIANI',
            'ERIZAL',
            'ALDA',
            'DEDIAGUNG',
            'ANDIKA',
            'DWIANTORO',
            'ELVIN',
            'DIAN',
            'HANA',
            'NURUL',
        ];

        foreach ($names as $name) {
            $email = strtoupper($name) . '@daijo.co.id';

            User::updateOrCreate(
                ['email' => $email],
                [
                    'role_id' => $roleId,
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make('daijo123'),
                    'remember_token' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]
            );
        }
    }
}
