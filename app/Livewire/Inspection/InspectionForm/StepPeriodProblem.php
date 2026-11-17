<?php

namespace App\Livewire\Inspection\InspectionForm;

use App\Traits\ClearsNestedSession;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class StepPeriodProblem extends Component
{
    use ClearsNestedSession;

    public $inspection_report_document_number;

    public string $periodKey = 'p1';

    public int $period = 1;

    public $start_time = '07:30';

    public $end_time = '09:30';

    public array $problems = [];

    public array $types = [];

    public array $sessionSaved = [];

    public bool $isSaved = true;

    protected $listeners = [
        'triggerSubcomponentSave' => 'handleSaveTrigger',
    ];

    public function handleSaveTrigger(?string $periodKey = null): void
    {
        if ($periodKey === null || $periodKey === $this->periodKey) {
            $this->autoSave();
        }
    }

    protected function rules(): array
    {
        return [
            'problems.*.inspection_report_document_number' => 'required|string',
            'problems.*.type' => ['required', 'string', Rule::in($this->types)],
            'problems.*.time' => ['required', 'date_format:H:i'],
            'problems.*.cycle_time' => ['required', 'integer', 'min:1'],
            'problems.*.remarks' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function mount(
        $inspection_report_document_number = null,
        $period = 1,
        $start_time = null,
        $end_time = null
    ): void {
        $this->types = [
            'PART PROBLEM',
            'MOLD PROBLEM',
            'MACHINE PROBLEM',
            'TOOLS PROBLEM',
            'MATERIAL PROBLEM',
            'METHOD PROBLEM',
            'MAN PROBLEM',
            'OTHER PROBLEM',
        ];

        $this->inspection_report_document_number = $inspection_report_document_number
            ?: session('stepHeaderSaved.document_number');

        $this->period = (int) $period;
        $this->periodKey = 'p' . $this->period;

        if ($start_time) {
            $this->start_time = $start_time;
        } else {
            $st = session("stepDetailSaved.details.{$this->periodKey}.start_datetime");
            $this->start_time = $st ? Carbon::parse($st)->format('H:i') : '07:30';
        }

        if ($end_time) {
            $this->end_time = $end_time;
        } else {
            $et = session("stepDetailSaved.details.{$this->periodKey}.end_datetime");
            $this->end_time = $et ? Carbon::parse($et)->format('H:i') : '09:30';
        }

        $this->problems = session("stepDetailSaved.problems.{$this->periodKey}", []);
        $this->sessionSaved = $this->problems;
        $this->isSaved = true;
    }

    public function isTimeWithinPeriod(string $time): bool
    {
        if (empty($this->start_time) || empty($this->end_time)) {
            return true;
        }

        $t = strtotime($time);
        $start = strtotime($this->start_time);
        $end = strtotime($this->end_time);

        if ($end < $start) {
            // Cross-midnight period (e.g. 23:00 to 01:00)
            return $t >= $start || $t <= $end;
        }

        return $t >= $start && $t <= $end;
    }

    public function addProblem(): void
    {
        $now = now()->format('H:i');
        $initialTime = $this->isTimeWithinPeriod($now) ? $now : $this->start_time;

        $this->problems[] = [
            'row_key' => (string) Str::uuid(),
            'inspection_report_document_number' => $this->inspection_report_document_number,
            'type' => 'MACHINE PROBLEM',
            'time' => $initialTime,
            'cycle_time' => 15,
            'remarks' => '',
        ];

        $this->autoSave();
    }

    public function removeProblem($index): void
    {
        unset($this->problems[$index]);
        $this->problems = array_values($this->problems);
        $this->autoSave();
    }

    public function updated($field): void
    {
        $this->validateOnly($field);
        $this->autoSave();
    }

    public function autoSave(): void
    {
        session()->put("stepDetailSaved.problems.{$this->periodKey}", $this->problems);
        $this->sessionSaved = $this->problems;
        $this->isSaved = true;
        $this->dispatch('periodProblemsSaved', periodKey: $this->periodKey)->to(StepDetail::class);
    }

    public function render(): View
    {
        return view('livewire.inspection.form.step-period-problem');
    }
}
