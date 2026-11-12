<?php

namespace App\Livewire\Inspection\InspectionForm;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class StepDetail extends Component
{
    public $inspection_report_document_number;

    public $document_number;

    public int $period = 1;

    public $start_datetime;

    public $end_datetime;

    public $start_time;

    public $end_time;

    public $operator;

    public $inspector;

    public $shift;

    public $part_name;

    public $part_number;

    public string $periodKey = 'p1';

    public int $reloadToken = 0;

    public bool $savedDetails = false;

    public bool $savedFirst = false;

    public bool $savedDimensions = false;

    public bool $savedSecond = false;

    public bool $savedJudgement = false;

    public array $unlockedPeriods = [];

    protected $rules = [
        'inspection_report_document_number' => 'required|string',
        'document_number' => 'required|string',
        'period' => 'required|integer|min:1|max:4',
        'start_time' => 'required|date_format:H:i',
        'end_time' => 'required|date_format:H:i',
    ];

    protected $listeners = [
        'firstInspectionSaved' => 'refreshSavedFlags',
        'firstInspectionReset' => 'refreshSavedFlags',
        'dimensionsSaved' => 'refreshSavedFlags',
        'dimensionsReset' => 'refreshSavedFlags',
        'secondInspectionSaved' => 'refreshSavedFlags',
        'secondInspectionReset' => 'refreshSavedFlags',
        'samplingSaved' => 'refreshSavedFlags',
        'samplingReset' => 'refreshSavedFlags',
        'packagingSaved' => 'refreshSavedFlags',
        'packagingReset' => 'refreshSavedFlags',
        'judgementSaved' => 'refreshSavedFlags',
        'judgementReset' => 'refreshSavedFlags',
    ];

    public function updated($field): void
    {
        $this->validateOnly($field);
    }

    public function mount($inspection_report_document_number = null): void
    {
        $this->inspection_report_document_number = $inspection_report_document_number
            ?: session('stepHeaderSaved.document_number');

        $saved = session('stepDetailSaved');
        $this->period = (int) data_get($saved, 'period', 1);

        $this->generateDocumentNumber();

        if ($saved) {
            foreach ($saved as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
            if (! empty($saved['start_datetime'])) {
                $this->start_time = Carbon::parse($saved['start_datetime'])->format('H:i');
            }
            if (! empty($saved['end_datetime'])) {
                $this->end_time = Carbon::parse($saved['end_datetime'])->format('H:i');
            }
        }

        $this->shift = session('stepHeaderSaved.shift') ?? null;
        $this->operator = session('stepHeaderSaved.operator') ?? 'N/A';
        $this->inspector = session('stepHeaderSaved.inspector') ?? 'N/A';
        $this->part_name = session('stepHeaderSaved.part_name') ?? 'N/A';
        $this->part_number = session('stepHeaderSaved.part_number') ?? 'N/A';

        if ($this->shift) {
            $this->updatedPeriod();
        }
        $this->saveStep();
        $this->refreshSavedFlags();
    }

    public function updatedPeriod(): void
    {
        $shift = (int) ($this->shift ?? 1);
        $period = (int) ($this->period ?? 1);

        $shiftTimes = [
            1 => ['07:30', '15:30'],
            2 => ['15:30', '23:30'],
            3 => ['23:30', '07:30'],
        ];

        $periodOffset = $period - 1;
        [$shiftStart, $shiftEnd] = $shiftTimes[$shift] ?? ['07:30', '15:30'];

        $start = Carbon::createFromTimeString($shiftStart);
        $end = Carbon::createFromTimeString($shiftEnd);

        if ($shift === 3 && $end->lessThan($start)) {
            $end->addDay();
        }

        $startPeriod = $start->copy()->addHours($periodOffset * 2);
        $endPeriod = $startPeriod->copy()->addHours(2);

        if ($endPeriod->greaterThan($end)) {
            $endPeriod = $end;
        }

        $this->start_datetime = $startPeriod->format('Y-m-d H:i:s');
        $this->end_datetime = $endPeriod->format('Y-m-d H:i:s');
        $this->start_time = $startPeriod->format('H:i');
        $this->end_time = $endPeriod->format('H:i');
    }

    public function selectPeriod(int $q): void
    {
        $this->period = $q;
        $this->updatedPeriod();
        $this->generateDocumentNumber();
        $this->saveStep();
        $this->computeSaveFlags();
    }

    private function generateDocumentNumber(): void
    {
        $this->periodKey = $this->periodKey();
        if (session("stepDetailSaved.details.$this->periodKey.document_number")) {
            $this->document_number = session("stepDetailSaved.details.$this->periodKey.document_number");
        } else {
            $this->document_number = 'DETAIL-' . now()->format('Ymd-His') . '-' . strtoupper(Str::random(4));
        }
    }

    private function periodKey(?int $period = null): string
    {
        $p = $period ?? ($this->period ?? 1);

        return 'p' . $p;
    }

    public function computeSaveFlags(): void
    {
        $pk = $this->periodKey();
        $bag = session('stepDetailSaved', []);
        $this->savedDetails = ! empty(data_get($bag, "details.$pk"));
        $this->savedFirst = ! empty(data_get($bag, "first_inspections.$pk"));
        $this->savedDimensions = ! empty(data_get($bag, "dimensions.$pk"));
        $this->savedSecond = ! empty(data_get($bag, "second_inspections.$pk")) &&
            ! empty(data_get($bag, "samples.$pk")) &&
            ! empty(data_get($bag, "packagings.$pk"));
        $this->savedJudgement = ! empty(data_get($bag, "judgements.$pk"));
    }

    public function refreshSavedFlags(): void
    {
        $this->computeSaveFlags();
    }

    public function saveStep(): void
    {
        $this->validate();
        $data = session('stepDetailSaved', []);
        $data['shift'] = session('stepHeaderSaved.shift') ?? ($data['shift'] ?? null);
        $data['period'] = $this->period ?? ($data['period'] ?? 1);

        $this->periodKey = 'p' . $this->period;

        $data['details'][$this->periodKey] = [
            'document_number' => $this->document_number,
            'inspection_report_document_number' => $this->inspection_report_document_number,
            'period' => $this->period,
            'start_datetime' => $this->start_datetime,
            'end_datetime' => $this->end_datetime,
        ];

        session(['stepDetailSaved' => $data]);
        $this->reloadToken++;
        $this->computeSaveFlags();
    }

    public function getPeriodTimeRange(int $period): string
    {
        $shift = (int) ($this->shift ?? session('stepHeaderSaved.shift', 1));
        $shiftTimes = [
            1 => ['07:30', '15:30'],
            2 => ['15:30', '23:30'],
            3 => ['23:30', '07:30'],
        ];

        [$shiftStart, $shiftEnd] = $shiftTimes[$shift] ?? ['07:30', '15:30'];
        $start = Carbon::createFromTimeString($shiftStart);
        $end = Carbon::createFromTimeString($shiftEnd);

        if ($shift === 3 && $end->lessThan($start)) {
            $end->addDay();
        }

        $offset = $period - 1;
        $pStart = $start->copy()->addHours($offset * 2);
        $pEnd = $pStart->copy()->addHours(2);

        if ($pEnd->greaterThan($end)) {
            $pEnd = $end;
        }

        return $pStart->format('H:i') . ' - ' . $pEnd->format('H:i');
    }

    public function isPeriodSaved(?int $p = null): bool
    {
        $targetP = $p ?? $this->period;
        $pk = 'p' . $targetP;
        $bag = session('stepDetailSaved', []);

        if (! empty(data_get($bag, "saved_periods.$pk"))) {
            return true;
        }

        $hasDetail = ! empty(data_get($bag, "details.$pk"));
        $hasFirst = ! empty(data_get($bag, "first_inspections.$pk"));
        $hasDimensions = ! empty(data_get($bag, "dimensions.$pk"));
        $hasSecond = ! empty(data_get($bag, "second_inspections.$pk"));
        $hasPackagings = ! empty(data_get($bag, "packagings.$pk"));
        $hasJudgement = ! empty(data_get($bag, "judgements.$pk"));

        return $hasDetail && ($hasFirst || $hasDimensions || $hasSecond || $hasPackagings || $hasJudgement);
    }

    public function isPeriodLocked(?int $p = null): bool
    {
        $targetP = $p ?? $this->period;
        if (! empty($this->unlockedPeriods[$targetP])) {
            return false;
        }

        return $this->isPeriodSaved($targetP);
    }

    public function unlockPeriod(?int $p = null): void
    {
        $targetP = $p ?? $this->period;
        $this->unlockedPeriods[$targetP] = true;
    }

    public function lockPeriod(?int $p = null): void
    {
        $targetP = $p ?? $this->period;
        unset($this->unlockedPeriods[$targetP]);
    }

    public function saveCurrentPeriod(): void
    {
        $this->saveStep();
        $this->dispatch('triggerSubcomponentSave', periodKey: $this->periodKey);

        $bag = session('stepDetailSaved', []);
        $bag['saved_periods'][$this->periodKey] = true;
        session(['stepDetailSaved' => $bag]);

        $this->computeSaveFlags();
        unset($this->unlockedPeriods[$this->period]);
        $this->dispatch('stepDetailSaved');
        $this->dispatch('toast', message: "Data Periode {$this->period} berhasil disimpan!");
    }

    public function copyFromPreviousPeriod(): void
    {
        if ($this->period <= 1) {
            $this->dispatch('toast', message: 'Periode 1 adalah periode awal. Tidak ada periode sebelumnya.', type: 'warning');
            return;
        }

        $prevPeriod = $this->period - 1;
        $prevKey = 'p' . $prevPeriod;
        $currentKey = $this->periodKey;
        $bag = session('stepDetailSaved', []);

        $hasPrev = ! empty(data_get($bag, "first_inspections.$prevKey"))
            || ! empty(data_get($bag, "dimensions.$prevKey"))
            || ! empty(data_get($bag, "second_inspections.$prevKey"))
            || ! empty(data_get($bag, "packagings.$prevKey"));

        if (! $hasPrev) {
            $this->dispatch('toast', message: "Data Periode {$prevPeriod} belum tersedia untuk disalin.", type: 'warning');
            return;
        }

        // 1. Copy Dimension Template (Keep Area, Lower Limit, Upper Limit, UOM; clear actual_value and judgement)
        $prevDimensions = data_get($bag, "dimensions.$prevKey", []);
        if (! empty($prevDimensions)) {
            $copiedDimensions = [];
            foreach ($prevDimensions as $dim) {
                $copiedDimensions[] = [
                    'row_key' => (string) Str::uuid(),
                    'inspection_report_document_number' => $this->inspection_report_document_number,
                    'area' => $dim['area'] ?? '',
                    'lower_limit' => $dim['lower_limit'] ?? null,
                    'upper_limit' => $dim['upper_limit'] ?? null,
                    'limit_uom' => $dim['limit_uom'] ?? 'mm',
                    'actual_value' => null,
                    'judgement' => 'OK',
                    'remarks' => null,
                    'start_datetime' => $this->start_datetime,
                    'end_datetime' => $this->end_datetime,
                ];
            }
            $bag['dimensions'][$currentKey] = $copiedDimensions;
        }

        // 2. Copy Packaging Template (Keep SNP, box label; default judgement OK)
        $prevPackagings = data_get($bag, "packagings.$prevKey", []);
        if (! empty($prevPackagings)) {
            $copiedPackagings = [];
            foreach ($prevPackagings as $pack) {
                $copiedPackagings[] = [
                    'row_key' => (string) Str::uuid(),
                    'second_inspection_document_number' => $this->document_number,
                    'snp' => $pack['snp'] ?? 100,
                    'box_label' => $pack['box_label'] ?? '',
                    'judgement' => 'OK',
                    'remarks' => null,
                ];
            }
            $bag['packagings'][$currentKey] = $copiedPackagings;
        }

        // 3. Copy First Piece defaults (Weight & UOM)
        $prevFirst = data_get($bag, "first_inspections.$prevKey", []);
        if (! empty($prevFirst)) {
            $bag['first_inspections'][$currentKey] = [
                'detail_inspection_report_document_number' => $this->document_number,
                'appearance' => 'OK',
                'weight' => $prevFirst['weight'] ?? session('stepHeaderSaved.weight'),
                'weight_uom' => $prevFirst['weight_uom'] ?? session('stepHeaderSaved.weight_uom', 'g'),
                'fitting_test' => $prevFirst['fitting_test'] ?? null,
                'remarks' => null,
            ];
        }

        // 4. Copy Lot Size Setup
        $prevSecond = data_get($bag, "second_inspections.$prevKey", []);
        if (! empty($prevSecond)) {
            $bag['second_inspections'][$currentKey] = [
                'detail_inspection_report_document_number' => $this->document_number,
                'document_number' => $this->document_number,
                'lot_size_quantity' => $prevSecond['lot_size_quantity'] ?? null,
                'skipLotSize' => $prevSecond['skipLotSize'] ?? false,
            ];
        }

        session(['stepDetailSaved' => $bag]);
        $this->reloadToken++;
        $this->computeSaveFlags();
        $this->dispatch('toast', message: "Struktur template Periode {$prevPeriod} berhasil disalin ke Periode {$this->period}!", type: 'success');
    }

    public function proceedToStep3(): void
    {
        $this->saveCurrentPeriod();
        $this->dispatch('nextStep');
    }

    public function resetStep(bool $clearOnlyCurrentPeriod = true): void
    {
        $saved = session('stepDetailSaved', []);
        $this->reset(['start_datetime', 'end_datetime', 'start_time', 'end_time']);

        if ($clearOnlyCurrentPeriod) {
            $pk = $this->periodKey($this->period);
            $sections = [
                'details',
                'first_inspections',
                'dimensions',
                'second_inspections',
                'samples',
                'packagings',
                'judgements',
                'saved_periods',
            ];

            foreach ($sections as $section) {
                if (isset($saved[$section][$pk])) {
                    unset($saved[$section][$pk]);
                }
            }

            $saved['period'] = $this->period;
            session(['stepDetailSaved' => $saved]);
        } else {
            session()->forget('stepDetailSaved');
        }

        $this->updatedPeriod();
        $this->generateDocumentNumber();
        $this->reloadToken++;
        $this->dispatch('toast', message: 'Data periode berhasil di-reset!');
        $this->refreshSavedFlags();
    }

    public function render(): View
    {
        $this->shift = session('stepHeaderSaved.shift') ?? ($this->shift ?? 1);
        $this->part_number = session('stepHeaderSaved.part_number') ?? $this->part_number;
        $this->part_name = session('stepHeaderSaved.part_name') ?? $this->part_name;
        $this->inspector = session('stepHeaderSaved.inspector') ?? $this->inspector;

        $pk = $this->periodKey($this->period);
        $bag = session('stepDetailSaved', []);

        $savedSummary = [
            'isSaved' => $this->isPeriodSaved($this->period),
            'isLocked' => $this->isPeriodLocked($this->period),
            'first' => data_get($bag, "first_inspections.$pk"),
            'dimensions' => data_get($bag, "dimensions.$pk", []),
            'second' => data_get($bag, "second_inspections.$pk"),
            'samples' => data_get($bag, "samples.$pk", []),
            'packagings' => data_get($bag, "packagings.$pk", []),
            'judgement' => data_get($bag, "judgements.$pk"),
        ];

        return view('livewire.inspection.form.step-detail', [
            'savedSummary' => $savedSummary,
        ]);
    }
}