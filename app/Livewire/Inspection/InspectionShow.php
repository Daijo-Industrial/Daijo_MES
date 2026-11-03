<?php

namespace App\Livewire\Inspection;

use App\Models\Inspection\InspectionReport;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class InspectionShow extends Component
{
    public InspectionReport $report;

    public function mount(InspectionReport $inspection_report): void
    {
        $inspection_report->load([
            'detailInspectionReports' => function ($q) {
                $q->orderBy('period');
            },
            'detailInspectionReports.firstInspections',
            'dimensionData',
            'detailInspectionReports.secondInspections',
            'detailInspectionReports.secondInspections.samplingData',
            'detailInspectionReports.secondInspections.packagingData',
            'detailInspectionReports.judgementData',
            'quantityData',
            'problemData',
        ]);

        $this->report = $inspection_report;
    }

    public function render(): View
    {
        return view('livewire.inspection.show', [
            'report' => $this->report,
            'inspectionReport' => $this->report,
        ])->layout('layouts.app');
    }
}
