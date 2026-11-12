<?php

namespace App\Livewire\Inspection\InspectionForm;

use App\Traits\ClearsNestedSession;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class StepSampling extends Component
{
    use ClearsNestedSession;

    public array $samples = [];

    public $second_inspection_document_number;

    public string $periodKey = 'p1';

    public array $sessionSaved = [];

    public array $baselineKey = [];

    public ?string $savedAt = null;

    public bool $isSaved = false;

    protected $listeners = [
        'triggerSubcomponentSave' => 'handleSaveTrigger',
    ];

    public function handleSaveTrigger(?string $periodKey = null): void
    {
        if ($periodKey === null || $periodKey === $this->periodKey) {
            if (! empty($this->samples)) {
                $this->saveStep();
            }
        }
    }

    protected function rules(): array
    {
        $rules = [
            'samples.*.second_inspection_document_number' => 'required|string',
            'samples.*.quantity' => 'required|integer|min:1',
            'samples.*.box_label' => 'required|string',
            'samples.*.appearance' => 'required|string|in:OK,NG',
        ];

        foreach ($this->samples as $i => $row) {
            $isNG = ($row['appearance'] ?? '') === 'NG';

            $rules["samples.$i.ng_quantity"] = [
                $isNG ? 'required' : 'nullable',
                'integer',
                'min:1',
                "lte:samples.$i.quantity",
            ];

            $rules["samples.$i.remarks"] = [$isNG ? 'required' : 'nullable', 'string'];
        }

        return $rules;
    }

    protected function ensureRowKeys(): void
    {
        foreach ($this->samples as $i => $row) {
            if (empty($row['row_key'])) {
                $this->samples[$i]['row_key'] = (string) Str::uuid();
            }
        }
    }

    protected function buildBaselineMap(): void
    {
        $this->baselineKey = [];
        foreach ($this->sessionSaved as $row) {
            if (! empty($row['row_key'])) {
                $this->baselineKey[$row['row_key']] = $row;
            }
        }
    }

    public function mount($second_inspection_document_number = null): void
    {
        $this->second_inspection_document_number = $second_inspection_document_number;
        $this->periodKey = 'p' . session('stepDetailSaved.period', 1);

        $this->samples = session("stepDetailSaved.samples.{$this->periodKey}", []);
        $this->sessionSaved = session("stepDetailSaved.samples.{$this->periodKey}", []);
        $this->savedAt = session("stepDetailSaved.samples_meta.{$this->periodKey}.savedAt");
        $this->isSaved = ! empty($this->savedAt);

        if (empty($this->samples)) {
            $this->addSample();
        } else {
            foreach ($this->samples as &$sample) {
                $sample['second_inspection_document_number'] = $this->second_inspection_document_number;
            }
        }
        $this->ensureRowKeys();
        $this->buildBaselineMap();
    }

    public function addSample(): void
    {
        $this->samples[] = [
            'row_key' => (string) Str::uuid(),
            'second_inspection_document_number' => $this->second_inspection_document_number,
            'quantity' => '',
            'box_label' => '',
            'appearance' => 'OK',
            'ng_quantity' => null,
            'remarks' => '',
        ];
    }

    public function removeSample($index): void
    {
        unset($this->samples[$index]);
        $this->samples = array_values($this->samples);
    }

    public function updated($property, $value = null): void
    {
        $this->validateOnly($property);

        if (Str::endsWith($property, '.appearance')) {
            $index = (int) Str::between($property, 'samples.', '.appearance');
            if ($value === 'OK') {
                unset($this->samples[$index]['ng_quantity'], $this->samples[$index]['remarks']);
            }
        }
    }

    public function saveStep(): void
    {
        $this->validate();

        session()->put("stepDetailSaved.samples.{$this->periodKey}", $this->samples);
        $this->savedAt = now()->toIso8601String();
        session()->put("stepDetailSaved.samples_meta.{$this->periodKey}.savedAt", $this->savedAt);

        $this->sessionSaved = $this->samples;
        $this->buildBaselineMap();
        $this->isSaved = ! empty($this->samples);

        $this->dispatch('samplingSaved', savedAt: $this->savedAt);
        $this->dispatch('toast', message: 'Sampling data berhasil disimpan!');
    }

    public function resetStep(): void
    {
        $this->samples = [];
        $this->resetValidation();
        $this->addSample();

        $this->forgetNestedKey('stepDetailSaved.samples', $this->periodKey);
        $this->forgetNestedKey('stepDetailSaved.samples_meta', $this->periodKey);

        $this->sessionSaved = [];
        $this->baselineKey = [];
        $this->savedAt = null;
        $this->isSaved = false;

        $this->dispatch('samplingReset');
        $this->dispatch('toast', message: 'Sampling data di-reset!');
    }

    public function render(): View
    {
        return view('livewire.inspection.form.step-sampling');
    }
}
