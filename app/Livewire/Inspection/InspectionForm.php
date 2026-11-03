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
        'setStep' => 'setStep',
    ];

    public function mount(): void
    {
        $this->currentStep = session('lastStepVisited', 1);
        $this->stepSessionKeys = [
            1 => 'stepHeaderSaved',
            2 => 'stepDetailSaved',
            3 => 'stepProblemSaved',
            4 => 'stepFinalSubmit',
        ];

        foreach ($this->stepSessionKeys as $step => $key) {
            if (session()->has($key)) {
                $this->filledSteps[$step] = true;
            }
        }

        $this->inspection_report_document_number = session('stepHeaderSaved.document_number');
    }

    public function markStepFilled(): void
    {
        $this->filledSteps[$this->currentStep] = true;
    }

    public function handlerHeaderSaved($data): void
    {
        $this->filledSteps[1] = true;
        $this->inspection_report_document_number = $data['document_number'] ?? null;
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

    public function setStep($step): void
    {
        $this->currentStep = (int) $step;
        session(['lastStepVisited' => $this->currentStep]);
    }

    public function render(): View
    {
        return view('livewire.inspection.form')->layout('layouts.app');
    }
}
