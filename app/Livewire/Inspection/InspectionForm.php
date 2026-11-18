<?php

namespace App\Livewire\Inspection;

use App\Livewire\Inspection\Concerns\HandlesInspectionDraft;
use App\Models\Inspection\DetailInspectionReport;
use App\Models\Inspection\InspectionDimension;
use App\Models\Inspection\InspectionProblem;
use App\Models\Inspection\InspectionReport;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class InspectionForm extends Component
{
    use HandlesInspectionDraft;

    public int $currentStep = 1;

    public array $filledSteps = [];

    public ?string $inspection_report_document_number = null;

    public ?InspectionReport $report = null;

    public array $stepSessionKeys = [];

    public ?array $pendingDraftInfo = null;

    public string $resetToken = '';

    protected $listeners = [
        'stepHeaderSaved' => 'handlerHeaderSaved',
        'stepDetailSaved' => 'markStepFilled',
        'stepProblemSaved' => 'markStepFilled',
        'stepFinalSubmit' => 'markStepFilled',
        'nextStep' => 'nextStep',
        'prevStep' => 'prevStep',
        'setStep' => 'setStep',
    ];

    public function mount($inspection_report = null): void
    {
        $this->resetToken = Str::random(6);

        $this->stepSessionKeys = [
            1 => 'stepHeaderSaved',
            2 => 'stepDetailSaved',
            3 => 'stepProblemSaved',
            4 => 'stepFinalSubmit',
        ];

        if ($inspection_report) {
            $this->report = $inspection_report instanceof InspectionReport
                ? $inspection_report
                : InspectionReport::find($inspection_report);
        }

        if ($this->report) {
            $this->authorize('update', $this->report);

            $this->inspection_report_document_number = $this->report->document_number;
            $this->loadDraftFromDatabase($this->report);
            $this->currentStep = (int) session('lastStepVisited', 2);
            $this->filledSteps = $this->computeFilledSteps();
            return;
        }

        $this->authorize('create', InspectionReport::class);

        if (request()->query('new') == 1) {
            $this->startNewReport(false);
            return;
        }

        if (request()->query('continue') == 1) {
            $draft = $this->findPendingDraft();
            if ($draft) {
                $this->pendingDraftInfo = $draft;
                $this->continueDraft();
                return;
            }
        }

        $this->inspection_report_document_number = session('stepHeaderSaved.document_number');
        $this->currentStep = (int) session('lastStepVisited', 1);
        $this->filledSteps = $this->computeFilledSteps();
    }

    public function continueDraft(): void
    {
        if (! $this->pendingDraftInfo) {
            return;
        }

        if (! empty($this->pendingDraftInfo['id'])) {
            $report = InspectionReport::find($this->pendingDraftInfo['id']);
            if ($report) {
                $this->report = $report;
                $this->loadDraftFromDatabase($report);
            }
        }

        $this->inspection_report_document_number = $this->pendingDraftInfo['document_number'] ?? session('stepHeaderSaved.document_number');
        $this->filledSteps = $this->computeFilledSteps();

        $targetStep = (int) ($this->pendingDraftInfo['last_step'] ?? session('lastStepVisited', 0));
        if ($targetStep >= 1 && $targetStep <= 4) {
            $this->currentStep = $targetStep;
        } elseif ($this->filledSteps[1] ?? false) {
            $this->currentStep = 2;
        } else {
            $this->currentStep = 1;
        }

        session(['lastStepVisited' => $this->currentStep]);

        $this->dispatch('toast', message: 'Melanjutkan draft laporan: ' . $this->inspection_report_document_number);
    }

    public function startNewReport(bool $notify = true): void
    {
        $this->clearWizardSession();

        $this->report = null;
        $this->inspection_report_document_number = null;
        $this->currentStep = 1;
        $this->pendingDraftInfo = null;
        $this->filledSteps = [
            1 => false,
            2 => false,
            3 => false,
            4 => false,
        ];
        $this->resetToken = Str::random(6);

        if ($notify) {
            $this->dispatch('toast', message: 'Sesi dibersihkan. Memulai laporan inspeksi baru.');
        }
    }

    public function loadDraftFromDatabase(InspectionReport $report): void
    {
        $timezone = config('mes.timezone') ?: 'Asia/Jakarta';

        session([
            'stepHeaderSaved' => [
                'document_number' => $report->document_number,
                'customer' => $report->customer,
                'inspection_date' => $report->inspection_date?->format('Y-m-d') ?: (string) $report->inspection_date,
                'part_number' => $report->part_number,
                'part_name' => $report->part_name,
                'weight' => $report->weight,
                'weight_uom' => $report->weight_uom,
                'material' => $report->material,
                'color' => $report->color,
                'tool_number_or_cav_number' => $report->tool_number_or_cav_number,
                'machine_number' => $report->machine_number,
                'shift' => $report->shift,
                'operator' => $report->operator,
                'inspector' => $report->inspector,
                'dic_id' => $report->dic_id,
            ],
            'stepHeaderSavedAt' => now()->setTimezone($timezone)->toIso8601String(),
        ]);

        $details = DetailInspectionReport::where('inspection_report_document_number', $report->document_number)
            ->with([
                'firstInspections',
                'secondInspections.samplingData',
                'secondInspections.packagingData',
                'judgementData',
            ])
            ->get();

        $detailBag = [
            'details' => [],
            'first_inspections' => [],
            'dimensions' => [],
            'second_inspections' => [],
            'samples' => [],
            'packagings' => [],
            'judgements' => [],
            'saved_periods' => [],
        ];

        foreach ($details as $d) {
            $pk = 'p' . $d->period;
            $detailBag['details'][$pk] = [
                'inspection_report_document_number' => $report->document_number,
                'document_number' => $d->document_number,
                'period' => $d->period,
                'start_datetime' => $d->start_datetime?->format('Y-m-d H:i:s') ?: (string) $d->start_datetime,
                'end_datetime' => $d->end_datetime?->format('Y-m-d H:i:s') ?: (string) $d->end_datetime,
            ];
            $detailBag['saved_periods'][$pk] = true;

            if ($first = $d->firstInspections->first()) {
                $detailBag['first_inspections'][$pk] = [
                    'detail_inspection_report_document_number' => $d->document_number,
                    'appearance' => $first->appearance,
                    'remarks' => $first->remarks,
                    'weight' => $first->weight,
                    'weight_uom' => $first->weight_uom,
                    'fitting_test' => $first->fitting_test,
                ];
            }

            if ($second = $d->secondInspections->first()) {
                $detailBag['second_inspections'][$pk] = [
                    'detail_inspection_report_document_number' => $d->document_number,
                    'document_number' => $second->document_number,
                    'lot_size_quantity' => $second->lot_size_quantity,
                ];

                $detailBag['samples'][$pk] = $second->samplingData->map(fn ($s) => [
                    'second_inspection_document_number' => $second->document_number,
                    'quantity' => $s->quantity,
                    'box_label' => $s->box_label,
                    'appearance' => $s->appearance,
                    'ng_quantity' => $s->ng_quantity,
                    'remarks' => $s->remarks,
                ])->all();

                $detailBag['packagings'][$pk] = $second->packagingData->map(fn ($pkg) => [
                    'second_inspection_document_number' => $second->document_number,
                    'snp' => $pkg->snp,
                    'box_label' => $pkg->box_label,
                    'judgement' => $pkg->judgement,
                    'remarks' => $pkg->remarks,
                ])->all();
            }

            if ($judgement = $d->judgementData->first()) {
                $detailBag['judgements'][$pk] = [
                    'detail_inspection_report_document_number' => $d->document_number,
                    'pass_quantity' => $judgement->pass_quantity,
                    'reject_quantity' => $judgement->reject_quantity,
                ];
            }
        }

        $dims = InspectionDimension::where('inspection_report_document_number', $report->document_number)->get();
        if ($dims->isNotEmpty()) {
            foreach ($details as $d) {
                $pk = 'p' . $d->period;
                $detailBag['dimensions'][$pk] = $dims->map(fn ($dim) => [
                    'inspection_report_document_number' => $report->document_number,
                    'lower_limit' => $dim->lower_limit,
                    'upper_limit' => $dim->upper_limit,
                    'limit_uom' => $dim->limit_uom,
                    'actual_value' => $dim->actual_value,
                    'start_datetime' => $dim->start_datetime?->format('Y-m-d H:i:s') ?: (string) $dim->start_datetime,
                    'end_datetime' => $dim->end_datetime?->format('Y-m-d H:i:s') ?: (string) $dim->end_datetime,
                    'judgement' => $dim->judgement,
                    'area' => $dim->area,
                    'remarks' => $dim->remarks,
                ])->all();
            }
        }

        $problems = InspectionProblem::where('inspection_report_document_number', $report->document_number)->get();
        if ($problems->isNotEmpty()) {
            session([
                'stepProblemSaved' => $problems->map(fn ($p) => [
                    'inspection_report_document_number' => $report->document_number,
                    'type' => $p->type,
                    'time' => substr((string) $p->time, 0, 5),
                    'cycle_time' => $p->cycle_time,
                    'remarks' => $p->remarks,
                ])->all(),
            ]);

            foreach ($details as $d) {
                $pk = 'p' . $d->period;
                $st = $d->start_datetime ? \Carbon\Carbon::parse($d->start_datetime)->format('H:i') : null;
                $et = $d->end_datetime ? \Carbon\Carbon::parse($d->end_datetime)->format('H:i') : null;
                if ($st && $et) {
                    $detailBag['problems'][$pk] = $problems->filter(function ($prob) use ($st, $et) {
                        $t = strtotime(substr((string) $prob->time, 0, 5));
                        $s = strtotime($st);
                        $e = strtotime($et);
                        if ($e < $s) {
                            return $t >= $s || $t <= $e;
                        }

                        return $t >= $s && $t <= $e;
                    })->map(fn ($p) => [
                        'inspection_report_document_number' => $report->document_number,
                        'type' => $p->type,
                        'time' => substr((string) $p->time, 0, 5),
                        'cycle_time' => $p->cycle_time,
                        'remarks' => $p->remarks,
                    ])->values()->all();
                }
            }
        }

        session(['stepDetailSaved' => $detailBag]);
    }

    public function computeFilledSteps(): array
    {
        $header = session('stepHeaderSaved', []);
        $step1 = ! empty($header['document_number'])
            && ! empty($header['part_number'])
            && ! empty($header['customer'])
            && ! empty($header['machine_number'])
            && ! empty($header['shift'])
            && ! empty($header['operator'])
            && ! empty($header['inspector']);

        $detail = session('stepDetailSaved', []);
        $savedPeriods = data_get($detail, 'saved_periods', []);
        $hasSavedPeriod = collect($savedPeriods)->contains(fn ($val) => ! empty($val));
        if (! $hasSavedPeriod && ! empty($detail['details'])) {
            foreach ($detail['details'] as $pk => $d) {
                if (! empty(data_get($detail, "first_inspections.$pk"))
                    && ! empty(data_get($detail, "second_inspections.$pk"))
                    && ! empty(data_get($detail, "judgements.$pk"))) {
                    $hasSavedPeriod = true;
                    break;
                }
            }
        }
        $step2 = $hasSavedPeriod;

        $step3 = session()->has('stepProblemSaved');

        return [
            1 => (bool) $step1,
            2 => (bool) $step2,
            3 => (bool) $step3,
            4 => false,
        ];
    }

    public function markStepFilled(): void
    {
        $this->filledSteps = $this->computeFilledSteps();
    }

    public function handlerHeaderSaved($data = []): void
    {
        $this->filledSteps = $this->computeFilledSteps();
        $this->inspection_report_document_number = $data['document_number'] ?? session('stepHeaderSaved.document_number');
    }

    public function updatedCurrentStep($step): void
    {
        session(['lastStepVisited' => $step]);
    }

    public function nextStep(): void
    {
        if ($this->currentStep < count($this->stepSessionKeys)) {
            $this->currentStep++;
            session(['lastStepVisited' => $this->currentStep]);
        }
    }

    public function prevStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
            session(['lastStepVisited' => $this->currentStep]);
        }
    }

    public function setStep($step): void
    {
        $this->currentStep = (int) $step;
        session(['lastStepVisited' => $this->currentStep]);
    }

    public function render(): View
    {
        $this->filledSteps = $this->computeFilledSteps();

        return view('livewire.inspection.form', [
            'filledSteps' => $this->filledSteps,
        ])->layout('layouts.app');
    }
}
