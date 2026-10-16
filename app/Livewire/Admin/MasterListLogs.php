<?php

namespace App\Livewire\Admin;

use App\Models\MasterItemLog;
use Livewire\Component;
use Livewire\WithPagination;

class MasterListLogs extends Component
{
    use WithPagination;

    public $search = '';

    protected $paginationTheme = 'tailwind';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $logs = MasterItemLog::with('user')
            ->where(function ($query) {
                $query->where('item_code', 'like', '%' . $this->search . '%')
                    ->orWhere('action', 'like', '%' . $this->search . '%')
                    ->orWhereHas('user', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%');
                    });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('livewire.admin.master-list-logs', [
            'logs' => $logs,
        ]);
    }
}
