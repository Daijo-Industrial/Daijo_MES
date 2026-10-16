<?php

use App\Http\Controllers\PpicReportExportController;
use App\Http\Controllers\ProductionReportController;
use App\Http\Controllers\ReportController;
use App\Livewire\Asakai\AsakaiDetail;
use App\Livewire\Asakai\AsakaiForm;
use App\Livewire\Asakai\AsakaiList;
use App\Livewire\Report\ApiLogDashboard;
use App\Livewire\Report\DailyReport;
use App\Livewire\Report\MachineActiveHours;
use App\Livewire\Report\MachineDailyReport;
use App\Livewire\Report\MonthlyReport;
use App\Livewire\Report\WeeklyReport;
use Illuminate\Support\Facades\Route;

// ============================================
// PUBLIC REPORT ROUTES
// ============================================

Route::prefix('asakai')->name('asakai.')->group(function () {
    Route::get('/', AsakaiList::class)->name('index');
    Route::get('/create', AsakaiForm::class)->name('create');
    Route::get('/{id}/edit', AsakaiForm::class)->name('edit');
    Route::get('/{id}', AsakaiDetail::class)->name('detail');
});

Route::prefix('report')->name('report.')->group(function () {
    Route::get('/daily', DailyReport::class)->name('daily');
    Route::get('/daily/export', [ReportController::class, 'exportDaily'])->name('daily.export');

    Route::get('/weekly', WeeklyReport::class)->name('weekly');
    Route::get('/weekly/export', [ReportController::class, 'exportWeekly'])->name('weekly.export');

    Route::get('/monthly', MonthlyReport::class)->name('monthly');
    Route::get('/monthly/export', [ReportController::class, 'exportMonthly'])->name('monthly.export');

    // New Route for Machine Active Hours
    Route::get('/machine-active-hours', MachineActiveHours::class)->name('machine-active-hours');
});

Route::get('/production-daily-report', [ProductionReportController::class, 'index'])->name('production.report');
Route::get('/api-log-dashboard', ApiLogDashboard::class)->name('api.dashboard');

// ============================================
// AUTHENTICATED REPORT ROUTES
// ============================================
Route::middleware('auth')->group(function () {
    // Machine Daily Production Report
    Route::get('/operator/daily-report', MachineDailyReport::class)->name('operator.daily-report');
    Route::get('/ppic/machine-daily-report', MachineDailyReport::class)->name('ppic.machine-daily-report');
    Route::get('/ppic/machine-daily-report/export', [PpicReportExportController::class, 'exportDailyProduction'])->name('ppic.machine-daily-report.export');
});
