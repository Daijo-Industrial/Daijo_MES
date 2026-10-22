<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use Illuminate\Database\Seeder;

class BranchDepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jakarta = Branch::where('code', 'JKT')->first();
        $karawang = Branch::where('code', 'KRW')->first();

        if ($jakarta) {
            // Jakarta Plant operates all active departments including Second Process (SP)
            $allDepartments = Department::active()->pluck('id');
            $jakarta->departments()->syncWithoutDetaching($allDepartments);
        }

        if ($karawang) {
            // Karawang Plant operates active departments EXCEPT Second Process (SP)
            $karawangDepartments = Department::active()
                ->where('code', '!=', 'SP')
                ->pluck('id');
            $karawang->departments()->syncWithoutDetaching($karawangDepartments);
        }
    }
}
