<?php

namespace App\Livewire\Inspection;

use App\Models\Inspection\InspectionDimension;
use App\Models\Inspection\InspectionProblem;
use App\Models\Inspection\InspectionQuantity;
use App\Models\Inspection\InspectionReport;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

class InspectionDashboard extends Component
{
    #[Url(as: 'from', except: '')]
    public string $dateFrom = '';

    #[Url(as: 'to', except: '')]
    public string $dateTo = '';

    #[Url(as: 'preset', except: 'this_month')]
    public string $preset = 'this_month';

    #[Url(as: 'shift', except: '')]
    public string $shift = '';

    #[Url(as: 'status', except: '')]
    public string $status = '';

    #[Url(as: 'customer', except: '')]
    public string $customer = '';

    #[Url(as: 'machine', except: '')]
    public string $machineNumber = '';

    #[Url(as: 'inspector', except: '')]
    public string $inspector = '';

    #[Url(as: 'part', except: '')]
    public string $partNumber = '';

    public array $draftFilters = [];

    public array $kpi = [];

    public array $trendChart = [];

    public array $shiftChart = [];

    public array $customerChart = [];

    public array $passRejectChart = [];

    public array $topFailingParts = [];

    public array $topProblemTypes = [];

    public array $latestReports = [];

    public array $dimensionFailures = [];

    public array $customerOptions = [];

    public array $shiftOptions = [];

    public array $machineOptions = [];

    public array $inspectorOptions = [];

    public bool $ready = false;

    public function mount(): void
    {
        if ($this->dateFrom === '' && $this->dateTo === '') {
            if ($this->preset !== 'all' && $this->preset !== 'custom') {
                [$this->dateFrom, $this->dateTo] = $this->resolvePresetDates($this->preset);
            }
        } else {
            $this->determinePresetFromDates();
        }

        $this->customerOptions = $this->distinctOptions('customer');
        $this->shiftOptions = InspectionReport::query()
            ->select('shift')
            ->whereNotNull('shift')
            ->distinct()
            ->orderBy('shift')
            ->pluck('shift')
            ->all();
        $this->machineOptions = $this->distinctOptions('machine_number');
        $this->inspectorOptions = $this->distinctOptions('inspector');

        if ($this->dateFrom !== '' || $this->dateTo !== '') {
            $hasData = InspectionReport::query()
                ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('inspection_date', '>=', $this->dateFrom))
                ->when($this->dateTo !== '', fn ($q) => $q->whereDate('inspection_date', '<=', $this->dateTo))
                ->exists();

            if (! $hasData) {
                $latestDate = InspectionReport::query()->max('inspection_date');
                if ($latestDate) {
                    $end = Carbon::parse($latestDate);
                    $this->dateTo = $end->toDateString();
                    $this->dateFrom = $end->copy()->subDays(29)->toDateString();
                    $this->determinePresetFromDates();
                }
            }
        }

        $this->syncDraftFromActive();
        $this->computeData();
        $this->ready = true;
    }

    private function distinctOptions(string $column): array
    {
        return InspectionReport::query()
            ->select($column)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();
    }

    private function resolvePresetDates(string $preset): array
    {
        $now = Carbon::now();

        return match ($preset) {
            'today' => [$now->toDateString(), $now->toDateString()],
            'yesterday' => [
                $now->copy()->subDay()->toDateString(),
                $now->copy()->subDay()->toDateString(),
            ],
            'this_week' => [
                $now->copy()->startOfWeek()->toDateString(),
                $now->copy()->endOfWeek()->toDateString(),
            ],
            'this_month' => [
                $now->copy()->startOfMonth()->toDateString(),
                $now->copy()->endOfMonth()->toDateString(),
            ],
            'last_month' => [
                $now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            default => ['', ''],
        };
    }

    private function determinePresetFromDates(): void
    {
        if ($this->dateFrom === '' && $this->dateTo === '') {
            $this->preset = 'all';

            return;
        }

        foreach (['today', 'yesterday', 'this_week', 'this_month', 'last_month'] as $candidate) {
            [$expectedFrom, $expectedTo] = $this->resolvePresetDates($candidate);
            if ($this->dateFrom === $expectedFrom && $this->dateTo === $expectedTo) {
                $this->preset = $candidate;

                return;
            }
        }

        $this->preset = 'custom';
    }

    private function syncDraftFromActive(): void
    {
        $this->draftFilters = [
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'shift' => $this->shift,
            'status' => $this->status,
            'customer' => $this->customer,
            'machine_number' => $this->machineNumber,
            'inspector' => $this->inspector,
            'part_number' => $this->partNumber,
        ];
    }

    public function updatedDateFrom(): void
    {
        $this->determinePresetFromDates();
        $this->syncDraftFromActive();
        $this->computeData();
    }

    public function updatedDateTo(): void
    {
        $this->determinePresetFromDates();
        $this->syncDraftFromActive();
        $this->computeData();
    }

    public function updatedCustomer(): void
    {
        $this->syncDraftFromActive();
        $this->computeData();
    }

    public function updatedPartNumber(): void
    {
        $this->syncDraftFromActive();
        $this->computeData();
    }

    public function applyFilters(): void
    {
        $from = $this->draftFilters['date_from'] ?? '';
        $to = $this->draftFilters['date_to'] ?? '';

        if ($from !== '' && $to !== '' && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        $this->dateFrom = $from;
        $this->dateTo = $to;
        $this->shift = (string) ($this->draftFilters['shift'] ?? '');
        $this->status = (string) ($this->draftFilters['status'] ?? '');
        $this->customer = (string) ($this->draftFilters['customer'] ?? '');
        $this->machineNumber = (string) ($this->draftFilters['machine_number'] ?? '');
        $this->inspector = (string) ($this->draftFilters['inspector'] ?? '');
        $this->partNumber = (string) ($this->draftFilters['part_number'] ?? '');

        $this->determinePresetFromDates();
        $this->syncDraftFromActive();
        $this->computeData();
    }

    public function removeFilter(string $key): void
    {
        match ($key) {
            'date' => [$this->dateFrom, $this->dateTo, $this->preset] = ['', '', 'all'],
            'shift' => $this->shift = '',
            'status' => $this->status = '',
            'customer' => $this->customer = '',
            'machine_number' => $this->machineNumber = '',
            'inspector' => $this->inspector = '',
            'part_number' => $this->partNumber = '',
            default => null,
        };

        $this->syncDraftFromActive();
        $this->computeData();
    }

    public function getHasActiveFiltersProperty(): bool
    {
        return $this->preset !== 'this_month'
            || $this->shift !== ''
            || $this->status !== ''
            || $this->customer !== ''
            || $this->machineNumber !== ''
            || $this->inspector !== ''
            || $this->partNumber !== '';
    }

    public function clearFilters(): void
    {
        $this->preset = 'this_month';
        [$this->dateFrom, $this->dateTo] = $this->resolvePresetDates('this_month');
        $this->shift = '';
        $this->status = '';
        $this->customer = '';
        $this->machineNumber = '';
        $this->inspector = '';
        $this->partNumber = '';
        $this->syncDraftFromActive();
        $this->computeData();
    }

    private function applyReportFilters($query, string $table = 'inspection_reports')
    {
        return $query
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate("{$table}.inspection_date", '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate("{$table}.inspection_date", '<=', $this->dateTo))
            ->when($this->shift !== '', fn ($q) => $q->where("{$table}.shift", $this->shift))
            ->when($this->status !== '', fn ($q) => $q->where("{$table}.status", $this->status))
            ->when($this->customer !== '', fn ($q) => $q->where("{$table}.customer", $this->customer))
            ->when($this->machineNumber !== '', fn ($q) => $q->where("{$table}.machine_number", $this->machineNumber))
            ->when($this->inspector !== '', fn ($q) => $q->where("{$table}.inspector", $this->inspector))
            ->when($this->partNumber !== '', fn ($q) => $q->where("{$table}.part_number", 'like', '%' . trim($this->partNumber) . '%'));
    }

    private function baseReportQuery()
    {
        return $this->applyReportFilters(InspectionReport::query());
    }

    private function baseQuantityQuery()
    {
        return $this->applyReportFilters(
            InspectionQuantity::query()->join(
                'inspection_reports',
                'inspection_quantities.inspection_report_document_number',
                '=',
                'inspection_reports.document_number'
            )
        );
    }

    private function computeData(): void
    {
        $this->computeKpi();
        $this->computeTrendChart();
        $this->computeShiftChart();
        $this->computeCustomerChart();
        $this->computePassRejectChart();
        $this->computeTopFailingParts();
        $this->computeTopProblemTypes();
        $this->computeLatestReports();
        $this->computeDimensionFailures();

        $this->dispatch(
            'charts-ready',
            trend: $this->trendChart,
            shift: $this->shiftChart,
            customer: $this->customerChart,
            passReject: $this->passRejectChart
        );
    }

    private function computeKpi(): void
    {
        $totalReports = $this->baseReportQuery()->count();

        $qtyStats = $this->baseQuantityQuery()
            ->selectRaw('
                COALESCE(SUM(output_quantity), 0)   AS total_output,
                COALESCE(SUM(pass_quantity), 0)     AS total_pass,
                COALESCE(SUM(reject_quantity), 0)   AS total_reject,
                COALESCE(AVG(pass_rate), 0)         AS avg_pass_rate,
                COALESCE(AVG(reject_rate), 0)       AS avg_reject_rate,
                COALESCE(AVG(ng_sample_rate), 0)    AS avg_ng_sample_rate
            ')
            ->first();

        $this->kpi = [
            'total_reports' => $totalReports,
            'total_output' => (int) ($qtyStats->total_output ?? 0),
            'total_pass' => (int) ($qtyStats->total_pass ?? 0),
            'total_reject' => (int) ($qtyStats->total_reject ?? 0),
            'avg_pass_rate' => round((float) ($qtyStats->avg_pass_rate ?? 0), 1),
            'avg_reject_rate' => round((float) ($qtyStats->avg_reject_rate ?? 0), 1),
            'avg_ng_sample_rate' => round((float) ($qtyStats->avg_ng_sample_rate ?? 0), 1),
        ];
    }

    private function computeTrendChart(): void
    {
        $rows = $this->baseReportQuery()
            ->selectRaw('DATE(inspection_date) AS day, COUNT(*) AS cnt')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $this->trendChart = [
            'labels' => $rows->pluck('day')->toArray(),
            'data' => $rows->pluck('cnt')->map(fn ($v) => (int) $v)->toArray(),
        ];
    }

    private function computeShiftChart(): void
    {
        $rows = $this->baseReportQuery()
            ->selectRaw('shift, COUNT(*) AS cnt')
            ->groupBy('shift')
            ->orderBy('shift')
            ->get();

        $this->shiftChart = [
            'labels' => $rows->pluck('shift')->map(fn ($s) => "Shift {$s}")->toArray(),
            'data' => $rows->pluck('cnt')->map(fn ($v) => (int) $v)->toArray(),
        ];
    }

    private function computeCustomerChart(): void
    {
        $rows = $this->baseReportQuery()
            ->selectRaw('customer, COUNT(*) AS cnt')
            ->groupBy('customer')
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();

        $this->customerChart = [
            'labels' => $rows->pluck('customer')->toArray(),
            'data' => $rows->pluck('cnt')->map(fn ($v) => (int) $v)->toArray(),
        ];
    }

    private function computePassRejectChart(): void
    {
        $rows = $this->baseQuantityQuery()
            ->selectRaw('DATE(inspection_reports.inspection_date) AS day, SUM(pass_quantity) AS pass_qty, SUM(reject_quantity) AS reject_qty')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $this->passRejectChart = [
            'labels' => $rows->pluck('day')->toArray(),
            'pass' => $rows->pluck('pass_qty')->map(fn ($v) => (int) $v)->toArray(),
            'reject' => $rows->pluck('reject_qty')->map(fn ($v) => (int) $v)->toArray(),
        ];
    }

    private function computeTopFailingParts(): void
    {
        $this->topFailingParts = $this->baseQuantityQuery()
            ->selectRaw('inspection_reports.part_number, inspection_reports.part_name, COUNT(*) AS reports, AVG(reject_rate) AS avg_reject, SUM(reject_quantity) AS total_reject')
            ->groupBy('inspection_reports.part_number', 'inspection_reports.part_name')
            ->orderByDesc('avg_reject')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'part_number' => $r->part_number,
                'part_name' => $r->part_name,
                'reports' => (int) $r->reports,
                'avg_reject' => round((float) $r->avg_reject, 2),
                'total_reject' => (int) $r->total_reject,
            ])
            ->toArray();
    }

    private function computeTopProblemTypes(): void
    {
        $rows = $this->applyReportFilters(
            InspectionProblem::query()->join(
                'inspection_reports',
                'inspection_problems.inspection_report_document_number',
                '=',
                'inspection_reports.document_number'
            )
        )
            ->selectRaw('inspection_problems.type, COUNT(*) AS cnt')
            ->groupBy('inspection_problems.type')
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();

        $this->topProblemTypes = $rows->map(fn ($r) => [
            'type' => $r->type ?: 'Unknown',
            'count' => (int) $r->cnt,
        ])->toArray();
    }

    private function computeLatestReports(): void
    {
        $this->latestReports = $this->baseReportQuery()
            ->select(['id', 'document_number', 'customer', 'part_number', 'inspection_date', 'shift'])
            ->orderByDesc('inspection_date')
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'document_number' => $r->document_number,
                'customer' => $r->customer,
                'part_number' => $r->part_number,
                'inspection_date' => Carbon::parse($r->inspection_date)->format('d M Y'),
                'shift' => $r->shift,
            ])
            ->toArray();
    }

    private function computeDimensionFailures(): void
    {
        $model = new InspectionDimension;
        $table = $model->getTable();

        $rows = $this->applyReportFilters(
            DB::connection($model->getConnectionName())->table($table)
                ->join(
                    'inspection_reports',
                    "{$table}.inspection_report_document_number",
                    '=',
                    'inspection_reports.document_number'
                )
                ->where("{$table}.judgement", 'NG')
        )
            ->selectRaw("{$table}.area, COUNT(*) AS ng_count")
            ->groupBy("{$table}.area")
            ->orderByDesc('ng_count')
            ->limit(10)
            ->get();

        $this->dimensionFailures = $rows->map(fn ($r) => [
            'area' => $r->area ?: 'Unknown',
            'ng_count' => (int) $r->ng_count,
        ])->toArray();
    }

    public function render(): View
    {
        return view('livewire.inspection.dashboard')->layout('layouts.app');
    }
}
