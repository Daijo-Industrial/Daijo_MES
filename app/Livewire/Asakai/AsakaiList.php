<?php

namespace App\Livewire\Asakai;

use App\Models\Asakai;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class AsakaiList extends Component
{
    use WithPagination;

    public $search = '';

    public $statusFilter = '';

    public $customerFilter = '';

    public $shiftFilter = '';

    public $dateFrom = '';

    public $dateTo = '';

    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'customerFilter' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'statusFilter', 'customerFilter', 'shiftFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function delete($id)
    {
        $asakai = Asakai::findOrFail($id);

        if ($asakai->status === 'closed') {
            session()->flash('error', 'Data dengan status CLOSED tidak dapat dihapus!');

            return;
        }
        // Check permission
        // if (!$asakai->canBeEditedBy(auth()->user())) {
        //     session()->flash('error', 'Anda tidak memiliki izin untuk menghapus data ini.');
        //     return;
        // }

        $asakai->delete();
        session()->flash('success', 'Data Asakai berhasil dihapus!');
    }

    public function changeStatus($id, $status)
    {
        $asakai = Asakai::findOrFail($id);

        if ($status === 'closed') {
            $asakai->markAsClosed();
        } else {
            $asakai->update(['status' => $status]);
        }

        session()->flash('success', 'Status berhasil diubah!');
    }

    public function render()
    {
        $asakais = Asakai::with(['pics', 'creator'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('part_no', 'like', '%' . $this->search . '%')
                        ->orWhere('issue', 'like', '%' . $this->search . '%')
                        ->orWhere('customer', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->customerFilter, fn ($q) => $q->where('customer', 'like', '%' . $this->customerFilter . '%'))
            ->when($this->shiftFilter, fn ($q) => $q->where('lot_shift', $this->shiftFilter))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('date_issue', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('date_issue', '<=', $this->dateTo))
            ->latest('date_issue')
            ->paginate($this->perPage);

        // Get unique customers for filter dropdown
        $customers = Asakai::distinct()->pluck('customer')->sort();

        return view('livewire.asakai.asakai-list', [
            'asakais' => $asakais,
            'customers' => $customers,
        ]);
    }
}
