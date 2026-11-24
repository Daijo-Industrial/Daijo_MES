<?php

namespace Database\Seeders;

use App\Models\MasterCustomerDelivery;
use Illuminate\Database\Seeder;

class MasterCustomerDeliverySeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'customer_code' => 'CUST001',
                'customer_name' => 'PT Maju Jaya Sentosa',
            ],
            [
                'customer_code' => 'CUST002',
                'customer_name' => 'CV Berkah Abadi',
            ],
            [
                'customer_code' => 'CUST003',
                'customer_name' => 'PT Indo Prima Mandiri',
            ],
            [
                'customer_code' => 'CUST004',
                'customer_name' => 'UD Sumber Rejeki',
            ],
            [
                'customer_code' => 'CUST005',
                'customer_name' => 'PT Cahaya Bintang',
            ],
            [
                'customer_code' => 'CUST006',
                'customer_name' => 'CV Karya Makmur',
            ],
            [
                'customer_code' => 'CUST007',
                'customer_name' => 'PT Surya Gemilang',
            ],
            [
                'customer_code' => 'CUST008',
                'customer_name' => 'UD Harapan Baru',
            ],
            [
                'customer_code' => 'CUST009',
                'customer_name' => 'PT Nusantara Pratama',
            ],
            [
                'customer_code' => 'CUST010',
                'customer_name' => 'CV Mitra Sejahtera',
            ],
            [
                'customer_code' => 'CUST011',
                'customer_name' => 'PT Anugrah Teknik Indonesia',
            ],
            [
                'customer_code' => 'CUST012',
                'customer_name' => 'CV Sinar Terang',
            ],
            [
                'customer_code' => 'CUST013',
                'customer_name' => 'PT Buana Raya',
            ],
            [
                'customer_code' => 'CUST014',
                'customer_name' => 'UD Rizki Barokah',
            ],
            [
                'customer_code' => 'CUST015',
                'customer_name' => 'PT Global Persada',
            ],
        ];

        foreach ($customers as $customer) {
            MasterCustomerDelivery::create($customer);
        }
    }
}
