<?php

use App\Http\Controllers\FirstPieceInspectionController;
use App\Http\Controllers\IpqcInspectionController;
use App\Http\Controllers\SecondProcessDashboardController;
use App\Http\Controllers\SecondProcessReportAnalyticsController;
use App\Http\Controllers\SecondProcessReportController;
use App\Http\Controllers\SpProductionApprovalController;
use App\Http\Controllers\SpProductionSessionController;
use App\Http\Controllers\SpWorkOrderController;
use Illuminate\Support\Facades\Route;

// Second Process Routes Group (Plant-Aware & Permission Gated)
Route::middleware(['auth', 'sp.plant-access'])->group(function () {
    // Second Process Reports & Work Orders
    Route::post('/sp-work-orders/{id}/release', [SpWorkOrderController::class, 'release'])->name('sp-work-orders.release');
    Route::post('/sp-work-orders/{id}/revert-to-draft', [SpWorkOrderController::class, 'revertToDraft'])->name('sp-work-orders.revert-to-draft');
    Route::resource('sp-work-orders', SpWorkOrderController::class);

    // Second Process Approvals
    Route::prefix('sp-approvals')->name('sp-approvals.')->group(function () {
        Route::get('/', [SpProductionApprovalController::class, 'index'])->name('index');
        Route::get('/{session}', [SpProductionApprovalController::class, 'show'])->name('show');
        Route::post('/{session}/approve', [SpProductionApprovalController::class, 'approve'])->name('approve');
        Route::post('/{session}/reject', [SpProductionApprovalController::class, 'reject'])->name('reject');
    });

    Route::post('sp-sessions/{workOrder}/start', [SpProductionSessionController::class, 'start'])->name('sp-sessions.start');
    Route::get('sp-session/line/{lineSlug}', [SpProductionSessionController::class, 'lineGateway'])->name('sp-sessions.line-gateway');
    Route::prefix('app')->name('app.')->group(function () {
        Route::get('sp-sessions/{session}', [SpProductionSessionController::class, 'show'])->name('sp-sessions.show');
        Route::post('sp-sessions/{session}/finish', [SpProductionSessionController::class, 'finish'])->name('sp-sessions.finish');
        Route::get('sp-sessions/{session}/closeout', [SpProductionSessionController::class, 'closeout'])->name('sp-sessions.closeout');
        Route::post('sp-sessions/{session}/closeout', [SpProductionSessionController::class, 'submitCloseout'])->name('sp-sessions.submit-closeout');
        Route::post('sp-sessions/{session}/approve', [SpProductionApprovalController::class, 'approve'])->name('sp-sessions.approve');
        Route::post('sp-sessions/{session}/production', [SpProductionSessionController::class, 'addProduction'])->name('sp-sessions.add-production');
        Route::post('sp-sessions/{session}/reject', [SpProductionSessionController::class, 'addReject'])->name('sp-sessions.add-reject');
        Route::post('sp-sessions/{session}/rework', [SpProductionSessionController::class, 'addRework'])->name('sp-sessions.add-rework');
        Route::post('sp-sessions/{session}/downtime', [SpProductionSessionController::class, 'addDowntime'])->name('sp-sessions.add-downtime');
        Route::post('sp-sessions/{session}/pause', [SpProductionSessionController::class, 'pause'])->name('sp-sessions.pause');
        Route::post('sp-sessions/{session}/resume', [SpProductionSessionController::class, 'resume'])->name('sp-sessions.resume');
        Route::post('sp-sessions/{session}/input', [SpProductionSessionController::class, 'addInput'])->name('sp-sessions.add-input');
        Route::post('sp-sessions/{session}/manpower', [SpProductionSessionController::class, 'addManpower'])->name('sp-sessions.add-manpower');
        Route::delete('sp-sessions/{session}/manpower/{manpower}', [SpProductionSessionController::class, 'removeManpower'])->name('sp-sessions.remove-manpower');
        Route::delete('sp-sessions/{session}/production/{entry}', [SpProductionSessionController::class, 'deleteProductionEntry'])->name('sp-sessions.delete-production');
        Route::delete('sp-sessions/{session}/reject/{entry}', [SpProductionSessionController::class, 'deleteRejectEntry'])->name('sp-sessions.delete-reject');
        Route::delete('sp-sessions/{session}/downtime/{entry}', [SpProductionSessionController::class, 'deleteDowntimeEntry'])->name('sp-sessions.delete-downtime');
        Route::delete('sp-sessions/{session}/rework/{entry}', [SpProductionSessionController::class, 'deleteReworkEntry'])->name('sp-sessions.delete-rework');
        Route::delete('sp-sessions/{session}/input/{entry}', [SpProductionSessionController::class, 'deleteInputEntry'])->name('sp-sessions.delete-input');
    });

    Route::get('second-process-dashboard', [SecondProcessDashboardController::class, 'index'])->name('second-process.dashboard');
    Route::get('sp-line-dashboard/{line}', [SecondProcessDashboardController::class, 'lineDashboard'])->name('second-process.line-dashboard');
    Route::get('second-process-report-analytics', [SecondProcessReportAnalyticsController::class, 'index'])->name('second-process.report-analytics');
    Route::get('second-process-reports/search-items', [SecondProcessReportController::class, 'searchItems'])->name('second-process-reports.search-items');
    Route::get('second-process-reports/search-customers', [SecondProcessReportController::class, 'searchCustomers'])->name('second-process-reports.search-customers');
    Route::post('second-process-reports/{id}/sign/{role}', [SecondProcessReportController::class, 'sign'])->name('second-process-reports.sign');
    Route::post('second-process-reports/{id}/reject', [SecondProcessReportController::class, 'reject'])->name('second-process-reports.reject');
    Route::resource('second-process-reports', SecondProcessReportController::class);

    // IPQC Inspections (standalone)
    Route::get('ipqc-inspections/search-items', [IpqcInspectionController::class, 'searchItems'])->name('ipqc-inspections.search-items');
    Route::resource('ipqc-inspections', IpqcInspectionController::class);

    // First Piece Inspection routes
    Route::get('first-piece-inspections/check-approval', [FirstPieceInspectionController::class, 'checkApproval'])->name('first-piece-inspections.check-approval');
    Route::post('first-piece-inspections/{id}/sign/{role}', [FirstPieceInspectionController::class, 'sign'])->name('first-piece-inspections.sign');
    Route::resource('first-piece-inspections', FirstPieceInspectionController::class);
});
