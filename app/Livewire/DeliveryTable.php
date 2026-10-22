<?php

namespace App\Livewire;

use App\Models\Delivery\DelschedFinal;
use Livewire\Component;
use Livewire\WithPagination;

class DeliveryTable extends Component
{
    use WithPagination;

    public $search = '';

    public $statusFilter = '';

    public $sortField = 'id';

    public $sortDirection = 'asc';

    public $perPage = 10;

    protected $queryString = ['search', 'statusFilter', 'sortField', 'sortDirection', 'perPage'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render()
    {
        $deliveries = DelschedFinal::query()
            ->when($this->search, function ($query) {
                $query->where('so_number', 'like', '%' . $this->search . '%')
                    ->orWhere('customer_name', 'like', '%' . $this->search . '%')
                    ->orWhere('item_name', 'like', '%' . $this->search . '%')
                    ->orWhere('item_code', 'like', '%' . $this->search . '%');
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.delivery-table', [
            'deliveries' => $deliveries,
        ]);
    }

    public function getStatusBadgeClass($status)
    {
        return match ($status) {
            'Finish' => 'bg-green-500',
            'Danger' => 'bg-red-500',
            'Warning' => 'bg-yellow-500',
            default => 'bg-gray-500',
        };
    }
}
