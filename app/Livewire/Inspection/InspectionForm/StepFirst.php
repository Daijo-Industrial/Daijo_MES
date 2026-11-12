<?php

namespace App\Livewire\Inspection\InspectionForm;

use App\Traits\ClearsNestedSession;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StepFirst extends Component
{
    use ClearsNestedSession;

    public $detail_inspection_report_document_number;

    public $appearance;

    public $weight;

    public $weight_uom = 'g';

    public $fitting_test;

    public $remarks;

    public string $periodKey = 'p1';

    public array $sessionSaved = [];

    public ?string $savedAt = null;

    public bool $isSaved = false;

    protected $listeners = [
        'triggerSubcomponentSave' => 'handleSaveTrigger',
    ];

    public function handleSaveTrigger(?string $periodKey = null): void
    {
        if ($periodKey === null || $periodKey === $this->periodKey) {
            if (! empty($this->appearance) && ! empty($this->weight)) {
                $this->saveStep();
            }
        }
    }

    protected $rules = [
        'detail_inspection_report_document_number' => 'required|string',
        'appearance' => 'required|in:OK,NG',
        'weight' => 'required|numeric|min:0',
        'weight_uom' => 'required|string',
        'fitting_test' => 'nullable|string',
        'remarks' => 'required_if:appearance,NG|nullable|string',
    ];

    public function updated($property): void
    {
        $this->validateOnly($property);
        $this->autoSave();
    }

    public function updatedAppearance($value): void
    {
        if ($value !== 'NG') {
            $this->remarks = null;
        }
        $this->autoSave();
    }

    public function autoSave(): void
    {
        if (! empty($this->appearance) && $this->weight !== null && $this->weight !== '') {
            $data = $this->snapshot();
            session()->put("stepDetailSaved.first_inspections.{$this->periodKey}", $data);
            $this->sessionSaved = $data;
            $this->isSaved = true;
            $this->dispatch('firstInspectionSaved')->to(StepDetail::class);
        }
    }

    protected function snapshot(): array
    {
        $data = [
            'detail_inspection_report_document_number' => $this->detail_inspection_report_document_number,
            'appearance' => $this->appearance,
            'weight' => $this->weight,
            'weight_uom' => $this->weight_uom,
            'fitting_test' => $this->fitting_test,
            'remarks' => $this->remarks,
        ];

        if (strtoupper((string) $this->appearance) === 'OK') {
            unset($data['remarks']);
        }

        return $data;
    }

    public function mount($detail_inspection_report_document_number = null): void
    {
        $this->detail_inspection_report_document_number = $detail_inspection_report_document_number;
        $this->periodKey = 'p' . session('stepDetailSaved.period', 1);

        $saved = session("stepDetailSaved.first_inspections.{$this->periodKey}", []);
        $this->sessionSaved = $saved;
        $this->savedAt = session("stepDetailSaved.first_inspection_meta.{$this->periodKey}.savedAt");
        $this->isSaved = ! empty($saved);

        if ($saved) {
            foreach ($saved as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }

        if (empty($this->weight)) {
            $this->weight = session('stepHeaderSaved.weight');
            $this->weight_uom = session('stepHeaderSaved.weight_uom', 'g');
        }
    }

    public function saveStep(): void
    {
        $this->validate();

        $data = $this->snapshot();
        session()->put("stepDetailSaved.first_inspections.{$this->periodKey}", $data);

        $this->savedAt = now()->toIso8601String();
        session()->put("stepDetailSaved.first_inspection_meta.{$this->periodKey}.savedAt", $this->savedAt);

        $this->sessionSaved = $data;
        $this->isSaved = true;

        $this->dispatch('toast', message: 'First piece inspection berhasil disimpan!');
        $this->dispatch('firstInspectionSaved', savedAt: $this->savedAt);
        $this->dispatch('firstInspectionSaved')->to(StepDetail::class);
    }

    public function resetStep(): void
    {
        $this->appearance = null;
        $this->weight = session('stepHeaderSaved.weight');
        $this->weight_uom = session('stepHeaderSaved.weight_uom', 'g');
        $this->fitting_test = null;
        $this->remarks = null;

        $this->resetValidation();

        $this->forgetNestedKey('stepDetailSaved.first_inspections', $this->periodKey);
        $this->forgetNestedKey('stepDetailSaved.first_inspection_meta', $this->periodKey);

        $this->sessionSaved = [];
        $this->savedAt = null;
        $this->isSaved = false;

        $this->dispatch('firstInspectionReset');
        $this->dispatch('firstInspectionReset')->to(StepDetail::class);
        $this->dispatch('toast', message: 'First piece inspection di-reset!');
    }

    public function render(): View
    {
        return view('livewire.inspection.form.step-first');
    }
}
