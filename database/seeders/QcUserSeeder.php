<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class QcUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create or get QUALITY role
        $role = Role::firstOrCreate(['name' => 'QUALITY']);

        // 2. Create or update QC user
        $user = User::withTrashed()->where('email', 'qc@daijo.co.id')->orWhere('username', 'qc')->first();
        if ($user) {
            $user->update([
                'username'  => 'QC01',
                'name'      => 'QC01',
                'email'     => 'qc@daijo.co.id',
                'password'  => Hash::make('password123'),
                'role_id'   => $role->id,
                'is_active' => true,
                'deleted_at'=> null,
            ]);
        } else {
            User::create([
                'username'  => 'QC01',
                'name'      => 'QC01',
                'email'     => 'qc@daijo.co.id',
                'password'  => Hash::make('password123'),
                'role_id'   => $role->id,
                'is_active' => true,
            ]);
        }

        $this->command->info("User QC berhasil dibuat/diupdate (Username: qc, Password: password123, Role: QUALITY).");
    }
}
