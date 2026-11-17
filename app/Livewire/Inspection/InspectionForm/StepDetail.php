<?php

namespace App\Livewire\Inspection\InspectionForm;

use App\Models\Inspection\DetailInspectionReport;
use App\Models\Inspection\FirstInspection;
use App\Models\Inspection\InspectionDimension;
use App\Models\Inspection\InspectionJudgement;
use App\Models\Inspection\InspectionPackaging;
use App\Models\Inspection\InspectionProblem;
use App\Models\Inspection\InspectionSampling;
use App\Models\Inspection\SecondInspection;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class StepDetail extends Component
{
    public $inspection_report_document_number;

    public $document_number;

    public int $period = 1;

    public bool $isWizardActive = false;

    public int $subStep = 1;

    public $machine_number;

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

    public bool $savedProblems = true;

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
        'periodProblemsSaved' => 'refreshSavedFlags',
        'periodProblemsReset' => 'refreshSavedFlags',
    ];

    public function updated($field): void
    {
        $this->validateOnly($field);
    }

    public function mount($inspection_report_document_number = null): void
    {
        $this->inspection_report_document_number = $inspection_report_document_number
            ?: session('stepHeaderSaved.document_number');

        $headerDoc = session('stepHeaderSaved.document_number');
        $saved = session('stepDetailSaved');

        // Reset stepDetailSaved if it belongs to a different inspection report document
        if ($headerDoc && ! empty($saved['details'])) {
            $firstDetailDoc = data_get(reset($saved['details']), 'inspection_report_document_number');
            if ($firstDetailDoc && $firstDetailDoc !== $headerDoc) {
                session()->forget('stepDetailSaved');
                $saved = null;
            }
        }

        $this->shift = session('stepHeaderSaved.shift') ?? null;
        $this->operator = session('stepHeaderSaved.operator') ?? 'N/A';
        $this->inspector = session('stepHeaderSaved.inspector') ?? 'N/A';
        $this->part_name = session('stepHeaderSaved.part_name') ?? 'N/A';
        $this->part_number = session('stepHeaderSaved.part_number') ?? 'N/A';
        $this->machine_number = session('stepHeaderSaved.machine_number') ?? '-';

        if (empty($saved['period'])) {
            $this->period = $this->determineCurrentPeriod();
        } else {
            $this->period = (int) $saved['period'];
        }

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

        if ($this->shift) {
            $this->updatedPeriod();
        }
        $this->saveStep();
        $this->refreshSavedFlags();
    }

    public function determineCurrentPeriod(): int
    {
        $shift = (int) ($this->shift ?? session('stepHeaderSaved.shift', 1));
        [$shiftStart, $shiftEnd] = $this->getShiftTimes($shift);
        $tz = config('mes.timezone', 'Asia/Jakarta');
        $now = now()->setTimezone($tz);
        $start = Carbon::createFromTimeString($shiftStart, $tz);
        $end = Carbon::createFromTimeString($shiftEnd, $tz);

        if ($shift === 3 && $end->lessThan($start)) {
            if ($now->hour < 12) {
                $start->subDay();
            } else {
                $end->addDay();
            }
        }

        for ($p = 1; $p <= 4; $p++) {
            $pStart = $start->copy()->addHours(($p - 1) * 2);
            $pEnd = ($p === 4) ? $end : $start->copy()->addHours($p * 2);
            if ($now->betweenIncluded($pStart, $pEnd)) {
                return $p;
            }
        }

        return 1;
    }

    private function getShiftTimes(int $shift): array
    {
        $shifts = config('mes.shifts', []);
        $shiftData = $shifts[$shift] ?? null;

        if ($shiftData && ! empty($shiftData['start']) && ! empty($shiftData['end'])) {
            return [$shiftData['start'], $shiftData['end']];
        }

        return ['07:30', '15:30'];
    }

    public function updatedPeriod(): void
    {
        $shift = (int) ($this->shift ?? 1);
        $period = (int) ($this->period ?? 1);

        $periodOffset = $period - 1;
        [$shiftStart, $shiftEnd] = $this->getShiftTimes($shift);

        $tz = config('mes.timezone', 'Asia/Jakarta');
        $start = Carbon::createFromTimeString($shiftStart, $tz);
        $end = Carbon::createFromTimeString($shiftEnd, $tz);

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

    public function startPeriod(int $p): void
    {
        $this->period = $p;
        $this->subStep = 1;
        $this->updatedPeriod();
        $this->generateDocumentNumber();
        $this->saveStep();
        $this->computeSaveFlags();
        $this->isWizardActive = true;
    }

    public function editPeriod(int $p): void
    {
        $this->unlockPeriod($p);
        $this->startPeriod($p);
    }

    public function goToSubStep(int $step): void
    {
        if ($step >= 1 && $step <= 5) {
            $this->dispatch('triggerSubcomponentSave', periodKey: $this->periodKey);
            $this->subStep = $step;
            $this->saveStep();
            $this->persistPeriodToDatabase($this->period);
        }
    }

    public function nextSubStep(): void
    {
        $this->dispatch('triggerSubcomponentSave', periodKey: $this->periodKey);
        $this->saveStep();
        $this->persistPeriodToDatabase($this->period);

        if ($this->subStep < 5) {
            $this->subStep++;
        } else {
            $this->finishPeriod();
        }
    }

    public function prevSubStep(): void
    {
        $this->dispatch('triggerSubcomponentSave', periodKey: $this->periodKey);
        $this->saveStep();
        $this->persistPeriodToDatabase($this->period);

        if ($this->subStep > 1) {
            $this->subStep--;
        } else {
            $this->exitToHub();
        }
    }

    public function finishPeriod(): void
    {
        $this->dispatch('triggerSubcomponentSave', periodKey: $this->periodKey);
        $this->saveCurrentPeriod();
        $this->isWizardActive = false;
        $this->subStep = 1;
        $this->dispatch('stepDetailSaved');
        $this->dispatch('toast', message: "Inspeksi Periode {$this->period} berhasil diselesaikan dan disimpan!");
    }

    public function exitToHub(): void
    {
        $this->dispatch('triggerSubcomponentSave', periodKey: $this->periodKey);
        $this->saveStep();
        $this->persistPeriodToDatabase($this->period);
        $this->isWizardActive = false;
        $this->subStep = 1;
    }

    public function resetPeriod(int $p): void
    {
        $this->period = $p;
        $this->resetStep(true);
        $this->isWizardActive = false;
        $this->subStep = 1;
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

    public function getSubStepsSummaryProperty(): array
    {
        $pk = $this->periodKey();
        $bag = session('stepDetailSaved', []);

        // 1. First Piece
        $first = data_get($bag, "first_inspections.$pk");
        $firstSummary = 'Belum diisi';
        if ($first) {
            $app = data_get($first, 'appearance');
            $wt = data_get($first, 'weight');
            $uom = data_get($first, 'weight_uom', 'g');
            $parts = [];
            if ($app) {
                $parts[] = "App: {$app}";
            }
            if ($wt !== null && $wt !== '') {
                $parts[] = "{$wt}{$uom}";
            }
            $firstSummary = ! empty($parts) ? implode(' • ', $parts) : 'Tersimpan';
        }

        // 2. Dimensi
        $dims = data_get($bag, "dimensions.$pk", []);
        $dimCount = count($dims);
        if ($dimCount > 0) {
            $dimOk = count(array_filter($dims, fn ($d) => ($d['judgement'] ?? '') === 'OK'));
            $dimNg = count(array_filter($dims, fn ($d) => ($d['judgement'] ?? '') === 'NG'));
            $dimSummary = "{$dimCount} Titik ({$dimOk} OK" . ($dimNg > 0 ? ", {$dimNg} NG" : '') . ')';
        } else {
            $dimSummary = 'Belum ada titik';
        }

        // 3. Second & Sampling
        $second = data_get($bag, "second_inspections.$pk");
        $samples = data_get($bag, "samples.$pk", []);
        $secondSummary = 'Belum diisi';
        if ($second || ! empty($samples)) {
            $boxCount = count($samples);
            $secondSummary = $boxCount > 0 ? "{$boxCount} Box Sample" : 'Tersimpan';
        }

        // 4. Judgement & Output
        $judgement = data_get($bag, "judgements.$pk");
        $judgSummary = 'Belum diisi';
        if ($judgement) {
            $pass = (int) data_get($judgement, 'pass_quantity', 0);
            $rej = (int) data_get($judgement, 'reject_quantity', 0);
            $total = $pass + $rej;
            $judgSummary = "Pass: {$pass} • Rej: {$rej} ({$total} pcs)";
        }

        // 5. Masalah & Loss Time
        $probs = data_get($bag, "problems.$pk", []);
        $probCount = count($probs);
        if ($probCount > 0) {
            $totalLoss = collect($probs)->sum(fn ($pr) => (int) data_get($pr, 'cycle_time', 0));
            $probSummary = "{$probCount} Masalah ({$totalLoss} mnt)";
        } else {
            $probSummary = 'Normal (0 Masalah)';
        }

        return [
            1 => [
                'title' => 'First Piece',
                'isSaved' => $this->savedFirst,
                'summary' => $firstSummary,
            ],
            2 => [
                'title' => 'Dimensi',
                'isSaved' => $this->savedDimensions,
                'summary' => $dimSummary,
            ],
            3 => [
                'title' => 'Second & Sampling',
                'isSaved' => $this->savedSecond,
                'summary' => $secondSummary,
            ],
            4 => [
                'title' => 'Judgement & Output',
                'isSaved' => $this->savedJudgement,
                'summary' => $judgSummary,
            ],
            5 => [
                'title' => 'Masalah & Loss Time',
                'isSaved' => true,
                'summary' => $probSummary,
            ],
        ];
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
        [$shiftStart, $shiftEnd] = $this->getShiftTimes($shift);

        $tz = config('mes.timezone', 'Asia/Jakarta');
        $start = Carbon::createFromTimeString($shiftStart, $tz);
        $end = Carbon::createFromTimeString($shiftEnd, $tz);

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

    public function getPeriodTimeBounds(int $period): array
    {
        $pk = 'p' . $period;
        $bag = session('stepDetailSaved', []);
        $st = data_get($bag, "details.$pk.start_datetime");
        $et = data_get($bag, "details.$pk.end_datetime");

        if ($st && $et) {
            return [
                Carbon::parse($st)->format('H:i'),
                Carbon::parse($et)->format('H:i'),
            ];
        }

        $shift = (int) ($this->shift ?? session('stepHeaderSaved.shift', 1));
        [$shiftStart, $shiftEnd] = $this->getShiftTimes($shift);

        $tz = config('mes.timezone', 'Asia/Jakarta');
        $start = Carbon::createFromTimeString($shiftStart, $tz);
        $end = Carbon::createFromTimeString($shiftEnd, $tz);

        if ($shift === 3 && $end->lessThan($start)) {
            $end->addDay();
        }

        $offset = $period - 1;
        $pStart = $start->copy()->addHours($offset * 2);
        $pEnd = $pStart->copy()->addHours(2);

        if ($pEnd->greaterThan($end)) {
            $pEnd = $end;
        }

        return [$pStart->format('H:i'), $pEnd->format('H:i')];
    }

    public function isTimeInBounds(string $time, string $start, string $end): bool
    {
        $cleanTime = substr(trim($time), 0, 5);
        $t = strtotime($cleanTime);
        $s = strtotime($start);
        $e = strtotime($end);

        if ($e < $s) {
            return $t >= $s || $t <= $e;
        }

        return $t >= $s && $t <= $e;
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

        $pk = 'p' . $targetP;
        $bag = session('stepDetailSaved', []);

        return ! empty(data_get($bag, "saved_periods.$pk"));
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
        $pk = 'p' . $targetP;
        $bag = session('stepDetailSaved', []);
        $bag['saved_periods'][$pk] = true;
        session(['stepDetailSaved' => $bag]);
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
        $this->persistPeriodToDatabase($this->period);
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
        if ($this->isWizardActive) {
            $this->saveCurrentPeriod();
            $this->isWizardActive = false;
        }
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
                'problems',
                'saved_periods',
            ];

            foreach ($sections as $section) {
                if (isset($saved[$section][$pk])) {
                    unset($saved[$section][$pk]);
                }
            }

            $saved['period'] = $this->period;
            session(['stepDetailSaved' => $saved]);
            $this->deletePeriodFromDatabase($this->period);
        } else {
            session()->forget('stepDetailSaved');
        }

        $this->updatedPeriod();
        $this->generateDocumentNumber();
        $this->reloadToken++;
        $this->dispatch('toast', message: 'Data periode berhasil di-reset!');
        $this->refreshSavedFlags();
    }

    public function persistPeriodToDatabase(int $period): void
    {
        $pk = 'p' . $period;
        $bag = session('stepDetailSaved', []);
        $headerDoc = $this->inspection_report_document_number ?: session('stepHeaderSaved.document_number');
        if (! $headerDoc) {
            return;
        }

        $detailData = data_get($bag, "details.$pk");
        if (! $detailData) {
            return;
        }

        $detailDocNum = data_get($detailData, 'document_number', $this->document_number);

        DB::transaction(function () use ($headerDoc, $period, $detailDocNum, $detailData, $bag, $pk) {
            DetailInspectionReport::updateOrCreate(
                [
                    'inspection_report_document_number' => $headerDoc,
                    'period' => $period,
                ],
                [
                    'document_number' => $detailDocNum,
                    'start_datetime' => data_get($detailData, 'start_datetime'),
                    'end_datetime' => data_get($detailData, 'end_datetime'),
                ]
            );

            $firstData = data_get($bag, "first_inspections.$pk");
            if ($firstData) {
                FirstInspection::updateOrCreate(
                    ['detail_inspection_report_document_number' => $detailDocNum],
                    [
                        'appearance' => data_get($firstData, 'appearance', 'OK'),
                        'remarks' => data_get($firstData, 'remarks'),
                        'weight' => (float) data_get($firstData, 'weight', 0),
                        'weight_uom' => data_get($firstData, 'weight_uom', 'g'),
                        'fitting_test' => data_get($firstData, 'fitting_test'),
                    ]
                );
            }

            $dims = data_get($bag, "dimensions.$pk", []);
            $validAreas = [];
            if (! empty($dims)) {
                foreach ($dims as $d) {
                    $area = trim((string) data_get($d, 'area', ''));
                    if ($area === '') {
                        continue;
                    }
                    $validAreas[] = $area;

                    $lower = data_get($d, 'lower_limit');
                    $upper = data_get($d, 'upper_limit');
                    $actual = data_get($d, 'actual_value');

                    InspectionDimension::updateOrCreate(
                        [
                            'inspection_report_document_number' => $headerDoc,
                            'area' => $area,
                        ],
                        [
                            'lower_limit' => (is_numeric($lower) && $lower !== '') ? (float) $lower : null,
                            'upper_limit' => (is_numeric($upper) && $upper !== '') ? (float) $upper : null,
                            'limit_uom' => data_get($d, 'limit_uom', 'mm'),
                            'actual_value' => (is_numeric($actual) && $actual !== '') ? (float) $actual : null,
                            'start_datetime' => data_get($detailData, 'start_datetime'),
                            'end_datetime' => data_get($detailData, 'end_datetime'),
                            'judgement' => data_get($d, 'judgement'),
                            'remarks' => data_get($d, 'remarks'),
                        ]
                    );
                }
            }

            // Clean up any stale or blank area records for this report
            $staleDimQuery = InspectionDimension::where('inspection_report_document_number', $headerDoc);
            if (! empty($validAreas)) {
                $staleDimQuery->where(function ($q) use ($validAreas) {
                    $q->whereNotIn('area', $validAreas)
                        ->orWhereNull('area')
                        ->orWhere('area', '');
                });
            } else {
                $staleDimQuery->where(function ($q) {
                    $q->whereNull('area')->orWhere('area', '');
                });
            }
            $staleDimQuery->delete();

            $secondData = data_get($bag, "second_inspections.$pk");
            if ($secondData) {
                $secondDoc = data_get($secondData, 'document_number') ?: ('SEC-' . now()->format('Ymd-His') . '-' . strtoupper(Str::random(4)));
                SecondInspection::updateOrCreate(
                    ['detail_inspection_report_document_number' => $detailDocNum],
                    [
                        'document_number' => $secondDoc,
                        'lot_size_quantity' => data_get($secondData, 'lot_size_quantity'),
                    ]
                );

                $samples = data_get($bag, "samples.$pk", []);
                if (! empty($samples)) {
                    InspectionSampling::where('second_inspection_document_number', $secondDoc)->delete();
                    foreach ($samples as $s) {
                        InspectionSampling::create([
                            'second_inspection_document_number' => $secondDoc,
                            'quantity' => (int) data_get($s, 'quantity', 0),
                            'box_label' => data_get($s, 'box_label', '-'),
                            'appearance' => data_get($s, 'appearance', 'OK'),
                            'ng_quantity' => (int) data_get($s, 'ng_quantity', 0),
                            'remarks' => data_get($s, 'remarks'),
                        ]);
                    }
                }

                $packagings = data_get($bag, "packagings.$pk", []);
                if (! empty($packagings)) {
                    InspectionPackaging::where('second_inspection_document_number', $secondDoc)->delete();
                    foreach ($packagings as $pkg) {
                        InspectionPackaging::create([
                            'second_inspection_document_number' => $secondDoc,
                            'snp' => (int) data_get($pkg, 'snp', 0),
                            'box_label' => data_get($pkg, 'box_label', '-'),
                            'judgement' => data_get($pkg, 'judgement', 'OK'),
                            'remarks' => data_get($pkg, 'remarks'),
                        ]);
                    }
                }
            }

            $judgementData = data_get($bag, "judgements.$pk");
            if ($judgementData) {
                InspectionJudgement::updateOrCreate(
                    ['detail_inspection_report_document_number' => $detailDocNum],
                    [
                        'pass_quantity' => (int) data_get($judgementData, 'pass_quantity', 0),
                        'reject_quantity' => (int) data_get($judgementData, 'reject_quantity', 0),
                    ]
                );
            }

            // Sync period problems to inspection_problems table
            [$startTime, $endTime] = $this->getPeriodTimeBounds($period);
            $existingProblems = InspectionProblem::where('inspection_report_document_number', $headerDoc)->get();
            $toDeleteIds = [];
            foreach ($existingProblems as $prob) {
                if ($this->isTimeInBounds((string) $prob->time, $startTime, $endTime)) {
                    $toDeleteIds[] = $prob->id;
                }
            }
            if (! empty($toDeleteIds)) {
                InspectionProblem::whereIn('id', $toDeleteIds)->delete();
            }

            $periodProbs = data_get($bag, "problems.$pk", []);
            if (! empty($periodProbs)) {
                foreach ($periodProbs as $pr) {
                    if (empty($pr['type']) || empty($pr['time'])) {
                        continue;
                    }
                    InspectionProblem::create([
                        'inspection_report_document_number' => $headerDoc,
                        'type' => $pr['type'],
                        'time' => $pr['time'],
                        'cycle_time' => (int) data_get($pr, 'cycle_time', 0),
                        'remarks' => data_get($pr, 'remarks'),
                    ]);
                }
            }
        });

        // Synchronize all merged period problems into session('stepProblemSaved') for Step 3 recap & Final Submit
        $allProblems = [];
        for ($i = 1; $i <= 4; $i++) {
            $pKey = 'p' . $i;
            $pProblems = data_get($bag, "problems.$pKey", []);
            if (is_array($pProblems)) {
                foreach ($pProblems as $prob) {
                    $allProblems[] = $prob;
                }
            }
        }
        session(['stepProblemSaved' => $allProblems]);
    }

    public function deletePeriodFromDatabase(int $period): void
    {
        $headerDoc = $this->inspection_report_document_number ?: session('stepHeaderSaved.document_number');
        if (! $headerDoc) {
            return;
        }

        $detail = DetailInspectionReport::where('inspection_report_document_number', $headerDoc)
            ->where('period', $period)
            ->first();

        if ($detail) {
            FirstInspection::where('detail_inspection_report_document_number', $detail->document_number)->delete();
            $second = SecondInspection::where('detail_inspection_report_document_number', $detail->document_number)->first();
            if ($second) {
                InspectionSampling::where('second_inspection_document_number', $second->document_number)->delete();
                InspectionPackaging::where('second_inspection_document_number', $second->document_number)->delete();
                $second->delete();
            }
            InspectionJudgement::where('detail_inspection_report_document_number', $detail->document_number)->delete();
            $detail->delete();
        }

        [$startTime, $endTime] = $this->getPeriodTimeBounds($period);
        $existingProblems = InspectionProblem::where('inspection_report_document_number', $headerDoc)->get();
        $toDeleteIds = [];
        foreach ($existingProblems as $prob) {
            if ($this->isTimeInBounds((string) $prob->time, $startTime, $endTime)) {
                $toDeleteIds[] = $prob->id;
            }
        }
        if (! empty($toDeleteIds)) {
            InspectionProblem::whereIn('id', $toDeleteIds)->delete();
        }
    }

    public function render(): View
    {
        $this->shift = session('stepHeaderSaved.shift') ?? ($this->shift ?? 1);
        $this->part_number = session('stepHeaderSaved.part_number') ?? $this->part_number;
        $this->part_name = session('stepHeaderSaved.part_name') ?? $this->part_name;
        $this->inspector = session('stepHeaderSaved.inspector') ?? $this->inspector;
        $this->machine_number = session('stepHeaderSaved.machine_number') ?? ($this->machine_number ?? '-');

        $bag = session('stepDetailSaved', []);

        $periodsSummary = [];
        for ($p = 1; $p <= 4; $p++) {
            $pk = 'p' . $p;
            $first = data_get($bag, "first_inspections.$pk");
            $dims = data_get($bag, "dimensions.$pk", []);
            $second = data_get($bag, "second_inspections.$pk");
            $samples = data_get($bag, "samples.$pk", []);
            $packagings = data_get($bag, "packagings.$pk", []);
            $judgement = data_get($bag, "judgements.$pk");

            $dimOkCount = collect($dims)->where('judgement', 'OK')->count();
            $dimNgCount = collect($dims)->where('judgement', 'NG')->count();
            $dimTotal = count($dims);

            $sampleBoxCount = count($samples);
            $sampleNgTotal = collect($samples)->sum(fn ($s) => (int) data_get($s, 'ng_quantity', 0));

            $passQty = (int) data_get($judgement, 'pass_quantity', 0);
            $rejQty = (int) data_get($judgement, 'reject_quantity', 0);
            $totalOutput = $passQty + $rejQty;

            $periodProbs = data_get($bag, "problems.$pk", []);
            $problemCount = count($periodProbs);
            $problemLossTime = collect($periodProbs)->sum(fn ($pr) => (int) data_get($pr, 'cycle_time', 0));

            $isSaved = $this->isPeriodSaved($p);

            $periodsSummary[$p] = [
                'period' => $p,
                'time_range' => $this->getPeriodTimeRange($p),
                'isSaved' => $isSaved,
                'isLocked' => $this->isPeriodLocked($p),
                'first' => $first,
                'first_appearance' => data_get($first, 'appearance'),
                'first_weight' => data_get($first, 'weight'),
                'first_weight_uom' => data_get($first, 'weight_uom', 'g'),
                'first_fitting' => data_get($first, 'fitting_test'),
                'dim_total' => $dimTotal,
                'dim_ok' => $dimOkCount,
                'dim_ng' => $dimNgCount,
                'second_lot_size' => data_get($second, 'lot_size_quantity'),
                'second_skip_lot' => data_get($second, 'skipLotSize'),
                'sample_box_count' => $sampleBoxCount,
                'sample_ng_total' => $sampleNgTotal,
                'packaging_snp' => data_get($packagings, '0.snp'),
                'packaging_count' => count($packagings),
                'pass_quantity' => $passQty,
                'reject_quantity' => $rejQty,
                'total_output' => $totalOutput,
                'problem_count' => $problemCount,
                'problem_loss_time' => $problemLossTime,
                'problems' => $periodProbs,
            ];
        }

        $hasAnySavedPeriod = collect($periodsSummary)->contains(fn ($p) => $p['isSaved']);
        $recommendedPeriod = $this->determineCurrentPeriod();

        return view('livewire.inspection.form.step-detail', [
            'periodsSummary' => $periodsSummary,
            'hasAnySavedPeriod' => $hasAnySavedPeriod,
            'recommendedPeriod' => $recommendedPeriod,
            'savedSummary' => $periodsSummary[$this->period] ?? [],
        ]);
    }
}