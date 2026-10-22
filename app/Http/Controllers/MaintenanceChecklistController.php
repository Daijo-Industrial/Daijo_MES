<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceCheckDetail;
use App\Models\MaintenanceCheckHeader;
use App\Models\MaintenanceCheckItem;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MaintenanceChecklistController extends Controller
{
    /**
     * Get active production date based on 07:30 AM daily reset.
     */
    private function getActiveProductionDate(): string
    {
        return Carbon::now('Asia/Jakarta')
            ->subHours(7)
            ->subMinutes(30)
            ->format('Y-m-d');
    }

    /**
     * Get today's or selected date's checklist data for a specific machine.
     */
    public function getTodayData(Request $request, $machineId)
    {
        $prodDate = $request->query('date') ?: $this->getActiveProductionDate();

        $items = MaintenanceCheckItem::orderBy('sort_order')->get();
        $header = MaintenanceCheckHeader::where('machine_id', $machineId)
            ->where('date', $prodDate)
            ->with('details')
            ->first();

        return response()->json([
            'success' => true,
            'production_date' => $prodDate,
            'display_date' => Carbon::parse($prodDate)->format('d-m-Y'),
            'is_filled' => $header !== null,
            'header' => $header,
            'items' => $items,
        ]);
    }

    /**
     * Store or update checklist submission.
     */
    public function store(Request $request)
    {
        $request->validate([
            'machine_id' => 'required|exists:users,id',
            'prepared_by' => 'required|string|max:100',
            'approved_by' => 'nullable|string|max:100',
            'check_time' => 'nullable|string',
            'items' => 'required|array',
        ]);

        $prodDate = $this->getActiveProductionDate();
        $now = Carbon::now('Asia/Jakarta');

        // Automatically transform PIC & Approved By to UPPERCASE (CAPS LOCK)
        $preparedBy = mb_strtoupper(trim($request->prepared_by), 'UTF-8');
        $approvedBy = $request->approved_by ? mb_strtoupper(trim($request->approved_by), 'UTF-8') : null;
        $checkTime = $request->check_time ?: $now->format('H:i');

        $header = MaintenanceCheckHeader::updateOrCreate(
            [
                'machine_id' => $request->machine_id,
                'date' => $prodDate,
            ],
            [
                'check_time' => $checkTime,
                'prepared_by' => $preparedBy,
                'approved_by' => $approvedBy,
                'status' => 'COMPLETED',
            ]
        );

        foreach ($request->items as $itemId => $itemInput) {
            $value = is_array($itemInput) ? ($itemInput['value'] ?? 'OK') : (string) $itemInput;
            $remarks = is_array($itemInput) ? ($itemInput['remarks'] ?? null) : null;

            $checkItem = MaintenanceCheckItem::find($itemId);
            if (! $checkItem) {
                continue;
            }

            $isNormal = true;
            $valTrim = trim($value);
            if ($checkItem->input_type === 'ok_ng') {
                if ($valTrim === '-') {
                    $isNormal = true; // Lewati / Skip is NORMAL
                } else {
                    $isNormal = (strtoupper($valTrim) === 'OK');
                }
            } elseif ($checkItem->input_type === 'numeric') {
                if (is_numeric($valTrim)) {
                    $num = (float) $valTrim;
                    if (str_contains(strtolower($checkItem->item_name), 'temp')) {
                        $isNormal = ($num < 60);
                    } elseif (str_contains(strtolower($checkItem->item_name), 'pump')) {
                        $isNormal = ($num >= 100 && $num <= 200);
                    }
                } else {
                    $isNormal = false;
                }
            }

            MaintenanceCheckDetail::updateOrCreate(
                [
                    'header_id' => $header->id,
                    'item_id' => $itemId,
                ],
                [
                    'value' => $value,
                    'is_normal' => $isNormal,
                    'remarks' => $remarks,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Checklist Maintenance berhasil disimpan!',
            'header' => $header->load('details'),
        ]);
    }
}
