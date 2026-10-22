<?php

namespace App\Livewire\Report;

use App\Models\Asakai;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class DailyReport extends Component
{
    public $date;

    public $customerFilter = '';

    public $partFilter = '';

    public $shiftFilter = '';

    public function mount()
    {
        $this->date = Carbon::today()->format('Y-m-d');
    }

    public function export($type = 'excel')
    {
        return redirect()->route('report.daily.export', [
            'date' => $this->date,
            'customer' => $this->customerFilter,
            'part' => $this->partFilter,
            'shift' => $this->shiftFilter,
            'type' => $type,
        ]);
    }

    public function resetFilters()
    {
        $this->reset(['customerFilter', 'partFilter', 'shiftFilter']);
    }

    public function render()
    {
        $asakais = Asakai::with(['pics', 'rcas', 'correctiveActions', 'creator'])
            ->daily($this->date)
            ->when($this->customerFilter, fn ($q) => $q->where('customer', 'like', "%{$this->customerFilter}%"))
            ->when($this->partFilter, fn ($q) => $q->where('part_no', 'like', "%{$this->partFilter}%"))
            ->when($this->shiftFilter, fn ($q) => $q->where('lot_shift', $this->shiftFilter))
            ->get();

        $customers = Asakai::distinct()->pluck('customer')->sort();

        return view('livewire.report.daily-report', [
            'asakais' => $asakais,
            'customers' => $customers,
            'total' => $asakais->count(),
            'totalQuantity' => $asakais->sum('quantity'),
            'byShift' => $asakais->groupBy('lot_shift'),
            'byCustomer' => $asakais->groupBy('customer'),
        ]);
    }
}
