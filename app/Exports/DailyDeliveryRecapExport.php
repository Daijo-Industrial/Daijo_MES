<?php

namespace App\Exports;

use App\Exports\Sheets\DailyDeliveryBoxDetailSheet;
use App\Exports\Sheets\DailyDeliveryItemSummarySheet;
use App\Services\WmsDeliveryRecapService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DailyDeliveryRecapExport implements WithMultipleSheets
{
    protected string $selectedDate;

    protected ?string $shiftFilter;

    protected ?string $deliveryFilter;

    protected ?string $search;

    public function __construct(
        string $selectedDate,
        ?string $shiftFilter = null,
        ?string $deliveryFilter = null,
        ?string $search = null
    ) {
        $this->selectedDate = $selectedDate;
        $this->shiftFilter = $shiftFilter;
        $this->deliveryFilter = $deliveryFilter;
        $this->search = $search;
    }

    public function sheets(): array
    {
        $service = app(WmsDeliveryRecapService::class);
        $recap = $service->getDailyRecap(
            $this->selectedDate,
            $this->shiftFilter,
            $this->deliveryFilter,
            $this->search
        );

        return [
            new DailyDeliveryItemSummarySheet($recap['items'], $recap['time_window_label']),
            new DailyDeliveryBoxDetailSheet($recap['box_logs']),
        ];
    }
}
