<?php

namespace App\Livewire\Inspection\InspectionForm;

use App\Models\Inspection\SecondInspection;
use App\Traits\ClearsNestedSession;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class StepSecond extends Component
{
    use ClearsNestedSession;

    public $detail_inspection_report_document_number;

    public $document_number;

    public $lot_size_quantity;

    public bool $skipLotSize = false;

    public bool $secondInspectionSaved = false;

    public string $periodKey = 'p1';

    public array $sessionSaved = [];

    public ?string $savedAt = null;

    public bool $isSaved = false;

    public bool $savedSamples = false;

    public bool $savedPackagings = false;

    protected $listeners = [
        'samplingSaved' => 'refreshSavedFlags',
        'samplingReset' => 'refreshSavedFlags',
        'packagingSaved' => 'refreshSavedFlags',
        'packagingReset' => 'refreshSavedFlags',
        'triggerSubcomponentSave' => 'handleSaveTrigger',
    ];

    public function handleSaveTrigger(?string $periodKey = null): void
    {
        if ($periodKey === null || $periodKey === $this->periodKey) {
            if ($this->skipLotSize || ! empty($this->lot_size_quantity)) {
                $this->saveStep();
            }
        }
    }

    protected function rules(): array
    {
        return [
            'detail_inspection_report_document_number' => 'required|string',
            'document_number' => 'required|string',
            'lot_size_quantity' => [
                'nullable',
                'numeric',
                'min:1',
                Rule::requiredIf(fn () => ! $this->skipLotSize),
            ],
            'skipLotSize' => 'boolean',
        ];
    }

    public function computeSaveFlags(): void
    {
        $pk = $this->periodKey;
        $bag = session('stepDetailSaved', []);
        $this->savedSamples = ! empty(data_get($bag, "samples.$pk"));
        $this->savedPackagings = ! empty(data_get($bag, "packagings.$pk"));
    }

    public function refreshSavedFlags(): void
    {
        $this->computeSaveFlags();
    }

    public function updated($field): void
    {
        $this->validateOnly($field);
    }

    public function mount($detail_inspection_report_document_number = null): void
    {
        $this->detail_inspection_report_document_number = $detail_inspection_report_document_number;
        $this->periodKey = 'p' . session('stepDetailSaved.period', 1);

        $saved = session("stepDetailSaved.second_inspections.{$this->periodKey}", []);
        $this->sessionSaved = $saved;
        $this->savedAt = session("stepDetailSaved.second_inspections_meta.{$this->periodKey}.savedAt");
        $this->isSaved = ! empty($saved);

        if ($saved) {
            foreach ($saved as $k => $v) {
                if (property_exists($this, $k)) {
                    $this->$k = $v;
                }
            }
            $this->secondInspectionSaved = true;
        }

        if (! $this->document_number) {
            $this->document_number = 'SECOND-' . now()->format('Ymd-His') . '-' . strtoupper(Str::random(4));
        }

        $this->refreshSavedFlags();
    }

    public function saveStep(): void
    {
        $this->validate();

        $data = [
            'detail_inspection_report_document_number' => $this->detail_inspection_report_document_number,
            'document_number' => $this->document_number,
            'lot_size_quantity' => $this->skipLotSize ? null : $this->lot_size_quantity,
            'skipLotSize' => $this->skipLotSize,
        ];

        session()->put("stepDetailSaved.second_inspections.{$this->periodKey}", $data);
        $this->savedAt = now()->toIso8601String();
        session()->put("stepDetailSaved.second_inspections_meta.{$this->periodKey}.savedAt", $this->savedAt);

        $this->sessionSaved = $data;
        $this->isSaved = true;
        $this->secondInspectionSaved = true;

        $this->refreshSavedFlags();

        $this->dispatch('toast', message: 'Second inspection lot size berhasil disimpan!');
        $this->dispatch('secondInspectionSaved')->to(StepDetail::class);
        $this->dispatch('secondInspectionSaved', savedAt: $this->savedAt);
    }

    public function resetStep(): void
    {
        $this->forgetNestedKey('stepDetailSaved.second_inspections', $this->periodKey);
        $this->forgetNestedKey('stepDetailSaved.second_inspections_meta', $this->periodKey);

        $this->reset(['lot_size_quantity', 'skipLotSize']);
        $this->secondInspectionSaved = false;

        $this->sessionSaved = [];
        $this->savedAt = null;
        $this->isSaved = false;

        $this->refreshSavedFlags();

        $this->dispatch('secondInspectionReset');
        $this->dispatch('secondInspectionReset')->to(StepDetail::class);
        $this->dispatch('toast', message: 'Second inspection di-reset!');
    }

    public function render(): View
    {
        return view('livewire.inspection.form.step-second');
    }
}
