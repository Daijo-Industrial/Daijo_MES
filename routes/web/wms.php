<?php

use App\Http\Controllers\Store\SOController;
use App\Livewire\Barcode\StoreDashboard;
use App\Livewire\MaterialWarehouse\MasterListMaterialIndex;
use App\Livewire\MaterialWarehouse\MaterialDailyAudit;
use App\Livewire\MaterialWarehouse\MaterialFifoDirectorDashboard;
use App\Livewire\MaterialWarehouse\MaterialIncomingCreator;
use App\Livewire\MaterialWarehouse\MaterialOutgoingCreator;
use App\Livewire\MaterialWarehouse\MaterialOutgoingHistory;
use App\Livewire\MaterialWarehouse\MaterialPalletIndex;
use App\Livewire\MaterialWarehouse\MaterialQrLookup;
use App\Livewire\MaterialWarehouse\MaterialStockCard;
use App\Livewire\MaterialWarehouse\PublicRackMapping;
use App\Livewire\MaterialWarehouse\RackMapping as MwhRackMapping;
use App\Livewire\ProductionStoreHandoverIndex;
use App\Livewire\Store\SoTracking;
use App\Livewire\UploadSpkHistory;
use App\Livewire\Wms\DailyDeliveryRecap;
use App\Livewire\Wms\PalletFormCreator;
use App\Livewire\Wms\PalletFormIndex;
use App\Livewire\Wms\PalletFormLookup;
use App\Livewire\Wms\PalletLogIndex;
use App\Livewire\Wms\PalletOutbound;
use App\Livewire\Wms\PalletSorting;
use App\Livewire\Wms\PickingGuide;
use App\Livewire\Wms\RackMapping as WmsRackMapping;
use App\Livewire\Wms\SapSyncMonitor;
use App\Livewire\Wms\WmsDashboard;
use App\Models\MwhPallet;
use App\Models\WmsPalletForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ============================================
// PUBLIC WMS & MATERIAL WAREHOUSE ROUTES
// ============================================

// Route untuk dashboard Store
Route::get('/dashboard/store', StoreDashboard::class)->name('dashboard.store');

// Public Material Pallet Live Check (Cryptographically Signed URL - Safe for Public IP)
Route::get('/public/material-pallet/{palletId}', function (Request $request, $palletId) {
    if (! $request->hasValidSignature()) {
        abort(403, 'Akses Ditolak: Halaman ini hanya dapat diakses melalui QR Code fisik resmi.');
    }

    $pallet = MwhPallet::with([
        'warehouse',
        'position.rack.warehouse',
        'initialPosition.rack.warehouse',
        'material',
        'incomingHeader',
        'outgoings.position.rack',
    ])->where('pallet_id', strtoupper($palletId))->first();

    return view('material-warehouse.public_pallet_lookup', compact('pallet', 'palletId'));
})->name('mwh.public-pallet-lookup')->middleware('throttle:60,1');

Route::get('/public/material-warehouse/mapping', PublicRackMapping::class)
    ->name('mwh.public-mapping')
    ->middleware('throttle:60,1');

// Director FIFO Dashboard (Public Access - No Login Required)
Route::get('/material-warehouse/fifo-dashboard', MaterialFifoDirectorDashboard::class)
    ->name('mwh.fifo-dashboard');
Route::get('/material-warehouse/director-dashboard', MaterialFifoDirectorDashboard::class)
    ->name('mwh.director-dashboard.public');
Route::get('/public/material-warehouse/fifo-dashboard', MaterialFifoDirectorDashboard::class)
    ->name('mwh.fifo-dashboard.public');

// ============================================
// AUTHENTICATED WMS & MATERIAL WAREHOUSE ROUTES
// ============================================
Route::middleware('auth')->group(function () {
    Route::prefix('wms')->name('wms.')->group(function () {
        Route::get('/sap-sync-monitor', SapSyncMonitor::class)->name('sap-sync-monitor');
        Route::get('/sap-sync-monitor-delivery', SapSyncMonitor::class)->name('sap-sync-monitor-delivery');
    });

    Route::middleware('store.access')->group(function () {
        Route::get('/upload-spk-history', UploadSpkHistory::class)->name('upload.spk.history');

        Route::prefix('wms')->name('wms.')->group(function () {
            Route::get('/dashboard', WmsDashboard::class)->name('dashboard');
            Route::get('/outbound', PalletOutbound::class)->name('outbound');
            Route::get('/mapping', WmsRackMapping::class)->name('mapping');
            Route::get('/pallet-logs', PalletLogIndex::class)->name('logs');
            Route::get('/pallet-form/lookup', PalletFormLookup::class)->name('pallet-form.lookup');
            Route::get('/pallet-form/history', PalletFormIndex::class)->name('pallet-form.index');
            Route::get('/pallet-form/create-delivery', PalletFormCreator::class)->name('pallet-form.create-delivery');
            Route::get('/pallet-form/daily-recap', DailyDeliveryRecap::class)->name('pallet-form.daily-recap');
            Route::get('/store-handover', ProductionStoreHandoverIndex::class)->name('store-handover');
            Route::get('/pallet-form/sorting', PalletSorting::class)->name('pallet-form.sorting');
            Route::get('/pallet-form/picking-guide', PickingGuide::class)->name('pallet-form.picking-guide');
            Route::get('/pallet-form/print/{id}', function ($id) {
                $palletForm = WmsPalletForm::with('position')->findOrFail($id);

                return view('wms.pallet_form_print', compact('palletForm'));
            })->name('pallet-form.print');
        });

        Route::prefix('material-warehouse')->name('mwh.')->group(function () {
            Route::get('/mapping', MwhRackMapping::class)->name('mapping');
            Route::get('/master-list', MasterListMaterialIndex::class)->name('master-list.index');
            Route::get('/incoming/create', MaterialIncomingCreator::class)->name('incoming.create');
            Route::get('/pallets', MaterialPalletIndex::class)->name('pallets.index');
            Route::get('/daily-audit', MaterialDailyAudit::class)->name('daily-audit');
            Route::get('/outgoing/create', MaterialOutgoingCreator::class)->name('outgoing.create');
            Route::get('/outgoing/history', MaterialOutgoingHistory::class)->name('outgoing.history');
            Route::get('/stock-card', MaterialStockCard::class)->name('stock-card.index');
            Route::get('/qr-lookup', MaterialQrLookup::class)->name('qr-lookup');
            Route::get('/pallet/print/{palletId}', function ($palletId) {
                $pallet = MwhPallet::with([
                    'incomingHeader',
                    'position.rack.warehouse',
                    'initialPosition.rack.warehouse',
                    'material',
                    'warehouse',
                    'outgoings.position.rack',
                ])->where('pallet_id', $palletId)->firstOrFail();

                return view('material-warehouse.material_pallet_print', compact('pallet'));
            })->name('pallet.print');
        });
    });

    // Route untuk Handle SO dari diss
    Route::get('/so/dashboard', [SOController::class, 'dashboard'])->name('so.dashboard');
    Route::get('/so/index', [SOController::class, 'index'])->name('so.index');
    Route::get('/so/filter', [SOController::class, 'index'])->name('so.filter');
    Route::get('/so/filterauto', [SOController::class, 'filter'])->name('so.filterauto');
    Route::get('/so/process/{docNum}', [SOController::class, 'process'])->name('so.process');
    Route::post('/so/scan', [SOController::class, 'scanBarcode'])->name('so.scanBarcode');
    Route::get('/update-so-data/{docNum}', [SOController::class, 'updateSoData'])->name('update.so.data');
    Route::post('/import-excel', [SOController::class, 'import'])->name('import.so.data');

    Route::get('/pegawai/scan', [SOController::class, 'indexpegawai'])->name('pegawai.scan');
    Route::get('/so/tracking', SoTracking::class)->name('so.tracking');

    Route::put('/scan/{id}', [SOController::class, 'update'])->name('scan.update');
    Route::delete('/scan/{id}', [SOController::class, 'destroy'])->name('scan.delete');
});
