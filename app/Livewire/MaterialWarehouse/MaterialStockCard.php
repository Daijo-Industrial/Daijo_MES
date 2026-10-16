<?php

namespace App\Livewire\MaterialWarehouse;

use App\Models\MasterListMaterial;
use App\Models\MwhOutgoing;
use App\Models\MwhPallet;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class MaterialStockCard extends Component
{
    use WithPagination;

    public string $selectedItemCode = '';

    public string $search = '';

    public string $filterType = 'ALL'; // ALL, INCOMING, OUTGOING

    public $whse_id = 'ALL';

    public array $warehouses = [];

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public string $sortDirection = 'DESC'; // DESC (Terbaru -> Terlama), ASC (Terlama -> Terbaru)

    public int $perPage = 25;

    protected $queryString = [
        'selectedItemCode' => ['except' => ''],
        'whse_id' => ['except' => 'ALL'],
        'search' => ['except' => ''],
        'filterType' => ['except' => 'ALL'],
        'fromDate' => ['except' => ''],
        'toDate' => ['except' => ''],
        'sortDirection' => ['except' => 'DESC'],
        'perPage' => ['except' => 25],
    ];

    public function getActiveMaterialsProperty()
    {
        $palQ = MwhPallet::whereNotNull('item_code');
        $outQ = MwhOutgoing::whereNotNull('item_code');

        if ($this->whse_id && $this->whse_id !== 'ALL') {
            $whseId = (int) $this->whse_id;
            $palQ->where(function ($q) use ($whseId) {
                $q->where('whse_id', $whseId)->orWhereHas('position.rack', fn ($rq) => $rq->where('whse_id', $whseId));
            });
            $outQ->where(function ($q) use ($whseId) {
                $q->where('whse_id', $whseId)->orWhereHas('position.rack', fn ($rq) => $rq->where('whse_id', $whseId));
            });
        }

        $incomingCodes = $palQ->select('item_code')->distinct()->pluck('item_code');
        $outgoingCodes = $outQ->select('item_code')->distinct()->pluck('item_code');

        $activeCodes = $incomingCodes->merge($outgoingCodes)->unique()->filter()->values();

        if ($activeCodes->isEmpty()) {
            return collect();
        }

        $masterMaterials = MasterListMaterial::whereIn('item_code', $activeCodes)->get()->keyBy('item_code');

        return $activeCodes->map(function ($code) use ($masterMaterials) {
            $master = $masterMaterials->get($code);

            return (object) [
                'item_code' => $code,
                'item_description' => $master?->item_description ?: 'Material ' . $code,
                'purchasing_uom' => $master?->purchasing_uom ?: 'KG',
                'preferred_supplier' => $master?->preferred_supplier ?: '-',
            ];
        })->sortBy('item_code')->values();
    }

    public function mount(): void
    {
        $this->warehouses = \App\Models\MwhWarehouse::orderBy('id', 'asc')->get()->toArray();
        if (empty($this->warehouses)) {
            \App\Models\MwhWarehouse::firstOrCreate(['whse_code' => 'KBN'], ['whse_name' => 'Gudang Material KBN']);
            \App\Models\MwhWarehouse::firstOrCreate(['whse_code' => 'KRW'], ['whse_name' => 'Gudang Material Karawang']);
            $this->warehouses = \App\Models\MwhWarehouse::orderBy('id', 'asc')->get()->toArray();
        }

        // Default to today's date if not set in request URL
        if (empty($this->fromDate)) {
            $this->fromDate = now()->format('Y-m-d');
        }
        if (empty($this->toDate)) {
            $this->toDate = now()->format('Y-m-d');
        }
    }

    public function updatingWhseId(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedItemCode(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function updatingSortDirection(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function toggleSortDirection(): void
    {
        $this->sortDirection = $this->sortDirection === 'DESC' ? 'ASC' : 'DESC';
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterType', 'sortDirection', 'perPage']);
        $this->fromDate = now()->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function render()
    {
        $materials = $this->activeMaterials;
        $selectedMaterial = ! empty($this->selectedItemCode) ? $materials->firstWhere('item_code', $this->selectedItemCode) : null;
        $masterMaterials = MasterListMaterial::all()->keyBy('item_code');

        $summary = [
            'current_stock' => 0.0,
            'total_incoming' => 0.0,
            'total_outgoing' => 0.0,
            'active_pallets' => 0,
            'uom' => $selectedMaterial?->purchasing_uom ?? 'KG',
        ];

        // 1. Calculate live summary stats
        $palletQuery = MwhPallet::query();
        $outgoingQuery = MwhOutgoing::query();

        if ($this->whse_id && $this->whse_id !== 'ALL') {
            $whseId = (int) $this->whse_id;
            $palletQuery->where(function ($q) use ($whseId) {
                $q->where('whse_id', $whseId)
                    ->orWhereHas('position.rack', fn ($rq) => $rq->where('whse_id', $whseId))
                    ->orWhereHas('initialPosition.rack', fn ($rq) => $rq->where('whse_id', $whseId));
            });
            $outgoingQuery->where(function ($q) use ($whseId) {
                $q->where('whse_id', $whseId)
                    ->orWhereHas('position.rack', fn ($rq) => $rq->where('whse_id', $whseId))
                    ->orWhereHas('pallet.initialPosition.rack', fn ($rq) => $rq->where('whse_id', $whseId));
            });
        }

        if (! empty($this->selectedItemCode)) {
            $palletQuery->where('item_code', $this->selectedItemCode);
            $outgoingQuery->where('item_code', $this->selectedItemCode);
        }

        $summary['current_stock'] = (float) (clone $palletQuery)->where('current_qty', '>', 0)->sum('current_qty');
        $summary['active_pallets'] = (clone $palletQuery)->where('current_qty', '>', 0)->count();
        $summary['total_incoming'] = (float) (clone $palletQuery)->sum('initial_qty');
        $summary['total_outgoing'] = (float) (clone $outgoingQuery)->sum('qty_taken');

        // 2. Fetch Incoming movements (MwhPallet records)
        $incomingsQuery = MwhPallet::with(['incomingHeader', 'position.rack', 'initialPosition.rack', 'outgoings.position.rack']);
        if ($this->whse_id && $this->whse_id !== 'ALL') {
            $whseId = (int) $this->whse_id;
            $incomingsQuery->where(function ($q) use ($whseId) {
                $q->where('whse_id', $whseId)
                    ->orWhereHas('position.rack', fn ($rq) => $rq->where('whse_id', $whseId))
                    ->orWhereHas('initialPosition.rack', fn ($rq) => $rq->where('whse_id', $whseId));
            });
        }
        if (! empty($this->selectedItemCode)) {
            $incomingsQuery->where('item_code', $this->selectedItemCode);
        }

        $incomings = $incomingsQuery->get()->map(function ($pallet) use ($masterMaterials) {
            $dt = $pallet->created_at ?: ($pallet->incomingHeader?->arrival_date ? Carbon::parse($pallet->incomingHeader->arrival_date) : null);
            $master = $masterMaterials->get($pallet->item_code);
            $isRetur = ($pallet->incomingHeader?->incoming_type === 'RETURN_PRODUCTION');

            $sourceDest = $isRetur
                ? ('Retur dari: ' . ($pallet->incomingHeader?->returned_from ?: 'Produksi'))
                : ($pallet->incomingHeader?->supplier_name ?: 'Internal Supplier');

            $subRef = $isRetur
                ? ('Ref Outgoing: ' . ($pallet->incomingHeader?->original_outgoing_code ?: '-') . ' | Lot: ' . ($pallet->lot_no ?: '-'))
                : ('Lot: ' . ($pallet->lot_no ?: '-') . ' | PO: ' . ($pallet->incomingHeader?->po_number ?: '-'));

            $remarks = $isRetur
                ? ('Retur Sisa Produksi (' . number_format($pallet->current_qty, 2) . ' KG sisa)')
                : ('Penerimaan Supplier (' . number_format($pallet->current_qty, 2) . ' KG sisa)');

            $slotCode = $pallet->position?->position_code
                ?: ($pallet->initialPosition?->position_code
                ?: ($pallet->outgoings->first()?->position?->position_code ?: 'UNASSIGNED'));

            return [
                'id' => 'IN-' . $pallet->id,
                'timestamp' => $dt,
                'date_formatted' => $dt ? $dt->timezone('Asia/Jakarta')->format('d M Y H:i') : '-',
                'type' => $isRetur ? 'RETURN_PRODUCTION' : 'INCOMING',
                'item_code' => $pallet->item_code,
                'item_description' => $master?->item_description ?: 'Material ' . $pallet->item_code,
                'ref_code' => $pallet->pallet_id,
                'sub_ref' => $subRef,
                'source_destination' => $sourceDest,
                'slot_code' => $slotCode,
                'qty_in' => (float) $pallet->initial_qty,
                'qty_out' => 0.0,
                'pallet_qty' => (float) $pallet->current_qty,
                'uom' => $pallet->uom ?? 'KG',
                'remarks' => $pallet->incomingHeader?->remarks ?: $remarks,
            ];
        });

        // 3. Fetch Outgoing movements (MwhOutgoing records)
        $outgoingsQuery = MwhOutgoing::with(['pallet.position.rack', 'pallet.initialPosition.rack', 'position.rack']);
        if ($this->whse_id && $this->whse_id !== 'ALL') {
            $whseId = (int) $this->whse_id;
            $outgoingsQuery->where(function ($q) use ($whseId) {
                $q->where('whse_id', $whseId)
                    ->orWhereHas('position.rack', fn ($rq) => $rq->where('whse_id', $whseId))
                    ->orWhereHas('pallet.initialPosition.rack', fn ($rq) => $rq->where('whse_id', $whseId));
            });
        }
        if (! empty($this->selectedItemCode)) {
            $outgoingsQuery->where('item_code', $this->selectedItemCode);
        }

        $outgoings = $outgoingsQuery->get()->map(function ($out) use ($masterMaterials) {
            $dt = $out->created_at ?: ($out->outgoing_date ? Carbon::parse($out->outgoing_date) : null);
            $master = $masterMaterials->get($out->item_code);

            $slotCode = $out->position?->position_code
                ?: ($out->pallet?->position?->position_code
                ?: ($out->pallet?->initialPosition?->position_code ?: '-'));

            return [
                'id' => 'OUT-' . $out->id,
                'timestamp' => $dt,
                'date_formatted' => $dt ? $dt->timezone('Asia/Jakarta')->format('d M Y H:i') : '-',
                'type' => 'OUTGOING',
                'item_code' => $out->item_code,
                'item_description' => $master?->item_description ?: 'Material ' . $out->item_code,
                'ref_code' => $out->outgoing_code,
                'sub_ref' => 'Pallet: ' . $out->pallet_id,
                'source_destination' => 'Tujuan: ' . ($out->issued_to ?: 'Produksi'),
                'slot_code' => $slotCode,
                'qty_in' => 0.0,
                'qty_out' => (float) $out->qty_taken,
                'pallet_qty' => (float) ($out->pallet?->current_qty ?? 0.0),
                'uom' => $out->uom ?? 'KG',
                'remarks' => $out->remarks ?: 'Pengambilan Material',
            ];
        });

        // 4. Merge and sort chronologically for balance calculation
        $allMovements = $incomings->concat($outgoings)->sortBy('timestamp')->values();

        // 5. Calculate Running Balance & Opening Balance per Material
        $fromCarbon = $this->fromDate ? Carbon::parse($this->fromDate)->startOfDay() : null;
        $toCarbon = $this->toDate ? Carbon::parse($this->toDate)->endOfDay() : null;

        $openingBalances = [];
        $runningBalances = [];

        $calculatedMovements = collect();

        foreach ($allMovements as $item) {
            $itemCode = $item['item_code'];
            $itemDt = $item['timestamp'];

            if (! isset($runningBalances[$itemCode])) {
                $runningBalances[$itemCode] = 0.0;
                $openingBalances[$itemCode] = 0.0;
            }

            // If transaction happened BEFORE fromDate, accumulate to openingBalances
            if ($fromCarbon && $itemDt && $itemDt->lt($fromCarbon)) {
                if ($item['type'] === 'INCOMING') {
                    $openingBalances[$itemCode] += $item['qty_in'];
                } else {
                    $openingBalances[$itemCode] -= $item['qty_out'];
                }
                $runningBalances[$itemCode] = max(0.0, $openingBalances[$itemCode]);
                continue;
            }

            if ($item['type'] === 'INCOMING') {
                $runningBalances[$itemCode] += $item['qty_in'];
            } else {
                $runningBalances[$itemCode] -= $item['qty_out'];
            }
            $item['balance'] = max(0.0, $runningBalances[$itemCode]);

            // Check toDate limit
            if ($toCarbon && $itemDt && $itemDt->gt($toCarbon)) {
                continue;
            }

            $calculatedMovements->push($item);
        }

        // 6. Prepend Opening Balance row ONLY IF a single specific material is selected
        if (! empty($this->selectedItemCode) && $fromCarbon) {
            $openingBalance = $openingBalances[$this->selectedItemCode] ?? 0.0;
            if ($openingBalance > 0) {
                $openingRow = [
                    'id' => 'OPENING-BAL',
                    'timestamp' => $fromCarbon,
                    'date_formatted' => $fromCarbon->timezone('Asia/Jakarta')->format('d M Y') . ' (00:00)',
                    'type' => 'OPENING_BALANCE',
                    'item_code' => $this->selectedItemCode,
                    'item_description' => $selectedMaterial?->item_description ?: '',
                    'ref_code' => 'SALDO AWAL',
                    'sub_ref' => 'Akumulasi Sebelum ' . $fromCarbon->format('d M Y'),
                    'source_destination' => 'Stok Bawaan Periode Sebelumnya',
                    'slot_code' => '-',
                    'qty_in' => $openingBalance,
                    'qty_out' => 0.0,
                    'pallet_qty' => $openingBalance,
                    'uom' => $selectedMaterial?->purchasing_uom ?? 'KG',
                    'balance' => $openingBalance,
                    'remarks' => 'Saldo awal per tanggal ' . $fromCarbon->format('d M Y'),
                ];

                $calculatedMovements->prepend($openingRow);
            }
        }

        // 7. Apply Search and Type Filters
        $movements = $calculatedMovements;

        if ($this->filterType === 'INCOMING') {
            $movements = $movements->filter(fn ($i) => in_array($i['type'], ['INCOMING', 'RETURN_PRODUCTION', 'OPENING_BALANCE']));
        } elseif ($this->filterType === 'OUTGOING') {
            $movements = $movements->filter(fn ($i) => in_array($i['type'], ['OUTGOING', 'OPENING_BALANCE']));
        }

        if (! empty($this->search)) {
            $term = strtolower(trim($this->search));
            $movements = $movements->filter(function ($item) use ($term) {
                return str_contains(strtolower($item['item_code']), $term)
                    || str_contains(strtolower($item['item_description']), $term)
                    || str_contains(strtolower($item['ref_code']), $term)
                    || str_contains(strtolower($item['sub_ref']), $term)
                    || str_contains(strtolower($item['source_destination']), $term)
                    || str_contains(strtolower($item['slot_code']), $term)
                    || str_contains(strtolower($item['remarks']), $term);
            });
        }

        // 8. Apply Sorting (ASC vs DESC)
        if ($this->sortDirection === 'DESC') {
            $movements = $movements->sortByDesc('timestamp')->values();
        } else {
            $movements = $movements->sortBy('timestamp')->values();
        }

        // 9. Paginate Collection
        $totalCount = $movements->count();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = $this->perPage;

        $paginatedMovements = new LengthAwarePaginator(
            $movements->slice(($page - 1) * $perPage, $perPage)->values(),
            $totalCount,
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );

        return view('livewire.material-warehouse.material-stock-card', [
            'warehouses' => $this->warehouses,
            'materials' => $materials,
            'selectedMaterial' => $selectedMaterial,
            'movements' => $paginatedMovements,
            'totalMovementsCount' => $totalCount,
            'summary' => $summary,
        ]);
    }
}
