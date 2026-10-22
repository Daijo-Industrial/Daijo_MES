<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Define the 8 consolidated Second Process permissions (1 permission per feature/menu)
        $consolidatedPermissions = [
            [
                'name' => 'second-process-work-orders',
                'label' => 'SP Work Orders',
                'group' => 'Second Process',
                'description' => 'Full access to Second Process work orders: view, create, edit, release, and revert lot dispatches.',
            ],
            [
                'name' => 'second-process-first-piece',
                'label' => 'SP First Piece Inspections',
                'group' => 'Second Process',
                'description' => 'Access and inspect First Piece Inspection (FPI) setup and approval gate records.',
            ],
            [
                'name' => 'second-process-ipqc',
                'label' => 'SP IPQC Inspections',
                'group' => 'Second Process',
                'description' => 'Access and manage In-Process Quality Control (IPQC) sample measurement records.',
            ],
            [
                'name' => 'second-process-dashboard',
                'label' => 'SP Floor Overview Dashboard',
                'group' => 'Second Process',
                'description' => 'Access Second Process shop floor overview, line dashboard, and live monitoring.',
            ],
            [
                'name' => 'second-process-sessions',
                'label' => 'SP Production Sessions',
                'group' => 'Second Process',
                'description' => 'Access line gateway, execute production sessions, and log hourly production downtime.',
            ],
            [
                'name' => 'second-process-approvals',
                'label' => 'SP Production Approvals',
                'group' => 'Second Process',
                'description' => 'View production session approval queue and authorize session closeout approvals.',
            ],
            [
                'name' => 'second-process-reports',
                'label' => 'SP Daily Production Reports',
                'group' => 'Second Process',
                'description' => 'Full access to daily reports: view, create, edit, delete, reconcile material input/output, and print.',
            ],
            [
                'name' => 'second-process-analytics',
                'label' => 'SP Report Analytics',
                'group' => 'Second Process',
                'description' => 'Access Second Process defect rate charts, yield trends, and downtime analytics dashboard.',
            ],
        ];

        // 2. Insert or update the consolidated permissions
        foreach ($consolidatedPermissions as $permData) {
            Permission::updateOrCreate(
                ['name' => $permData['name']],
                $permData
            );
        }

        // 3. Migrate existing role assignments from old split permissions to consolidated ones
        $mapping = [
            'second-process-work-orders' => ['view-second-process-work-orders', 'manage-sp-work-orders'],
            'second-process-first-piece' => ['view-second-process-first-piece'],
            'second-process-ipqc' => ['view-second-process-ipqc'],
            'second-process-dashboard' => ['view-second-process-dashboard'],
            'second-process-sessions' => ['view-second-process-sessions'],
            'second-process-approvals' => ['view-second-process-approvals', 'approve-sp-sessions'],
            'second-process-reports' => ['view-second-process-reports', 'manage-second-process-reports'],
            'second-process-analytics' => ['view-second-process-analytics'],
        ];

        foreach ($mapping as $consolidatedName => $legacyNames) {
            $newPerm = Permission::where('name', $consolidatedName)->first();
            if (! $newPerm) {
                continue;
            }

            $legacyPermIds = Permission::whereIn('name', $legacyNames)->pluck('id');
            if ($legacyPermIds->isNotEmpty()) {
                $roleIds = DB::table('role_permissions')
                    ->whereIn('permission_id', $legacyPermIds)
                    ->pluck('role_id')
                    ->unique();

                foreach ($roleIds as $roleId) {
                    DB::table('role_permissions')->updateOrInsert(
                        ['role_id' => $roleId, 'permission_id' => $newPerm->id],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                }
            }
        }

        // 4. Clean up legacy split permissions from permissions and role_permissions tables
        $legacyToDelete = [
            'view-second-process-work-orders',
            'manage-sp-work-orders',
            'view-second-process-first-piece',
            'view-second-process-ipqc',
            'view-second-process-dashboard',
            'view-second-process-sessions',
            'view-second-process-approvals',
            'approve-sp-sessions',
            'view-second-process-reports',
            'manage-second-process-reports',
            'view-second-process-analytics',
            'view-second-process-ops',
            'view-second-process-links',
        ];

        $oldIds = Permission::whereIn('name', $legacyToDelete)->pluck('id');
        if ($oldIds->isNotEmpty()) {
            DB::table('role_permissions')->whereIn('permission_id', $oldIds)->delete();
            Permission::whereIn('id', $oldIds)->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversal drops consolidated permissions
        $names = [
            'second-process-work-orders',
            'second-process-first-piece',
            'second-process-ipqc',
            'second-process-dashboard',
            'second-process-sessions',
            'second-process-approvals',
            'second-process-reports',
            'second-process-analytics',
        ];

        $ids = Permission::whereIn('name', $names)->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        Permission::whereIn('id', $ids)->delete();
    }
};
