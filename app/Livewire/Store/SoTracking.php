<?php

namespace App\Livewire\Store;

use App\Models\ScannedData;
use App\Models\SoData;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SoTracking extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $spkSearch = '';

    #[Url(history: true)]
    public string $labelSearch = '';

    #[Url(history: true)]
    public string $docNumSearch = '';

    #[Url(history: true)]
    public string $itemCodeSearch = '';

    #[Url(history: true)]
    public string $customerSearch = '';

    #[Url(history: true)]
    public string $statusFilter = 'ALL'; // 'ALL', 'DONE', 'PROGRESS'

    #[Url(history: true)]
    public ?string $dateFrom = null;

    #[Url(history: true)]
    public ?string $dateTo = null;

    #[Url(history: true)]
    public int $perPage = 25;

    public string $sortField = 'scanned_at';
    public string $sortDirection = 'desc';

    public string $barcodeScanInput = '';
    public ?string $lastScannedFeedback = null;
    public bool $showAdvancedFilter = false;

    // DO Detail Modal State
    public bool $showDocDetailModal = false;
    public ?string $selectedDocNum = null;
    public array $docDetailSummary = [];
    public array $docDetailItems = [];

    public function updatedSearch(): void
    {
        $this->parseTabSeparatedSearch();
        $this->resetPage();
    }

    public function updatedSpkSearch(): void
    {
        $this->resetPage();
    }

    public function updatedLabelSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDocNumSearch(): void
    {
        $this->resetPage();
    }

    public function updatedItemCodeSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCustomerSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    /**
     * Auto-detect and parse tab-delimited barcode string if scanned into the main search box.
     */
    protected function parseTabSeparatedSearch(): void
    {
        $val = trim($this->search);
        if (str_contains($val, "\t")) {
            $parts = array_map('trim', explode("\t", $val));
            if (count($parts) >= 4) {
                // Format Daijo Box: [Part No] \t [SPK No] \t [Qty] \t [Label]
                $this->itemCodeSearch = $parts[0];
                $this->spkSearch = $parts[1];
                $this->labelSearch = $parts[3];
                $this->search = '';
                $this->lastScannedFeedback = "Deteksi Barcode Box: Part [{$parts[0]}], SPK [{$parts[1]}], Label [{$parts[3]}]";
            } elseif (count($parts) >= 2) {
                $this->spkSearch = $parts[1];
                $this->search = '';
                $this->lastScannedFeedback = "Deteksi Barcode: SPK [{$parts[1]}]";
            }
        }
    }

    /**
     * Dedicated hardware scanner input submission.
     */
    public function processBarcodeScan(): void
    {
        $raw = trim($this->barcodeScanInput);
        if (empty($raw)) {
            return;
        }

        if (str_contains($raw, "\t")) {
            $parts = array_map('trim', explode("\t", $raw));
            if (count($parts) >= 4) {
                $this->itemCodeSearch = $parts[0];
                $this->spkSearch = $parts[1];
                $this->labelSearch = $parts[3];
                $this->lastScannedFeedback = "Hasil Scan: Part [{$parts[0]}], SPK [{$parts[1]}], Label [{$parts[3]}]";
            } elseif (count($parts) >= 2) {
                $this->spkSearch = $parts[1];
                $this->lastScannedFeedback = "Hasil Scan: SPK [{$parts[1]}]";
            }
            $this->search = '';
        } else {
            $this->search = $raw;
            $this->lastScannedFeedback = "Hasil Scan: [{$raw}]";
        }

        $this->barcodeScanInput = '';
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->spkSearch = '';
        $this->labelSearch = '';
        $this->docNumSearch = '';
        $this->itemCodeSearch = '';
        $this->customerSearch = '';
        $this->statusFilter = 'ALL';
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->lastScannedFeedback = null;
        $this->resetPage();
    }

    public function toggleAdvancedFilter(): void
    {
        $this->showAdvancedFilter = !$this->showAdvancedFilter;
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    /**
     * Inspect quick DO details inside an interactive modal.
     */
    public function openDocDetail(string $docNum): void
    {
        $this->selectedDocNum = $docNum;
        $this->showDocDetailModal = true;

        $soItems = SoData::where('doc_num', $docNum)->get();
        $customer = $soItems->first()?->customer ?? 'UNKNOWN';
        $postingDate = $soItems->first()?->posting_date ?? '-';
        $isDone = $soItems->isNotEmpty() && $soItems->every('is_done', 1);

        $scannedBoxes = ScannedData::where('doc_num', $docNum)->get();

        $this->docDetailSummary = [
            'doc_num'             => $docNum,
            'customer'            => $customer,
            'posting_date'        => $postingDate,
            'is_done'             => $isDone,
            'total_so_qty'        => (int) $soItems->sum('quantity'),
            'total_scanned_qty'   => (int) $scannedBoxes->sum('quantity'),
            'total_scanned_boxes' => $scannedBoxes->count(),
            'unique_spk_count'    => $scannedBoxes->pluck('spk_code')->filter()->unique()->count(),
        ];

        $this->docDetailItems = $soItems->groupBy('item_code')->map(function ($group, $itemCode) use ($scannedBoxes) {
            $first = $group->first();
            $targetQty = (int) $group->sum('quantity');
            $boxScans = $scannedBoxes->where('item_code', $itemCode);
            $scannedQty = (int) $boxScans->sum('quantity');
            $scannedCount = $boxScans->count();

            return [
                'item_code'     => $itemCode,
                'item_name'     => $first->item_name ?? '-',
                'target_qty'    => $targetQty,
                'scanned_qty'   => $scannedQty,
                'scanned_boxes' => $scannedCount,
                'is_finished'   => $scannedQty >= $targetQty && $targetQty > 0,
            ];
        })->values()->toArray();
    }

    public function closeDocDetail(): void
    {
        $this->showDocDetailModal = false;
        $this->selectedDocNum = null;
        $this->docDetailSummary = [];
        $this->docDetailItems = [];
    }

    /**
     * Check if any search filter is actively entered by the user.
     * Prevents running heavy queries across millions of rows on initial page load.
     */
    public function hasActiveSearch(): bool
    {
        return !empty(trim($this->spkSearch))
            || !empty(trim($this->labelSearch))
            || !empty(trim($this->search))
            || !empty(trim($this->docNumSearch))
            || !empty(trim($this->itemCodeSearch))
            || !empty(trim($this->customerSearch))
            || !empty($this->dateFrom)
            || !empty($this->dateTo);
    }

    /**
     * Build base query directly on ScannedData utilizing composite indexes (spk_code, label, doc_num).
     * Avoids heavy derived table subqueries on the entire so_datas table.
     */
    protected function buildFilteredQuery()
    {
        $query = ScannedData::query();

        // Specific filter: SPK Code (uses composite index idx_scanned_data_spk_label)
        if (!empty(trim($this->spkSearch))) {
            $term = trim($this->spkSearch);
            $query->where('scanned_data.spk_code', 'like', "%{$term}%");
        }

        // Specific filter: Label Box
        if (!empty(trim($this->labelSearch))) {
            $term = trim($this->labelSearch);
            $query->where('scanned_data.label', 'like', "%{$term}%");
        }

        // Specific filter: DO / SO Document Number
        if (!empty(trim($this->docNumSearch))) {
            $term = trim($this->docNumSearch);
            $query->where('scanned_data.doc_num', 'like', "%{$term}%");
        }

        // Specific filter: Part Number
        if (!empty(trim($this->itemCodeSearch))) {
            $term = trim($this->itemCodeSearch);
            $query->where('scanned_data.item_code', 'like', "%{$term}%");
        }

        // Specific filter: Customer
        if (!empty(trim($this->customerSearch))) {
            $term = trim($this->customerSearch);
            $matchingDocNums = SoData::where('customer', 'like', "%{$term}%")
                ->pluck('doc_num')
                ->unique();
            $query->whereIn('scanned_data.doc_num', $matchingDocNums);
        }

        // Universal search
        if (!empty(trim($this->search))) {
            $term = trim($this->search);
            $matchingDocNums = SoData::where('customer', 'like', "%{$term}%")
                ->orWhere('item_name', 'like', "%{$term}%")
                ->pluck('doc_num')
                ->unique();

            $query->where(function ($q) use ($term, $matchingDocNums) {
                $q->where('scanned_data.spk_code', 'like', "%{$term}%")
                  ->orWhere('scanned_data.label', 'like', "%{$term}%")
                  ->orWhere('scanned_data.doc_num', 'like', "%{$term}%")
                  ->orWhere('scanned_data.item_code', 'like', "%{$term}%");
                if ($matchingDocNums->isNotEmpty()) {
                    $q->orWhereIn('scanned_data.doc_num', $matchingDocNums);
                }
            });
        }

        // Status DO filter
        if ($this->statusFilter === 'DONE') {
            $doneDocNums = SoData::select('doc_num')
                ->groupBy('doc_num')
                ->havingRaw('MIN(is_done) = 1')
                ->pluck('doc_num');
            $query->whereIn('scanned_data.doc_num', $doneDocNums);
        } elseif ($this->statusFilter === 'PROGRESS') {
            $progressDocNums = SoData::select('doc_num')
                ->groupBy('doc_num')
                ->havingRaw('MIN(is_done) = 0')
                ->pluck('doc_num');
            $query->whereIn('scanned_data.doc_num', $progressDocNums);
        }

        // Date range filter
        if (!empty($this->dateFrom)) {
            $query->whereDate('scanned_data.created_at', '>=', $this->dateFrom);
        }

        if (!empty($this->dateTo)) {
            $query->whereDate('scanned_data.created_at', '<=', $this->dateTo);
        }

        return $query;
    }

    /**
     * Build complete query for record listing with sorting.
     */
    protected function buildQuery()
    {
        $query = $this->buildFilteredQuery();

        // Sorting
        $sortCol = match ($this->sortField) {
            'doc_num'   => 'scanned_data.doc_num',
            'spk_code'  => 'scanned_data.spk_code',
            'label'     => 'scanned_data.label',
            'item_code' => 'scanned_data.item_code',
            'quantity'  => 'scanned_data.quantity',
            default     => 'scanned_data.created_at',
        };

        return $query->orderBy($sortCol, $this->sortDirection);
    }

    /**
     * Enrich ScannedData records with customer, item_name, and is_done from SoData.
     * Efficiently executes 1 indexed lookup on SoData for ONLY the current page's doc_nums.
     */
    protected function enrichRecords($collection)
    {
        if (!$collection || $collection->isEmpty()) {
            return $collection;
        }

        $docNums = $collection->pluck('doc_num')->unique()->filter()->values();
        if ($docNums->isEmpty()) {
            return $collection;
        }

        $soDataMap = SoData::whereIn('doc_num', $docNums)
            ->select(['doc_num', 'item_code', 'customer', 'item_name', 'is_done', 'posting_date'])
            ->get()
            ->groupBy('doc_num');

        return $collection->transform(function ($row) use ($soDataMap) {
            $docItems = $soDataMap->get($row->doc_num);
            $itemMatch = $docItems?->firstWhere('item_code', $row->item_code) ?? $docItems?->first();

            $row->customer = $itemMatch?->customer ?? 'N/A';
            $row->item_name = $itemMatch?->item_name ?? '-';
            $row->is_done = $docItems ? $docItems->every('is_done', 1) : false;
            $row->posting_date = $itemMatch?->posting_date ?? '-';
            $row->scanned_at = $row->created_at;

            return $row;
        });
    }

    /**
     * Instant CSV Export for filtered audit records.
     */
    public function exportCsv()
    {
        $records = $this->buildQuery()->take(5000)->get();
        $this->enrichRecords($records);
        $filename = 'tracking_spk_label_do_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, [
                'No DO / SO',
                'Customer',
                'Status DO',
                'Nomor SPK',
                'Label Box',
                'Part No (Item Code)',
                'Nama Item / Deskripsi',
                'Quantity (pcs)',
                'Gudang',
                'Waktu Scan',
            ]);

            foreach ($records as $row) {
                fputcsv($handle, [
                    $row->doc_num,
                    $row->customer ?? '-',
                    $row->is_done ? 'SELESAI (DONE)' : 'IN PROGRESS',
                    $row->spk_code ?? '-',
                    $row->label ?? '-',
                    $row->item_code,
                    $row->item_name ?? '-',
                    $row->quantity,
                    $row->warehouse ?? '-',
                    $row->scanned_at ? Carbon::parse($row->scanned_at)->format('Y-m-d H:i:s') : '-',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function searchBySpkAndLabel(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $hasActiveSearch = $this->hasActiveSearch();

        // When user opens the menu without active input, DO NOT run heavy queries!
        if (!$hasActiveSearch) {
            return view('livewire.store.so-tracking', [
                'records'         => null,
                'stats'           => (object) [
                    'total_boxes'      => 0,
                    'total_qty'        => 0,
                    'unique_spk_count' => 0,
                    'unique_do_count'  => 0,
                ],
                'isSearching'     => false,
                'directMatches'   => collect(),
                'hasActiveSearch' => false,
            ]);
        }

        // 1. Calculate KPI stats on current filter query (aggregates ONLY, super fast on scanned_data)
        $stats = $this->buildFilteredQuery()->selectRaw('
            COUNT(*) as total_boxes,
            COALESCE(SUM(scanned_data.quantity), 0) as total_qty,
            COUNT(DISTINCT scanned_data.spk_code) as unique_spk_count,
            COUNT(DISTINCT scanned_data.doc_num) as unique_do_count
        ')->first();

        // 2. Grouped DO summary if searching by SPK/Label/Search (Level Dokumen DO)
        $isSearching = !empty(trim($this->spkSearch)) || !empty(trim($this->labelSearch)) || !empty(trim($this->search));
        $matchingDos = collect();

        if ($isSearching) {
            $doGrouped = (clone $this->buildFilteredQuery())
                ->select([
                    'scanned_data.doc_num',
                    DB::raw('COUNT(*) as box_count'),
                    DB::raw('COALESCE(SUM(scanned_data.quantity), 0) as total_qty'),
                    DB::raw('MAX(scanned_data.created_at) as last_scanned_at'),
                ])
                ->groupBy('scanned_data.doc_num')
                ->orderByDesc('last_scanned_at')
                ->take(6)
                ->get();

            if ($doGrouped->isNotEmpty()) {
                $docNums = $doGrouped->pluck('doc_num')->values();
                $soDatas = SoData::whereIn('doc_num', $docNums)
                    ->select(['doc_num', 'customer', 'item_name', 'is_done', 'posting_date'])
                    ->get()
                    ->groupBy('doc_num');

                $matchingDos = $doGrouped->map(function ($item) use ($soDatas) {
                    $docItems = $soDatas->get($item->doc_num);
                    $firstItem = $docItems?->first();

                    return (object) [
                        'doc_num'         => $item->doc_num,
                        'customer'        => $firstItem?->customer ?? 'N/A',
                        'is_done'         => $docItems ? $docItems->every('is_done', 1) : false,
                        'posting_date'    => $firstItem?->posting_date ?? '-',
                        'box_count'       => (int) $item->box_count,
                        'total_qty'       => (int) $item->total_qty,
                        'last_scanned_at' => $item->last_scanned_at,
                    ];
                });
            }
        }

        // 3. Paginated records (Level Box Scan individual)
        $records = $this->buildQuery()->paginate($this->perPage);
        $this->enrichRecords($records->getCollection());

        return view('livewire.store.so-tracking', [
            'records'         => $records,
            'stats'           => $stats,
            'isSearching'     => $isSearching,
            'matchingDos'     => $matchingDos,
            'directMatches'   => $matchingDos, // alias for backwards compatibility
            'hasActiveSearch' => true,
        ]);
    }
}
