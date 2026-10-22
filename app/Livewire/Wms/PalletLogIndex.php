<?php

namespace App\Livewire\Wms;

use App\Models\WmsPalletLog;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class PalletLogIndex extends Component
{
    use WithPagination;

    public $search = '';

    // Stats
    public $totalInToday = 0;

    public $totalOutToday = 0;

    protected $queryString = ['search'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function mount()
    {
        $this->calculateStats();
    }

    public function calculateStats()
    {
        $this->totalInToday = WmsPalletLog::where('transaction_type', 'IN')
            ->whereDate('created_at', Carbon::today())
            ->count();

        $this->totalOutToday = WmsPalletLog::where('transaction_type', 'OUT')
            ->whereDate('created_at', Carbon::today())
            ->count();
    }

    public function render()
    {
        $logs = WmsPalletLog::with(['position', 'user', 'pallet.details.item.customer'])
            ->when($this->search, function ($query) {
                $query->where('pallet_id', 'like', '%' . $this->search . '%')
                    ->orWhere('notes', 'like', '%' . $this->search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('livewire.wms.pallet-log-index', [
            'logs' => $logs,
        ]);
    }
}
