<?php

namespace App\Livewire\Inspection;

use App\Livewire\Inspection\Concerns\HandlesInspectionDraft;
use App\Models\Inspection\DetailInspectionReport;
use App\Models\Inspection\FirstInspection;
use App\Models\Inspection\InspectionDimension;
use App\Models\Inspection\InspectionJudgement;
use App\Models\Inspection\InspectionPackaging;
use App\Models\Inspection\InspectionProblem;
use App\Models\Inspection\InspectionQuantity;
use App\Models\Inspection\InspectionReport;
use App\Models\Inspection\InspectionSampling;
use App\Models\Inspection\SecondInspection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InspectionIndex extends Component
{
    use HandlesInspectionDraft;
    use WithPagination;

    public bool $showDraftModal = false;

    public ?array $pendingDraftInfo = null;

    #[Url(as: 's', except: '')]
    public string $search = '';

    #[Url(as: 'sort', except: 'id')]
    public string $sortField = 'id';

    #[Url(as: 'dir', except: 'desc')]
    public string $sortDir = 'desc';

    #[Url(as: 'perPage', except: 10)]
    public int $perPage = 10;

    public array $showCol = [
        'document_number' => true,
        'inspection_date' => true,
        'shift' => true,
        'customer' => true,
        'machine_number' => true,
        'part_number' => true,
        'inspector' => true,
        'status' => true,
    ];

    #[Url(as: 'f')]
    public array $filters = [
        'document_number' => '',
        'date_from' => '',
        'date_to' => '',
        'shift' => '',
        'customer' => '',
        'machine_number' => '',
        'inspector' => '',
        'part_number' => '',
        'status' => '',
    ];

    public array $draftFilters = [];

    #[Url(as: 'preset', except: 'this_week')]
    public string $preset = 'this_week';

    public bool $ready = false;

    private const SORTABLE = [
        'id',
        'inspection_date',
        'shift',
        'customer',
        'machine_number',
        'part_number',
        'inspector',
    ];

    public function mount(): void
    {
        $this->authorize('viewAny', InspectionReport::class);

        $this->ready = false;

        $this->filters = array_merge([
            'document_number' => '',
            'date_from' => '',
            'date_to' => '',
            'shift' => '',
            'customer' => '',
            'machine_number' => '',
            'inspector' => '',
            'part_number' => '',
            'status' => '',
        ], $this->filters);

        if ($this->filters['date_from'] === '' && $this->filters['date_to'] === '') {
            if ($this->preset !== 'all' && $this->preset !== 'custom') {
                [$from, $to] = $this->resolvePresetDates($this->preset);
                $this->filters['date_from'] = $from;
                $this->filters['date_to'] = $to;
            }
        } else {
            $this->determinePresetFromDates();
        }

        $this->draftFilters = $this->filters;
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

    public function load(): void
    {
        $this->ready = true;
    }

    public function loadData(): void
    {
        $this->ready = true;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function setPreset(string $preset): void
    {
        [$from, $to] = $this->resolvePresetDates($preset);
        $this->draftFilters['date_from'] = $from;
        $this->draftFilters['date_to'] = $to;
    }

    public function applyFilters(): void
    {
        if (! empty($this->draftFilters['date_from']) && ! empty($this->draftFilters['date_to'])) {
            if ($this->draftFilters['date_from'] > $this->draftFilters['date_to']) {
                [$this->draftFilters['date_from'], $this->draftFilters['date_to']] = [
                    $this->draftFilters['date_to'],
                    $this->draftFilters['date_from'],
                ];
            }
        }

        $this->filters = $this->draftFilters;
        $this->determinePresetFromDates();
        $this->resetPage();
    }

    public function removeFilter(string $key): void
    {
        if ($key === 'date') {
            $this->filters['date_from'] = '';
            $this->filters['date_to'] = '';
            $this->preset = 'all';
            $this->draftFilters['date_from'] = '';
            $this->draftFilters['date_to'] = '';
        } elseif (array_key_exists($key, $this->filters)) {
            $this->filters[$key] = '';
            $this->draftFilters[$key] = '';
        }
        $this->resetPage();
    }

    public function getHasActiveFiltersProperty(): bool
    {
        return $this->preset !== 'this_month'
            || $this->filters['document_number'] !== ''
            || $this->filters['shift'] !== ''
            || $this->filters['customer'] !== ''
            || $this->filters['machine_number'] !== ''
            || $this->filters['inspector'] !== ''
            || $this->filters['part_number'] !== ''
            || $this->filters['status'] !== '';
    }

    private function determinePresetFromDates(): void
    {
        $from = $this->filters['date_from'] ?? '';
        $to = $this->filters['date_to'] ?? '';

        if ($from === '' && $to === '') {
            $this->preset = 'all';

            return;
        }

        foreach (['today', 'yesterday', 'this_week', 'this_month', 'last_month'] as $candidate) {
            [$expectedFrom, $expectedTo] = $this->resolvePresetDates($candidate);
            if ($from === $expectedFrom && $to === $expectedTo) {
                $this->preset = $candidate;

                return;
            }
        }

        $this->preset = 'custom';
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDir = 'asc';
        }
        $this->resetPage();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->preset = 'this_month';
        [$from, $to] = $this->resolvePresetDates('this_month');

        $this->filters = [
            'document_number' => '',
            'date_from' => $from,
            'date_to' => $to,
            'shift' => '',
            'customer' => '',
            'machine_number' => '',
            'inspector' => '',
            'part_number' => '',
            'status' => '',
        ];
        $this->draftFilters = $this->filters;
        $this->resetPage();
    }

    private function baseQuery()
    {
        $field = in_array($this->sortField, self::SORTABLE, true)
            ? $this->sortField
            : 'id';
        $dir = $this->sortDir === 'asc' ? 'asc' : 'desc';

        return InspectionReport::query()
            ->select([
                'id',
                'document_number',
                'inspection_date',
                'shift',
                'customer',
                'part_number',
                'part_name',
                'machine_number',
                'inspector',
                'status',
            ])
            ->when($this->search !== '', function ($q) {
                $s = '%' . trim($this->search) . '%';
                $q->where(
                    fn ($qq) => $qq
                        ->where('document_number', 'like', $s)
                        ->orWhere('customer', 'like', $s)
                        ->orWhere('part_number', 'like', $s)
                        ->orWhere('part_name', 'like', $s)
                        ->orWhere('machine_number', 'like', $s)
                        ->orWhere('inspector', 'like', $s)
                );
            })
            ->when(
                $this->filters['document_number'] !== '',
                fn ($q) => $q->where(
                    'document_number',
                    'like',
                    '%' . trim($this->filters['document_number']) . '%'
                )
            )
            ->when(
                $this->filters['customer'] !== '',
                fn ($q) => $q->where('customer', $this->filters['customer'])
            )
            ->when(
                $this->filters['machine_number'] !== '',
                fn ($q) => $q->where('machine_number', $this->filters['machine_number'])
            )
            ->when(
                $this->filters['inspector'] !== '',
                fn ($q) => $q->where('inspector', $this->filters['inspector'])
            )
            ->when(
                $this->filters['part_number'] !== '',
                fn ($q) => $q->where(
                    'part_number',
                    'like',
                    '%' . trim($this->filters['part_number']) . '%'
                )
            )
            ->when(
                $this->filters['shift'] !== '',
                fn ($q) => $q->where('shift', $this->filters['shift'])
            )
            ->when(
                $this->filters['date_from'] !== '',
                fn ($q) => $q->whereDate('inspection_date', '>=', $this->filters['date_from'])
            )
            ->when(
                $this->filters['date_to'] !== '',
                fn ($q) => $q->whereDate('inspection_date', '<=', $this->filters['date_to'])
            )
            ->when(
                $this->filters['status'] !== '',
                fn ($q) => $q->where('status', $this->filters['status'])
            )
            ->orderBy($field, $dir)
            ->when($field !== 'id', fn ($q) => $q->orderBy('id', 'desc'));
    }

    public function exportCsv(): StreamedResponse
    {
        if (! $this->ready) {
            $this->ready = true;
        }

        $filename = 'inspection-reports-' . now()->format('Ymd-His') . '.csv';

        $labels = [
            'document_number' => 'Document No',
            'inspection_date' => 'Date',
            'shift' => 'Shift',
            'customer' => 'Customer',
            'machine_number' => 'Machine No',
            'part_number' => 'Part Number',
            'inspector' => 'Inspector',
            'status' => 'Status',
        ];
        $visibleCols = array_keys(array_filter($this->showCol));
        $headers = array_map(fn ($k) => $labels[$k] ?? $k, $visibleCols);

        return response()->streamDownload(
            function () use ($visibleCols, $headers) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM
                fputcsv($out, $headers);

                $this->baseQuery()->chunk(1000, function ($chunk) use ($out, $visibleCols) {
                    foreach ($chunk as $r) {
                        $row = [];
                        foreach ($visibleCols as $col) {
                            $val = $r->{$col};
                            if ($col === 'inspection_date' && $val) {
                                $val = Carbon::parse($val)->format('Y-m-d');
                            }
                            $row[] = $val;
                        }
                        fputcsv($out, $row);
                    }
                });

                fclose($out);
            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
                'Pragma' => 'no-cache',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]
        );
    }

    public function handleCreateClick()
    {
        $this->authorize('create', InspectionReport::class);

        $draft = $this->findPendingDraft();
        if ($draft) {
            $this->pendingDraftInfo = $draft;
            $this->showDraftModal = true;

            return;
        }

        return $this->redirect(route('inspection-reports.create'), navigate: true);
    }

    public function startNewReport()
    {
        $this->authorize('create', InspectionReport::class);

        $this->clearWizardSession();
        $this->showDraftModal = false;

        return $this->redirect(route('inspection-reports.create', ['new' => 1]), navigate: true);
    }

    public function continueDraft()
    {
        $this->showDraftModal = false;

        return $this->redirect(route('inspection-reports.create', ['continue' => 1]), navigate: true);
    }

    public function deleteDraft(int $id): void
    {
        $report = InspectionReport::find($id);

        if (! $report) {
            session()->flash('error', 'Laporan tidak ditemukan.');

            return;
        }

        $this->authorize('delete', $report);

        $docNum = $report->document_number;

        DB::transaction(function () use ($report, $docNum) {
            $details = DetailInspectionReport::where('inspection_report_document_number', $docNum)->get();

            foreach ($details as $detail) {
                FirstInspection::where('detail_inspection_report_document_number', $detail->document_number)->delete();

                $secondInspections = SecondInspection::where('detail_inspection_report_document_number', $detail->document_number)->get();
                foreach ($secondInspections as $second) {
                    InspectionPackaging::where('second_inspection_document_number', $second->document_number)->delete();
                    InspectionSampling::where('second_inspection_document_number', $second->document_number)->delete();
                    $second->delete();
                }

                InspectionJudgement::where('detail_inspection_report_document_number', $detail->document_number)->delete();
                $detail->delete();
            }

            InspectionDimension::where('inspection_report_document_number', $docNum)->delete();
            InspectionProblem::where('inspection_report_document_number', $docNum)->delete();
            InspectionQuantity::where('inspection_report_document_number', $docNum)->delete();

            $report->delete();
        });

        if (session('stepHeaderSaved.document_number') === $docNum) {
            $this->clearWizardSession();
        }

        if ($this->showDraftModal) {
            $this->showDraftModal = false;
            $this->pendingDraftInfo = null;
        }

        session()->flash('success', "Draft laporan {$docNum} berhasil dihapus.");
    }

    public function render()
    {
        $shiftOptions = InspectionReport::query()
            ->select('shift')
            ->whereNotNull('shift')
            ->distinct()
            ->orderBy('shift')
            ->pluck('shift')
            ->all();

        $customerOptions = InspectionReport::query()
            ->select('customer')
            ->whereNotNull('customer')
            ->where('customer', '!=', '')
            ->distinct()
            ->orderBy('customer')
            ->pluck('customer')
            ->all();

        $machineOptions = InspectionReport::query()
            ->select('machine_number')
            ->whereNotNull('machine_number')
            ->where('machine_number', '!=', '')
            ->distinct()
            ->orderBy('machine_number')
            ->pluck('machine_number')
            ->all();

        $inspectorOptions = InspectionReport::query()
            ->select('inspector')
            ->whereNotNull('inspector')
            ->where('inspector', '!=', '')
            ->distinct()
            ->orderBy('inspector')
            ->pluck('inspector')
            ->all();

        $perPageOptions = [10, 25, 50, 100];

        $reports = $this->ready
            ? $this->baseQuery()->paginate($this->perPage)->withQueryString()
            : collect();

        return view(
            'livewire.inspection.index',
            compact(
                'reports',
                'shiftOptions',
                'customerOptions',
                'machineOptions',
                'inspectorOptions',
                'perPageOptions'
            )
        )->layout('layouts.app');
    }
}
