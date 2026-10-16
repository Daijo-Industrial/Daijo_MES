<?php

use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\InitialBarcodeController;
use App\Http\Controllers\MasterItemController;
use Illuminate\Support\Facades\Route;

// ============================================
// PUBLIC BARCODE ROUTES
// ============================================

// Barcode untuk generate label produksi terbaru
Route::get('/barcode/custom-generate', [InitialBarcodeController::class, 'customGenerateForm'])->name('barcode.custom.form');
Route::get('/barcode/custom-generate/logs', [InitialBarcodeController::class, 'customGenerateLogs'])->name('barcode.custom.logs');
Route::post('/barcode/custom-generate/print', [InitialBarcodeController::class, 'customGeneratePrint'])->name('barcode.custom.print');
Route::get('/barcode/custom-generate/reprint/{id}', [InitialBarcodeController::class, 'customGenerateReprint'])->name('barcode.custom.reprint');
Route::get('/api/get-spks-by-item', [InitialBarcodeController::class, 'getSpksByItem'])->name('api.get-spks-by-item');

// Route untuk barcode ALC Engineering & Yanfeng labels
Route::get('/barcodealcindex', [BarcodeController::class, 'alcindex'])->name('barcode.alc.index');
Route::get('/all-label-yanfeng', [BarcodeController::class, 'generateAllLabelYangeng'])->name('all.label.yanfeng');
Route::post('/generate-label-yanfeng40x15', [BarcodeController::class, 'generateLabelYangeng40x15'])->name('generate.label.yanfeng40x15');
Route::post('/generate-label-yanfeng25x10', [BarcodeController::class, 'generateLabelYangeng25x10'])->name('generate.label.yanfeng25x10');
Route::post('/generate-label-yanfeng50x20', [BarcodeController::class, 'generateLabelYangeng50x20'])->name('generate.label.yanfeng50x20');
Route::post('/generate-label-yanfeng50x35', [BarcodeController::class, 'generateLabelYangeng50x35'])->name('generate.label.yanfeng50x35');

// ============================================
// AUTHENTICATED BARCODE ROUTES
// ============================================
Route::middleware('auth')->group(function () {
    // ROUTE UNTUK FITUR BARCODE STORE
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
    Route::get('barcode/stockall/{location?}', [BarcodeController::class, 'stockall'])->name('stockallbarcode');
    Route::get('barcode/summary', [BarcodeController::class, 'summaryDashboard'])->name('summaryDashboard');

    // Route untuk master box data (Part Master)
    Route::get('barcode/box-master', [BarcodeController::class, 'indexStoreBoxData'])->name('barcode.box_master.index');
    Route::post('barcode/box-master', [BarcodeController::class, 'storeStoreBoxData'])->name('barcode.box_master.store');
    Route::put('barcode/box-master/{id}', [BarcodeController::class, 'updateStoreBoxData'])->name('barcode.box_master.update');
    Route::delete('barcode/box-master/{id}', [BarcodeController::class, 'destroyStoreBoxData'])->name('barcode.box_master.destroy');

    // Route history barcode generated
    Route::get('barcode/history', [BarcodeController::class, 'historyStoreBoxDetails'])->name('barcode.history.index');
    Route::get('barcode/box-detail', [BarcodeController::class, 'indexStoreBoxDetail'])->name('barcode.box_detail.index');
    Route::put('barcode/box-detail/{id}', [BarcodeController::class, 'updateStoreBoxDetail'])->name('barcode.box_detail.update');
    Route::post('barcode/reprint', [BarcodeController::class, 'reprintBarcode'])->name('barcode.reprint');

    // Route untuk tambahin customer di store barcode
    Route::get('/add-customer', [BarcodeController::class, 'addCustomer'])->name('customer.add');
    Route::post('/add-customer', [BarcodeController::class, 'storeCustomer'])->name('customer.store');
    Route::delete('/customer/{id}', [BarcodeController::class, 'destroyCustomer'])->name('customer.destroy');

    // Route untuk master item di store barcode
    Route::get('master-item', [MasterItemController::class, 'index'])->middleware('verified')->name('master-item.index');

    Route::get('/initialbarcode', [InitialBarcodeController::class, 'index'])->name('barcode.index');
    Route::post('/barcodes/generate', [InitialBarcodeController::class, 'generate'])->name('barcode.generate');
    Route::get('/manualbarcodes', [InitialBarcodeController::class, 'manualgenerate'])->name('manualbarcode.index');
    Route::post('/generate-barcode', [InitialBarcodeController::class, 'generateBarcode'])->name('generate.barcode');
});
