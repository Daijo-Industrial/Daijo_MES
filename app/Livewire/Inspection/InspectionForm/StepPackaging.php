<?php

namespace App\Livewire\Inspection\InspectionForm;

use App\Traits\ClearsNestedSession;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class StepPackaging extends Component
{
    use ClearsNestedSession;

    public $second_inspection_document_number;

    public array $packagings = [];

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
            if (! empty($this->packagings)) {
                $this->saveStep();
            }
        }
    }

    protected $rules = [
        'packagings.*.second_inspection_document_number' => 'required|string',
        'packagings.*.snp' => 'required|integer|min:1',
        'packagings.*.box_label' => 'required|string',
        'packagings.*.judgement' => 'required|in:OK,NG',
        'packagings.*.remarks' => 'nullable|string|required_if:packagings.*.judgement,NG',
    ];

    protected function ensureRowKeys(): void
    {
        foreach ($this->packagings as $i => $row) {
            if (empty($row['row_key'])) {
                $this->packagings[$i]['row_key'] = (string) Str::uuid();
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

        $this->packagings = session("stepDetailSaved.packagings.{$this->periodKey}", []);
        $this->sessionSaved = session("stepDetailSaved.packagings.{$this->periodKey}", []);
        $this->savedAt = session("stepDetailSaved.packagings_meta.{$this->periodKey}.savedAt");
        $this->isSaved = ! empty($this->packagings);

        if (empty($this->packagings)) {
            $this->addPackaging();
        } else {
            foreach ($this->packagings as &$item) {
                if (empty($item['second_inspection_document_number'])) {
                    $item['second_inspection_document_number'] = $this->second_inspection_document_number;
                }
            }
        }

        $this->ensureRowKeys();
        $this->buildBaselineMap();
    }

    public function addPackaging(): void
    {
        $this->packagings[] = [
            'row_key' => (string) Str::uuid(),
            'second_inspection_document_number' => $this->second_inspection_document_number,
            'snp' => '',
            'box_label' => '',
            'judgement' => 'OK',
            'remarks' => '',
        ];
    }

    public function removePackaging($index): void
    {
        unset($this->packagings[$index]);
        $this->packagings = array_values($this->packagings);
    }

    public function updated($property, $value = null): void
    {
        $this->validateOnly($property);

        if (Str::endsWith($property, '.judgement')) {
            $index = (int) Str::between($property, 'packagings.', '.judgement');
            if ($value === 'OK') {
                unset($this->packagings[$index]['remarks']);
            }
        }
    }

    public function saveStep(): void
    {
        $this->validate();

        session()->put("stepDetailSaved.packagings.{$this->periodKey}", $this->packagings);
        $this->savedAt = now()->toIso8601String();
        session()->put("stepDetailSaved.packagings_meta.{$this->periodKey}.savedAt", $this->savedAt);

        $this->sessionSaved = $this->packagings;
        $this->buildBaselineMap();
        $this->isSaved = ! empty($this->packagings);

        $this->dispatch('packagingSaved', savedAt: $this->savedAt);
        $this->dispatch('toast', message: 'Packaging check berhasil disimpan!');
    }

    public function resetStep(): void
    {
        $this->packagings = [];
        $this->forgetNestedKey('stepDetailSaved.packagings', $this->periodKey);
        $this->forgetNestedKey('stepDetailSaved.packagings_meta', $this->periodKey);
        $this->resetValidation();
        $this->addPackaging();

        $this->sessionSaved = [];
        $this->baselineKey = [];
        $this->savedAt = null;
        $this->isSaved = false;

        $this->dispatch('packagingReset');
        $this->dispatch('toast', message: 'Packaging check di-reset!');
    }

    public function render(): View
    {
        return view('livewire.inspection.form.step-packaging');
    }
}
