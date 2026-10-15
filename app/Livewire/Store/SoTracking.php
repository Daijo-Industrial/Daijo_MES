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
     * Build base query joining ScannedData with SoData metadata without N+1 duplication.
     */
    protected function buildQuery()
    {
        $query = ScannedData::query()
            ->leftJoinSub(
                DB::table('so_datas')
                    ->select([
                        'doc_num',
                        'item_code',
                        DB::raw('MAX(customer) as customer'),
                        DB::raw('MAX(item_name) as item_name'),
                        DB::raw('MAX(is_done) as is_done'),
                        DB::raw('MAX(posting_date) as posting_date')
                    ])
                    ->groupBy('doc_num', 'item_code'),
                'so_items',
                function ($join) {
                    $join->on('scanned_data.doc_num', '=', 'so_items.doc_num')
                         ->on('scanned_data.item_code', '=', 'so_items.item_code');
                }
            )
            ->leftJoinSub(
                DB::table('so_datas')
                    ->select([
                        'doc_num',
                        DB::raw('MAX(customer) as fallback_customer'),
                        DB::raw('MAX(is_done) as fallback_is_done'),
                    ])
                    ->groupBy('doc_num'),
                'so_headers',
                'scanned_data.doc_num',
                '=',
                'so_headers.doc_num'
            )
            ->select([
                'scanned_data.id',
                'scanned_data.doc_num',
                'scanned_data.item_code',
                'scanned_data.spk_code',
                'scanned_data.quantity',
                'scanned_data.warehouse',
                'scanned_data.label',
                'scanned_data.created_at as scanned_at',
                DB::raw("COALESCE(so_items.customer, so_headers.fallback_customer, 'N/A') as customer"),
                'so_items.item_name',
                DB::raw('COALESCE(so_items.is_done, so_headers.fallback_is_done, 0) as is_done'),
                'so_items.posting_date',
            ]);

        // Universal search
        if (!empty(trim($this->search))) {
            $term = trim($this->search);
            $query->where(function ($q) use ($term) {
                $q->where('scanned_data.spk_code', 'like', "%{$term}%")
                  ->orWhere('scanned_data.label', 'like', "%{$term}%")
                  ->orWhere('scanned_data.doc_num', 'like', "%{$term}%")
                  ->orWhere('scanned_data.item_code', 'like', "%{$term}%")
                  ->orWhere('so_items.customer', 'like', "%{$term}%")
                  ->orWhere('so_headers.fallback_customer', 'like', "%{$term}%");
            });
        }

        // Specific filter: SPK Code
        if (!empty(trim($this->spkSearch))) {
            $term = trim($this->spkSearch);
            $query->where('scanned_data.spk_code', 'like', "%{$term}%");
        }

        // Specific filter: Label
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
            $query->where(function ($q) use ($term) {
                $q->where('so_items.customer', 'like', "%{$term}%")
                  ->orWhere('so_headers.fallback_customer', 'like', "%{$term}%");
            });
        }

        // Status DO filter
        if ($this->statusFilter === 'DONE') {
            $query->whereRaw('COALESCE(so_items.is_done, so_headers.fallback_is_done, 0) = 1');
        } elseif ($this->statusFilter === 'PROGRESS') {
            $query->whereRaw('COALESCE(so_items.is_done, so_headers.fallback_is_done, 0) = 0');
        }

        // Date range filter
        if (!empty($this->dateFrom)) {
            $query->whereDate('scanned_data.created_at', '>=', $this->dateFrom);
        }

        if (!empty($this->dateTo)) {
            $query->whereDate('scanned_data.created_at', '<=', $this->dateTo);
        }

        // Sorting
        $sortCol = match ($this->sortField) {
            'doc_num'   => 'scanned_data.doc_num',
            'spk_code'  => 'scanned_data.spk_code',
            'label'     => 'scanned_data.label',
            'item_code' => 'scanned_data.item_code',
            'quantity'  => 'scanned_data.quantity',
            'customer'  => DB::raw("COALESCE(so_items.customer, so_headers.fallback_customer, 'N/A')"),
            default     => 'scanned_data.created_at',
        };

        return $query->orderBy($sortCol, $this->sortDirection);
    }

    /**
     * Instant CSV Export for filtered audit records.
     */
    public function exportCsv()
    {
        $records = $this->buildQuery()->get();
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
        $baseQuery = $this->buildQuery();

        // Calculate KPI stats on current filter query
        $stats = (clone $baseQuery)->selectRaw('
            COUNT(*) as total_boxes,
            COALESCE(SUM(scanned_data.quantity), 0) as total_qty,
            COUNT(DISTINCT scanned_data.spk_code) as unique_spk_count,
            COUNT(DISTINCT scanned_data.doc_num) as unique_do_count
        ')->first();

        $isSearching = !empty(trim($this->spkSearch)) || !empty(trim($this->labelSearch)) || !empty(trim($this->search));
        $directMatches = $isSearching ? (clone $baseQuery)->take(5)->get() : collect();

        $records = $baseQuery->paginate($this->perPage);

        return view('livewire.store.so-tracking', [
            'records'       => $records,
            'stats'         => $stats,
            'isSearching'   => $isSearching,
            'directMatches' => $directMatches,
        ]);
    }
}
