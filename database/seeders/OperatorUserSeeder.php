<?php

namespace Database\Seeders;

use App\Models\OperatorUser;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OperatorUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Initialize Faker to generate fake names
        $faker = Faker::create();

        // Create 10 users with random name and password
        for ($i = 0; $i < 10; $i++) {
            OperatorUser::create([
                'name' => $faker->firstName, // Random name
                'password' => Str::random(10), // Random 10-character password, hashed
            ]);
        }
    }
}
