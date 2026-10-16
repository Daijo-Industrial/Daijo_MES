<?php

namespace App\Exports;

use App\Models\DailyItemCode;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PpicDailyProductionExport implements FromView, ShouldAutoSize, WithStyles, WithTitle
{
    protected string $date;

    protected ?int $machineId;

    public function __construct(string $date, ?int $machineId = null)
    {
        $this->date = $date;
        $this->machineId = $machineId;
    }

    public function title(): string
    {
        $formattedDate = Carbon::parse($this->date)->format('d-M-Y');

        return "Prod_Daily_{$formattedDate}";
    }

    public function view(): View
    {
        $query = DailyItemCode::whereDate('schedule_date', $this->date)
            ->whereHas('hourlyRemarks')
            ->with(['user', 'masterItem.customer', 'hourlyRemarks', 'scannedData']);

        if ($this->machineId) {
            $query->where('user_id', $this->machineId);
        }

        $dailyPlans = $query->get()->sortBy(function ($plan) {
            return $plan->user?->name ?? 'ZZZ';
        });

        // Process data grouped by Machine & Part No
        $reportData = [];
        $no = 1;

        foreach ($dailyPlans as $plan) {
            $machineCode = $plan->user?->name ?? 'MC-UNK';
            $masterItem = $plan->masterItem;
            $customer = $masterItem?->customer?->customer_name ?? $masterItem?->customer_code ?? '-';
            $partNo = $masterItem?->part_no ?? $plan->item_code;
            $partName = $masterItem?->part_name ?? $masterItem?->item_name ?? '-';

            // Extract Machine Tonage (MCT) e.g. "110A" -> "110T", "150C" -> "150T", "300A" -> "300T"
            preg_match('/(\d+)/', $machineCode, $matches);
            $mct = ! empty($matches[1]) ? $matches[1] . 'T' : '100T';

            // Find distinct item_codes from ProductionScannedData for this DIC
            $scannedItemCodes = \App\Models\ProductionScannedData::where('dic_id', $plan->id)
                ->whereNotNull('item_code')
                ->where('item_code', '!=', '')
                ->select('item_code')
                ->distinct()
                ->pluck('item_code')
                ->toArray();

            // Ensure plan's main item_code is included as the primary item
            $distinctItemCodes = array_values(array_unique(array_filter(array_merge([$plan->item_code], $scannedItemCodes))));
            $isPair = count($distinctItemCodes) > 1;

            $primaryNo = $no++;

            foreach ($distinctItemCodes as $index => $itemCode) {
                $isPrimary = ($index === 0);

                // Lookup item details from MasterListItem or fallback
                $itemMaster = \App\Models\MasterListItem::where('item_code', $itemCode)
                    ->with('customer')
                    ->first() ?? ($isPrimary ? $masterItem : null);

                $custName = $itemMaster?->customer?->customer_name ?? $itemMaster?->customer_code ?? ($isPrimary ? $customer : '-');
                $itemPartNo = $itemMaster?->part_no ?? $itemCode;
                $itemPartName = $itemMaster?->part_name ?? $itemMaster?->item_name ?? ($isPrimary ? $partName : '-');

                // Extract Cycle Time for this item
                $itemCycleTimeSec = $isPrimary
                    ? ((! empty($plan->temporal_cycle_time) && $plan->temporal_cycle_time > 0)
                        ? (float) $plan->temporal_cycle_time
                        : (! empty($itemMaster?->cycle_time) && $itemMaster->cycle_time > 0 ? (float) $itemMaster->cycle_time : null))
                    : (! empty($itemMaster?->cycle_time) && $itemMaster->cycle_time > 0 ? (float) $itemMaster->cycle_time : null);

                // Target per hour
                if ($itemCycleTimeSec && $itemCycleTimeSec > 0) {
                    $itemTargetPerHour = (int) round(3600 / $itemCycleTimeSec);
                } else {
                    $avgTargetRemark = (int) $plan->hourlyRemarks->avg('target');
                    $itemTargetPerHour = $avgTargetRemark > 0 ? $avgTargetRemark : 80;
                }

                // Determine Shift 1, 2, 3 actual quantities based on DIC's assigned shift ($plan->shift)
                $planShift = (int) ($plan->shift ?? 1);
                // Determine actual production quantity (Actual Qty) for this DIC
                $hourlySum = (int) $plan->hourlyRemarks->sum('actual_production');
                if ($hourlySum > 0) {
                    $dicActualTotal = $hourlySum;
                } elseif (! empty($plan->actual_quantity) && $plan->actual_quantity > 0) {
                    $dicActualTotal = (int) $plan->actual_quantity;
                } else {
                    $dicActualTotal = 0;
                }

                // If DIC has pair items (multiple distinct item codes), divide Actual Qty by number of pair items
                $pairCount = count($distinctItemCodes);
                if ($isPair && $pairCount > 1) {
                    $itemQty = (int) round($dicActualTotal / $pairCount);
                } else {
                    $itemQty = $dicActualTotal;
                }

                $shift1Qty = ($planShift === 1) ? $itemQty : 0;
                $shift2Qty = ($planShift === 2) ? $itemQty : 0;
                $shift3Qty = ($planShift === 3) ? $itemQty : 0;

                $totalShift = $shift1Qty + $shift2Qty + $shift3Qty;

                // Time calculation:
                // Primary item holds planned, actual, and downtime.
                // Pair item MUST BE 0 as requested by user ("untuk planned actual time dan downtime isi 0 karena ini pair")
                if ($isPrimary) {
                    $filledSlotsCount = $plan->hourlyRemarks->count();
                    $rowPlanHours = $filledSlotsCount > 0 ? $filledSlotsCount : 24;

                    $rowActualHours = 0.0;
                    if ($itemTargetPerHour > 0 && $totalShift > 0) {
                        $rowActualHours = round($totalShift / $itemTargetPerHour, 2);
                    } else {
                        $rowActualHours = (float) $plan->hourlyRemarks->filter(fn ($r) => $r->actual_production > 0)->count();
                    }

                    $rowDowntimeHours = round(max(0, $rowPlanHours - $rowActualHours), 2);
                } else {
                    $rowPlanHours = 0;
                    $rowActualHours = 0;
                    $rowDowntimeHours = 0;
                }

                $reportData[] = [
                    'no' => $isPrimary ? $primaryNo : '',
                    'date' => Carbon::parse($plan->schedule_date)->format('d-M-y'),
                    'customer' => strtoupper($custName),
                    'mc' => $machineCode,
                    'part_no' => $itemPartNo,
                    'part_name' => $itemPartName,
                    'shift_1' => $shift1Qty,
                    'shift_2' => $shift2Qty,
                    'shift_3' => $shift3Qty,
                    'total_shift' => $totalShift,
                    'mct' => $mct,
                    'cycle_time' => $itemCycleTimeSec ? round($itemCycleTimeSec, 1) : '-',
                    'target_h' => $itemTargetPerHour,
                    'plan' => $rowPlanHours,
                    'actual' => $rowActualHours,
                    'downtime' => $rowDowntimeHours,
                    'is_pair_sub' => ! $isPrimary,
                ];
            }
        }

        return view('exports.ppic_daily_production', [
            'date' => Carbon::parse($this->date)->format('d-M-Y'),
            'reportData' => $reportData,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style table header fonts
            1 => ['font' => ['bold' => true, 'size' => 12]],
            2 => ['font' => ['bold' => true, 'size' => 10]],
            3 => ['font' => ['bold' => true, 'size' => 10]],
        ];
    }
}
