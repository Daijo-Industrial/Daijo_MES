<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $perm = Permission::updateOrCreate(
            ['name' => 'second-process-defects'],
            [
                'label' => 'SP Defect Catalog & Aliases',
                'group' => 'Second Process',
                'description' => 'Dedicated access to manage Second Process master defect catalog and defect name alias mappings.',
            ]
        );

        if (Schema::hasTable('roles') && Schema::hasTable('role_permissions')) {
            $roles = Role::whereIn('name', ['ADMIN', 'SUPER-ADMIN', 'PE', 'QUALITY', 'LEADER', 'SUPERVISOR'])->get();
            foreach ($roles as $role) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $role->id, 'permission_id' => $perm->id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $perm = Permission::where('name', 'second-process-defects')->first();
        if ($perm) {
            if (Schema::hasTable('role_permissions')) {
                DB::table('role_permissions')->where('permission_id', $perm->id)->delete();
            }
            $perm->delete();
        }
    }
};
