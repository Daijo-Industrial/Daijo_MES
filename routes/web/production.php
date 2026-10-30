<?php

use App\Http\Controllers\AssemblyDailyController;
use App\Http\Controllers\CapacityByForecastController;
use App\Http\Controllers\DaijoMesHomeController;
use App\Http\Controllers\DailyItemCodeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryScheduleController;
use App\Http\Controllers\Inventory\StockHealthController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InvLineListController;
use App\Http\Controllers\MaintenanceChecklistController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\Production\BillOfMaterialController;
use App\Http\Controllers\Production\ForecastProductionController;
use App\Http\Controllers\Production\MachineStatusController;
use App\Http\Controllers\Production\WorkshopController;
use App\Http\Controllers\ProductionDashboardController;
use App\Http\Controllers\ProductionNgController;
use App\Http\Controllers\ProductionPayableController;
use App\Http\Controllers\SpkBomPayloadPreviewController;
use App\Http\Controllers\SpkChangeLogController;
use App\Http\Controllers\UpdateDailyController;
use App\Livewire\DeliveryAnalysis;
use App\Livewire\DeliveryScheduleCalendar;
use App\Livewire\DeliveryScheduleForm;
use App\Livewire\MachineStatusPublic;
use App\Livewire\Maintenance\ChecklistReport;
use App\Livewire\Maintenance\MasterMould\Index as MasterMouldIndex;
use App\Livewire\Maintenance\MouldChecklist\Form as MouldChecklistForm;
use App\Livewire\Maintenance\MouldChecklist\Index as MouldChecklistIndex;
use App\Livewire\Maintenance\MouldChecklist\Show as MouldChecklistShow;
use App\Livewire\ManualSync;
use App\Livewire\MasterBomView;
use App\Livewire\MasterListItemView;
use App\Livewire\MasterMachineManager;
use App\Livewire\Ppic\PpicListUpMachine;
use App\Livewire\ProductionDashboard;
use App\Livewire\ProductionStoreHandoverIndex;
use App\Livewire\ProductionSummaryMonitor;
use App\Livewire\Store\ProductionScanCrosscheck;
use App\Livewire\Qc\QcStockTransfer;
use App\Livewire\Qc\QcStockTransferKbn;
use App\Livewire\ReceiptProductionLogs;
use App\Livewire\Sap\SapMesVerification;
use App\Livewire\SpkBomChangesView;
use Illuminate\Support\Facades\Route;

// ============================================
// PUBLIC PRODUCTION ROUTES
// ============================================

Route::get('/machine-monitor', [MachineStatusController::class, 'index'])->name('machine.monitor');
Route::get('/public/machine-monitor', MachineStatusPublic::class)->name('machine.monitor.public');
Route::get('/production-dashboard', ProductionDashboard::class)->name('production-dashboard');

Route::get('/master-list-item', MasterListItemView::class)->name('master-list-item');
Route::get('/master-bom', MasterBomView::class)->name('master-bom.index');

// ROUTE untuk handle ng-type produksi
Route::get('/ng-types', [ProductionNgController::class, 'index'])->name('ngtypes.index');
Route::post('/ng-types', [ProductionNgController::class, 'store'])->name('ngtypes.store');
Route::delete('/ng-types/{id}', [ProductionNgController::class, 'destroy'])->name('ngtypes.delete');

// ROUTE UNTUK MONITORING SPK MBA EMMA / INTAN
Route::get('/monitoring-spk', [MonitoringController::class, 'index'])->name('monitoring.spk.index');
Route::get('/monitoring-spkdetail/{spk}', [MonitoringController::class, 'show'])->name('monitoring.spk.detail');

// ROUTE UNTUK PERBANDINGAN & LOG PERUBAHAN SPK (SPK CHANGE TRACKING)
Route::get('/spk-changes', [SpkChangeLogController::class, 'index'])->name('spk.changes.index');
Route::post('/spk-changes/sync', [SpkChangeLogController::class, 'triggerSync'])->name('spk.changes.sync');
Route::get('/spk-changes/history/{spk}', [SpkChangeLogController::class, 'getHistory'])->name('spk.changes.history');

// ROUTE UNTUK SPK BOM CHANGES (INTEGRASI SPK & FORMULA BOM NON-WIP)
Route::get('/spk-bom-changes', SpkBomChangesView::class)->name('spk.bom-changes.index');
Route::get('/spk-bom-changes/preview-payload', [SpkBomPayloadPreviewController::class, 'show'])->name('spk.bom-changes.preview-payload');

// Route Maintenance Predictive Checklist Modal & Admin Report
Route::get('/maintenance-checklist/today/{machineId}', [MaintenanceChecklistController::class, 'getTodayData'])->name('maintenance.checklist.today');
Route::post('/maintenance-checklist/save', [MaintenanceChecklistController::class, 'store'])->name('maintenance.checklist.save');
Route::get('/maintenance/checklist-report', ChecklistReport::class)->name('maintenance.checklist-report');

// Route untuk hapus hourly remark
Route::delete('/hourly-remarks/{id}', [ProductionDashboardController::class, 'destroyHourlyRemark'])->name('hourly-remarks.destroy');

// Route Ajax untuk get daily item codes dan hitung perhitungan max quantity spk
Route::get('/daily-item-code/get-item-codes', [DailyItemCodeController::class, 'getItemCodes'])->name('daily-item-code.get-item-codes');
Route::get('/daily-item-code/max-quantity', [DailyItemCodeController::class, 'getMaxQuantity'])->name('daily-item-code.get-max-quantity');

// Route untuk cek data yang akan dikirim ke SAP
Route::get('/send-api', [DailyItemCodeController::class, 'generateDataForSap'])->name('send.api');

// Route untuk Home Daijo MES (Dashboard utama untuk semua fitur)
Route::get('/daijo-mes-home', [DaijoMesHomeController::class, 'index'])->name('daijo.mes.home');

// Route untuk production day dashboard dan api log dashboard
Route::get('/production-day-dashboard', [ProductionDashboardController::class, 'index'])->name('djoni.dashboard');
Route::get('/get-machines-by-item', [ProductionDashboardController::class, 'getMachinesByItem']);

// Capacity forecast dashboard bawaan diss
Route::get('/capacity-forecast-dashboard', [CapacityByForecastController::class, 'dashboard'])->name('capacity_forecast_dashboard');

// ============================================
// AUTHENTICATED PRODUCTION ROUTES
// ============================================
Route::middleware('auth')->group(function () {
    // untuk update spk secara manual
    Route::get('/manual-sync', ManualSync::class)->name('manual-sync');

    Route::prefix('production-payables')->group(function () {
        Route::get('/', [ProductionPayableController::class, 'index'])->name('production-payables.index');
        Route::get('/upload', [ProductionPayableController::class, 'uploadForm'])->name('production-payables.upload');
        Route::post('/import', [ProductionPayableController::class, 'import'])->name('production-payables.import');
        Route::get('/{id}', [ProductionPayableController::class, 'show'])->name('production-payables.show');
        Route::put('/{id}/status', [ProductionPayableController::class, 'updateStatus'])->name('production-payables.status');
        Route::delete('/{id}', [ProductionPayableController::class, 'destroy'])->name('production-payables.destroy');
    });

    Route::get('/production-summary-monitor', ProductionSummaryMonitor::class)->name('production-summary-monitor');
    Route::get('/receipt-production-logs', ReceiptProductionLogs::class)->name('receipt-production-logs');
    Route::get('/sap-verification', SapMesVerification::class)->name('sap.verification');
    Route::get('/qc-stock-transfer', QcStockTransfer::class)->name('qc-stock-transfer');
    Route::get('/qc-stock-transfer-kbn', QcStockTransferKbn::class)->name('qc-stock-transfer-kbn');
    Route::get('/master-machine', MasterMachineManager::class)->name('master-machine.index');

    // Production project route
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
    Route::put('/daily-item-codes/{id}/temporal-cycle-time', [DashboardController::class, 'updateCycleTime'])->name('daily-item-codes.updateCycleTime');
    Route::put('/daily-item-codes/{id}/material-lot', [DashboardController::class, 'updateMaterialLot'])->name('daily-item-codes.updateMaterialLot');
    Route::get('/daily-item-codes/{id}/accessory-lots', [DashboardController::class, 'getAccessoryLots'])->name('daily-item-codes.getAccessoryLots');
    Route::post('/daily-item-codes/{id}/accessory-lots', [DashboardController::class, 'storeAccessoryLot'])->name('daily-item-codes.storeAccessoryLot');
    Route::delete('/accessory-lots/{id}', [DashboardController::class, 'deleteAccessoryLot'])->name('accessory-lots.destroy');
    Route::post('/hourly-remarks', [DashboardController::class, 'storeHourlyRemark'])->name('hourly-remarks.store');
    Route::post('/production-output-log', [DashboardController::class, 'storeOutputLog'])->name('production.output-log.store');
    Route::get('/production-output-log/print/{id}', [DashboardController::class, 'printOutputLog'])->name('production.output-log.print');

    Route::put('/daily-item-codes/{id}/temporal-cavity', [DashboardController::class, 'updateTemporalCavity'])->name('daily-item-codes.updatecavity');
    Route::put('/daily-item-codes/{id}/cycle-time-cavity', [DashboardController::class, 'updateCycleTimeAndCavity'])->name('daily-item-codes.updateCycleTimeCavity');
    Route::put('/daily-item-codes/{id}/resin-usage', [DashboardController::class, 'updateResinUsage'])->name('daily-item-codes.updateresin');
    Route::post('/daily-item-codes/update-remark/{id}', [DashboardController::class, 'updateRemarkDIC']);

    Route::put('/hourly-remarks/{id}/update-actual-production', [DashboardController::class, 'updateActualProduction'])->name('hourly-remarks.updateActualProduction');
    Route::put('/hourly-remarks/{id}/ng', [DashboardController::class, 'updateNgProduction'])->name('hourly-remarks.updateNg');
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
    Route::get('/adjust-machine/details/{id}', [DashboardController::class, 'getAdjustMachineLogDetails'])->name('adjust.machine.details');
    Route::post('/adjust-machine/update/{id}', [DashboardController::class, 'updateAdjustMachineLog'])->name('adjust.machine.update');
    Route::post('/repair-machine/start', [DashboardController::class, 'startRepairMachine'])->name('repair.machine.start');
    Route::post('/repair-machine/end', [DashboardController::class, 'endRepairMachine'])->name('repair.machine.end');
    Route::post('/repair-machine/update/{id}', [DashboardController::class, 'updateRepairMachineLog'])->name('repair.machine.update');

    Route::get('/dashboardplastic', [DashboardController::class, 'dashboardPlastic']);
    Route::get('/reset-job', [DashboardController::class, 'resetJob'])->name('reset.job');

    // ROUTE UNTUK FITUR DAILY ITEM CODES
    Route::get('/daily-item-codes', [DailyItemCodeController::class, 'index'])->name('daily-item-code.index');
    Route::post('/daily-item-code', [DailyItemCodeController::class, 'store'])->name('daily-item-code.store');
    Route::get('/daily-item-code', [DailyItemCodeController::class, 'create'])->name('daily-item-code.create');
    Route::post('/calculate-item', [DailyItemCodeController::class, 'calculateItem'])->name('calculate.item');
    Route::post('/apply-item-code/{machine_id}', [DailyItemCodeController::class, 'applyItemCode'])->name('apply-item-code');
    Route::get('/daily-item-codes/daily', [DailyItemCodeController::class, 'daily'])->name('daily-item-code.daily');
    Route::put('/daily-item-codes/{id}', [DailyItemCodeController::class, 'update'])->name('daily-item-code.update');
    Route::delete('/daily-item-codes/{id}', [DailyItemCodeController::class, 'destroy'])->name('daily-item-code.destroy');
    Route::get('/ppic/listup-machine', PpicListUpMachine::class)->name('ppic.listup.index');

    // Route untuk bikin tanggal Maintenance
    Route::get('/maintenance/index', [MaintenanceController::class, 'index'])->name('maintenance.index');

    // belum dipake sepertinya
    Route::get('/assembly-daily-process', [AssemblyDailyController::class, 'index'])->name('assembly.daily.process.index');
    Route::get('/assembly-daily-process/create', [AssemblyDailyController::class, 'create'])->name('assembly.daily.process.create');
    Route::post('/assembly-daily-process/store', [AssemblyDailyController::class, 'store'])->name('assembly.daily.process.store');

    // Route untuk program Moulding Bill of Material
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
    Route::get('/production/bom/create', [BillOfMaterialController::class, 'create'])->name('production.bom.create');
    Route::get('/get-item-codes', [BillOfMaterialController::class, 'getItemCodes'])->name('get-item-codes');
    Route::post('/production/bom/store', [BillOfMaterialController::class, 'store'])->name('production.bom.store');
    Route::delete('/image/{id}', [BillOfMaterialController::class, 'destroyImage'])->name('image.delete');

    // ROUTE untuk Workshop MOULDING
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
    Route::get('/workshop/add-manual', [WorkshopController::class, 'addManualWorkshop'])->name('workshop.addManual');
    Route::get('/workshop/children/{parentId}', [WorkshopController::class, 'getChildren'])->name('workshop.children');
    Route::post('/workshop/scan-manual', [WorkshopController::class, 'handleScanManual'])->name('workshop.scan.manual');

    // Route untuk Delivery Schedule
    Route::get('deliveryschedule/averagemonth', [DeliveryScheduleController::class, 'averageschedule'])->name('delsched.averagemonth');
    Route::get('deliveryschedule/index', [DeliveryScheduleController::class, 'index'])->name('indexds');
    Route::get('deliveryschedule/raw', [DeliveryScheduleController::class, 'indexraw'])->name('rawdelsched');
    Route::get('deliveryschedule/wip', [DeliveryScheduleController::class, 'indexfinal'])->name('indexfinalwip');
    Route::get('delsched/start1', [DeliveryScheduleController::class, 'step1'])->name('deslsched.step1');
    Route::get('delsched/wip/step1', [DeliveryScheduleController::class, 'step1wip'])->name('delschedwip.step1');
    Route::get('delsched/check-status', [DeliveryScheduleController::class, 'checkStatus'])->name('delsched.checkStatus');
    Route::get('delsched/start2', [DeliveryScheduleController::class, 'step2'])->name('deslsched.step2');
    Route::get('delsched/start3', [DeliveryScheduleController::class, 'step3'])->name('deslsched.step3');
    Route::get('delsched/start4', [DeliveryScheduleController::class, 'step4'])->name('deslsched.step4');
    Route::get('delsched/wip/step2', [DeliveryScheduleController::class, 'step2wip'])->name('delschedwip.step2');
    Route::get('/delivery-analysis', DeliveryAnalysis::class)->name('delivery.analysis');
    Route::get('/delivery-schedule-create', DeliveryScheduleForm::class)->name('delivery-schedule.form');
    Route::get('/delivery-schedule/calendar', DeliveryScheduleCalendar::class)->name('delivery-schedule.calendar');
    Route::get('new-delivery-schedule', [DeliveryScheduleController::class, 'deliveryScheduleNewIndex'])->name('testnewdelivery');
    Route::get('/export-delivery-schedule', [DeliveryScheduleController::class, 'exportDeliverySchedule'])->name('export.delivery.schedule');
    Route::get('/export-delivery-schedule-template', [DeliveryScheduleController::class, 'exportTemplate'])->name('export.delivery.schedule.template');
    Route::post('/import-delivery-schedule', [DeliveryScheduleController::class, 'importDeliverySchedule'])->name('import.delivery.schedule');
    Route::get('export-delschedfinal', [DeliveryScheduleController::class, 'exportToExcel'])->name('export.delschedfinal');
    Route::get('delschedfinal/dashboard', [DeliveryScheduleController::class, 'dashboardUser'])->name('delschedfinal.dashboard');
    Route::get('management/delivery-schedule', [DeliveryScheduleController::class, 'deliveryManagement'])->name('management.delivery.index');
    Route::get('delete/delivery-schedule-data', [DeliveryScheduleController::class, 'deleteDeliveryScheduleData'])->name('delete.delivery.data');

    Route::get('production/forecast', [ForecastProductionController::class, 'index'])->name('production.forecast.index');

    Route::get('updatepage/index', [UpdateDailyController::class, 'index'])->name('indexupdatepage');
    Route::post('/processdailydata', [UpdateDailyController::class, 'update'])->name('updatedata');

    // ROUTE UNTUK INVENTORY MATERIAL DAN FINISHED GOODS
    Route::get('/inventory/mtr', [InventoryController::class, 'showMtrInventory'])->name('inventory.mtr');
    Route::get('/inventory/fg', [InventoryController::class, 'showFgInventory'])->name('inventory.fg');
    Route::get('/inventory/stock-health', [StockHealthController::class, 'index'])->name('inventory.stock-health');
    Route::get('/inventory/line-list', [InvLineListController::class, 'index'])->name('invlinelist');
    Route::post('/add/line', [InvLineListController::class, 'addline'])->name('addline');
    Route::put('/edit/line/{id}', [InvLineListController::class, 'editline'])->name('editline');
    Route::delete('/delete/line/{linecode}', [InvLineListController::class, 'deleteline'])->name('deleteline');

    // ROUTE UNTUK CAPACITY BY FORECASTING BAWAAN DISS
    Route::get('/production/capacity-forecast', [CapacityByForecastController::class, 'index'])->name('capacityforecastindex');
    Route::get('/production/capacity-line', [CapacityByForecastController::class, 'line'])->name('capacityforecastline');
    Route::get('/production/capacity-distribution', [CapacityByForecastController::class, 'distribution'])->name('capacityforecastdistribution');
    Route::get('/production/capacity-detail', [CapacityByForecastController::class, 'detail'])->name('capacityforecastdetail');
    Route::get('/production/capacity-forecast/view-step', [CapacityByForecastController::class, 'viewstep1'])->name('viewstep1');
    Route::get('/production/capacity-forecast/step1', [CapacityByForecastController::class, 'step1'])->name('step1');
    Route::get('/production/capacity-forecast/step1second', [CapacityByForecastController::class, 'step1_second'])->name('step1second');
    Route::get('/production/capacity-forecast/step2', [CapacityByForecastController::class, 'step2'])->name('step2');
    Route::get('/production/capacity-forecast/step2logic', [CapacityByForecastController::class, 'step2logic'])->name('step2logic');
    Route::get('/production/capacity-forecast/step3', [CapacityByForecastController::class, 'step3'])->name('step3');
    Route::get('/production/capacity-forecast/step3logic', [CapacityByForecastController::class, 'step3logic'])->name('step3logic');
    Route::get('/production/capacity-forecast/step3last', [CapacityByForecastController::class, 'step3logiclast'])->name('step3logiclast');

    Route::post('/submit/spk', [DashboardController::class, 'submitSPK'])->name('submit.spk');

    // Master Mould & Mould Checklist Routes
    Route::get('/master-mould', MasterMouldIndex::class)->name('master-mould.index');
    Route::get('/mould-checklist', MouldChecklistIndex::class)->name('mould-checklist.index');
    Route::get('/mould-checklist/create', MouldChecklistForm::class)->name('mould-checklist.create');
    Route::get('/mould-checklist/{id}', MouldChecklistShow::class)->name('mould-checklist.show');
    Route::get('/mould-checklist/{id}/edit', MouldChecklistForm::class)->name('mould-checklist.edit');

    // Kontrol & Rekonsiliasi Harian Produksi - Store
    Route::get('/production-store-handover', ProductionStoreHandoverIndex::class)->name('production.store-handover');
    Route::get('/production-scan-crosscheck', ProductionScanCrosscheck::class)->name('production.scan-crosscheck');
});
