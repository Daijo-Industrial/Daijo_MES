<?php

namespace App\Livewire\Admin;

use App\Models\MasterBusinessPartner;
use App\Models\MasterCustomerDelivery;
use App\Models\MasterItemImportBatch;
use App\Models\MasterItemLog;
use Livewire\Component;
use Livewire\WithPagination;

class MasterListLogs extends Component
{
    use WithPagination;

    public string $search = '';

    /**
     * Active tab filter: 'updates' (default), 'all', or 'creates'
     */
    public string $actionFilter = 'updates';

    /**
     * Active Excel upload batch code filter (e.g. 'BATCH-20261019-1018')
     */
    public string $batchFilter = '';

    public int $perPage = 25;

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'search' => ['except' => ''],
        'actionFilter' => ['except' => 'updates'],
        'batchFilter' => ['except' => ''],
    ];

    public const UPDATE_ACTIONS = ['inline_edit', 'excel_import_update', 'update'];

    public const CREATE_ACTIONS = ['excel_import_create', 'csv_import_create', 'create'];

    public const FIELD_LABELS = [
        'item_code' => 'Item Code',
        'item_name' => 'Nama Part / Item',
        'customer_code' => 'Customer / Pelanggan',
        'part_weight' => 'Berat Part (g)',
        'cycle_time' => 'Cycle Time (dtk)',
        'tipe_mesin' => 'Tipe Mesin (Tonase)',
        'standart_packaging_list' => 'Std Packaging (pcs)',
        'setup_time_minute' => 'Setup Time (mnt)',
        'pair' => 'Pair',
        'cavity' => 'Cavity',
        'project_code' => 'Project Code',
        'family' => 'Family / Project',
        'description_in_foreign_lang' => 'Desc / Part No OEM',
        'color' => 'Warna (Color)',
        'half_code_1' => 'Half Code 1',
        'half_code_2' => 'Half Code 2',
        'position' => 'Posisi / Tipe',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function setActionFilter(string $filter): void
    {
        $this->actionFilter = in_array($filter, ['updates', 'all', 'creates'], true) ? $filter : 'updates';
        $this->resetPage();
    }

    public function selectBatch(string $batchCode): void
    {
        $this->batchFilter = $batchCode;
        // Default to updates so the user immediately investigates what this upload changed
        $this->actionFilter = 'updates';
        $this->resetPage();
    }

    public function clearBatchFilter(): void
    {
        $this->batchFilter = '';
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->actionFilter = 'updates';
        $this->batchFilter = '';
        $this->resetPage();
    }

    public function render()
    {
        // 1. Fetch available upload batches for the inspector header
        $batches = MasterItemImportBatch::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $activeBatch = $this->batchFilter !== ''
            ? $batches->firstWhere('batch_code', $this->batchFilter)
            : null;

        // 2. Tab counters (scoped to the active batch if selected, or global)
        $countQuery = MasterItemLog::query();
        if ($this->batchFilter !== '') {
            $countQuery->where('upload_batch_id', $this->batchFilter);
        }

        $tabCounts = [
            'all' => (clone $countQuery)->count(),
            'updates' => (clone $countQuery)->where(function ($q) {
                $q->whereIn('action', self::UPDATE_ACTIONS)
                    ->orWhereNotNull('old_values');
            })->count(),
            'creates' => (clone $countQuery)->whereIn('action', self::CREATE_ACTIONS)->count(),
        ];

        // 3. Base query
        $query = MasterItemLog::with(['user', 'batch']);

        // Apply upload batch filter
        if ($this->batchFilter !== '') {
            $query->where('upload_batch_id', $this->batchFilter);
        }

        // Apply action tab filter
        if ($this->actionFilter === 'updates') {
            $query->where(function ($q) {
                $q->whereIn('action', self::UPDATE_ACTIONS)
                    ->orWhereNotNull('old_values');
            });
        } elseif ($this->actionFilter === 'creates') {
            $query->whereIn('action', self::CREATE_ACTIONS);
        }

        // Apply search keyword
        $search = trim($this->search);
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('item_code', 'like', '%' . $search . '%')
                    ->orWhere('action', 'like', '%' . $search . '%')
                    ->orWhere('file_name', 'like', '%' . $search . '%')
                    ->orWhere('upload_batch_id', 'like', '%' . $search . '%')
                    ->orWhere('new_values', 'like', '%' . $search . '%')
                    ->orWhere('old_values', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        // 4. Resolve customer names for all customer codes in current page logs
        $customerMap = $this->resolveCustomerMap($logs->items());

        return view('livewire.admin.master-list-logs', [
            'logs' => $logs,
            'batches' => $batches,
            'activeBatch' => $activeBatch,
            'tabCounts' => $tabCounts,
            'fieldLabels' => self::FIELD_LABELS,
            'customerMap' => $customerMap,
        ]);
    }

    /**
     * Resolves human-friendly customer names for any customer codes found in old_values or new_values
     */
    protected function resolveCustomerMap(array $logItems): array
    {
        $codes = [];
        foreach ($logItems as $log) {
            if (isset($log->old_values['customer_code'])) {
                $codes[] = (string) $log->old_values['customer_code'];
            }
            if (isset($log->new_values['customer_code'])) {
                $codes[] = (string) $log->new_values['customer_code'];
            }
        }

        $validCodes = array_values(array_filter(array_unique($codes), function ($c) {
            return $c !== '' && $c !== '0' && $c !== '-' && strtolower($c) !== 'null';
        }));

        if (empty($validCodes)) {
            return [];
        }

        $map = MasterBusinessPartner::whereIn('bp_code', $validCodes)
            ->pluck('bp_name', 'bp_code')
            ->all();

        $missing = array_diff($validCodes, array_keys($map));
        if (! empty($missing)) {
            $deliveryMap = MasterCustomerDelivery::whereIn('customer_code', $missing)
                ->pluck('customer_name', 'customer_code')
                ->all();
            $map = array_merge($map, $deliveryMap);
        }

        return $map;
    }
}
