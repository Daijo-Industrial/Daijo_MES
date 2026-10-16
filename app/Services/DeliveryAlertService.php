<?php

namespace App\Services;

use App\Models\Delivery\DelschedFinal;
use App\Models\Delivery\DelschedFinalWip;
use Carbon\Carbon;

class DeliveryAlertService
{
    public function getNextThreeDays()
    {
        $dates = [
            Carbon::now()->addDay()->toDateString(),
            Carbon::now()->addDays(2)->toDateString(),
            Carbon::now()->addDays(3)->toDateString(),
        ];

        $wip = DelschedFinalWip::whereIn('delivery_date', $dates)
            ->where('status', '!=', 'SUCCESS')
            ->orderBy('delivery_date')
            ->orderBy('customer_name')
            ->orderBy('wip_code')
            ->get()
            ->groupBy('delivery_date');

        $final = DelschedFinal::whereIn('delivery_date', $dates)
            ->where('status', '!=', 'SUCCESS')
            ->orderBy('delivery_date')
            ->orderBy('customer_name')
            ->orderBy('item_code')
            ->get()
            ->groupBy('delivery_date');

        return (object) [
            'wip' => $wip,
            'final' => $final,
        ];
    }
}
