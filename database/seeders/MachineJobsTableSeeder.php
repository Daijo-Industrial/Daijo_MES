<?php

namespace Database\Seeders;

use App\Models\MachineJob;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MachineJobsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = \App\Models\User::whereHas('role', function($query){
            $query->where('name', 'OPERATOR');
        })->get();
        foreach ($users as $user) {
            MachineJob::firstOrCreate(['user_id' => $user->id]);
        }
    }
}
