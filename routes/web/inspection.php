<?php

use App\Livewire\Inspection\InspectionDashboard;
use App\Livewire\Inspection\InspectionForm;
use App\Livewire\Inspection\InspectionIndex;
use App\Livewire\Inspection\InspectionShow;
use App\Models\Inspection\InspectionReport;
use Illuminate\Support\Facades\Route;

// IPQC Inspection Reports Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/inspection-reports', InspectionIndex::class)
        ->middleware('can:viewAny,' . InspectionReport::class)
        ->name('inspection-reports.index');

    Route::get('/inspection-report/create', InspectionForm::class)
        ->middleware('can:create,' . InspectionReport::class)
        ->name('inspection-reports.create');

    Route::get('/inspection-reports/create', InspectionForm::class)
        ->middleware('can:create,' . InspectionReport::class);

    Route::get('/inspection-reports/{report}/edit', InspectionForm::class)
        ->middleware('can:update,report')
        ->name('inspection-reports.edit');

    Route::get('/inspection-reports/dashboard', InspectionDashboard::class)
        ->middleware('can:viewAny,' . InspectionReport::class)
        ->name('inspection-reports.dashboard');

    Route::get('/inspection-reports/{report}', InspectionShow::class)
        ->middleware('can:view,report')
        ->name('inspection-reports.show');
});
