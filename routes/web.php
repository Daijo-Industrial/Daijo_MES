<?php

use App\Http\Controllers\AssemblyDailyController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\DailyItemCodeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FirstPieceInspectionController;
use App\Http\Controllers\SecondProcessReportController;
use App\Http\Controllers\SecondProcessDashboardController;
use App\Http\Controllers\Production\BillOfMaterialController;
use App\Http\Controllers\Production\WorkshopController;
use App\Http\Controllers\Production\ForecastProductionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\InitialBarcodeController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\MasterItemController;
use App\Http\Controllers\DeliveryScheduleController;
use App\Http\Controllers\UpdateDailyController;
use App\Http\Controllers\NotificationRecepientController;
use App\Http\Controllers\Store\SOController;
use App\Http\Controllers\WaitingPurchaseOrderController;
use App\Http\Controllers\InvLineListController;
use App\Http\Controllers\Setting\HolidayScheduleController;
use App\Http\Controllers\CapacityByForecastController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\OperatorUserController;
use App\Http\Controllers\ProductionDashboardController;
use App\Http\Controllers\MasterListItemController;
use App\Http\Controllers\DaijoMesHomeController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\ProductionReportController;
use App\Http\Controllers\ProductionNgController;
use App\Http\Controllers\MasterItemPhotoController;
use App\Http\Controllers\DeliveryVerificationController;
use App\Http\Controllers\ProductionPayableController;



use App\Livewire\Barcode\StoreDashboard;
use App\Livewire\DeliveryAnalysis;

use App\Livewire\Maintenance\Machine\Index as MaintenanceMachineIndex;
use App\Livewire\Maintenance\Mould\Index as MaintenanceMouldIndex;
use App\Livewire\Maintenance\MaintenanceDashboard as DashboardMaintenance;
use App\Livewire\Maintenance\MachineDashboard as DashboardMachine;
use App\Livewire\Maintenance\MouldDashboard as DashboardMould;

use App\Livewire\DeliveryScheduleForm;
use App\Livewire\DeliveryScheduleCalendar;

use App\Livewire\MasterListItemView;

use Illuminate\Support\Facades\Auth;
use App\Models\User;

use App\Services\BaseSapService;
use App\Services\SpkMasterService;

use App\Services\BomWipService;

use App\Services\DelActualService;

use App\Services\DelSoService;

use App\Services\InventoryFgService;

use App\Services\InventoryMtrService;

use App\Services\LineProductionService;

use App\Services\RejectService;

use App\Livewire\LoginSwitcher as LivewireLoginSwitcher;

use App\Livewire\Asakai\AsakaiForm;
use App\Livewire\Asakai\AsakaiList;
use App\Livewire\Asakai\AsakaiDetail;
use App\Livewire\Report\DailyReport;
use App\Livewire\Report\WeeklyReport;
use App\Livewire\Report\MonthlyReport;
use App\Http\Controllers\ReportController;
use App\Livewire\ProductionDashboard;

use App\Livewire\ProductionSummaryMonitor;
use App\Livewire\ReceiptProductionLogs;
use App\Livewire\ManualSync;




/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

    //barcode untuk generate label produksi terbaru 
    Route::get('/barcode/custom-generate', [InitialBarcodeController::class, 'customGenerateForm'])->name('barcode.custom.form');
    Route::get('/barcode/custom-generate/logs', [InitialBarcodeController::class, 'customGenerateLogs'])->name('barcode.custom.logs');
    Route::post('/barcode/custom-generate/print', [InitialBarcodeController::class, 'customGeneratePrint'])->name('barcode.custom.print');
    Route::get('/barcode/custom-generate/reprint/{id}', [InitialBarcodeController::class, 'customGenerateReprint'])->name('barcode.custom.reprint');
    Route::get('/api/get-spks-by-item', [InitialBarcodeController::class, 'getSpksByItem'])->name('api.get-spks-by-item');
    //barcode untuk generate label produksi terbaru 


    Route::get('/machine-monitor', [\App\Http\Controllers\Production\MachineStatusController::class, 'index'])
        ->name('machine.monitor');




    Route::get('/production-dashboard', ProductionDashboard::class)
        ->name('production-dashboard');
    

   Route::prefix('asakai')->name('asakai.')->group(function () {
        Route::get('/', AsakaiList::class)->name('index');
        Route::get('/create', AsakaiForm::class)->name('create');
        Route::get('/{id}/edit', AsakaiForm::class)->name('edit');
        Route::get('/{id}', AsakaiDetail::class)->name('detail');
    });


    // ============================================
    // REPORT ROUTES (NO AUTH)
    // ============================================
    Route::prefix('report')->name('report.')->group(function () {
        Route::get('/daily', DailyReport::class)->name('daily');
        Route::get('/daily/export', [ReportController::class, 'exportDaily'])->name('daily.export');
        
        Route::get('/weekly', WeeklyReport::class)->name('weekly');
        Route::get('/weekly/export', [ReportController::class, 'exportWeekly'])->name('weekly.export');
        
        Route::get('/monthly', MonthlyReport::class)->name('monthly');
        Route::get('/monthly/export', [ReportController::class, 'exportMonthly'])->name('monthly.export');
        
        // New Route for Machine Active Hours
        Route::get('/machine-active-hours', \App\Livewire\Report\MachineActiveHours::class)->name('machine-active-hours');
    });


    //Route untuk delivery verification controller
    Route::get('/delivery-verification', [DeliveryVerificationController::class, 'index'])->name('delivery.verification');
    Route::post('/delivery-verification', [DeliveryVerificationController::class, 'check'])->name('delivery.verification.check');
    //Route untuk delivery verification controller



    //Route untuk photo master item 
    Route::get('/master-items-photo', [MasterItemPhotoController::class, 'index'])->name('master.items');

    Route::post('/master-items-photo/{itemCode}/upload', [MasterItemPhotoController::class, 'upload'])
        ->name('master.items.upload');
    //Route untuk photo master item

    Route::get('/master-list-item', MasterListItemView::class)->name('master-list-item');
    Route::get('/master-bom', \App\Livewire\MasterBomView::class)->name('master-bom.index');

    //ROUTE untuk handle ng-type produksi 
    Route::get('/ng-types', [ProductionNgController::class, 'index'])->name('ngtypes.index');
    Route::post('/ng-types', [ProductionNgController::class, 'store'])->name('ngtypes.store');
    Route::delete('/ng-types/{id}', [ProductionNgController::class, 'destroy'])->name('ngtypes.delete');
    //ROUTE untuk handle ng-type produksi 

    Route::get('/production-daily-report', [ProductionReportController::class, 'index'])->name('production.report');


    // ROUTE UNTUK MONITORING SPK MBA EMMA / INTAN
    Route::get('/monitoring-spk', [MonitoringController::class, 'index'])
        ->name('monitoring.spk.index');

    Route::get('/monitoring-spkdetail/{spk}', [MonitoringController::class, 'show'])
        ->name('monitoring.spk.detail');

    // ROUTE UNTUK PERBANDINGAN & LOG PERUBAHAN SPK (SPK CHANGE TRACKING)
    Route::get('/spk-changes', [\App\Http\Controllers\SpkChangeLogController::class, 'index'])->name('spk.changes.index');
    Route::post('/spk-changes/sync', [\App\Http\Controllers\SpkChangeLogController::class, 'triggerSync'])->name('spk.changes.sync');
    Route::get('/spk-changes/history/{spk}', [\App\Http\Controllers\SpkChangeLogController::class, 'getHistory'])->name('spk.changes.history');

    // ROUTE UNTUK SPK BOM CHANGES (INTEGRASI SPK & FORMULA BOM NON-WIP)
    Route::get('/spk-bom-changes', \App\Livewire\SpkBomChangesView::class)->name('spk.bom-changes.index');
    Route::get('/spk-bom-changes/preview-payload', [\App\Http\Controllers\SpkBomPayloadPreviewController::class, 'show'])->name('spk.bom-changes.preview-payload');

    // ROUTE UNTUK MONITORING SPK MBA EMMA / INTAN
    // Route::get('/{user}', [DashboardController::class, 'autoLogin']);

    Route::get('/machine-monitor', [\App\Http\Controllers\Production\MachineStatusController::class, 'index'])
        ->name('machine.monitor');

    Route::get("/test-bomwip", function (LineProductionService $LineProductionService) {
        return $LineProductionService->SyncData();
    });


    Route::get("/test-sap-data", function (BaseSapService $sap) {
        $data = $sap->testGet("/api/sap_bom_wip/list", [
            "startDate" => "2025-03-01",
            "itemGroupCode" => "103",
        ]);
        return response()->json($data);
    });


    // Route untuk barcode ALC Engineering 
    Route::get('/barcodealcindex', [BarcodeController::class, 'alcindex'])->name('barcode.alc.index');

    Route::get('/all-label-yanfeng', [BarcodeController::class, 'generateAllLabelYangeng'])->name('all.label.yanfeng');

    Route::post('/generate-label-yanfeng40x15', [BarcodeController::class, 'generateLabelYangeng40x15'])->name('generate.label.yanfeng40x15');   
    Route::post('/generate-label-yanfeng25x10', [BarcodeController::class, 'generateLabelYangeng25x10'])->name('generate.label.yanfeng25x10');  
    
    Route::post('/generate-label-yanfeng50x20', [BarcodeController::class, 'generateLabelYangeng50x20'])->name('generate.label.yanfeng50x20');  
    Route::post('/generate-label-yanfeng50x35', [BarcodeController::class, 'generateLabelYangeng50x35'])->name('generate.label.yanfeng50x35');   
    // Route untuk barcode ALC Engineering 


    //Route untuk maintenance dan mould dashboard produksi 
    Route::get('/maintenance-dashboard',  DashboardMaintenance::class)
        ->name('maintenance.dashboard');

    Route::get('/machine-dashboard',  DashboardMachine::class)
        ->name('machine.dashboard');
    
    Route::get('/mould-dashboard',  DashboardMould::class)
        ->name('mould.dashboard');

    // Route Maintenance Predictive Checklist Modal & Admin Report
    Route::get('/maintenance-checklist/today/{machineId}', [\App\Http\Controllers\MaintenanceChecklistController::class, 'getTodayData'])->name('maintenance.checklist.today');
    Route::post('/maintenance-checklist/save', [\App\Http\Controllers\MaintenanceChecklistController::class, 'store'])->name('maintenance.checklist.save');
    Route::get('/maintenance/checklist-report', \App\Livewire\Maintenance\ChecklistReport::class)->name('maintenance.checklist-report');
    
    //Route untuk maintenance dan mould dashboard produksi 

    //Route untuk dashboard Store 
    Route::get('/dashboard/store', StoreDashboard::class)->name('dashboard.store');
    //Route untuk dashboard Store 
        

    Route::get('test/spk/1', [SpkMasterService::class, 'getAll']);


    //Route untuk ubah status DIC oleh admin 
    Route::post('/admin/dailyitemcodes/{id}/set-status', [ProductionDashboardController::class, 'setStatus'])->name('dailyitemcodes.set-status');
    //Route untuk ubah status DIC oleh admin 

    //Route untuk hapus hourly remark
    Route::delete('/hourly-remarks/{id}', [ProductionDashboardController::class, 'destroyHourlyRemark'])->name('hourly-remarks.destroy');
    //Route untuk hapus hourly remark

    //Route untuk view admin daily item code
    Route::get('/admin/dailyitemcodes', [ProductionDashboardController::class, 'adminView'])->name('admin.dailyitemcodes');
    //Route untuk view admin daily item code

    Route::get('/test/ROPdata', [DashboardController::class, 'showROPData']);


    //-- Route Ajax untuk get daily item codes dan hitung perhitungan max quantity spk 
    Route::get('/daily-item-code/get-item-codes', [DailyItemCodeController::class, 'getItemCodes'])
        ->name('daily-item-code.get-item-codes');

    Route::get('/daily-item-code/max-quantity', [DailyItemCodeController::class, 'getMaxQuantity'])
        ->name('daily-item-code.get-max-quantity');
    //-- Route Ajax untuk get daily item codes dan hitung perhitungan max quantity spk 



    //Route untuk cek data yang akan dikirim ke SAP 
    Route::get('/send-api', [DailyItemCodeController::class, 'generateDataForSap'])->name('send.api');

    //Route untuk Home Daijo MES (Dashboard utama untuk semua fitur)
    Route::get('/daijo-mes-home', [DaijoMesHomeController::class, 'index'])
        ->name('daijo.mes.home');
     //Route untuk Home Daijo MES (Dashboard utama untuk semua fitur)

    //Route untuk production day dashboard dan api log dashboard
    Route::get('/production-day-dashboard', [ProductionDashboardController::class, 'index'])->name('djoni.dashboard');
    Route::get('/api-log-dashboard', \App\Livewire\Report\ApiLogDashboard::class)->name('api.dashboard');
    Route::get('/get-machines-by-item', [ProductionDashboardController::class, 'getMachinesByItem']);
    //Route untuk production day dashboard dan api log dashboard

    //Route untuk handle operator dan operator baru 
    Route::get('/id-card/operator', [OperatorUserController::class, 'showIdCard']);
    Route::get('/operator-users/qr-codes', [OperatorUserController::class, 'showQr']);
    Route::get('/operator-users/upload', [OperatorUserController::class, 'uploadForm'])->name('operator-users.upload');
    Route::post('/operator-users/import', [OperatorUserController::class, 'import'])->name('operator-users.import');
    Route::get('/operator-users/show-all', [OperatorUserController::class, 'showAllOperator'])->name('show.all.operators');
    Route::get('/operator/create', [OperatorUserController::class, 'createOperator'])->name('operator.create');
    Route::post('/operator/store', [OperatorUserController::class, 'storeOperator'])->name('operator.store');

    Route::put('/operator/update-profile-picture', [OperatorUserController::class, 'updateProfilePicture'])->name('operator.updateProfilePicture');
    Route::get('/operator-users', [OperatorUserController::class, 'index'])->name('operator.index');

    //Route untuk handle operator dan operator baru 

    //masih belum tau 
    Route::get('/external-users', [UpdateDailyController::class, 'getUsers']);
    //masih belum tau 

    //bawaaan diss 
    Route::get('/capacity-forecast-dashboard', [CapacityByForecastController::class, 'dashboard'])->name('capacity_forecast_dashboard');
    //bawaan diss



    // Handle the POST request to update a Master Zone

    //Route untuk handle Zone mesin 
    Route::get('/zone/edit', [OperatorUserController::class, 'editZone'])->name('zone.edit');
    Route::post('/zone/update', [OperatorUserController::class, 'updateZone'])->name('zone.update');
    //Route untuk handle Zone mesin 


    Route::redirect('/', '/login');

    // Route::view('dashboard', 'dashboard')
    //     ->middleware(['auth', 'verified'])
    //     ->name('dashboard');

    Route::get('dashboard', [DashboardController::class,'index'])->middleware(['auth', 'verified'])
        ->name('dashboard');


        //FITUR ADMIN OPERATOR 5 NOVEMBER 2025 
    Route::get('adminoperator', [DashboardController::class,'operatoradmin'])->middleware(['auth', 'verified'])
        ->name('adminoperator');
    Route::delete('/hourly-remarks/{id}', [DashboardController::class, 'deleteremark'])->name('hourlyremarks.destroy');
    Route::post('/storedic', [DashboardController::class, 'createFromAdmin'])->name('dicadmin.store');
    Route::delete('/dailyitemcodesdelete/{id}', [DashboardController::class, 'deletedic'])
        ->name('dailyitemcodes.delete');
        //FITUR ADMIN OPERATOR 5 NOVEMBER 2025 


        

    Route::view('profile', 'profile')
        ->middleware(['auth'])
        ->name('profile');

    // Route untuk auto login 
    // Auto-login route (must not interfere with other routes)
    Route::get('/auto-login/{user_id}', function ($user_id) {
        // dd($user_id);
        $user = User::where('name', $user_id)->first();

        if ($user && $user->is_active) {
            Auth::login($user);
            return redirect()->route('dashboard');
        }

        return redirect('/login')->withErrors(['error' => 'User not found or account is deactivated.']);
    })->where('user_id', '[0-9A-Za-z]+'); // Ensure only valid user IDs
    // Route untuk auto login

// Public Material Pallet Live Check (Cryptographically Signed URL - Safe for Public IP)
Route::get('/public/material-pallet/{palletId}', function (\Illuminate\Http\Request $request, $palletId) {
    if (! $request->hasValidSignature()) {
        abort(403, 'Akses Ditolak: Halaman ini hanya dapat diakses melalui QR Code fisik resmi.');
    }

    $pallet = \App\Models\MwhPallet::with([
        'warehouse',
        'position.rack.warehouse',
        'initialPosition.rack.warehouse',
        'material',
        'incomingHeader',
        'outgoings.position.rack'
    ])->where('pallet_id', strtoupper($palletId))->first();

    return view('material-warehouse.public_pallet_lookup', compact('pallet', 'palletId'));
})->name('mwh.public-pallet-lookup')->middleware('throttle:60,1');

Route::get('/public/material-warehouse/mapping', \App\Livewire\MaterialWarehouse\PublicRackMapping::class)
    ->name('mwh.public-mapping')
    ->middleware('throttle:60,1');

// Director FIFO Dashboard (Public Access - No Login Required)
Route::get('/material-warehouse/fifo-dashboard', \App\Livewire\MaterialWarehouse\MaterialFifoDirectorDashboard::class)
    ->name('mwh.fifo-dashboard');
Route::get('/material-warehouse/director-dashboard', \App\Livewire\MaterialWarehouse\MaterialFifoDirectorDashboard::class)
    ->name('mwh.director-dashboard.public');
Route::get('/public/material-warehouse/fifo-dashboard', \App\Livewire\MaterialWarehouse\MaterialFifoDirectorDashboard::class)
    ->name('mwh.fifo-dashboard.public');


Route::middleware('auth')->group(function (){

    // Machine Daily Production Report
    Route::get('/operator/daily-report', \App\Livewire\Report\MachineDailyReport::class)->name('operator.daily-report');
    Route::get('/ppic/machine-daily-report', \App\Livewire\Report\MachineDailyReport::class)->name('ppic.machine-daily-report');
    Route::get('/ppic/machine-daily-report/export', [\App\Http\Controllers\PpicReportExportController::class, 'exportDailyProduction'])->name('ppic.machine-daily-report.export');

    Route::get('/user-role-manager', function () {
        if (\Illuminate\Support\Facades\Gate::denies('manage-users-roles')) {
            abort(403);
        }
        $currentTab = request()->query('tab', 'users');
        return view('admin.user_role_manager.index', compact('currentTab'));
    })->name('admin.user-role-manager');

    Route::get('/admin/branches-departments', function () {
        if (\Illuminate\Support\Facades\Gate::denies('manage-branches-departments')) {
            abort(403);
        }
        return view('admin.branches_departments.index');
    })->name('admin.branches-departments');

    Route::get('/admin/plant-departments', function () {
        if (\Illuminate\Support\Facades\Gate::denies('manage-branches-departments')) {
            abort(403);
        }
        return view('admin.branches_departments.index', ['initialTab' => 'plant-departments']);
    })->name('admin.plant-departments');

    // untuk update spk secara manual 
    Route::get('/manual-sync', ManualSync::class)->name('manual-sync');

    //untuk upload spk history di program store 
    Route::get('/upload-spk-history', \App\Livewire\UploadSpkHistory::class)->middleware('store.access')->name('upload.spk.history');

    Route::prefix('wms')->name('wms.')->group(function () {
        Route::get('/sap-sync-monitor', \App\Livewire\Wms\SapSyncMonitor::class)->name('sap-sync-monitor');
        Route::get('/sap-sync-monitor-delivery', \App\Livewire\Wms\SapSyncMonitor::class)->name('sap-sync-monitor-delivery');
    });

    Route::prefix('wms')->name('wms.')->middleware('store.access')->group(function () {
        Route::get('/dashboard', \App\Livewire\Wms\WmsDashboard::class)->name('dashboard');
        Route::get('/outbound', \App\Livewire\Wms\PalletOutbound::class)->name('outbound');
        Route::get('/mapping', \App\Livewire\Wms\RackMapping::class)->name('mapping');
        Route::get('/pallet-logs', \App\Livewire\Wms\PalletLogIndex::class)->name('logs');
        Route::get('/pallet-form/lookup', \App\Livewire\Wms\PalletFormLookup::class)->name('pallet-form.lookup');
        Route::get('/pallet-form/history', \App\Livewire\Wms\PalletFormIndex::class)->name('pallet-form.index');
        Route::get('/pallet-form/create-delivery', \App\Livewire\Wms\PalletFormCreator::class)->name('pallet-form.create-delivery');
        Route::get('/pallet-form/daily-recap', \App\Livewire\Wms\DailyDeliveryRecap::class)->name('pallet-form.daily-recap');
        Route::get('/pallet-form/sorting', \App\Livewire\Wms\PalletSorting::class)->name('pallet-form.sorting');
        Route::get('/pallet-form/picking-guide', \App\Livewire\Wms\PickingGuide::class)->name('pallet-form.picking-guide');
        Route::get('/pallet-form/print/{id}', function ($id) {
            $palletForm = \App\Models\WmsPalletForm::with('position')->findOrFail($id);
            return view('wms.pallet_form_print', compact('palletForm'));
        })->name('pallet-form.print');
    });

    Route::prefix('material-warehouse')->name('mwh.')->middleware('store.access')->group(function () {
        Route::get('/mapping', \App\Livewire\MaterialWarehouse\RackMapping::class)->name('mapping');
        Route::get('/master-list', \App\Livewire\MaterialWarehouse\MasterListMaterialIndex::class)->name('master-list.index');
        Route::get('/incoming/create', \App\Livewire\MaterialWarehouse\MaterialIncomingCreator::class)->name('incoming.create');
        Route::get('/pallets', \App\Livewire\MaterialWarehouse\MaterialPalletIndex::class)->name('pallets.index');
        Route::get('/outgoing/create', \App\Livewire\MaterialWarehouse\MaterialOutgoingCreator::class)->name('outgoing.create');
        Route::get('/outgoing/history', \App\Livewire\MaterialWarehouse\MaterialOutgoingHistory::class)->name('outgoing.history');
        Route::get('/stock-card', \App\Livewire\MaterialWarehouse\MaterialStockCard::class)->name('stock-card.index');
        Route::get('/qr-lookup', \App\Livewire\MaterialWarehouse\MaterialQrLookup::class)->name('qr-lookup');
        Route::get('/pallet/print/{palletId}', function ($palletId) {
            $pallet = \App\Models\MwhPallet::with([
                'incomingHeader', 
                'position.rack.warehouse', 
                'initialPosition.rack.warehouse',
                'material', 
                'warehouse',
                'outgoings.position.rack'
            ])->where('pallet_id', $palletId)->firstOrFail();
            return view('material-warehouse.material_pallet_print', compact('pallet'));
        })->name('pallet.print');
    });


    Route::prefix('production-payables')->group(function () {

        Route::get('/', [ProductionPayableController::class, 'index'])
            ->name('production-payables.index');

        Route::get('/upload', [ProductionPayableController::class, 'uploadForm'])
            ->name('production-payables.upload');

        Route::post('/import', [ProductionPayableController::class, 'import'])
            ->name('production-payables.import');

        Route::get('/{id}', [ProductionPayableController::class, 'show'])
            ->name('production-payables.show');

        Route::put('/{id}/status', [ProductionPayableController::class, 'updateStatus'])
            ->name('production-payables.status');

        Route::delete('/{id}', [ProductionPayableController::class, 'destroy'])
            ->name('production-payables.destroy');
    });


    Route::get('/production-summary-monitor', ProductionSummaryMonitor::class)->name('production-summary-monitor');

    Route::get('/receipt-production-logs', ReceiptProductionLogs::class)->name('receipt-production-logs');
    Route::get('/qc-stock-transfer', \App\Livewire\Qc\QcStockTransfer::class)->name('qc-stock-transfer');
    Route::get('/qc-stock-transfer-kbn', \App\Livewire\Qc\QcStockTransferKbn::class)->name('qc-stock-transfer-kbn');



    //-- Production project route
    Route::post('/dashboard/update-machine-job', [DashboardController::class, 'updateMachineJob'])->name('update.machine_job');
    Route::get('/generate-barcode/{item_code}/{quantity}', [DashboardController::class, 'itemCodeBarcode'])->name('generate.itemcode.barcode');
    Route::post('/process/itemproduction', [DashboardController::class, 'procesProductionBarcodes'])->name('process.productionbarcode');
    Route::post('/process/itemproduction/losspackage', [DashboardController::class, 'procesProductionBarcodesLoss'])->name('process.productionbarcodeloss');
    Route::get('/reset-jobs', [DashboardController::class, 'resetJobs'])->name('reset.jobs');
    Route::post('/update-employee-name', [DashboardController::class, 'updateEmployeeName'])->name('updateEmployeeName');
    Route::post('/verify-nik-password', [DashboardController::class, 'verifyNIKPassword'])->name('verify.nik.password');
    Route::post('/verify-nik-mould-change', [DashboardController::class, 'verifyNik'])->name('verify.nik');
    Route::post('/hourly-remarks/{id}/update-remark', [DashboardController::class, 'updateRemark']);
    Route::delete('/spk-scan/{id}', [DashboardController::class, 'deleteScanData'])->name('spk-scan.destroy');
    Route::put('/daily-item-codes/{id}/temporal-cycle-time', [DashboardController::class, 'updateCycleTime'])
    ->name('daily-item-codes.updateCycleTime');
    Route::put('/daily-item-codes/{id}/material-lot', [DashboardController::class, 'updateMaterialLot'])
    ->name('daily-item-codes.updateMaterialLot');
    Route::get('/daily-item-codes/{id}/accessory-lots', [DashboardController::class, 'getAccessoryLots'])
    ->name('daily-item-codes.getAccessoryLots');
    Route::post('/daily-item-codes/{id}/accessory-lots', [DashboardController::class, 'storeAccessoryLot'])
    ->name('daily-item-codes.storeAccessoryLot');
    Route::delete('/accessory-lots/{id}', [DashboardController::class, 'deleteAccessoryLot'])
    ->name('accessory-lots.destroy');
    Route::post('/hourly-remarks', [DashboardController::class, 'storeHourlyRemark'])->name('hourly-remarks.store');
    Route::post('/production-output-log', [DashboardController::class, 'storeOutputLog'])->name('production.output-log.store');
    Route::get('/production-output-log/print/{id}', [DashboardController::class, 'printOutputLog'])->name('production.output-log.print');

    Route::put('/daily-item-codes/{id}/temporal-cavity', [DashboardController::class, 'updateTemporalCavity'])
    ->name('daily-item-codes.updatecavity');

    Route::put('/daily-item-codes/{id}/resin-usage', [DashboardController::class, 'updateResinUsage'])
    ->name('daily-item-codes.updateresin');

    Route::post('/daily-item-codes/update-remark/{id}', [DashboardController::class, 'updateRemarkDIC']);

    Route::put('/hourly-remarks/{id}/update-actual-production', [DashboardController::class, 'updateActualProduction'])
    ->name('hourly-remarks.updateActualProduction');
    Route::put('/hourly-remarks/{id}/ng', [DashboardController::class, 'updateNgProduction'])
    ->name('hourly-remarks.updateNg');

    Route::post('/hourly-remark/{id}/add-ng', [DashboardController::class, 'addNg']);
    Route::get('/ng-detail/{id}', [DashboardController::class, 'showNgDetail']);
    Route::put('/ng-detail/{id}', [DashboardController::class, 'updateNgDetail']);
    Route::delete('/ng-detail/{id}', [DashboardController::class, 'destroyNgDetail']);

    // ROUTE UNTUK MOULD , ADJUST dan REPAIR (AJAX CALL)
    Route::post('/mould-change/start', [DashboardController::class, 'startMouldChange'])->name('mould.change.start');
    Route::post('/mould-change/end', [DashboardController::class, 'endMouldChange'])->name('mould.change.end');
    Route::post('/mould-change/update/{id}', [DashboardController::class, 'updateMouldChangeLog'])->name('mould.change.update');
    Route::post('/adjust-machine/start', [DashboardController::class, 'startAdjustMachine'])->name('adjust.machine.start');
    Route::post('/adjust-machine/end', [DashboardController::class, 'endAdjustMachine'])->name('adjust.machine.end');
    Route::post('/adjust-machine/update/{id}', [DashboardController::class, 'updateAdjustMachineLog'])->name('adjust.machine.update');
    Route::post('/repair-machine/start', [DashboardController::class, 'startRepairMachine'])->name('repair.machine.start');
    Route::post('/repair-machine/end', [DashboardController::class, 'endRepairMachine'])->name('repair.machine.end');
    Route::post('/repair-machine/update/{id}', [DashboardController::class, 'updateRepairMachineLog'])->name('repair.machine.update');
    // ROUTE UNTUK MOULD , ADJUST dan REPAIR (AJAX CALL)

    Route::get('/dashboardplastic', [DashboardController::class, 'dashboardPlastic']);
    Route::get('/reset-job', [DashboardController::class, 'resetJob'])->name('reset.job');

    //ROUTE UNTUK FITUR BARCODE STORE 
    Route::get('barcode/index', [BarcodeController::class, 'index'])->name('barcode.base.index');
    Route::get('barcode/inandout/index', [BarcodeController::class, 'inandoutpage'])->name('inandout.index');
    Route::get('barcode/missing/index', [BarcodeController::class, 'missingbarcodeindex'])->name('missingbarcode.index');
    Route::post('barcode/missing/generate', [BarcodeController::class, 'missingbarcodegenerator'])->name('generateBarcodeMissing');
    Route::post('barcode/process/save', [BarcodeController::class, 'processInAndOut'])->name('process.in.and.out');
    Route::post('process/inandoutbarcode', [BarcodeController::class, 'storeInAndOut'])->name('processbarcodeinandout');
    Route::get('indexbarcode', [BarcodeController::class, 'indexBarcode'])->name('barcodeindex');
    Route::post('packaging-barcode-generate', [BarcodeController::class, 'generateBarcode'])->name('generatepackagingbarcode');
    Route::post('packaging-barcode-zebra', [BarcodeController::class, 'generateLabelZebra'])->name('generate.zebra.barcode');
    Route::get('barcode/list', [BarcodeController::class, 'barcodelist'])->name('list.barcode');
    Route::get('barcode/latest/item', [BarcodeController::class, 'latestitemdetails'])->name('updated.barcode.item.position');
    Route::get('barcode/historytable', [BarcodeController::class, 'historybarcodelist'])->name('barcode.historytable');
    Route::get('/barcode/filter', [BarcodeController::class, 'filter'])->name('barcode.filter');
    Route::get('barcode/latest/item', [BarcodeController::class, 'latestitemdetails'])->name('updated.barcode.item.position');
    Route::get('barcode/stockall/{location?}', [BarcodeController::class, 'stockall'])->name('stockallbarcode');
    Route::get('barcode/summary', [BarcodeController::class, 'summaryDashboard'])->name('summaryDashboard');

    //Route untuk master box data (Part Master)
    Route::get('barcode/box-master', [BarcodeController::class, 'indexStoreBoxData'])->name('barcode.box_master.index');
    Route::post('barcode/box-master', [BarcodeController::class, 'storeStoreBoxData'])->name('barcode.box_master.store');
    Route::put('barcode/box-master/{id}', [BarcodeController::class, 'updateStoreBoxData'])->name('barcode.box_master.update');
    Route::delete('barcode/box-master/{id}', [BarcodeController::class, 'destroyStoreBoxData'])->name('barcode.box_master.destroy');

    //Route history barcode generated
    Route::get('barcode/history', [BarcodeController::class, 'historyStoreBoxDetails'])->name('barcode.history.index');
    Route::get('barcode/box-detail', [BarcodeController::class, 'indexStoreBoxDetail'])->name('barcode.box_detail.index');
    Route::put('barcode/box-detail/{id}', [BarcodeController::class, 'updateStoreBoxDetail'])->name('barcode.box_detail.update');
    Route::post('barcode/reprint', [BarcodeController::class, 'reprintBarcode'])->name('barcode.reprint');

    //Route untuk tambahin customer di store barcode
    Route::get('/add-customer', [BarcodeController::class, 'addCustomer'])->name('customer.add');
    Route::post('/add-customer', [BarcodeController::class, 'storeCustomer'])->name('customer.store');
    Route::delete('/customer/{id}', [BarcodeController::class, 'destroyCustomer'])->name('customer.destroy');
    //Route untuk tambahin customer di store barcode

    //Route untuk master item di store barcode
    Route::get('master-item', [MasterItemController::class, 'index'])->middleware(['auth', 'verified'])->name('master-item.index');
    //Route untuk master item di store barcode


    Route::get('/initialbarcode', [InitialBarcodeController::class, 'index'])->name('barcode.index');
    Route::post('/barcodes/generate', [InitialBarcodeController::class, 'generate'])->name('barcode.generate');
    Route::get('/manualbarcodes', [InitialBarcodeController::class, 'manualgenerate'])->name('manualbarcode.index');
    Route::post('/generate-barcode', [InitialBarcodeController::class, 'generateBarcode'])->name('generate.barcode');

    //ROUTE UNTUK FITUR BARCODE STORE 


    //ROUTE UNTUK FITUR DAILY ITEM CODES
    Route::get('/daily-item-codes', [DailyItemCodeController::class, 'index'])->name('daily-item-code.index');
    Route::post('/daily-item-code', [DailyItemCodeController::class, 'store'])->name('daily-item-code.store');
    Route::get('/daily-item-code', [DailyItemCodeController::class, 'create'])->name('daily-item-code.create');
    Route::post('/calculate-item', [DailyItemCodeController::class, 'calculateItem'])->name('calculate.item');
    Route::get('/daily-item-codes', [DailyItemCodeController::class, 'index'])->name('daily-item-code.index');
    Route::post('/apply-item-code/{machine_id}', [DailyItemCodeController::class, 'applyItemCode'])->name('apply-item-code');
    Route::get('/daily-item-codes/daily', [DailyItemCodeController::class, 'daily'])->name('daily-item-code.daily');
    Route::put('/daily-item-codes/{id}', [DailyItemCodeController::class, 'update'])->name('daily-item-code.update');
    Route::delete('/daily-item-codes/{id}', [DailyItemCodeController::class, 'destroy'])->name('daily-item-code.destroy');
    Route::get('/ppic/listup-machine', \App\Livewire\Ppic\PpicListUpMachine::class)->name('ppic.listup.index');
    //ROUTE UNTUK FITUR DAILY ITEM CODES

    //Route untuk bikin tanggal Maintenance (not finish)
    Route::get('/maintenance/index', [MaintenanceController::class, 'index'])->name('maintenance.index');
    //Route untuk bikin tanggal Maintenance (not finish)

    //Route untuk Handle SO dari diss 
    Route::get('/so/dashboard', [SOController::class, 'dashboard'])->name('so.dashboard');
    Route::get('/so/index', [SOController::class, 'index'])->name('so.index');
    Route::get('/so/filter', [SOController::class, 'index'])->name('so.filter');
    Route::get('/so/filterauto', [SoController::class, 'filter'])->name('so.filterauto');
    Route::get('/so/process/{docNum}', [SOController::class, 'process'])->name('so.process');
    Route::post('/so/scan', [SOController::class, 'scanBarcode'])->name('so.scanBarcode');
    Route::get('/update-so-data/{docNum}', [SOController::class, 'updateSoData'])->name('update.so.data');
    Route::post('/import-excel', [SOController::class, 'import'])->name('import.so.data');

    Route::get('/pegawai/scan', [SOController::class, 'indexpegawai'])->name('pegawai.scan');
    //Route untuk Handle SO dari diss 

    Route::put('/scan/{id}', [SOController::class, 'update'])
        ->name('scan.update');

    Route::delete('/scan/{id}', [SOController::class, 'destroy'])
        ->name('scan.delete');

    //Route untuk Handle SO dari diss

    

    // belum dipake sepertinya
    Route::get('/assembly-daily-process', [AssemblyDailyController::class, 'index'])->name('assembly.daily.process.index');
    Route::get('/assembly-daily-process/create', [AssemblyDailyController::class, 'create'])->name('assembly.daily.process.create');
    //  belum dipake sepertinya

    Route::post('/assembly-daily-process/store', [AssemblyDailyController::class, 'store'])->name('assembly.daily.process.store');

    //-- Production project route
    //Route untuk program Moulding Bill of Material
    Route::get('/production/bom/index', [BillOfMaterialController::class, 'index'])->name('production.bom.index');
    Route::get('/production/bill-of-material/{id}', [BillOfMaterialController::class, 'show'])->name('production.bom.show');
    Route::delete('/production/delete-bill-of-material/{id}', [BillOfMaterialController::class, 'destroy'])->name('production.bom.destroy');
    Route::put('/production/edit-bill-of-material/{id}', [BillOfMaterialController::class, 'update'])->name('production.bom.update');
    Route::put('/production/bom/child/{id}/cancel', [BillOfMaterialController::class, 'cancel'])->name('production.bom.child.cancel');
    Route::put('/production/child-edit-bill-of-material/{id}', [BillOfMaterialController::class, 'updateChild'])->name('production.bom.child.update');
    Route::delete('/production/delete-child-bill-of-material/child/{id}', [BillOfMaterialController::class, 'destroyChild'])->name('production.bom.child.destroy');
    Route::post('/production/add-new-child/{bomParent}', [BillOfMaterialController::class, 'storeChild'])->name('production.bom.child.store');
    Route::post('/production/add-new-child-excel/{bomParentId}', [BillOfMaterialController::class, 'uploadChildItems'])->name('production.bom.child.upload');
    Route::put('/production/bom/child/{child}/assign_type', [BillOfMaterialController::class, 'assignType'])->name('production.bom.child.assign_type');
    Route::put('/production/bom/child/{id}/updateStatus', [BillOfMaterialController::class, 'updateStatusChild'])->name('production.bom.child.updateStatus');
    Route::post('/production/bom/child/{id}/assign_process', [BillOfMaterialController::class, 'assignProcess'])->name('production.bom.child.assign_process');
    Route::get('/production/material-detail-child/{id}/show', [BillOfMaterialController::class, 'materialDetail'])->name('production.child.detail.material');
    Route::post('/production/bom/child/{childId}/addBrokenQuantity', [BillOfMaterialController::class, 'addBrokenQuantity'])->name('production.bom.child.addBrokenQuantity');
    Route::delete('/production/process-delete/{id}', [BillOfMaterialController::class, 'destroyProcess'])->name('production.process.delete');
    Route::post('/production/process/accept/{id}', [BillOfMaterialController::class, 'accept'])->name('production.process.accept');

    Route::post('/excel-bom-upload', [BillOfMaterialController::class, 'uploadExcelBom'])->name('excel.bom.upload');
    

    Route::get('/print-all-material/{id}', [BillOfMaterialController::class, 'printAllMaterial'])->name('printAllMaterial');


    Route::get('/dashboard/tv', [BillOfMaterialController::class, 'dashboardTv'])->name('dashboard.moulding.tv');
    //Route untuk program Moulding Bill of Material
    
    //Route untuk admin moulding 
    Route::get('/production/bom/create', [BillOfMaterialController::class, 'create'])->name('production.bom.create');
    Route::get('/get-item-codes', [BillOfMaterialController::class, 'getItemCodes'])->name('get-item-codes');

    Route::post('/production/bom/store', [BillOfMaterialController::class, 'store'])->name('production.bom.store');
    //Route untuk admin moulding

    //ROUTE untuk Workshop MOULDING
    Route::post('/workshop/update-username', [WorkshopController::class, 'updateUsername'])->name('update.username');
    Route::post('/workshop/set-scan-start', [WorkshopController::class, 'setScanStart'])->name('workshop.set_scan_start');
    Route::get('/workshop/remove-scan-in/{id}', [WorkshopController::class, 'removeScanIn'])->name('workshop.removeScanIn');
    Route::post('/workshop/scan-start', [WorkshopController::class, 'handleScanStart'])->name('workshop.scan');
    Route::get('/workshop/index/{id}', [WorkshopController::class, 'index'])->name('workshop.index');
    Route::post('/workshop/scanout', [WorkshopController::class, 'handeScanOut'])->name('workshop.scan_out');
    Route::get('/workshop/mainmenu', [WorkshopController::class, 'mainMenuByWorkshop'])->name('workshop.main.menu');
    Route::post('/workshop/addworker', [WorkshopController::class, 'addworker'])->name('workshop.add.worker');
    Route::post('/workshop/remark/store/{log_id}', [WorkshopController::class, 'storeRemark'])->name('remark.store');
    Route::put('/workshop/update-worker', [WorkshopController::class, 'updateWorker'])->name('workshop.update.worker');

    Route::get('/workshop/summary', [WorkshopController::class, 'summaryDashboard'])->name('workshop.summary.dashboard');

    //add manual
    Route::get('/workshop/add-manual', [WorkshopController::class, 'addManualWorkshop'])->name('workshop.addManual');
    Route::get('/workshop/children/{parentId}', [WorkshopController::class, 'getChildren'])->name('workshop.children');
    Route::post('/workshop/scan-manual', [WorkshopController::class, 'handleScanManual'])->name('workshop.scan.manual');
    //add manual


    //ROUTE untuk Workshop MOULDING

    Route::post('file/upload', [FileController::class, 'upload'])->name('file.upload');
    Route::delete('/file/{id}', [FileController::class, 'destroy'])->name('file.delete');

    Route::resource('waiting_purchase_orders', WaitingPurchaseOrderController::class);
    Route::patch('waiting_purchase_orders/{waiting_purchase_order}/change-status', [WaitingPurchaseOrderController::class, 'changeStatus'])->name('waiting_purchase_orders.changeStatus');

    Route::get('/daily-waiting-purchase-orders/notification', function(){
        $waitingPurchaseOrders = \App\Models\WaitingPurchaseOrder::all();
        return new \App\Mail\DailyWaitingPurchaseOrders($waitingPurchaseOrders);
    });

    //Route untuk Delivery Schedule
    Route::get('deliveryschedule/averagemonth', [DeliveryScheduleController::class, 'averageschedule'])->name('delsched.averagemonth');
    Route::get('deliveryschedule/index', [DeliveryScheduleController::class, 'index'])->name('indexds');
    Route::get("deliveryschedule/raw", [DeliveryScheduleController::class, "indexraw"])->name("rawdelsched");
    Route::get('deliveryschedule/wip', [DeliveryScheduleController::class, 'indexfinal'])->name('indexfinalwip');


    // Trigger background process
    Route::get("delsched/start1", [DeliveryScheduleController::class, "step1"])->name("deslsched.step1");
    Route::get("delsched/wip/step1", [DeliveryScheduleController::class, "step1wip"])->name("delschedwip.step1");

    // Cek status proses (untuk polling)
    Route::get("delsched/check-status", [DeliveryScheduleController::class, "checkStatus"])->name("delsched.checkStatus");

    // Route lama (bisa dihapus kalau tidak dipakai lagi)
    Route::get("delsched/start2", [DeliveryScheduleController::class, "step2"])->name("deslsched.step2");
    Route::get("delsched/start3", [DeliveryScheduleController::class, "step3"])->name("deslsched.step3");
    Route::get("delsched/start4", [DeliveryScheduleController::class, "step4"])->name("deslsched.step4");
    Route::get("delsched/wip/step2", [DeliveryScheduleController::class, "step2wip"])->name("delschedwip.step2");

    Route::get('/delivery-analysis', DeliveryAnalysis::class)->name('delivery.analysis');


    // Route::get("delsched/start1", [DeliveryScheduleController::class, "step1"])->name("deslsched.step1");
    // Route::get("delsched/start2", [DeliveryScheduleController::class, "step2"])->name("deslsched.step2");
    // Route::get("delsched/start3", [DeliveryScheduleController::class, "step3"])->name("deslsched.step3");
    // Route::get("delsched/start4", [DeliveryScheduleController::class, "step4"])->name("deslsched.step4");
    // Route::get("delsched/wip/step1", [DeliveryScheduleController::class, "step1wip"])->name("delschedwip.step1");
    // Route::get("delsched/wip/step2", [DeliveryScheduleController::class, "step2wip"])->name("delschedwip.step2");
    // Route untuk Delivery Schedule


    // route untuk add delivery schedule baru 
    Route::get('/delivery-schedule-create', DeliveryScheduleForm::class)->name('delivery-schedule.form');
    Route::get('/delivery-schedule/calendar', DeliveryScheduleCalendar::class)->name('delivery-schedule.calendar');

    // route untuk show delivery schedule dalam table bisa export dan insert 
    Route::get('new-delivery-schedule', [DeliveryScheduleController::class, 'deliveryScheduleNewIndex'])->name('testnewdelivery');
    // Route::get('/login', LivewireLoginSwitcher::class)->name('login');

    Route::get('/export-delivery-schedule', [DeliveryScheduleController::class, 'exportDeliverySchedule'])->name('export.delivery.schedule');
    Route::get('/export-delivery-schedule-template', [DeliveryScheduleController::class, 'exportTemplate'])
        ->name('export.delivery.schedule.template');

    Route::post('/import-delivery-schedule', [DeliveryScheduleController::class, 'importDeliverySchedule'])
        ->name('import.delivery.schedule');


    Route::get('export-delschedfinal', [DeliveryScheduleController::class, 'exportToExcel'])->name('export.delschedfinal');
    Route::get('delschedfinal/dashboard', [DeliveryScheduleController::class, 'dashboardUser'])->name('delschedfinal.dashboard');

    Route::get('management/delivery-schedule', [DeliveryScheduleController::class, 'deliveryManagement'])->name('management.delivery.index');
    Route::get('delete/delivery-schedule-data',[DeliveryScheduleController::class, 'deleteDeliveryScheduleData'])->name('delete.delivery.data');

    Route::delete('/image/{id}', [BillOfMaterialController::class, 'destroyImage'])->name('image.delete');

    Route::get('production/forecast', [ForecastProductionController::class, 'index'])->name('production.forecast.index');

    Route::get("updatepage/index", [UpdateDailyController::class, "index"])->name("indexupdatepage");
    Route::post("/processdailydata", [UpdateDailyController::class, 'update'])->name("updatedata");

    Route::resource('notification_recipients', NotificationRecepientController::class);

    //ROUTE UNTUK FITUR HOLIDAY SCHEDULE
    Route::get('setting/holiday-schedule', [HolidayScheduleController::class, 'index'])->name('setting.holiday-schedule.index');
    Route::get('setting/holiday-schedule/create', [HolidayScheduleController::class, 'create'])->name('holiday-schedule.create');
    Route::post('setting/holiday-schedule/store', [HolidayScheduleController::class, 'store'])->name('holiday-schedule.store');

    Route::put('/holiday-schedule/{id}', [HolidayScheduleController::class, 'update'])->name('holiday-schedule.update');
    Route::get('holiday-schedule/export', [HolidayScheduleController::class, 'export'])->name('holiday-schedule.export');
    Route::post('holiday-schedule/import', [HolidayScheduleController::class, 'import'])->name('holiday-schedule.import');
    //ROUTE UNTUK FITUR HOLIDAY SCHEDULE

    //ROUTE UNTUK INVENTORY MATERIAL DAN FINISHED GOODS
    Route::get('/inventory/mtr', [InventoryController::class, 'showMtrInventory'])->name('inventory.mtr');
    Route::get('/inventory/fg', [InventoryController::class, 'showFgInventory'])->name('inventory.fg');
    Route::get('/inventory/stock-health', [\App\Http\Controllers\Inventory\StockHealthController::class, 'index'])->name('inventory.stock-health');
    Route::get('/inventory/line-list',  [InvLineListController::class, "index"])->name('invlinelist');
    Route::post("/add/line", [InvLineListController::class, "addline"])->name('addline');
    Route::put("/edit/line/{id}", [InvLineListController::class, "editline"])->name('editline');
    Route::delete("/delete/line/{linecode}", [InvLineListController::class, "deleteline"])->name('deleteline');
    //ROUTE UNTUK INVENTORY MATERIAL DAN FINISHED GOODS

    //ROUTE UNTUK CAPACITY BY FORECASTING BAWAAN DISS 
    Route::get("/production/capacity-forecast", [CapacityByForecastController::class, "index"])->name('capacityforecastindex');
    Route::get("/production/capacity-line", [CapacityByForecastController::class, "line"])->name('capacityforecastline');
    Route::get("/production/capacity-distribution", [CapacityByForecastController::class, "distribution"])->name('capacityforecastdistribution');
    Route::get("/production/capacity-detail", [CapacityByForecastController::class, "detail"])->name('capacityforecastdetail');

    Route::get("/production/capacity-forecast/view-step", [CapacityByForecastController::class, "viewstep1"])->name('viewstep1');
    Route::get("/production/capacity-forecast/step1", [CapacityByForecastController::class, "step1"])->name('step1');
    Route::get("/production/capacity-forecast/step1second", [CapacityByForecastController::class, "step1_second"])->name('step1second');

    Route::get("/production/capacity-forecast/step2", [CapacityByForecastController::class, "step2"])->name('step2');
    Route::get("/production/capacity-forecast/step2logic", [CapacityByForecastController::class, "step2logic"])->name('step2logic');

    Route::get("/production/capacity-forecast/step3", [CapacityByForecastController::class, "step3"])->name('step3');
    Route::get("/production/capacity-forecast/step3logic", [CapacityByForecastController::class, "step3logic"])->name('step3logic');
    Route::get("/production/capacity-forecast/step3last", [CapacityByForecastController::class, "step3logiclast"])->name('step3logiclast');
    //ROUTE UNTUK CAPACITY BY FORECASTING BAWAAN DISS 


    // Route::get('/master-list-item', [MasterListItemController::class, 'index'])->name('master.list.item');
    // Route::post('/generate-machine-list', [MasterListItemController::class, 'generateMachineList'])
    // ->name('generate.machine.list');


    Route::post('/submit/spk', [DashboardController::class, 'submitSPK'])->name('submit.spk');

    Route::get('/machine-index', MaintenanceMachineIndex::class)
        ->name('maintenance.machine.index');
    Route::get('/mould-index', MaintenanceMouldIndex::class)
        ->name('maintenance.mould.index');

    // Second Process Routes Group (Plant-Aware & Permission Gated)
    Route::middleware('sp.plant-access')->group(function () {
        // Second Process Reports & Work Orders
        Route::post('/sp-work-orders/{id}/release', [\App\Http\Controllers\SpWorkOrderController::class, 'release'])->name('sp-work-orders.release');
        Route::post('/sp-work-orders/{id}/revert-to-draft', [\App\Http\Controllers\SpWorkOrderController::class, 'revertToDraft'])->name('sp-work-orders.revert-to-draft');
        Route::resource('sp-work-orders', \App\Http\Controllers\SpWorkOrderController::class);

        // Second Process Approvals
        Route::prefix('sp-approvals')->name('sp-approvals.')->group(function () {
            Route::get('/', [\App\Http\Controllers\SpProductionApprovalController::class, 'index'])->name('index');
            Route::get('/{session}', [\App\Http\Controllers\SpProductionApprovalController::class, 'show'])->name('show');
            Route::post('/{session}/approve', [\App\Http\Controllers\SpProductionApprovalController::class, 'approve'])->name('approve');
            Route::post('/{session}/reject', [\App\Http\Controllers\SpProductionApprovalController::class, 'reject'])->name('reject');
        });

        Route::post('sp-sessions/{workOrder}/start', [\App\Http\Controllers\SpProductionSessionController::class, 'start'])->name('sp-sessions.start');
        Route::get('sp-session/line/{lineSlug}', [\App\Http\Controllers\SpProductionSessionController::class, 'lineGateway'])->name('sp-sessions.line-gateway');
        Route::prefix('app')->name('app.')->group(function () {
            Route::get('sp-sessions/{session}', [\App\Http\Controllers\SpProductionSessionController::class, 'show'])->name('sp-sessions.show');
            Route::post('sp-sessions/{session}/finish', [\App\Http\Controllers\SpProductionSessionController::class, 'finish'])->name('sp-sessions.finish');
            Route::get('sp-sessions/{session}/closeout', [\App\Http\Controllers\SpProductionSessionController::class, 'closeout'])->name('sp-sessions.closeout');
            Route::post('sp-sessions/{session}/closeout', [\App\Http\Controllers\SpProductionSessionController::class, 'submitCloseout'])->name('sp-sessions.submit-closeout');
            Route::post('sp-sessions/{session}/approve', [\App\Http\Controllers\SpProductionApprovalController::class, 'approve'])->name('sp-sessions.approve');
            Route::post('sp-sessions/{session}/production', [\App\Http\Controllers\SpProductionSessionController::class, 'addProduction'])->name('sp-sessions.add-production');
            Route::post('sp-sessions/{session}/reject', [\App\Http\Controllers\SpProductionSessionController::class, 'addReject'])->name('sp-sessions.add-reject');
            Route::post('sp-sessions/{session}/rework', [\App\Http\Controllers\SpProductionSessionController::class, 'addRework'])->name('sp-sessions.add-rework');
            Route::post('sp-sessions/{session}/downtime', [\App\Http\Controllers\SpProductionSessionController::class, 'addDowntime'])->name('sp-sessions.add-downtime');
            Route::post('sp-sessions/{session}/pause', [\App\Http\Controllers\SpProductionSessionController::class, 'pause'])->name('sp-sessions.pause');
            Route::post('sp-sessions/{session}/resume', [\App\Http\Controllers\SpProductionSessionController::class, 'resume'])->name('sp-sessions.resume');
            Route::post('sp-sessions/{session}/input', [\App\Http\Controllers\SpProductionSessionController::class, 'addInput'])->name('sp-sessions.add-input');
            Route::post('sp-sessions/{session}/manpower', [\App\Http\Controllers\SpProductionSessionController::class, 'addManpower'])->name('sp-sessions.add-manpower');
            Route::delete('sp-sessions/{session}/manpower/{manpower}', [\App\Http\Controllers\SpProductionSessionController::class, 'removeManpower'])->name('sp-sessions.remove-manpower');
            Route::delete('sp-sessions/{session}/production/{entry}', [\App\Http\Controllers\SpProductionSessionController::class, 'deleteProductionEntry'])->name('sp-sessions.delete-production');
            Route::delete('sp-sessions/{session}/reject/{entry}', [\App\Http\Controllers\SpProductionSessionController::class, 'deleteRejectEntry'])->name('sp-sessions.delete-reject');
            Route::delete('sp-sessions/{session}/downtime/{entry}', [\App\Http\Controllers\SpProductionSessionController::class, 'deleteDowntimeEntry'])->name('sp-sessions.delete-downtime');
            Route::delete('sp-sessions/{session}/rework/{entry}', [\App\Http\Controllers\SpProductionSessionController::class, 'deleteReworkEntry'])->name('sp-sessions.delete-rework');
            Route::delete('sp-sessions/{session}/input/{entry}', [\App\Http\Controllers\SpProductionSessionController::class, 'deleteInputEntry'])->name('sp-sessions.delete-input');
        });

        Route::get('second-process-dashboard', [SecondProcessDashboardController::class, 'index'])->name('second-process.dashboard');
        Route::get('sp-line-dashboard/{line}', [SecondProcessDashboardController::class, 'lineDashboard'])->name('second-process.line-dashboard');
        Route::get('second-process-report-analytics', [\App\Http\Controllers\SecondProcessReportAnalyticsController::class, 'index'])->name('second-process.report-analytics');
        Route::get('second-process-reports/search-items', [SecondProcessReportController::class, 'searchItems'])->name('second-process-reports.search-items');
        Route::get('second-process-reports/search-customers', [SecondProcessReportController::class, 'searchCustomers'])->name('second-process-reports.search-customers');
        Route::post('second-process-reports/{id}/sign/{role}', [SecondProcessReportController::class, 'sign'])->name('second-process-reports.sign');
        Route::post('second-process-reports/{id}/reject', [SecondProcessReportController::class, 'reject'])->name('second-process-reports.reject');
        Route::resource('second-process-reports', SecondProcessReportController::class);

        // IPQC Inspections (standalone)
        Route::get('ipqc-inspections/search-items', [\App\Http\Controllers\IpqcInspectionController::class, 'searchItems'])->name('ipqc-inspections.search-items');
        Route::resource('ipqc-inspections', \App\Http\Controllers\IpqcInspectionController::class);

        // First Piece Inspection routes
        Route::get('first-piece-inspections/check-approval', [FirstPieceInspectionController::class, 'checkApproval'])->name('first-piece-inspections.check-approval');
        Route::post('first-piece-inspections/{id}/sign/{role}', [FirstPieceInspectionController::class, 'sign'])->name('first-piece-inspections.sign');
        Route::resource('first-piece-inspections', FirstPieceInspectionController::class);
    });
    
    Route::get('/master-list-manager', [MasterListItemController::class, 'manage'])->name('admin.master-list-manager');
    Route::get('/master-list-logs', [MasterListItemController::class, 'logs'])->name('admin.master-list-logs');
    Route::get('/customer-delivery-manager', [MasterListItemController::class, 'customerDeliveryManage'])->name('admin.customer-delivery-manager');
    Route::get('/customer-delivery-logs', [MasterListItemController::class, 'customerDeliveryLogs'])->name('admin.customer-delivery-logs');
});


require __DIR__.'/auth.php';

Route::get('/public/machine-monitor', \App\Livewire\MachineStatusPublic::class)->name('machine.monitor.public');
