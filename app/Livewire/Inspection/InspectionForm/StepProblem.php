<?php

namespace App\Livewire\Inspection\InspectionForm;

use App\Traits\ClearsNestedSession;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class StepProblem extends Component
{
    use ClearsNestedSession;

    public $inspection_report_document_number;

    public array $problems = [];

    public array $types = [];

    public $shift;

    public $operator;

    public $inspector;

    public $part_name;

    public $part_number;

    public array $sessionSaved = [];

    public array $baselineByKey = [];

    public ?string $savedAt = null;

    public bool $isSaved = false;

    protected function rules(): array
    {
        return [
            'problems.*.inspection_report_document_number' => 'required|string',
            'problems.*.type' => ['required', 'string', Rule::in($this->types)],
            'problems.*.time' => 'required|date_format:H:i',
            'problems.*.cycle_time' => 'required|integer|min:1',
            'problems.*.remarks' => 'required|string|max:255',
        ];
    }

    protected function ensureRowKeys(): void
    {
        foreach ($this->problems as $i => $row) {
            if (! isset($row['row_key']) || ! $row['row_key']) {
                $this->problems[$i]['row_key'] = (string) Str::uuid();
            }
        }
    }

    protected function buildBaselineMap(): void
    {
        $this->baselineByKey = [];
        foreach ($this->sessionSaved as $row) {
            if (isset($row['row_key'])) {
                $this->baselineByKey[$row['row_key']] = $row;
            }
        }
    }

    public function mount($inspection_report_document_number = null): void
    {
        $this->types = [
            'NO PROBLEM',
            'PART PROBLEM',
            'MOLD PROBLEM',
            'MACHINE PROBLEM',
            'TOOLS PROBLEM',
            'MATERIAL PROBLEM',
            'METHOD PROBLEM',
            'MAN PROBLEM',
        ];

        $this->inspection_report_document_number = $inspection_report_document_number
            ?: session('stepHeaderSaved.document_number');

        $this->shift = session('stepHeaderSaved.shift', null);
        $this->operator = session('stepHeaderSaved.operator', null);
        $this->inspector = session('stepHeaderSaved.inspector', null);
        $this->part_name = session('stepHeaderSaved.part_name', null);
        $this->part_number = session('stepHeaderSaved.part_number', null);

        $this->problems = session('stepProblemSaved', []);
        $this->sessionSaved = session('stepProblemSaved', []);
        $this->savedAt = session('stepProblemSaved_meta.savedAt');
        $this->isSaved = ! empty($this->problems);

        if (empty($this->problems)) {
            $this->addProblem();
        } else {
            foreach ($this->problems as &$problem) {
                if (empty($problem['inspection_report_document_number'])) {
                    $problem['inspection_report_document_number'] = $this->inspection_report_document_number;
                }
            }
        }

        $this->ensureRowKeys();
        $this->buildBaselineMap();
    }

    public function addProblem(): void
    {
        $this->problems[] = [
            'row_key' => (string) Str::uuid(),
            'inspection_report_document_number' => $this->inspection_report_document_number,
            'time' => now()->format('H:i'),
            'cycle_time' => 60,
            'type' => 'NO PROBLEM',
            'remarks' => 'Normal production',
        ];
    }

    public function removeProblem($index): void
    {
        unset($this->problems[$index]);
        $this->problems = array_values($this->problems);
    }

    public function updated($field): void
    {
        $this->validateOnly($field);
    }

    public function saveStep(): void
    {
        $this->validate();

        session()->put('stepProblemSaved', $this->problems);
        $this->savedAt = now()->toIso8601String();
        session()->put('stepProblemSaved_meta.savedAt', $this->savedAt);

        $this->sessionSaved = $this->problems;
        $this->buildBaselineMap();
        $this->isSaved = true;

        $this->dispatch('stepProblemSaved');
        $this->dispatch('nextStep');
        $this->dispatch('toast', message: 'Daftar problem & loss time shift berhasil disimpan!');
    }

    public function resetStep(): void
    {
        $this->problems = [];
        session()->forget('stepProblemSaved');
        session()->forget('stepProblemSaved_meta');

        $this->sessionSaved = [];
        $this->baselineByKey = [];
        $this->savedAt = null;
        $this->isSaved = false;

        $this->addProblem();
        $this->resetValidation();

        $this->dispatch('problemsReset');
        $this->dispatch('toast', message: 'Daftar problem di-reset!');
    }

    public function render(): View
    {
        return view('livewire.inspection.form.step-problem');
    }
}
