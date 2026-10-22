<?php

namespace App\Livewire\Wms;

use App\Exports\DailyDeliveryRecapExport;
use App\Services\WmsDeliveryRecapService;
use Carbon\Carbon;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class DailyDeliveryRecap extends Component
{
    public string $selectedDate = '';

    public string $shiftFilter = 'ALL';

    public string $deliveryFilter = 'ALL';

    public string $search = '';

    public string $activeTab = 'items'; // 'items' | 'pallets' | 'box_logs'

    protected $queryString = [
        'selectedDate' => ['except' => ''],
        'shiftFilter' => ['except' => 'ALL'],
        'deliveryFilter' => ['except' => 'ALL'],
        'search' => ['except' => ''],
        'activeTab' => ['except' => 'items'],
    ];

    public function mount(): void
    {
        if (empty($this->selectedDate)) {
            $this->selectedDate = WmsDeliveryRecapService::getCurrentProductionDate();
        }
    }

    public function setYesterday(): void
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->subDay()->format('Y-m-d');
    }

    public function setToday(): void
    {
        $this->selectedDate = WmsDeliveryRecapService::getCurrentProductionDate();
    }

    public function setTomorrow(): void
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->addDay()->format('Y-m-d');
    }

    public function exportExcel()
    {
        $cleanDate = Carbon::parse($this->selectedDate)->format('Ymd');
        $fileName = "Rekap_Delivery_{$cleanDate}.xlsx";

        return Excel::download(
            new DailyDeliveryRecapExport(
                $this->selectedDate,
                $this->shiftFilter,
                $this->deliveryFilter,
                $this->search
            ),
            $fileName
        );
    }

    public function render(WmsDeliveryRecapService $recapService)
    {
        $recap = $recapService->getDailyRecap(
            $this->selectedDate,
            $this->shiftFilter,
            $this->deliveryFilter,
            $this->search
        );

        return view('livewire.wms.daily-delivery-recap', [
            'recap' => $recap,
        ]);
    }
}
