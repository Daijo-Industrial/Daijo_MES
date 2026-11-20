<?php

namespace App\Livewire\Inspection;

use App\Models\Inspection\InspectionReport;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class InspectionShow extends Component
{
    public InspectionReport $report;

    public function mount($report = null): void
    {
        if (! $report) {
            abort(404);
        }

        if (! ($report instanceof InspectionReport)) {
            $report = InspectionReport::findOrFail($report);
        }

        $this->authorize('view', $report);

        $report->load([
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

        $this->report = $report;
    }

    public function render(): View
    {
        return view('livewire.inspection.show', [
            'report' => $this->report,
        ])->layout('layouts.app');
    }
}
