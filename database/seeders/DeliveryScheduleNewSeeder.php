<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeliveryScheduleNewSeeder extends Seeder
{
    public function run()
    {
        DB::table('delivery_schedule_new')->truncate(); // Optional: Clears table before seeding

        $customers = ['CUST001', 'CUST002', 'CUST003', 'CUST004', 'CUST005'];
        $items = ['ITEM001', 'ITEM002', 'ITEM003', 'ITEM004', 'ITEM005'];

        $data = [];
        for ($i = 0; $i < 300; $i++) {
            $data[] = [
                'code' => Str::upper(Str::random(10)), // Unique random string
                'so_number' => rand(1000, 9999),
                'customer_code' => $customers[array_rand($customers)],
                'delivery_date' => Carbon::today()->subDays(rand(-365, 365)), // Random date from 2024 to 2025
                'item_code' => $items[array_rand($items)],
                'delivery_quantity' => rand(1, 100),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('delivery_schedule_new')->insert($data);
    }
}
