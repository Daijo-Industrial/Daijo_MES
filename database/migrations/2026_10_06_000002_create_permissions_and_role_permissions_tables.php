<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create permissions table
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('label');
                $table->string('group')->default('General');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // 2. Add permissions_configured indicator to roles table
        if (Schema::hasTable('roles') && !Schema::hasColumn('roles', 'permissions_configured')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->boolean('permissions_configured')->default(false)->after('name');
            });
        }

        // 3. Create role_permissions pivot table
        if (!Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['role_id', 'permission_id']);
            });
        }

        // 4. Seed system permissions with granular Second Process breakdown
        $permissions = [
            // User & System Administration
            [
                'name' => 'manage-users-roles',
                'label' => 'Manage Users & Roles',
                'group' => 'User & System Administration',
                'description' => 'Access User & Role Management portal, manage user accounts, assign roles and configure permissions.',
            ],
            [
                'name' => 'manage-branches-departments',
                'label' => 'Manage Branches & Departments',
                'group' => 'User & System Administration',
                'description' => 'Configure branch plant assignments and department organizational hierarchy.',
            ],
            [
                'name' => 'view-admin-links',
                'label' => 'View Admin Links',
                'group' => 'User & System Administration',
                'description' => 'Access administration navigation menus, system logs, and administrative tools.',
            ],

            // Production & Operations
            [
                'name' => 'view-operator-links',
                'label' => 'View Operator Links',
                'group' => 'Production & Operations',
                'description' => 'Access operator daily production interfaces and machine monitoring.',
            ],
            [
                'name' => 'view-production-links',
                'label' => 'View Production Links',
                'group' => 'Production & Operations',
                'description' => 'Access general production dashboard and machine output views.',
            ],
            [
                'name' => 'view-workshop-links',
                'label' => 'View Workshop Links',
                'group' => 'Production & Operations',
                'description' => 'Access workshop tooling, mould repairs, and workshop schedules.',
            ],
            [
                'name' => 'view-assembly-process-links',
                'label' => 'View Assembly Process Links',
                'group' => 'Production & Operations',
                'description' => 'Access assembly line daily operations, packing, and batch progress.',
            ],

            // Engineering & Quality
            [
                'name' => 'view-pe-links',
                'label' => 'View PE Links',
                'group' => 'Engineering & Quality',
                'description' => 'Access Process Engineering master data, cycle time calibration, and ALC setup.',
            ],
            [
                'name' => 'view-quality-links',
                'label' => 'View Quality Links',
                'group' => 'Engineering & Quality',
                'description' => 'Access Quality Department modules and inspection dashboards.',
            ],
            [
                'name' => 'execute-qc-inspections',
                'label' => 'Execute QC Inspections',
                'group' => 'Engineering & Quality',
                'description' => 'Perform First Piece Inspections (FPI), IPQC lot checks, and sign quality approvals.',
            ],

            // Warehouse & Inventory
            [
                'name' => 'view-warehouse-links',
                'label' => 'View Warehouse Links',
                'group' => 'Warehouse & Inventory',
                'description' => 'Access raw material warehouse, incoming deliveries, and pallet rack storage.',
            ],
            [
                'name' => 'view-store-links',
                'label' => 'View Store Links',
                'group' => 'Warehouse & Inventory',
                'description' => 'Access finished goods store, barcode scanning, and delivery order dispatch.',
            ],

            // Planning & Business
            [
                'name' => 'view-ppic-links',
                'label' => 'View PPIC Links',
                'group' => 'Planning & Business',
                'description' => 'Access PPIC scheduling, SPK master lists, and production order planning.',
            ],
            [
                'name' => 'view-business-links',
                'label' => 'View Business Links',
                'group' => 'Planning & Business',
                'description' => 'Access business development, customer pricing, and forecast analytics.',
            ],

            // Maintenance
            [
                'name' => 'view-maintenance-links',
                'label' => 'View Maintenance Links',
                'group' => 'Maintenance',
                'description' => 'Access preventive maintenance checklists and machine work orders.',
            ],

            // Second Process Domain (Fine-Grained Granular Pilot - Consolidated per Feature)
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

        $now = now();
        foreach ($permissions as $perm) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $perm['name']],
                [
                    'label' => $perm['label'],
                    'group' => $perm['group'],
                    'description' => $perm['description'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        // 5. Default mapping for existing roles
        $allSpPermissions = [
            'second-process-work-orders',
            'second-process-first-piece',
            'second-process-ipqc',
            'second-process-dashboard',
            'second-process-sessions',
            'second-process-approvals',
            'second-process-reports',
            'second-process-analytics',
        ];

        $rolePermissionsMap = [
            'SUPER-ADMIN' => array_merge([
                'manage-users-roles',
                'manage-branches-departments',
                'view-admin-links',
                'view-workshop-links',
                'view-warehouse-links',
                'view-operator-links',
                'view-pe-links',
                'view-store-links',
                'view-ppic-links',
                'view-maintenance-links',
                'view-assembly-process-links',
                'view-business-links',
                'view-production-links',
                'view-quality-links',
                'execute-qc-inspections',
            ], $allSpPermissions),

            'ADMIN' => array_merge([
                'view-admin-links',
                'manage-branches-departments',
                'view-workshop-links',
                'view-warehouse-links',
                'view-operator-links',
                'view-pe-links',
                'view-store-links',
                'view-ppic-links',
                'view-maintenance-links',
                'view-assembly-process-links',
                'view-business-links',
                'view-production-links',
                'view-quality-links',
                'execute-qc-inspections',
            ], $allSpPermissions),

            'WORKSHOP' => ['view-workshop-links'],
            'WAREHOUSE' => ['view-warehouse-links'],
            'OPERATOR' => ['view-operator-links'],
            'PE' => [
                'view-pe-links',
                'second-process-work-orders',
                'second-process-dashboard',
                'second-process-reports',
                'second-process-analytics',
            ],
            'STORE' => ['view-store-links'],
            'PPIC' => [
                'view-ppic-links',
                'second-process-reports',
                'second-process-analytics',
            ],
            'MAINTENANCE' => ['view-maintenance-links'],
            'SECONDPROCESS' => $allSpPermissions,
            'ASSEMBLYPROCESS' => ['view-assembly-process-links'],
            'BUSINESS' => ['view-business-links'],
            'PRODUCTION' => ['view-production-links'],
            'QUALITY' => [
                'view-quality-links',
                'execute-qc-inspections',
                'second-process-first-piece',
                'second-process-ipqc',
                'second-process-reports',
            ],
            'CHECKER' => ['view-operator-links', 'view-production-links'],
            'LEADER' => ['view-operator-links', 'view-production-links'],
            'SUPERVISOR' => ['view-operator-links', 'view-production-links'],
        ];

        $existingRoles = DB::table('roles')->get();
        $allPermissions = DB::table('permissions')->pluck('id', 'name')->toArray();

        foreach ($existingRoles as $role) {
            $roleName = strtoupper(trim($role->name));
            if (isset($rolePermissionsMap[$roleName])) {
                $permNames = $rolePermissionsMap[$roleName];
                $pivotData = [];

                foreach ($permNames as $pName) {
                    if (isset($allPermissions[$pName])) {
                        $pivotData[] = [
                            'role_id' => $role->id,
                            'permission_id' => $allPermissions[$pName],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if (!empty($pivotData)) {
                    DB::table('role_permissions')->insertOrIgnore($pivotData);
                }

                DB::table('roles')->where('id', $role->id)->update(['permissions_configured' => true]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');

        if (Schema::hasTable('roles') && Schema::hasColumn('roles', 'permissions_configured')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('permissions_configured');
            });
        }
    }
};
