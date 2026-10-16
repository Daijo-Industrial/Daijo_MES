<?php

namespace App\Livewire\Report;

use App\Models\ApiLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class ApiLogDashboard extends Component
{
    use WithPagination;

    public $search = '';

    public $apiNameFilter = '';

    public $statusFilter = '';

    public $perPage = 20;

    protected $updatesQueryString = ['search', 'apiNameFilter', 'statusFilter'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingApiNameFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'apiNameFilter', 'statusFilter']);
        $this->resetPage();
    }

    public function render()
    {
        // Gunakan simplePaginate biar nggak usah COUNT(*) yang berat di tabel gede
        $query = ApiLog::query()
            ->latest()
            ->where('created_at', '>=', Carbon::now()->subDays(30)); // Batasi 30 hari terakhir biar enteng

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('endpoint', 'like', '%' . $this->search . '%')
                    ->orWhere('message', 'like', '%' . $this->search . '%')
                    ->orWhere('api_name', 'like', '%' . $this->search . '%')
                    ->orWhere('request_payload', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->apiNameFilter) {
            $query->where('api_name', $this->apiNameFilter);
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        $logs = $query->simplePaginate($this->perPage);

        // Cache daftar nama API selama 1 jam biar nggak DISTINCT terus tiap refresh
        $apiNames = Cache::remember('api_log_names', 3600, function () {
            return ApiLog::where('created_at', '>=', Carbon::now()->subDays(60))
                ->select('api_name')
                ->distinct()
                ->whereNotNull('api_name')
                ->pluck('api_name');
        });

        return view('livewire.report.api-log-dashboard', [
            'logs' => $logs,
            'apiNames' => $apiNames,
        ])->layout('layouts.dashboard');
    }
}
