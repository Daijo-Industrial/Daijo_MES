<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;
use App\Models\User;
use App\Models\MachineJob;

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
                Daijo MES System
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
            @if (auth()->user()?->can('view-admin-links'))
                <!-- Admin Dashboard Link -->
                <livewire:sidebar-link href="{{ route('dashboard') }}" label="Dashboard Home" :active="request()->routeIs('dashboard')"
                    wire:navigate />

                @if (auth()->user()?->can('manage-users-roles'))
                    <livewire:sidebar-link href="{{ route('admin.user-role-manager') }}" label="User & Role Management"
                        :active="request()->routeIs('admin.user-role-manager')" wire:navigate />
                @endif

                @if (auth()->user()?->can('manage-branches-departments'))
                    <livewire:sidebar-link href="{{ route('admin.branches-departments') }}" label="Branches & Departments"
                        :active="request()->routeIs('admin.branches-departments') && !request()->routeIs('admin.plant-departments')" wire:navigate />
                    <livewire:sidebar-link href="{{ route('admin.plant-departments') }}" label="Plant Departments"
                        :active="request()->routeIs('admin.plant-departments')" wire:navigate />
                @endif

                <!-- 1. Dropdown: Dashboard All -->
                <livewire:parent-dropdown label="Dashboard All" :initiallyOpen="false" :childRoutes="[
                    ['name' => 'delschedfinal.dashboard', 'label' => 'Dashboard Delivery Schedule'],
                    ['name' => 'workshop.summary.dashboard', 'label' => 'Dashboard Proses Moulding'],
                    ['name' => 'dashboard.moulding.tv', 'label' => 'Dashboard Project Moulding'],
                    ['name' => 'report.machine-active-hours', 'label' => 'Machine Active Hours'],
                    ['name' => 'summaryDashboard', 'label' => 'Packaging Dashboard'],
                ]" />

                <!-- 2. Dropdown: Master Data & Setting -->
                <livewire:parent-dropdown label="Master Data & Setting" :initiallyOpen="false" :childRoutes="[
                    ['name' => 'setting.holiday-schedule.index', 'label' => 'Holiday Schedule'],
                    ['name' => 'admin.master-list-manager', 'label' => 'Master List Manager'],
                    ['name' => 'admin.business-partner-manager', 'label' => 'Master Business Partner'],
                    ['name' => 'admin.customer-delivery-manager', 'label' => 'Master Customer Delivery'],
                    ['name' => 'inventory.mtr', 'label' => 'Master MTR'],
                    ['name' => 'inventory.fg', 'label' => 'Master FG'],
                    ['name' => 'master-list-item', 'label' => 'Master List Item'],
                    ['name' => 'master-bom.index', 'label' => 'Master BOM (SAP)'],
                    ['name' => 'barcode.box_master.index', 'label' => 'Master Box Data'],
                    ['name' => 'barcode.box_detail.index', 'label' => 'Master Box Detail'],
                    ['name' => 'wms.mapping', 'label' => 'Warehouse Mapping'],
                    ['name' => 'mwh.mapping', 'label' => 'Material Warehouse Mapping'],
                    ['name' => 'mwh.master-list.index', 'label' => 'Master List Material'],
                    ['name' => 'customer.add', 'label' => 'Manage Barcode Customer'],
                ]" />

                <!-- 3. Dropdown: Production & Moulding -->
                <livewire:parent-dropdown label="Production & Moulding" :initiallyOpen="false" :childRoutes="[
                    ['name' => 'production.bom.index', 'label' => 'Production BOM'],
                    ['name' => 'daily-item-code.index', 'label' => 'Daily Production Plan'],
                    ['name' => 'ppic.machine-daily-report', 'label' => 'Laporan Produksi Mesin'],
                    ['name' => 'spk.changes.index', 'label' => 'Audit Log SPK (SAP Sync)'],
                    ['name' => 'spk.bom-changes.index', 'label' => 'SPK BOM Changes'],
                    ['name' => 'capacityforecastindex', 'label' => 'Capacity By Forecast'],
                    ['name' => 'waiting_purchase_orders.index', 'label' => 'Waiting Purchase Orders'],
                    ['name' => 'notification_recipients.index', 'label' => 'Notification Recipients'],
                    ['name' => 'maintenance.checklist-report', 'label' => 'Checklist Predictive Maintenance'],
                    ['name' => 'maintenance.index', 'label' => 'Maintenance Index'],
                    ['name' => 'invlinelist', 'label' => 'Machine List'],
                ]" />

                <!-- Dropdown: Maintenance & Machine -->
                <livewire:parent-dropdown label="Maintenance & Machine" :initiallyOpen="false" :childRoutes="[
                    ['name' => 'maintenance.checklist-report', 'label' => 'Checklist Predictive Maintenance'],
                    ['name' => 'maintenance.machine.index', 'label' => 'Maintenance Machine'],
                    ['name' => 'maintenance.mould.index', 'label' => 'Maintenance Mould'],
                    ['name' => 'maintenance.dashboard', 'label' => 'Dashboard Maintenance'],
                    ['name' => 'machine.dashboard', 'label' => 'Dashboard Machine'],
                    ['name' => 'mould.dashboard', 'label' => 'Dashboard Mould'],
                ]" />

                @if (app(\App\Services\PlantContextService::class)->supportsSecondProcess())
                    @php
                        $secondProcessLinks1 = array_values(array_filter([
                            auth()->user()?->can('second-process-work-orders') ? ['name' => 'sp-work-orders.index', 'label' => 'Work Orders'] : null,
                            auth()->user()?->can('second-process-first-piece') ? ['name' => 'first-piece-inspections.index', 'label' => 'First Piece Inspections'] : null,
                            auth()->user()?->can('second-process-ipqc') ? ['name' => 'ipqc-inspections.index', 'label' => 'IPQC Inspections'] : null,
                            auth()->user()?->can('second-process-dashboard') ? ['name' => 'second-process.dashboard', 'label' => 'Floor Overview Dashboard'] : null,
                            auth()->user()?->can('second-process-approvals') ? ['name' => 'sp-approvals.index', 'label' => 'Production Approvals'] : null,
                            auth()->user()?->can('second-process-analytics') ? ['name' => 'second-process.report-analytics', 'label' => 'Daily Report Analytics'] : null,
                            auth()->user()?->can('second-process-reports') ? ['name' => 'second-process-reports.index', 'label' => 'Second Process Daily Report'] : null,
                        ]));
                    @endphp

                    @if (!empty($secondProcessLinks1))
                        <!-- Dropdown: Second Process -->
                        <livewire:parent-dropdown label="Second Process" :initiallyOpen="false" :childRoutes="$secondProcessLinks1" />
                    @endif
                @endif

                @if (in_array(strtoupper(auth()->user()?->role?->name ?? ''), ['ADMIN', 'SUPER-ADMIN', 'STORE', 'WAREHOUSE']))
                    <!-- 4. Dropdown: WMS & Warehouse -->
                    <livewire:parent-dropdown label="WMS & Warehouse" :initiallyOpen="false" :childRoutes="[
                        ['name' => 'wms.dashboard', 'label' => 'WMS Rack Availability Dashboard'],
                        ['name' => 'wms.pallet-form.create-delivery', 'label' => 'Scan Delivery FG (Program Warehouse)'],
                        ['name' => 'wms.pallet-form.daily-recap', 'label' => 'Rekap Harian Delivery FG'],
                        ['name' => 'wms.pallet-form.index', 'label' => 'Assign Slot & Riwayat Pallet'],
                        ['name' => 'wms.pallet-form.lookup', 'label' => 'Pallet Detail Check'],
                        ['name' => 'wms.pallet-form.sorting', 'label' => 'Pallet Sorting and Consolidation'],
                        ['name' => 'wms.pallet-form.picking-guide', 'label' => 'Delivery Picking Guide (FIFO)'],
                        ['name' => 'wms.mapping', 'label' => 'Warehouse Mapping (FG)'],
                        ['name' => 'wms.logs', 'label' => 'Audit Trail Logs'],
                        ['name' => 'updated.barcode.item.position', 'label' => 'Detailed Item List'],
                    ]" />

                    <!-- 5. Dropdown: Material Warehouse -->
                    <livewire:parent-dropdown label="Material Warehouse" :initiallyOpen="false" :childRoutes="[
                        ['name' => 'mwh.master-list.index', 'label' => 'Master List Material'],
                        ['name' => 'mwh.mapping', 'label' => 'Material Warehouse Mapping'],
                        ['name' => 'mwh.incoming.create', 'label' => 'Penerimaan Material (Incoming)'],
                        ['name' => 'mwh.outgoing.create', 'label' => 'Pengambilan Material (Outgoing)'],
                        ['name' => 'mwh.outgoing.history', 'label' => 'Riwayat Outgoing Material'],
                        ['name' => 'mwh.stock-card.index', 'label' => 'Kartu Stok Material (Stock Card)'],
                        ['name' => 'mwh.fifo-dashboard', 'label' => 'Director FIFO Dashboard (Live)'],
                        ['name' => 'mwh.pallets.index', 'label' => 'Stock & Pallet Material'],
                        ['name' => 'mwh.qr-lookup', 'label' => 'Scan QR Material'],
                    ]" />
                @endif

                <!-- 5. Dropdown: Delivery & Business -->
                <livewire:parent-dropdown label="Delivery & Business" :initiallyOpen="false" :childRoutes="[
                    ['name' => 'delivery-schedule.form', 'label' => 'Delivery Schedule Input (BARU)'],
                    ['name' => 'delivery-schedule.calendar', 'label' => 'Kalender Delivery Schedule'],
                    ['name' => 'delivery.analysis', 'label' => 'Delivery Schedule Terbaru'],
                    ['name' => 'production.forecast.index', 'label' => 'Forecast Production'],
                    ['name' => 'management.delivery.index', 'label' => 'Delivery Data Delete'],
                    ['name' => 'indexds', 'label' => 'Delivery Schedule Data (Old)'],
                ]" />

                <!-- 6. Dropdown: Sales Order & DO -->
                <livewire:parent-dropdown label="Sales Order & DO" :initiallyOpen="false" :childRoutes="[
                    ['name' => 'so.index', 'label' => 'DATA SO / DO Index'],
                    ['name' => 'so.dashboard', 'label' => 'SO Dashboard'],
                    ['name' => 'pegawai.scan', 'label' => 'Scan SO'],
                ]" />

                <!-- 7. Dropdown: Barcode & Scan -->
                <livewire:parent-dropdown label="Barcode & Scan" :initiallyOpen="false" :childRoutes="[
                    ['name' => 'barcode.custom.form', 'label' => 'Custom Barcode Generator'],
                    ['name' => 'barcode.custom.logs', 'label' => 'Custom Barcode Logs'],
                    ['name' => 'barcodeindex', 'label' => 'Generate Barcode'],
                    ['name' => 'inandout.index', 'label' => 'Scan Barcode In/Out'],
                    ['name' => 'list.barcode', 'label' => 'Scan Document Log'],
                    ['name' => 'barcode.history.index', 'label' => 'Print History (New)'],
                    ['name' => 'stockallbarcode', 'label' => 'Stock Status'],
                ]" />

                <!-- 8. Dropdown: SAP Monitor -->
                <livewire:parent-dropdown label="SAP Monitor" :initiallyOpen="false" :childRoutes="[
                    ['name' => 'receipt-production-logs', 'label' => 'Cek Data masuk ke SAP'],
                    ['name' => 'production-summary-monitor', 'label' => 'Cek Stock Program ke SAP'],
                    ['name' => 'wms.sap-sync-monitor-delivery', 'label' => 'SAP Sync Monitor'],
                    ['name' => 'qc-stock-transfer', 'label' => 'QC Stock Transfer (FFI → FG/RJCT)'],
                    ['name' => 'api.dashboard', 'label' => 'API Log Dashboard'],
                ]" />
            @else
                @if (
                    !auth()->user()?->can('view-store-links') &&
                        !auth()->user()?->can('view-business-links') &&
                        !auth()->user()?->can('view-production-links') &&
                        !auth()->user()?->can('view-quality-links'))
                    <livewire:sidebar-link href="{{ route('dashboard') }}" label="Dashboard" :active="request()->routeIs('dashboard')"
                        wire:navigate />

                    <livewire:sidebar-link href="{{ route('operator.daily-report') }}" label="Laporan Harian Mesin"
                        :active="request()->routeIs('operator.daily-report')" wire:navigate />

                    <livewire:sidebar-link href="{{ route('maintenance.machine.index') }}" label="Maintenance Machine"
                        :active="request()->routeIs('maintenance.machine.index')" wire:navigate />

                    <livewire:sidebar-link href="{{ route('maintenance.mould.index') }}" label="Maintenance Mould"
                        :active="request()->routeIs('maintenance.mould.index')" wire:navigate />

                    <livewire:sidebar-link href="{{ route('maintenance.checklist-report') }}" label="Checklist Predictive Maintenance"
                        :active="request()->routeIs('maintenance.checklist-report')" wire:navigate />
                @endif


                @if (auth()->user()?->can('view-warehouse-links'))
                    <livewire:parent-dropdown label="Moulding" :childRoutes="[
                        ['name' => 'production.bom.index', 'label' => 'Production BOM'],
                        ['name' => 'waiting_purchase_orders.index', 'label' => 'Waiting Purchase Orders'],
                        ['name' => 'notification_recipients.index', 'label' => 'Notification Recipients'],
                        ['name' => 'workshop.summary.dashboard', 'label' => 'Dashboard Proses'],
                        ['name' => 'dashboard.moulding.tv', 'label' => 'Dashboard Project'],
                    ]" />

                    <livewire:sidebar-link href="{{ route('production.bom.index') }}" label="Production BOM"
                        :active="request()->routeIs('production.bom.index')" wire:navigate />

                    <livewire:sidebar-link href="{{ route('waiting_purchase_orders.index') }}"
                        label="Waiting Purchase Orders" :active="request()->routeIs('waiting_purchase_orders.index')" wire:navigate />

                    <livewire:sidebar-link href="{{ route('notification_recipients.index') }}"
                        label="Notification Recipients" :active="request()->routeIs('notification_recipients.index')" wire:navigate />
                @endif

                @if (auth()->user()?->can('view-business-links'))
                    <livewire:sidebar-link href="{{ route('delivery-schedule.form') }}"
                        label="Delivery Schedule Input (BARU)" :active="request()->routeIs('delivery-schedule.form')" wire:navigate />

                    <livewire:sidebar-link href="{{ route('delivery-schedule.calendar') }}"
                        label="Kalender Delivery Schedule" :active="request()->routeIs('delivery-schedule.calendar')" wire:navigate />

                    <livewire:sidebar-link href="{{ route('delivery.analysis') }}" label="Delivery Schedule Terbaru"
                        :active="request()->routeIs('delivery.analysis')" wire:navigate />
                @endif


                <!-- PE Links -->
                @if (auth()->user()?->can('view-pe-links'))
                    <livewire:sidebar-link href="{{ route('master-item.index') }}" label="Master Item"
                        :active="request()->routeIs('master-item.index')" wire:navigate />

                    <livewire:sidebar-link href="{{ route('master-bom.index') }}" label="Master List BOM"
                        :active="request()->routeIs('master-bom.index')" wire:navigate />

                    <livewire:sidebar-link href="{{ route('spk.bom-changes.index') }}" label="SPK BOM Changes"
                        :active="request()->routeIs('spk.bom-changes.*')" wire:navigate />
                @endif


                @if (auth()->user()?->can('view-production-links'))
                    <livewire:sidebar-link href="{{ route('receipt-production-logs') }}" label="Cek Data masuk ke SAP"
                        :active="request()->routeIs('receipt-production-logs')" wire:navigate />

                    <livewire:sidebar-link href="{{ route('production-summary-monitor') }}"
                        label="Cek stock dari Program ke SAP" :active="request()->routeIs('production-summary-monitor')" wire:navigate />

                    <livewire:sidebar-link href="{{ route('wms.sap-sync-monitor-delivery') }}" label="SAP Sync Monitor"
                        :active="request()->routeIs('wms.sap-sync-monitor*')" wire:navigate />

                    <livewire:sidebar-link href="{{ route('api.dashboard') }}" label="API Log Dashboard"
                        :active="request()->routeIs('api.dashboard')" wire:navigate />
                @endif


                <!-- PPIC Links -->
                @if (auth()->user()?->can('view-ppic-links'))
                    <livewire:sidebar-link href="{{ route('daily-item-code.index') }}" label="Daily Production Plan"
                        :active="request()->routeIs('daily-item-code.index')" wire:navigate />
                    <livewire:sidebar-link href="{{ route('ppic.listup.index') }}" label="PPIC List Up Mesin"
                        :active="request()->routeIs('ppic.listup.*')" wire:navigate />
                    <livewire:sidebar-link href="{{ route('ppic.machine-daily-report') }}"
                        label="Laporan Produksi Mesin" :active="request()->routeIs('ppic.machine-daily-report')" wire:navigate />
                    <livewire:sidebar-link href="{{ route('spk.changes.index') }}" label="Audit Log SPK (SAP Sync)"
                        :active="request()->routeIs('spk.changes.index')" wire:navigate />
                    <livewire:sidebar-link href="{{ route('spk.bom-changes.index') }}" label="SPK BOM Changes"
                        :active="request()->routeIs('spk.bom-changes.*')" wire:navigate />
                    <livewire:sidebar-link href="{{ route('master-list-item') }}" label="Master List Item"
                        :active="request()->routeIs('master-list-item')" wire:navigate />

                    <livewire:sidebar-link href="{{ route('receipt-production-logs') }}" label="Cek Data SPK ke SAP"
                        :active="request()->routeIs('receipt-production-logs')" wire:navigate />

                    @if (app(\App\Services\PlantContextService::class)->supportsSecondProcess() && auth()->user()?->can('view-second-process-reports'))
                        <!-- Second Process — Reports & Analytics for PPIC -->
                        <livewire:parent-dropdown label="Second Process — Reports & Analytics" :initiallyOpen="false" :childRoutes="[
                            ['name' => 'second-process.report-analytics', 'label' => 'Daily Report Analytics'],
                            ['name' => 'second-process-reports.index', 'label' => 'Second Process Daily Report'],
                        ]" />
                    @endif
                @endif

                <!-- Store Links -->
                @if (auth()->user()?->can('view-store-links'))
                    {{-- Consolidated Packaging Menu --}}
                    <livewire:parent-dropdown label="Packaging Menu" :initiallyOpen="false" :childRoutes="[
                        ['name' => 'barcode.custom.form', 'label' => 'Custom Barcode Generator'],
                        ['name' => 'barcode.custom.logs', 'label' => 'Custom Barcode Logs'],
                        ['name' => 'barcodeindex', 'label' => 'Generate Barcode'],
                        ['name' => 'barcode.box_master.index', 'label' => 'Master Box Data'],
                        ['name' => 'barcode.box_detail.index', 'label' => 'Master Box Detail'],
                        ['name' => 'barcode.history.index', 'label' => 'Print History (New)'],
                        ['name' => 'stockallbarcode', 'label' => 'Stock Status'],
                        ['name' => 'list.barcode', 'label' => 'Scan Document Log'],
                        ['name' => 'updated.barcode.item.position', 'label' => 'Detailed Item List'],
                        ['name' => 'summaryDashboard', 'label' => 'Packaging Dashboard'],
                    ]" />

                    <livewire:sidebar-link href="{{ route('inandout.index') }}" label="Scan Barcode (In/Out)"
                        :active="request()->routeIs('inandout.index')" wire:navigate />

                    <livewire:sidebar-link href="{{ route('customer.add') }}" label="Manage Customer"
                        :active="request()->routeIs('customer.add')" wire:navigate />

                    <livewire:sidebar-link href="{{ route('so.dashboard') }}" label="SO DASHBOARD" :active="request()->routeIs('so.dashboard')"
                        wire:navigate />

                    <livewire:sidebar-link href="{{ route('so.index') }}" label="DATA SO" :active="request()->routeIs('so.index')"
                        wire:navigate />

                    <livewire:sidebar-link href="{{ route('pegawai.scan') }}" label="SCAN SO" :active="request()->routeIs('pegawai.scan')"
                        wire:navigate />

                    <livewire:sidebar-link href="{{ route('production-summary-monitor') }}"
                        label="Cek stock Program ke SAP" :active="request()->routeIs('production-summary-monitor')" wire:navigate />

                    <livewire:sidebar-link href="{{ route('receipt-production-logs') }}" label="Cek Data masuk ke SAP"
                        :active="request()->routeIs('receipt-production-logs')" wire:navigate />

                    <livewire:sidebar-link href="{{ route('wms.dashboard') }}"
                        label="WMS Rack Availability Dashboard" :active="request()->routeIs('wms.dashboard')" wire:navigate />

                    <livewire:sidebar-link href="{{ route('wms.pallet-form.create-delivery') }}"
                        label="Scan Delivery FG" :active="request()->routeIs('wms.pallet-form.create-delivery')" wire:navigate />

                    <livewire:sidebar-link href="{{ route('wms.pallet-form.daily-recap') }}"
                        label="Rekap Harian Delivery" :active="request()->routeIs('wms.pallet-form.daily-recap')" wire:navigate />

                    <livewire:sidebar-link href="{{ route('wms.pallet-form.index') }}"
                        label="Assign Slot & Riwayat Pallet" :active="request()->routeIs('wms.pallet-form.index')" wire:navigate />

                    <livewire:sidebar-link href="{{ route('wms.pallet-form.lookup') }}" label="Pallet Detail Check"
                        :active="request()->routeIs('wms.pallet-form.lookup')" wire:navigate />

                    <livewire:sidebar-link href="{{ route('wms.logs') }}" label="Audit Trail Logs" :active="request()->routeIs('wms.logs')"
                        wire:navigate />

                    <livewire:sidebar-link href="{{ route('wms.mapping') }}" label="Warehouse Mapping"
                        :active="request()->routeIs('wms.mapping')" wire:navigate />

                    <livewire:parent-dropdown label="Gudang Material (MWH)" :initiallyOpen="false" :childRoutes="[
                        ['name' => 'mwh.master-list.index', 'label' => 'Master List Material'],
                        ['name' => 'mwh.mapping', 'label' => 'Material Warehouse Mapping'],
                        ['name' => 'mwh.incoming.create', 'label' => 'Penerimaan Material (Incoming)'],
                        ['name' => 'mwh.outgoing.create', 'label' => 'Pengambilan Material (Outgoing)'],
                        ['name' => 'mwh.outgoing.history', 'label' => 'Riwayat Outgoing Material'],
                        ['name' => 'mwh.stock-card.index', 'label' => 'Kartu Stok Material (Stock Card)'],
                        ['name' => 'mwh.fifo-dashboard', 'label' => 'Director FIFO Dashboard (Live)'],
                        ['name' => 'mwh.pallets.index', 'label' => 'Stock & Pallet Material'],
                        ['name' => 'mwh.qr-lookup', 'label' => 'Scan QR Material'],
                    ]" />

                    <livewire:sidebar-link href="{{ route('wms.pallet-form.sorting') }}"
                        label="Pallet Sorting and Consolidation" :active="request()->routeIs('wms.pallet-form.sorting')" wire:navigate />

                    <livewire:sidebar-link href="{{ route('wms.pallet-form.picking-guide') }}"
                        label="Delivery Picking Guide (FIFO)" :active="request()->routeIs('wms.pallet-form.picking-guide')" wire:navigate />

                    <livewire:sidebar-link href="{{ route('wms.sap-sync-monitor-delivery') }}"
                        label="SAP Sync Monitor" :active="request()->routeIs('wms.sap-sync-monitor*')" wire:navigate />
                @endif

                <hr>

                <!-- Maintenance Links -->
                @if (auth()->user()?->can('view-maintenance-links') || auth()->user()?->role_id == 8 || auth()->user()?->role?->name === 'MAINTENANCE')
                    <livewire:sidebar-link href="{{ route('maintenance.checklist-report') }}" label="Checklist Predictive Maintenance"
                        :active="request()->routeIs('maintenance.checklist-report')" wire:navigate />
                    <livewire:sidebar-link href="{{ route('maintenance.index') }}" label="Maintenance Index"
                        :active="request()->routeIs('maintenance.index')" wire:navigate />
                @endif

                <!-- Second Process Links (Departmental) -->
                @if (app(\App\Services\PlantContextService::class)->supportsSecondProcess() && !auth()->user()?->can('view-quality-links'))
                    @php
                        $secondProcessLinks2 = array_values(array_filter([
                            auth()->user()?->can('second-process-work-orders') ? ['name' => 'sp-work-orders.index', 'label' => 'Work Orders'] : null,
                            auth()->user()?->can('second-process-first-piece') ? ['name' => 'first-piece-inspections.index', 'label' => 'First Piece Inspections'] : null,
                            auth()->user()?->can('second-process-ipqc') ? ['name' => 'ipqc-inspections.index', 'label' => 'IPQC Inspections'] : null,
                            auth()->user()?->can('second-process-dashboard') ? ['name' => 'second-process.dashboard', 'label' => 'Floor Overview Dashboard'] : null,
                            auth()->user()?->can('second-process-approvals') ? ['name' => 'sp-approvals.index', 'label' => 'Production Approvals'] : null,
                            auth()->user()?->can('second-process-analytics') ? ['name' => 'second-process.report-analytics', 'label' => 'Daily Report Analytics'] : null,
                            auth()->user()?->can('second-process-reports') ? ['name' => 'second-process-reports.index', 'label' => 'Second Process Daily Report'] : null,
                        ]));
                    @endphp

                    @if (!empty($secondProcessLinks2))
                        <!-- Dropdown: Second Process -->
                        <livewire:parent-dropdown label="Second Process" :initiallyOpen="false" :childRoutes="$secondProcessLinks2" />
                    @endif
                @endif

                @if (auth()->user()?->can('view-quality-links'))
                    <livewire:sidebar-link href="{{ route('qc-stock-transfer') }}" label="QC Transfer (Karawang)"
                        :active="request()->routeIs('qc-stock-transfer')" wire:navigate />
                    <livewire:sidebar-link href="{{ route('qc-stock-transfer-kbn') }}" label="QC Transfer (KBN)"
                        :active="request()->routeIs('qc-stock-transfer-kbn')" wire:navigate />
                    @if (app(\App\Services\PlantContextService::class)->supportsSecondProcess())
                        <livewire:sidebar-link href="{{ route('ipqc-inspections.index') }}" label="IPQC Inspections"
                            :active="request()->routeIs('ipqc-inspections.*')" wire:navigate />
                    @endif
                @endif
            @endif
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
                @if (auth()->user() && auth()->user()?->can('view-admin-links'))
                    <a href="{{ route('profile') }}" wire:navigate
                        class="p-2 text-gray-500 hover:text-blue-600 hover:bg-white rounded-lg transition"
                        title="Profile">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </a>
                @endif

                <button wire:click="logout"
                    class="px-2.5 py-1.5 text-rose-600 hover:text-white hover:bg-rose-600 bg-rose-50 border border-rose-200/80 rounded-xl transition flex items-center space-x-1 text-xs font-bold shadow-xs active:scale-95"
                    title="Log Out">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>Logout</span>
                </button>
            </div>
        </div>
    </div>
</aside>
