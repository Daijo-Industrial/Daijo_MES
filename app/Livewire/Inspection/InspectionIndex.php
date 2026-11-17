<?php

namespace App\Livewire\Inspection;

use App\Models\Inspection\InspectionReport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InspectionIndex extends Component
{
    use WithPagination;

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
        'part_number' => true,
        'status' => true,
    ];

    public array $filters = [
        'document_number' => '',
        'date_from' => '',
        'date_to' => '',
        'shift' => '',
        'customer' => '',
        'part_number' => '',
        'status' => '',
    ];

    public array $draftFilters = [];

    #[Url(as: 'preset', except: 'all')]
    public string $preset = 'all';

    public bool $ready = false;

    private const SORTABLE = [
        'id',
        'inspection_date',
        'shift',
        'customer',
        'part_number',
    ];

    public function mount(): void
    {
        $this->ready = false;

        $now = Carbon::now();
        if ($this->preset === 'this_month' && $this->filters['date_from'] === '' && $this->filters['date_to'] === '') {
            $this->filters['date_from'] = $now->copy()->startOfMonth()->toDateString();
            $this->filters['date_to'] = $now->copy()->endOfMonth()->toDateString();
        } elseif ($this->preset === 'today' && $this->filters['date_from'] === '' && $this->filters['date_to'] === '') {
            $this->filters['date_from'] = $now->toDateString();
            $this->filters['date_to'] = $now->toDateString();
        } else {
            $this->determinePresetFromDates();
        }

        $this->draftFilters = $this->filters;
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
        $this->preset = $preset;
        $now = Carbon::now();

        switch ($preset) {
            case 'today':
                $this->draftFilters['date_from'] = $now->toDateString();
                $this->draftFilters['date_to'] = $now->toDateString();
                break;
            case 'yesterday':
                $yest = $now->copy()->subDay()->toDateString();
                $this->draftFilters['date_from'] = $yest;
                $this->draftFilters['date_to'] = $yest;
                break;
            case 'this_month':
                $this->draftFilters['date_from'] = $now->copy()->startOfMonth()->toDateString();
                $this->draftFilters['date_to'] = $now->copy()->endOfMonth()->toDateString();
                break;
            case 'all':
                $this->draftFilters['date_from'] = '';
                $this->draftFilters['date_to'] = '';
                break;
        }
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
        return ($this->preset !== 'all' && ($this->filters['date_from'] !== '' || $this->filters['date_to'] !== ''))
            || $this->filters['document_number'] !== ''
            || $this->filters['shift'] !== ''
            || $this->filters['customer'] !== ''
            || $this->filters['part_number'] !== ''
            || $this->filters['status'] !== '';
    }

    private function determinePresetFromDates(): void
    {
        $from = $this->filters['date_from'] ?? '';
        $to = $this->filters['date_to'] ?? '';
        $now = Carbon::now();

        if ($from === '' && $to === '') {
            $this->preset = 'all';
        } elseif ($from === $now->toDateString() && $to === $now->toDateString()) {
            $this->preset = 'today';
        } elseif ($from === $now->copy()->subDay()->toDateString() && $to === $now->copy()->subDay()->toDateString()) {
            $this->preset = 'yesterday';
        } elseif ($from === $now->copy()->startOfMonth()->toDateString() && $to === $now->copy()->endOfMonth()->toDateString()) {
            $this->preset = 'this_month';
        } else {
            $this->preset = 'custom';
        }
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
        $this->preset = 'all';
        $this->filters = [
            'document_number' => '',
            'date_from' => '',
            'date_to' => '',
            'shift' => '',
            'customer' => '',
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
                fn ($q) => $q->where(
                    'customer',
                    'like',
                    '%' . trim($this->filters['customer']) . '%'
                )
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
            'part_number' => 'Part Number',
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

    public function render()
    {
        $shiftOptions = InspectionReport::query()
            ->select('shift')
            ->distinct()
            ->orderBy('shift')
            ->pluck('shift')
            ->all();

        $perPageOptions = [10, 25, 50, 100];

        $reports = $this->ready
            ? $this->baseQuery()->paginate($this->perPage)->withQueryString()
            : collect();

        return view(
            'livewire.inspection.index',
            compact('reports', 'shiftOptions', 'perPageOptions')
        )->layout('layouts.app');
    }
}
