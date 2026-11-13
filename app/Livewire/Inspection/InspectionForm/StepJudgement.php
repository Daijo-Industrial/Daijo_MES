<?php

namespace App\Livewire\Inspection\InspectionForm;

use App\Traits\ClearsNestedSession;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StepJudgement extends Component
{
    use ClearsNestedSession;

    public $detail_inspection_report_document_number;

    public $pass_quantity;

    public $reject_quantity;

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
            if ($this->pass_quantity !== null && $this->reject_quantity !== null) {
                $this->saveStep();
            }
        }
    }

    protected $rules = [
        'detail_inspection_report_document_number' => 'required|string',
        'pass_quantity' => 'required|integer|min:0',
        'reject_quantity' => 'required|integer|min:0',
    ];

    public function updated($field): void
    {
        $this->validateOnly($field);
        $this->autoSave();
    }

    public function autoSave(): void
    {
        if ($this->pass_quantity !== null && $this->pass_quantity !== '' && $this->reject_quantity !== null && $this->reject_quantity !== '') {
            $data = $this->snapshot();
            session()->put("stepDetailSaved.judgements.{$this->periodKey}", $data);
            $this->sessionSaved = $data;
            $this->isSaved = true;
            $this->dispatch('judgementSaved')->to(StepDetail::class);
        }
    }

    protected function snapshot(): array
    {
        return [
            'detail_inspection_report_document_number' => $this->detail_inspection_report_document_number,
            'pass_quantity' => $this->pass_quantity,
            'reject_quantity' => $this->reject_quantity,
        ];
    }

    public function mount($detail_inspection_report_document_number = null): void
    {
        $this->detail_inspection_report_document_number = $detail_inspection_report_document_number;
        $this->periodKey = 'p' . session('stepDetailSaved.period', 1);

        $saved = session("stepDetailSaved.judgements.{$this->periodKey}", []);
        $this->sessionSaved = $saved;
        $this->savedAt = session("stepDetailSaved.judgements_meta.{$this->periodKey}.savedAt");
        $this->isSaved = ! empty($saved);

        if ($saved) {
            foreach ($saved as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
    }

    public function saveStep(): void
    {
        $this->validate();

        $data = $this->snapshot();

        session()->put("stepDetailSaved.judgements.{$this->periodKey}", $data);
        $this->savedAt = now()->toIso8601String();
        session()->put("stepDetailSaved.judgements_meta.{$this->periodKey}.savedAt", $this->savedAt);

        $this->sessionSaved = $data;
        $this->isSaved = true;

        $this->dispatch('toast', message: 'Judgement periode berhasil disimpan!');
        $this->dispatch('judgementSaved', savedAt: $this->savedAt);
        $this->dispatch('judgementSaved')->to(StepDetail::class);
    }

    public function resetStep(): void
    {
        $this->pass_quantity = null;
        $this->reject_quantity = null;

        $this->resetValidation();

        $this->forgetNestedKey('stepDetailSaved.judgements', $this->periodKey);
        $this->forgetNestedKey('stepDetailSaved.judgements_meta', $this->periodKey);

        $this->sessionSaved = [];
        $this->savedAt = null;
        $this->isSaved = false;

        $this->dispatch('toast', message: 'Judgement periode di-reset!');
        $this->dispatch('judgementReset');
        $this->dispatch('judgementReset')->to(StepDetail::class);
    }

    public function render(): View
    {
        return view('livewire.inspection.form.step-judgement');
    }
}
