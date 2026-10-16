<?php

namespace App\Livewire;

use App\Models\MasterCustomerDelivery;
use App\Models\MasterListItem;
use Livewire\Component;
use Livewire\WithPagination;

class MasterListItemView extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterCustomer = '';

    public string $filterMachine = '';

    public string $filterConnection = 'all'; // 'all', 'connected', 'unassigned'

    public int $perPage = 25;

    // Edit state
    public ?int $editingId = null;

    public array $editForm = [];

    // Add state
    public bool $isAdding = false;

    public array $addForm = [];

    protected function rules()
    {
        $rules = [
            'editForm.tipe_mesin' => 'nullable|string|max:255',
            'editForm.standart_packaging_list' => 'nullable|integer|min:0',
            'editForm.setup_time_minute' => 'nullable|string|max:255',
            'editForm.pair' => 'nullable|string|max:255',
            'editForm.cavity' => 'nullable|integer|min:0',
            'editForm.customer_code' => 'nullable|string|max:255',
            'editForm.cycle_time' => 'nullable|numeric|min:0',
        ];

        if ($this->isAdding) {
            $rules['addForm.item_code'] = 'required|string|max:255|unique:master_list_items,item_code';
            $rules['addForm.item_name'] = 'required|string|max:255';
            $rules['addForm.tipe_mesin'] = 'nullable|string|max:255';
            $rules['addForm.standart_packaging_list'] = 'nullable|integer|min:0';
            $rules['addForm.setup_time_minute'] = 'nullable|string|max:255';
            $rules['addForm.pair'] = 'nullable|string|max:255';
            $rules['addForm.cavity'] = 'nullable|integer|min:0';
            $rules['addForm.customer_code'] = 'nullable|string|max:255';
            $rules['addForm.cycle_time'] = 'nullable|numeric|min:0';
        }

        return $rules;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterCustomer()
    {
        $this->resetPage();
    }

    public function updatingFilterMachine()
    {
        $this->resetPage();
    }

    public function updatingFilterConnection()
    {
        $this->resetPage();
    }

    public function startEdit(int $id): void
    {
        $this->isAdding = false; // Close add form if open

        $item = MasterListItem::findOrFail($id);
        $this->editingId = $id;
        $this->editForm = [
            'tipe_mesin' => $item->tipe_mesin,
            'standart_packaging_list' => $item->standart_packaging_list,
            'setup_time_minute' => $item->setup_time_minute,
            'pair' => $item->pair,
            'cavity' => $item->cavity,
            'customer_code' => $item->customer_code,
            'cycle_time' => $item->cycle_time,
        ];
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editForm = [];
    }

    public function saveEdit(): void
    {
        $this->validate();

        if (isset($this->editForm['customer_code']) && ($this->editForm['customer_code'] === '' || $this->editForm['customer_code'] === '0')) {
            $this->editForm['customer_code'] = null;
        }

        MasterListItem::findOrFail($this->editingId)->update($this->editForm);

        $this->editingId = null;
        $this->editForm = [];
        session()->flash('success', 'Item updated successfully.');
    }

    public function startAdd(): void
    {
        $this->editingId = null; // Close edit form if open
        $this->isAdding = true;
        $this->resetErrorBag();
        $this->addForm = [
            'item_code' => '',
            'item_name' => '',
            'tipe_mesin' => '',
            'standart_packaging_list' => 0,
            'setup_time_minute' => '',
            'pair' => '',
            'cavity' => 0,
            'customer_code' => '',
            'cycle_time' => 0,
        ];
    }

    public function cancelAdd(): void
    {
        $this->isAdding = false;
        $this->addForm = [];
    }

    public function saveAdd(): void
    {
        $this->validate();

        if (isset($this->addForm['customer_code']) && ($this->addForm['customer_code'] === '' || $this->addForm['customer_code'] === '0')) {
            $this->addForm['customer_code'] = null;
        }

        MasterListItem::create($this->addForm);

        $this->isAdding = false;
        $this->addForm = [];
        session()->flash('success', 'New item added successfully.');
    }

    public function getCountsProperty(): array
    {
        $total = MasterListItem::count();
        $connected = MasterListItem::whereHas('customer')->count();
        $unassigned = $total - $connected;

        return [
            'total' => $total,
            'connected' => $connected,
            'unassigned' => $unassigned,
        ];
    }

    public function getCustomerListProperty()
    {
        return MasterCustomerDelivery::orderBy('customer_name')->get();
    }

    public function getMachineListProperty()
    {
        return MasterListItem::select('tipe_mesin')
            ->distinct()
            ->whereNotNull('tipe_mesin')
            ->orderBy('tipe_mesin')
            ->pluck('tipe_mesin');
    }

    public function render()
    {
        $items = MasterListItem::with('customer')
            ->when(
                $this->search,
                fn ($q) => $q->where(function ($query) {
                    $query->where('item_code', 'like', "%{$this->search}%")
                        ->orWhere('item_name', 'like', "%{$this->search}%");
                })
            )
            ->when(
                $this->filterCustomer,
                fn ($q) => $q->where('customer_code', $this->filterCustomer)
            )
            ->when(
                $this->filterMachine,
                fn ($q) => $q->where('tipe_mesin', $this->filterMachine)
            )
            ->when(
                $this->filterConnection === 'connected',
                fn ($q) => $q->whereHas('customer')
            )
            ->when(
                $this->filterConnection === 'unassigned',
                fn ($q) => $q->whereDoesntHave('customer')
            )
            ->orderBy('item_code')
            ->paginate($this->perPage);

        return view('livewire.master-list-item-view', [
            'items' => $items,
        ]);
    }
}
