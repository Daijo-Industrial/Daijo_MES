<?php

namespace App\Livewire;

use App\Models\Delivery\SapDelsched;
use Livewire\Component;
use Livewire\WithPagination;

class RawDelschedTable extends Component
{
    use WithPagination;

    public $search = '';

    public $sortField = 'item_code';

    public $sortDirection = 'asc';

    public $perPage = 10;

    protected $queryString = ['search', 'sortField', 'sortDirection', 'perPage'];

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
        $rawData = SapDelsched::query()
            ->when($this->search, function ($query) {
                $query->where('item_code', 'like', '%' . $this->search . '%')
                    ->orWhere('so_number', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.raw-delsched-table', [
            'rawData' => $rawData,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
        ]);
    }
}
