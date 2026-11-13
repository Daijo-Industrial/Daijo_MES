<?php

namespace App\Livewire\Inspection;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class InspectionForm extends Component
{
    public int $currentStep = 1;

    public array $filledSteps = [];

    public ?string $inspection_report_document_number = null;

    public array $stepSessionKeys = [];

    protected $listeners = [
        'stepHeaderSaved' => 'handlerHeaderSaved',
        'stepDetailSaved' => 'markStepFilled',
        'stepProblemSaved' => 'markStepFilled',
        'stepFinalSubmit' => 'markStepFilled',
        'nextStep' => 'nextStep',
        'prevStep' => 'prevStep',
        'setStep' => 'setStep',
    ];

    public function mount(): void
    {
        $this->currentStep = (int) session('lastStepVisited', 1);
        $this->stepSessionKeys = [
            1 => 'stepHeaderSaved',
            2 => 'stepDetailSaved',
            3 => 'stepProblemSaved',
            4 => 'stepFinalSubmit',
        ];

        $this->filledSteps = $this->computeFilledSteps();
        $this->inspection_report_document_number = session('stepHeaderSaved.document_number');
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
