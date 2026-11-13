<?php

use App\Livewire\Actions\Logout;
use App\Models\MachineJob;
use App\Models\User;
use App\Services\PlantContextService;
use Livewire\Volt\Component;

new class extends Component {
    public function logout(Logout $logout): void
    {
        $user = auth()->user();
        if ($user) {
            if ($user->role?->name === 'WORKSHOP') {
                $user->update(['username' => null]);
            } elseif ($user->role?->name === 'OPERATOR') {
                MachineJob::where('user_id', $user->id)->update(['employee_name' => null]);
            }
        }

        $logout();

        $this->redirect('/login', navigate: true);
    }

    /**
     * Determine if current user can access a menu item or group.
     */
    protected function userCanAccess(?User $user, array $item): bool
    {
        if (!$user) {
            return false;
        }

        if (isset($item['when'])) {
            $condition = is_callable($item['when']) ? $item['when']($user) : (bool) $item['when'];
            if (!$condition) {
                return false;
            }
        }

        if (isset($item['roles'])) {
            $userRole = strtoupper($user->role?->name ?? '');
            $allowedRoles = array_map('strtoupper', (array) $item['roles']);
            if (in_array($userRole, $allowedRoles, true)) {
                return true;
            }
        }

        if (!isset($item['can'])) {
            return !isset($item['roles']);
        }

        $abilities = (array) $item['can'];
        foreach ($abilities as $ability) {
            if ($user->can($ability)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get filtered navigation menu groups for current authenticated user.
     */
    public function getMenuGroups(): array
    {
        $user = auth()->user();
        if (!$user) {
            return [];
        }

        $groups = $this->navigationSchema();
        $filtered = [];

        foreach ($groups as $group) {
            if (isset($group['when'])) {
                $condition = is_callable($group['when']) ? $group['when']($user) : (bool) $group['when'];
                if (!$condition) {
                    continue;
                }
            }

            if (isset($group['can']) || isset($group['roles'])) {
                if (!$this->userCanAccess($user, $group)) {
                    continue;
                }
            }

            $visibleChildren = [];
            foreach ($group['children'] ?? [] as $child) {
                if ($this->userCanAccess($user, $child)) {
                    $visibleChildren[] = [
                        'name' => $child['name'],
                        'label' => $child['label'],
                        'params' => $child['params'] ?? [],
                    ];
                }
            }

            if (!empty($visibleChildren)) {
                $filtered[] = [
                    'label' => $group['label'],
                    'initiallyOpen' => $group['initiallyOpen'] ?? false,
                    'children' => $visibleChildren,
                ];
            }
        }

        return $filtered;
    }

    /**
     * Single Source of Truth Navigation Schema.
     */
    protected function navigationSchema(): array
    {
        return [
            // 1. Dashboard All
            [
                'label' => 'Dashboard All',
                'children' => [
                    ['name' => 'delschedfinal.dashboard', 'label' => 'Dashboard Delivery Schedule', 'can' => ['view-business-links', 'view-warehouse-links']],
                    ['name' => 'workshop.summary.dashboard', 'label' => 'Dashboard Proses Moulding', 'can' => ['view-workshop-links', 'view-warehouse-links']],
                    ['name' => 'dashboard.moulding.tv', 'label' => 'Dashboard Project Moulding', 'can' => ['view-workshop-links', 'view-warehouse-links']],
                    ['name' => 'report.machine-active-hours', 'label' => 'Machine Active Hours', 'can' => ['view-production-links']],
                    ['name' => 'summaryDashboard', 'label' => 'Packaging Dashboard', 'can' => ['view-store-links']],
                ],
            ],

            // 2. Master Data & Setting
            [
                'label' => 'Master Data & Setting',
                'children' => [
                    ['name' => 'setting.holiday-schedule.index', 'label' => 'Holiday Schedule', 'can' => ['view-ppic-links']],
                    ['name' => 'admin.master-list-manager', 'label' => 'Master List Manager', 'can' => ['manage-users-roles']],
                    ['name' => 'admin.business-partner-manager', 'label' => 'Master Business Partner', 'can' => ['view-admin-links']],
                    ['name' => 'admin.customer-delivery-manager', 'label' => 'Master Customer Delivery', 'can' => ['view-admin-links']],
                    ['name' => 'master-item.index', 'label' => 'Master Item', 'can' => ['view-pe-links']],
                    ['name' => 'master-bom.index', 'label' => 'Master BOM (SAP)', 'can' => ['view-pe-links']],
                    ['name' => 'barcode.box_master.index', 'label' => 'Master Box Data', 'can' => ['view-store-links']],
                    ['name' => 'barcode.box_detail.index', 'label' => 'Master Box Detail', 'can' => ['view-store-links']],
                    ['name' => 'wms.mapping', 'label' => 'Warehouse Mapping', 'can' => ['view-store-links', 'view-warehouse-links']],
                    ['name' => 'mwh.mapping', 'label' => 'Material Warehouse Mapping', 'can' => ['view-store-links', 'view-warehouse-links']],
                    ['name' => 'mwh.master-list.index', 'label' => 'Master List Material', 'can' => ['view-store-links', 'view-warehouse-links']],
                    ['name' => 'master-machine.index', 'label' => 'Master Mesin (Dashboard)', 'can' => ['view-production-links']],
                    ['name' => 'customer.add', 'label' => 'Manage Barcode Customer', 'can' => ['view-store-links']],
                ],
            ],

            // 3. Production & Moulding
            [
                'label' => 'Production & Moulding',
                'children' => [
                    ['name' => 'wms.production-scan-crosscheck', 'label' => 'Crosscheck Scan Produksi', 'can' => ['view-production-links', 'view-store-links']],
                    ['name' => 'production.store-handover', 'label' => 'Kontrol Produksi - Store', 'can' => ['view-production-links', 'view-store-links']],
                    ['name' => 'production.material-station', 'label' => 'Station Material Produksi', 'can' => ['view-production-links']],
                    ['name' => 'production.bom.index', 'label' => 'Production BOM', 'can' => ['view-production-links', 'view-warehouse-links']],
                    ['name' => 'daily-item-code.index', 'label' => 'Daily Production Plan', 'can' => ['view-production-links', 'view-ppic-links']],
                    ['name' => 'ppic.listup.index', 'label' => 'PPIC List Up Mesin', 'can' => ['view-ppic-links']],
                    ['name' => 'ppic.machine-daily-report', 'label' => 'Laporan Produksi Mesin', 'can' => ['view-production-links', 'view-ppic-links']],
                    ['name' => 'spk.changes.index', 'label' => 'Audit Log SPK (SAP Sync)', 'can' => ['view-production-links', 'view-ppic-links']],
                    ['name' => 'spk.bom-changes.index', 'label' => 'SPK BOM Changes', 'can' => ['view-production-links', 'view-ppic-links', 'view-pe-links']],
                    ['name' => 'capacityforecastindex', 'label' => 'Capacity By Forecast', 'can' => ['view-production-links', 'view-ppic-links']],
                    ['name' => 'waiting_purchase_orders.index', 'label' => 'Waiting Purchase Orders', 'can' => ['view-production-links', 'view-warehouse-links']],
                    ['name' => 'notification_recipients.index', 'label' => 'Notification Recipients', 'can' => ['view-production-links', 'view-warehouse-links']],
                    ['name' => 'invlinelist', 'label' => 'Machine List', 'can' => ['view-production-links']],
                ],
            ],

            // 4. Maintenance & Machine
            [
                'label' => 'Maintenance & Machine',
                'can' => ['view-maintenance-links'],
                'children' => [
                    ['name' => 'mould-checklist.index', 'label' => 'Mould Maintenance Checklist'],
                    ['name' => 'master-mould.index', 'label' => 'Master Mould Directory'],
                    ['name' => 'maintenance.checklist-report', 'label' => 'Laporan Predictive Maintenance Mesin'],
                ],
            ],

            // 5. Second Process
            [
                'label' => 'Second Process',
                'when' => fn() => app(PlantContextService::class)->supportsSecondProcess(),
                'children' => [
                    ['name' => 'sp-work-orders.index', 'label' => 'Work Orders', 'can' => ['second-process-work-orders', 'view-admin-links']],
                    ['name' => 'first-piece-inspections.index', 'label' => 'First Piece Inspections', 'can' => ['second-process-first-piece', 'view-admin-links']],
                    ['name' => 'ipqc-inspections.index', 'label' => 'IPQC Inspections', 'can' => ['second-process-ipqc', 'view-admin-links']],
                    ['name' => 'second-process.dashboard', 'label' => 'Floor Overview Dashboard', 'can' => ['second-process-dashboard', 'view-admin-links']],
                    ['name' => 'sp-approvals.index', 'label' => 'Production Approvals', 'can' => ['second-process-approvals', 'view-admin-links']],
                    ['name' => 'second-process.report-analytics', 'label' => 'Daily Report Analytics', 'can' => ['second-process-analytics', 'view-admin-links']],
                    ['name' => 'second-process.defect-manager', 'label' => 'Defect Catalog & Aliases', 'can' => ['second-process-reports', 'view-admin-links']],
                    ['name' => 'second-process-reports.index', 'label' => 'Second Process Daily Report', 'can' => ['second-process-reports', 'view-admin-links']],
                ],
            ],

            // 6. Plastic Injection IPQC
            [
                'label' => 'Plastic Injection IPQC',
                'can' => ['view-quality-links', 'execute-qc-inspections'],
                'initiallyOpen' => request()->routeIs('inspection-reports.*'),
                'children' => [
                    ['name' => 'inspection-reports.index', 'label' => 'Plastic Injection IPQC Reports'],
                    ['name' => 'inspection-reports.dashboard', 'label' => 'Inspection Dashboard'],
                ],
            ],

            // 7. WMS & Warehouse
            [
                'label' => 'WMS & Warehouse',
                'can' => ['view-store-links', 'view-warehouse-links'],
                'roles' => ['ADMIN', 'SUPER-ADMIN', 'STORE', 'WAREHOUSE'],
                'children' => [
                    ['name' => 'wms.dashboard', 'label' => 'WMS Rack Availability Dashboard'],
                    ['name' => 'wms.production-scan-crosscheck', 'label' => 'Crosscheck Scan Produksi'],
                    ['name' => 'production.store-handover', 'label' => 'Kontrol Serah Terima Produksi'],
                    ['name' => 'wms.pallet-form.create-delivery', 'label' => 'Scan Delivery FG (Program Warehouse)'],
                    ['name' => 'wms.pallet-form.daily-recap', 'label' => 'Rekap Harian Delivery FG'],
                    ['name' => 'wms.pallet-form.index', 'label' => 'Assign Slot & Riwayat Pallet'],
                    ['name' => 'wms.pallet-form.lookup', 'label' => 'Pallet Detail Check'],
                    ['name' => 'wms.pallet-form.sorting', 'label' => 'Pallet Sorting and Consolidation'],
                    ['name' => 'wms.pallet-form.picking-guide', 'label' => 'Delivery Picking Guide (FIFO)'],
                    ['name' => 'wms.mapping', 'label' => 'Warehouse Mapping (FG)'],
                    ['name' => 'wms.logs', 'label' => 'Audit Trail Logs'],
                    ['name' => 'updated.barcode.item.position', 'label' => 'Detailed Item List'],
                ],
            ],

            // 8. Material Warehouse
            [
                'label' => 'Material Warehouse',
                'can' => ['view-store-links', 'view-warehouse-links'],
                'roles' => ['ADMIN', 'SUPER-ADMIN', 'STORE', 'WAREHOUSE'],
                'children' => [
                    ['name' => 'mwh.master-list.index', 'label' => 'Master List Material'],
                    ['name' => 'mwh.mapping', 'label' => 'Material Warehouse Mapping'],
                    ['name' => 'mwh.daily-audit', 'label' => 'Audit Harian Material'],
                    ['name' => 'mwh.requests', 'label' => 'Permintaan Produksi (Requests)'],
                    ['name' => 'mwh.incoming.create', 'label' => 'Penerimaan Material (Incoming)'],
                    ['name' => 'mwh.outgoing.create', 'label' => 'Pengambilan Material (Outgoing)'],
                    ['name' => 'mwh.outgoing.history', 'label' => 'Riwayat Outgoing Material'],
                    ['name' => 'mwh.stock-card.index', 'label' => 'Kartu Stok Material (Stock Card)'],
                    ['name' => 'mwh.fifo-dashboard', 'label' => 'Director FIFO Dashboard (Live)'],
                    ['name' => 'mwh.pallets.index', 'label' => 'Stock & Pallet Material'],
                    ['name' => 'mwh.qr-lookup', 'label' => 'Scan QR Material'],
                ],
            ],

            // 9. Delivery & Business
            [
                'label' => 'Delivery & Business',
                'can' => ['view-business-links'],
                'children' => [
                    ['name' => 'delivery-schedule.form', 'label' => 'Delivery Schedule Input (BARU)', 'can' => ['view-business-links']],
                    ['name' => 'delivery-schedule.calendar', 'label' => 'Kalender Delivery Schedule', 'can' => ['view-business-links']],
                    ['name' => 'delivery.analysis', 'label' => 'Delivery Schedule Terbaru', 'can' => ['view-business-links']],
                    ['name' => 'delivery.po-reminder', 'label' => 'PO Reminder (Kebutuhan Material)', 'can' => ['view-business-links']],
                    ['name' => 'production.forecast.index', 'label' => 'Forecast Production', 'can' => ['view-business-links']],
                    ['name' => 'management.delivery.index', 'label' => 'Delivery Data Delete', 'can' => ['view-admin-links']],
                    ['name' => 'indexds', 'label' => 'Delivery Schedule Data (Old)', 'can' => ['view-admin-links']],
                ],
            ],

            // 10. Sales Order & DO
            [
                'label' => 'Sales Order & DO',
                'can' => ['view-store-links', 'view-business-links'],
                'children' => [
                    ['name' => 'so.index', 'label' => 'DATA SO / DO Index'],
                    ['name' => 'so.dashboard', 'label' => 'SO Dashboard'],
                    ['name' => 'pegawai.scan', 'label' => 'Scan SO'],
                    ['name' => 'so.tracking', 'label' => 'Tracking SPK & Label DO'],
                ],
            ],

            // 11. Packaging Menu
            [
                'label' => 'Packaging Menu',
                'can' => ['view-store-links'],
                'children' => [
                    ['name' => 'summaryDashboard', 'label' => 'Dashboard & Saldo Kemasan'],
                    ['name' => 'inandout.index', 'label' => 'Scan Barcode (In/Out)'],
                    ['name' => 'barcodeindex', 'label' => 'Cetak Barcode & Riwayat'],
                    ['name' => 'barcode.box_master.index', 'label' => 'Master Data Kemasan'],
                ],
            ],

            // 12. SAP Monitor
            [
                'label' => 'SAP Monitor',
                'can' => ['view-production-links', 'view-ppic-links', 'view-store-links', 'view-quality-links'],
                'children' => [
                    ['name' => 'sap.verification', 'label' => 'Verifikasi Data SAP vs MES', 'can' => ['view-admin-links']],
                    ['name' => 'receipt-production-logs', 'label' => 'Cek Data Masuk ke SAP', 'can' => ['view-production-links', 'view-ppic-links', 'view-store-links']],
                    ['name' => 'production-summary-monitor', 'label' => 'Cek Stock Program ke SAP', 'can' => ['view-production-links', 'view-store-links']],
                    ['name' => 'wms.sap-sync-monitor-delivery', 'label' => 'SAP Sync Monitor', 'can' => ['view-production-links', 'view-store-links']],
                    ['name' => 'qc-stock-transfer', 'label' => 'QC Stock Transfer (Karawang)', 'can' => ['view-quality-links']],
                    ['name' => 'qc-stock-transfer-kbn', 'label' => 'QC Stock Transfer (KBN)', 'can' => ['view-quality-links']],
                    ['name' => 'api.dashboard', 'label' => 'API Log Dashboard', 'can' => ['view-production-links']],
                ],
            ],
        ];
    }
}; ?>

<aside
    class="bg-white w-64 h-screen border-r border-gray-200
           fixed inset-y-0 left-0 flex flex-col justify-between
           transform transition-transform duration-200 ease-in-out
           z-30
           lg:translate-x-0"
    :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }" x-cloak>
    {{-- TOP --}}
    <div class="px-6 py-4 flex-grow overflow-y-auto border-scrollbar">
        <div class="flex items-center justify-between">
            <a href="{{ route('dashboard') }}" wire:navigate>
                <x-application-logo class="block fill-current text-gray-800 w-16 h-16" />
            </a>
            <span class="ms-5 font-semibold text-xl">
                Daijo MES
            </span>

            {{-- Close button mobile --}}
            <button @click="sidebarOpen = false" class="lg:hidden text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    class="h-6 w-6">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Plant Context / Switcher --}}
        <div class="mt-3 pt-3 pb-1 border-t border-gray-100">
            <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5 px-0.5">
                Plant / Branch
            </div>
            <livewire:common.plant-switcher />
        </div>

        <!-- Navigation Links -->
        <div class="space-y-2 mt-4">
            <!-- Core Dashboard Link -->
            <livewire:sidebar-link href="{{ route('dashboard') }}" label="Dashboard"
                :active="request()->routeIs('dashboard')" wire:navigate />

            <!-- Operator Daily Report (Shop floor machine operator interface) -->
            @if (auth()->user()?->can('view-operator-links') || auth()->user()?->hasRole('OPERATOR'))
                <livewire:sidebar-link href="{{ route('operator.daily-report') }}" label="Laporan Harian Mesin"
                    :active="request()->routeIs('operator.daily-report')" wire:navigate />
            @endif

            <!-- High-level System Administration Links -->
            @if (auth()->user()?->can('manage-users-roles'))
                <livewire:sidebar-link href="{{ route('admin.access-management') }}" label="Access Management"
                    :active="request()->routeIs('admin.access-management') || request()->routeIs('admin.user-role-manager')" wire:navigate />
                <livewire:sidebar-link href="{{ route('admin.activity-logs') }}" label="User Activity & Audit Logs"
                    :active="request()->routeIs('admin.activity-logs')" wire:navigate />
            @endif

            @if (auth()->user()?->can('manage-branches-departments'))
                <livewire:sidebar-link href="{{ route('admin.branches-departments') }}" label="Branches & Departments"
                    :active="request()->routeIs('admin.branches-departments') || request()->routeIs('admin.plant-departments')" wire:navigate />
            @endif

            <!-- Grouped Dropdown Navigation (Domain-driven & single source of truth) -->
            @foreach ($this->getMenuGroups() as $group)
                <livewire:parent-dropdown
                    :key="$group['label']"
                    :label="$group['label']"
                    :initiallyOpen="$group['initiallyOpen'] ?? false"
                    :childRoutes="$group['children']"
                />
            @endforeach
        </div>
    </div>

    {{-- BOTTOM USER FOOTER --}}
    <div class="p-4 border-t border-gray-200 bg-gray-50/80">
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center space-x-2.5 overflow-hidden">
                <div
                    class="w-9 h-9 rounded-xl bg-slate-800 text-white font-black text-xs flex items-center justify-center shadow-xs shrink-0">
                    {{ strtoupper(substr(auth()->user()?->name ?? 'G', 0, 2)) }}
                </div>
                <div class="truncate">
                    <div class="text-xs font-bold text-gray-900 truncate leading-tight">
                        {{ auth()->user()?->name ?? 'Guest' }}
                    </div>
                    <div class="text-[10px] text-gray-500 font-semibold uppercase truncate">
                        {{ auth()->user()?->role?->name ?? 'User' }}
                    </div>
                </div>
            </div>

            <div class="flex items-center space-x-1 shrink-0">
                @auth
                    <a href="{{ route('profile') }}" wire:navigate
                        class="p-2 text-gray-500 hover:text-blue-600 hover:bg-white rounded-lg transition"
                        title="Profile">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </a>

                    <button wire:click="logout"
                        class="px-2.5 py-1.5 text-rose-600 hover:text-white hover:bg-rose-600 bg-rose-50 border border-rose-200/80 rounded-xl transition flex items-center space-x-1 text-xs font-bold shadow-xs active:scale-95"
                        title="Log Out">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Logout</span>
                    </button>
                @else
                    <a href="{{ route('login') }}"
                        class="px-3 py-1.5 text-indigo-600 hover:text-white hover:bg-indigo-600 bg-indigo-50 border border-indigo-200/80 rounded-xl transition flex items-center space-x-1 text-xs font-bold shadow-xs active:scale-95"
                        title="Log In">
                        <span>Login</span>
                    </a>
                @endauth
            </div>
        </div>
    </div>
</aside>
