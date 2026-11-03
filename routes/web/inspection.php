<?php

use App\Livewire\Inspection\InspectionDashboard;
use App\Livewire\Inspection\InspectionForm;
use App\Livewire\Inspection\InspectionIndex;
use App\Livewire\Inspection\InspectionShow;
use Illuminate\Support\Facades\Route;

// Molding IPQC Inspection Reports Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/inspection-reports', InspectionIndex::class)->name('inspection-reports.index');
    Route::get('/inspection-report/create', InspectionForm::class)->name('inspection-reports.create');
    Route::get('/inspection-reports/dashboard', InspectionDashboard::class)->name('inspection-reports.dashboard');
    Route::get('/inspection-reports/{inspection_report}', InspectionShow::class)->name('inspection-reports.show');
});
