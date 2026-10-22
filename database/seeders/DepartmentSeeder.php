<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            ['code' => 'PROD', 'name' => 'Production', 'description' => 'Moulding and Plastic Injection Production'],
            ['code' => 'QC', 'name' => 'Quality Control', 'description' => 'Quality Control, QA & Inspection'],
            ['code' => 'PPIC', 'name' => 'PPIC', 'description' => 'Production Planning and Inventory Control'],
            ['code' => 'WHS', 'name' => 'Warehouse', 'description' => 'Material & FG Warehouse, Store'],
            ['code' => 'MTC', 'name' => 'Maintenance', 'description' => 'Machine and Tooling Maintenance'],
            ['code' => 'PE', 'name' => 'Production Engineering', 'description' => 'Production Engineering and Technical Support'],
            ['code' => 'WS', 'name' => 'Workshop', 'description' => 'Mould Workshop and Fabrication'],
            ['code' => 'SP', 'name' => 'Second Process', 'description' => 'Secondary Processing, Painting & Finishing'],
            ['code' => 'ASY', 'name' => 'Assembly Process', 'description' => 'Assembly Line & Packing Operations'],
            ['code' => 'BUS', 'name' => 'Business', 'description' => 'Business Development & Sales'],
            ['code' => 'ADM', 'name' => 'Administration', 'description' => 'Management, IT, HR & General Affairs'],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(
                ['code' => $dept['code']],
                array_merge($dept, ['is_active' => true])
            );
        }
    }
}
