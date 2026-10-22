<?php

namespace App\Livewire;

use App\Models\StoreBoxDetail;
use Livewire\Component;
use Livewire\WithPagination;

class BarcodeHistory extends Component
{
    use WithPagination;

    public $part_no = '';

    public $remark = '';

    public $date_from = '';

    public $date_to = '';

    public $selected = [];

    public $selectAll = false;

    public $rangeFrom = '';

    public $rangeTo = '';

    protected $queryString = [
        'part_no' => ['except' => ''],
        'remark' => ['except' => ''],
        'date_from' => ['except' => ''],
        'date_to' => ['except' => ''],
    ];

    public function updatedPage()
    {
        $this->selectAll = false;
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = $this->records->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function selectRange()
    {
        if (! $this->rangeFrom || ! $this->rangeTo) {
            return;
        }

        $rangeIds = StoreBoxDetail::whereBetween('label', [$this->rangeFrom, $this->rangeTo])
            ->when($this->part_no, fn ($q) => $q->where('part_no', 'like', '%' . $this->part_no . '%'))
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->toArray();

        $this->selected = array_unique(array_merge($this->selected, $rangeIds));
        $this->rangeFrom = '';
        $this->rangeTo = '';
    }

    public function clearFilters()
    {
        $this->reset(['part_no', 'remark', 'date_from', 'date_to', 'selected', 'selectAll']);
        $this->resetPage();
    }

    public function getRecordsProperty()
    {
        return StoreBoxDetail::query()
            ->when($this->part_no, fn ($q) => $q->where('part_no', 'like', '%' . $this->part_no . '%'))
            ->when($this->remark, fn ($q) => $q->where('remark', 'like', '%' . $this->remark . '%'))
            ->when($this->date_from, fn ($q) => $q->whereDate('created_at', '>=', $this->date_from))
            ->when($this->date_to, fn ($q) => $q->whereDate('created_at', '<=', $this->date_to))
            ->orderBy('part_no', 'asc')
            ->orderBy('label', 'asc')
            ->paginate(50);
    }

    public function render()
    {
        return view('livewire.barcode-history', [
            'history' => $this->records,
        ]);
    }
}
