<?php

namespace App\Livewire\Report;

use App\Models\DailyItemCode;
use App\Models\HourlyRemark;
use App\Models\ProductionNgDetail;
use App\Models\ProductionNgType;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class MachineDailyReport extends Component
{
    public $selectedDate;

    public $selectedMachineId;

    public $selectedItemCode = '';

    public bool $isPpicView = false;

    public bool $showChecklistModal = false;

    public $checklistHeader = null;

    public $checklistItems = [];

    public bool $showLotModal = false;

    public $editingPlanId = null;

    public $editingMaterialLot = '';

    public bool $showAccessoryModal = false;

    public $selectedPlanIdForAcc = null;

    public $newAccName = '';

    public $newAccLot = '';

    public function openLotModal($planId)
    {
        $plan = DailyItemCode::find($planId);
        if ($plan) {
            $this->editingPlanId = $plan->id;
            $this->editingMaterialLot = $plan->material_lot ?? '';
            $this->showLotModal = true;
        }
    }

    public function closeLotModal()
    {
        $this->showLotModal = false;
        $this->editingPlanId = null;
        $this->editingMaterialLot = '';
    }

    public function saveMaterialLot()
    {
        if ($this->editingPlanId) {
            $plan = DailyItemCode::find($this->editingPlanId);
            if ($plan) {
                $plan->material_lot = $this->editingMaterialLot ? strtoupper(trim($this->editingMaterialLot)) : null;
                $plan->save();
            }
        }

        $this->closeLotModal();
    }

    public function openAccessoryModal($planId)
    {
        $this->selectedPlanIdForAcc = $planId;
        $this->newAccName = '';
        $this->newAccLot = '';
        $this->showAccessoryModal = true;
    }

    public function closeAccessoryModal()
    {
        $this->showAccessoryModal = false;
        $this->selectedPlanIdForAcc = null;
        $this->newAccName = '';
        $this->newAccLot = '';
    }

    public function addAccessoryLot()
    {
        $this->validate([
            'newAccName' => 'required|string|max:255',
            'newAccLot' => 'required|string|max:255',
        ]);

        if ($this->selectedPlanIdForAcc) {
            \App\Models\DicAccessoryLot::create([
                'dic_id' => $this->selectedPlanIdForAcc,
                'accessory_name' => trim($this->newAccName),
                'accessory_lot' => trim($this->newAccLot),
            ]);
        }

        $this->newAccName = '';
        $this->newAccLot = '';
    }

    public function deleteAccessoryLot($accId)
    {
        $acc = \App\Models\DicAccessoryLot::find($accId);
        if ($acc) {
            $acc->delete();
        }
    }

    // Query string support for easy bookmarking and state preservation
    protected $queryString = [
        'selectedDate' => ['except' => ''],
        'selectedMachineId' => ['except' => ''],
        'selectedItemCode' => ['except' => ''],
    ];

    public function openChecklistModal()
    {
        if (! $this->selectedMachineId) {
            return;
        }

        $this->checklistHeader = \App\Models\MaintenanceCheckHeader::where('machine_id', $this->selectedMachineId)
            ->where('date', $this->selectedDate)
            ->with(['machine', 'details.item'])
            ->first();

        $this->checklistItems = \App\Models\MaintenanceCheckItem::orderBy('sort_order')->get();
        $this->showChecklistModal = true;
    }

    public function closeChecklistModal()
    {
        $this->showChecklistModal = false;
        $this->checklistHeader = null;
    }

    public static $shiftSlots = [
        1 => [
            1 => ['start' => '07:30:00', 'end' => '08:30:00'],
            2 => ['start' => '08:30:00', 'end' => '09:30:00'],
            3 => ['start' => '09:30:00', 'end' => '10:30:00'],
            4 => ['start' => '10:30:00', 'end' => '11:30:00'],
            5 => ['start' => '11:30:00', 'end' => '12:30:00'],
            6 => ['start' => '12:30:00', 'end' => '13:30:00'],
            7 => ['start' => '13:30:00', 'end' => '14:30:00'],
            8 => ['start' => '14:30:00', 'end' => '15:30:00'],
        ],
        2 => [
            1 => ['start' => '15:30:00', 'end' => '16:30:00'],
            2 => ['start' => '16:30:00', 'end' => '17:30:00'],
            3 => ['start' => '17:30:00', 'end' => '18:30:00'],
            4 => ['start' => '18:30:00', 'end' => '19:30:00'],
            5 => ['start' => '19:30:00', 'end' => '20:30:00'],
            6 => ['start' => '20:30:00', 'end' => '21:30:00'],
            7 => ['start' => '21:30:00', 'end' => '22:30:00'],
            8 => ['start' => '22:30:00', 'end' => '23:30:00'],
        ],
        3 => [
            1 => ['start' => '23:30:00', 'end' => '00:30:00'],
            2 => ['start' => '00:30:00', 'end' => '01:30:00'],
            3 => ['start' => '01:30:00', 'end' => '02:30:00'],
            4 => ['start' => '02:30:00', 'end' => '03:30:00'],
            5 => ['start' => '03:30:00', 'end' => '04:30:00'],
            6 => ['start' => '04:30:00', 'end' => '05:30:00'],
            7 => ['start' => '05:30:00', 'end' => '06:30:00'],
            8 => ['start' => '06:30:00', 'end' => '07:30:00'],
        ],
    ];

    public function mount()
    {
        $user = auth()->user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        // Determine mode based on route name
        $this->isPpicView = request()->routeIs('ppic.machine-daily-report');

        if ($this->isPpicView) {
            // Access control for PPIC / Admin view
            if (! $user->hasRole('PPIC') && ! $user->hasRole('ADMIN') && ! $user->hasRole('SUPER-ADMIN')) {
                abort(403, 'Unauthorized. Access is restricted to PPIC and Administrators.');
            }

            // Set default selected date if empty
            if (! $this->selectedDate) {
                $this->selectedDate = Carbon::today('Asia/Jakarta')->format('Y-m-d');
            }

            // Load default machine if empty
            if (! $this->selectedMachineId) {
                $firstMachine = User::whereHas('role', function ($q) {
                    $q->where('name', 'OPERATOR');
                })->orderBy('name')->first();
                $this->selectedMachineId = $firstMachine?->id;
            }
        } else {
            // Access control for Operator view
            if (! $user->hasRole('OPERATOR') && ! $user->hasRole('ADMIN') && ! $user->hasRole('SUPER-ADMIN')) {
                abort(403, 'Unauthorized. Access is restricted to Operators.');
            }

            // Operators are locked to their own user/machine record
            $this->selectedMachineId = $user->id;

            if (! $this->selectedDate) {
                $this->selectedDate = Carbon::today('Asia/Jakarta')->format('Y-m-d');
            }
        }
    }

    public function render()
    {
        // 1. Get Machines list (only needed for dropdown in PPIC view)
        $machines = $this->isPpicView
            ? User::whereHas('role', fn ($q) => $q->where('name', 'OPERATOR'))->orderBy('name')->get()
            : collect();

        $selectedMachine = User::find($this->selectedMachineId);

        // 2. Fetch daily plans (DailyItemCodes) for that date and machine
        $dailyPlans = collect();
        $dicIds = collect();
        if ($this->selectedMachineId) {
            $dailyPlans = DailyItemCode::where('user_id', $this->selectedMachineId)
                ->whereDate('schedule_date', $this->selectedDate)
                ->whereHas('hourlyRemarks')
                ->with(['masterItem.customer', 'scannedData', 'accessoryLots'])
                ->orderBy('shift', 'asc')
                ->get();

            $dicIds = $dailyPlans->pluck('id');
        }

        // Available distinct item codes for dropdown filter
        $availableItems = $dailyPlans->mapWithKeys(function ($plan) {
            $name = optional($plan->masterItem)->item_name ?? optional($plan->masterItem)->part_name ?? $plan->item_code;

            return [$plan->item_code => $name];
        })->all();

        // 3. Fetch hourly remarks with NG Details
        $hourlyRemarks = $dicIds->isNotEmpty()
            ? HourlyRemark::whereIn('dic_id', $dicIds)
                ->with(['dailyItemCode.masterItem', 'ngDetails.ngType'])
                ->get()
            : collect();

        // 4. Map Hourly Output per Shift (I, II, III) matching paper form layout
        $hourlyOutput = [1 => [], 2 => [], 3 => []];
        foreach ([1, 2, 3] as $shift) {
            foreach (self::$shiftSlots[$shift] as $hourIdx => $slot) {
                // Find matching remark in DB
                $remark = $hourlyRemarks->first(function ($r) use ($shift, $slot) {
                    return $r->dailyItemCode->shift == $shift
                        && $r->start_time == $slot['start']
                        && $r->end_time == $slot['end'];
                });

                $hourlyOutput[$shift][$hourIdx] = [
                    'time' => substr($slot['start'], 0, 5) . ' - ' . substr($slot['end'], 0, 5),
                    'target' => $remark?->target ?? 0,
                    'actual' => $remark?->actual_production ?? 0,
                    'ng' => $remark?->NG ?? 0,
                    'pic' => $remark?->pic ?? '-',
                    'remark' => $remark?->remark ?? '',
                ];
            }
        }

        // 5. Build Item Detail Rows (Shift, Time Slots, Target, Actual, NG & Detailed NG Types)
        $itemDetailRows = [];
        foreach ($dailyPlans as $plan) {
            if ($this->selectedItemCode && $plan->item_code !== $this->selectedItemCode) {
                continue;
            }

            $itemMaster = $plan->masterItem;
            $partName = $itemMaster?->part_name ?? $itemMaster?->item_name ?? '-';
            $custName = $itemMaster?->customer?->customer_name ?? $itemMaster?->customer_code ?? '-';

            $remarks = $hourlyRemarks->where('dic_id', $plan->id)->sortBy('start_time');

            $slots = [];
            foreach ($remarks as $r) {
                $ngList = [];
                foreach ($r->ngDetails as $ng) {
                    if ($ng->ng_quantity > 0) {
                        $ngList[] = [
                            'type' => $ng->ngType?->ng_type ?? 'NG',
                            'qty' => $ng->ng_quantity,
                            'remarks' => $ng->ng_remarks,
                        ];
                    }
                }

                $slots[] = [
                    'time' => substr($r->start_time, 0, 5) . ' - ' . substr($r->end_time, 0, 5),
                    'target' => $r->target ?? 0,
                    'actual' => $r->actual_production ?? 0,
                    'ng' => $r->NG ?? 0,
                    'ng_list' => $ngList,
                    'pic' => $r->pics_string ?: ($r->pic ?? '-'),
                    'remark' => $r->remark ?? '',
                ];
            }

            $itemDetailRows[] = [
                'plan_id' => $plan->id,
                'shift' => $plan->shift,
                'item_code' => $plan->item_code,
                'part_name' => $partName,
                'customer' => $custName,
                'slots' => $slots,
            ];
        }

        // 6. Operator names per shift (derived from unique HourlyRemark PICs: pic, pic_2, pic_3)
        $operatorNames = [1 => '-', 2 => '-', 3 => '-'];
        foreach ([1, 2, 3] as $shift) {
            $shiftRemarks = $hourlyRemarks->filter(fn ($r) => $r->dailyItemCode->shift == $shift);
            $pics = collect();
            foreach ($shiftRemarks as $r) {
                if (! empty($r->pic) && $r->pic !== '-') {
                    $pics->push($r->pic);
                }
                if (! empty($r->pic_2) && $r->pic_2 !== '-') {
                    $pics->push($r->pic_2);
                }
                if (! empty($r->pic_3) && $r->pic_3 !== '-') {
                    $pics->push($r->pic_3);
                }
            }
            $pics = $pics->unique();
            if ($pics->isNotEmpty()) {
                $operatorNames[$shift] = $pics->join(', ');
            }
        }

        // 7. Plastic Part Defective Analysis Matrix
        $ngTypes = ProductionNgType::all();
        $defectMatrix = [1 => [], 2 => [], 3 => []];
        foreach ([1, 2, 3] as $shift) {
            foreach ($ngTypes as $type) {
                $defectMatrix[$shift][$type->id] = 0;
            }
        }

        if ($dicIds->isNotEmpty()) {
            $ngDetails = ProductionNgDetail::whereIn('hourly_remark_id', $hourlyRemarks->pluck('id'))
                ->with('hourlyRemark.dailyItemCode')
                ->get();

            foreach ($ngDetails as $detail) {
                $shift = (int) ($detail->hourlyRemark->dailyItemCode->shift ?? 1);
                if (isset($defectMatrix[$shift][$detail->ng_type_id])) {
                    $defectMatrix[$shift][$detail->ng_type_id] += $detail->ng_quantity;
                }
            }
        }

        // 8. Calculate aggregate actual metrics for OK and NG
        $filteredPlans = $this->selectedItemCode ? $dailyPlans->where('item_code', $this->selectedItemCode) : $dailyPlans;
        $filteredRemarks = $this->selectedItemCode ? $hourlyRemarks->filter(fn ($r) => $r->dailyItemCode?->item_code === $this->selectedItemCode) : $hourlyRemarks;

        $totals = [
            'planned_target' => $filteredPlans->sum('quantity'),
            'actual_ok' => $filteredRemarks->sum('actual_production'),
            'actual_ng' => $filteredRemarks->sum('NG'),
        ];
        $totals['total_produced'] = $totals['actual_ok'] + $totals['actual_ng'];

        return view('livewire.report.machine-daily-report', [
            'machines' => $machines,
            'selectedMachine' => $selectedMachine,
            'dailyPlans' => $dailyPlans,
            'availableItems' => $availableItems,
            'itemDetailRows' => $itemDetailRows,
            'hourlyOutput' => $hourlyOutput,
            'operatorNames' => $operatorNames,
            'ngTypes' => $ngTypes,
            'defectMatrix' => $defectMatrix,
            'totals' => $totals,
        ]);
    }
}
