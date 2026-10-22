<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class SecondProcessUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timestamp = Carbon::now();

        // Ensure SECONDPROCESS role exists
        $role = Role::firstOrCreate(['name' => 'SECONDPROCESS']);

        // Single user to seed: Srie
        $username = 'srie';
        $name     = 'Srie';
        $email    = 'srie@daijo.co.id';
        $password = 'Srie1234';

        $user = User::withTrashed()
            ->where('email', $email)
            ->orWhere('username', $username)
            ->first();

        if ($user) {
            $user->update([
                'username'   => $username,
                'name'       => $name,
                'email'      => $email,
                'password'   => Hash::make($password),
                'role_id'    => $role->id,
                'deleted_at' => null,
                'updated_at' => $timestamp,
            ]);
        } else {
            User::create([
                'username'   => $username,
                'name'       => $name,
                'email'      => $email,
                'password'   => Hash::make($password),
                'role_id'    => $role->id,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        if (isset($this->command)) {
            $this->command->info("User Second Process ({$username} / {$email}) dengan role SECONDPROCESS berhasil dibuat/di-update!");
        }
    }
}
