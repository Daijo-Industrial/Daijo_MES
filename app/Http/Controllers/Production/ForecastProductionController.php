<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Production\SapForecast;
use Illuminate\Http\Request;

class ForecastProductionController extends Controller
{
    private function processMonthlyQuantities($items)
    {
        // Initialize an array to store monthly quantities
        $monthlyQuantities = array_fill(1, 12, 0); // 1 to 12 months, default quantity 0

        foreach ($items as $item) {
            // Get the month number from the forecast_date
            $month = \Carbon\Carbon::parse($item->forecast_date)->month;

            // Add the quantity to the corresponding month
            $monthlyQuantities[$month] += $item->quantity;
        }

        return $monthlyQuantities;
    }

    public function index(Request $request)
    {
        // Retrieve the filter input, if any, otherwise set it as null
        $forecastNameFilter = $request->get('forecast_name', '');

        // Retrieve the data, and filter by forecast name if set
        $processedData = SapForecast::when($forecastNameFilter, function ($query) use ($forecastNameFilter) {
            return $query->where('forecast_name', $forecastNameFilter);
        })
            ->get()
            ->groupBy('forecast_name')
            ->map(function ($group) {
                return $group->groupBy('item_no')->map(function ($items) {
                    return [
                        'item_no' => $items->first()->item_no,
                        'monthly_quantities' => $this->processMonthlyQuantities($items),
                    ];
                });
            });

        // Get all available forecast names for the filter dropdown
        $forecastNames = SapForecast::distinct('forecast_name')->pluck('forecast_name');

        return view('production.forecast_production', compact('processedData', 'forecastNames', 'forecastNameFilter'));
    }
}
