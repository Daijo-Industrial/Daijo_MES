<?php

namespace App\Livewire\MaterialWarehouse;

use App\Models\MasterListMaterial;
use App\Models\MwhOutgoing;
use App\Services\MaterialWarehouseService;
use Livewire\Component;
use Livewire\WithPagination;

class MaterialOutgoingHistory extends Component
{
    use WithPagination;

    // Filter & Search State
    public string $search = '';

    public $whse_id = 'ALL';

    public array $warehouses = [];

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public string $selectedItemCode = '';

    public string $selectedIssuedTo = '';

    public string $sortDirection = 'DESC'; // DESC, ASC

    public int $perPage = 25;

    // Detail & Delete Modal State
    public ?int $selectedOutgoingId = null;

    public bool $showDetailModal = false;

    public ?int $confirmDeleteId = null;

    public bool $showDeleteModal = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'whse_id' => ['except' => 'ALL'],
        'fromDate' => ['except' => ''],
        'toDate' => ['except' => ''],
        'selectedItemCode' => ['except' => ''],
        'selectedIssuedTo' => ['except' => ''],
        'sortDirection' => ['except' => 'DESC'],
        'perPage' => ['except' => 25],
    ];

    public function mount(): void
    {
        $this->warehouses = \App\Models\MwhWarehouse::orderBy('id', 'asc')->get()->toArray();
        if (empty($this->warehouses)) {
            \App\Models\MwhWarehouse::firstOrCreate(['whse_code' => 'KBN'], ['whse_name' => 'Gudang Material KBN']);
            \App\Models\MwhWarehouse::firstOrCreate(['whse_code' => 'KRW'], ['whse_name' => 'Gudang Material Karawang']);
            $this->warehouses = \App\Models\MwhWarehouse::orderBy('id', 'asc')->get()->toArray();
        }

        if (empty($this->fromDate)) {
            $this->fromDate = now()->startOfMonth()->format('Y-m-d');
        }
        if (empty($this->toDate)) {
            $this->toDate = now()->format('Y-m-d');
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedWhseId(): void
    {
        $this->resetPage();
    }

    public function updatedFromDate(): void
    {
        $this->resetPage();
    }

    public function updatedToDate(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedItemCode(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedIssuedTo(): void
    {
        $this->resetPage();
    }

    public function updatedSortDirection(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function setFilterPreset(string $preset): void
    {
        switch ($preset) {
            case 'today':
                $this->fromDate = now()->format('Y-m-d');
                $this->toDate = now()->format('Y-m-d');
                break;
            case 'this_week':
                $this->fromDate = now()->startOfWeek()->format('Y-m-d');
                $this->toDate = now()->format('Y-m-d');
                break;
            case 'this_month':
                $this->fromDate = now()->startOfMonth()->format('Y-m-d');
                $this->toDate = now()->format('Y-m-d');
                break;
            case 'all':
                $this->fromDate = null;
                $this->toDate = null;
                break;
        }
        $this->resetPage();
    }

    public function toggleSortDirection(): void
    {
        $this->sortDirection = $this->sortDirection === 'DESC' ? 'ASC' : 'DESC';
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'selectedItemCode', 'selectedIssuedTo', 'sortDirection', 'perPage']);
        $this->fromDate = now()->startOfMonth()->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function showDetail(int $id): void
    {
        $this->selectedOutgoingId = $id;
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->selectedOutgoingId = null;
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->confirmDeleteId = null;
    }

    public function deleteOutgoing(MaterialWarehouseService $mwhService): void
    {
        if (! $this->confirmDeleteId) {
            return;
        }

        try {
            $mwhService->cancelOutgoingPicking($this->confirmDeleteId);
            session()->flash('success', 'Transaksi pengambilan material berhasil dibatalkan dan stok pallet dikembalikan.');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal membatalkan transaksi: ' . $e->getMessage());
        }

        $this->closeDeleteModal();
        if ($this->showDetailModal) {
            $this->closeDetailModal();
        }
    }

    public function render()
    {
        $query = MwhOutgoing::with(['position.rack', 'material', 'pallet']);

        // Filter Warehouse Branch
        if ($this->whse_id && $this->whse_id !== 'ALL') {
            $whseId = (int) $this->whse_id;
            $query->where(function ($q) use ($whseId) {
                $q->where('whse_id', $whseId)
                    ->orWhereHas('position.rack', fn ($rq) => $rq->where('whse_id', $whseId));
            });
        }

        // Filter Date Range
        if ($this->fromDate) {
            $query->whereDate('outgoing_date', '>=', $this->fromDate);
        }
        if ($this->toDate) {
            $query->whereDate('outgoing_date', '<=', $this->toDate);
        }

        // Filter Material
        if (! empty($this->selectedItemCode)) {
            $query->where('item_code', $this->selectedItemCode);
        }

        // Filter Issued To
        if (! empty($this->selectedIssuedTo)) {
            $query->where('issued_to', $this->selectedIssuedTo);
        }

        // Search Term
        if (! empty($this->search)) {
            $st = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($st) {
                $q->where('outgoing_code', 'like', $st)
                    ->orWhere('pallet_id', 'like', $st)
                    ->orWhere('item_code', 'like', $st)
                    ->orWhere('issued_to', 'like', $st)
                    ->orWhere('remarks', 'like', $st)
                    ->orWhereHas('material', function ($mq) use ($st) {
                        $mq->where('item_description', 'like', $st);
                    })
                    ->orWhereHas('position', function ($pq) use ($st) {
                        $pq->where('position_code', 'like', $st)
                            ->orWhereHas('rack', function ($rq) use ($st) {
                                $rq->where('rack_code', 'like', $st);
                            });
                    });
            });
        }

        // Sorting
        $query->orderBy('outgoing_date', $this->sortDirection)
            ->orderBy('id', $this->sortDirection);

        // Stats calculation
        $statsQuery = clone $query;
        $statsData = [
            'total_qty' => (float) $statsQuery->sum('qty_taken'),
            'total_count' => $statsQuery->count(),
            'today_qty' => (float) MwhOutgoing::whereDate('outgoing_date', now()->format('Y-m-d'))->sum('qty_taken'),
            'unique_materials' => (clone $query)->distinct()->count('item_code'),
        ];

        $outgoings = $query->paginate($this->perPage);

        $selectedOutgoing = $this->selectedOutgoingId
            ? MwhOutgoing::with(['position.rack', 'material', 'pallet.incomingHeader'])->find($this->selectedOutgoingId)
            : null;

        $availableMaterials = MasterListMaterial::whereIn('item_code', MwhOutgoing::select('item_code')->distinct()->pluck('item_code'))
            ->orderBy('item_code')
            ->get(['item_code', 'item_description']);

        $availableIssuedTo = MwhOutgoing::whereNotNull('issued_to')
            ->select('issued_to')
            ->distinct()
            ->orderBy('issued_to')
            ->pluck('issued_to');

        return view('livewire.material-warehouse.material-outgoing-history', [
            'warehouses' => $this->warehouses,
            'outgoings' => $outgoings,
            'stats' => $statsData,
            'selectedOutgoing' => $selectedOutgoing,
            'availableMaterials' => $availableMaterials,
            'availableIssuedTo' => $availableIssuedTo,
        ]);
    }
}
