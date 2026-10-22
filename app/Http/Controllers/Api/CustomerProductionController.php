<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyItemCode;
use Illuminate\Http\Request;

class CustomerProductionController extends Controller
{
    public function range(Request $request)
    {
        // ── Token Authentication ──
        $expectedToken = config('services.customer_api.token');
        $token = $request->bearerToken() ?? $request->header('X-API-TOKEN');

        if (empty($expectedToken) || $token !== $expectedToken) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid or missing token',
            ], 401);
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        // Optimized query: Eager load sums directly from DB using subqueries instead of hydrating thousands of HourlyRemark records
        $dailyItems = DailyItemCode::query()
            ->whereBetween('schedule_date', [
                $request->start_date,
                $request->end_date,
            ])
            ->whereHas('hourlyRemarks')
            ->withSum('hourlyRemarks as actual_quantity', 'actual_production')
            ->withSum('hourlyRemarks as total_ng', 'NG')
            ->with([
                'user:id,name',
            ])
            ->get();

        $data = $dailyItems
            ->groupBy('item_code')
            ->map(function ($items, $itemCode) {
                $totalShots = (int) $items->sum(function ($item) {
                    return (int) $item->actual_quantity + (int) $item->total_ng;
                });
                $totalNg = (int) $items->sum(function ($item) {
                    return (int) $item->total_ng;
                });

                return [
                    'item_code' => $itemCode,
                    'planned_quantity' => (int) $items->sum('quantity'),
                    'total_shots' => $totalShots,
                    'total_ng' => $totalNg,
                    'machines' => $items
                        ->map(function ($item) {
                            $actualQty = (int) $item->actual_quantity;
                            $ngQty = (int) $item->total_ng;

                            return [
                                'daily_item_code_id' => $item->id,
                                'machine_name' => $item->user->name ?? '-',
                                'shift' => $item->shift,
                                'schedule_date' => $item->schedule_date,
                                'planned_quantity' => $item->quantity,
                                'actual_quantity' => $actualQty,
                                'final_quantity' => $actualQty + $ngQty,
                                'start_time' => $item->start_time,
                                'end_time' => $item->end_time,
                                'remark' => $item->remark,
                                'total_shots' => $actualQty + $ngQty,
                                'total_ng' => $ngQty,
                            ];
                        })
                        ->values(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'data' => $data,
        ]);
    }
}
