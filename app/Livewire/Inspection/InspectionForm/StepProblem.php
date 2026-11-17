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
            'problems.*.remarks' => 'nullable|string|max:255',
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

        $this->shift = session('stepHeaderSaved.shift', null);
        $this->operator = session('stepHeaderSaved.operator', null);
        $this->inspector = session('stepHeaderSaved.inspector', null);
        $this->part_name = session('stepHeaderSaved.part_name', null);
        $this->part_number = session('stepHeaderSaved.part_number', null);

        $this->problems = session('stepProblemSaved', []);
        if (empty($this->problems)) {
            $periodProblems = session('stepDetailSaved.problems', []);
            $merged = [];
            foreach (range(1, 4) as $p) {
                $pk = 'p' . $p;
                $rows = $periodProblems[$pk] ?? [];
                if (is_array($rows)) {
                    foreach ($rows as $r) {
                        $merged[] = $r;
                    }
                }
            }
            if (! empty($merged)) {
                $this->problems = $merged;
            }
        }

        $this->sessionSaved = $this->problems;
        $this->savedAt = session('stepProblemSaved_meta.savedAt');
        $this->isSaved = true;

        foreach ($this->problems as &$problem) {
            if (empty($problem['inspection_report_document_number'])) {
                $problem['inspection_report_document_number'] = $this->inspection_report_document_number;
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
            'time' => now()->timezone('Asia/Jakarta')->format('H:i'),
            'cycle_time' => 15,
            'type' => 'MACHINE PROBLEM',
            'remarks' => '',
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

        $headerDoc = session('stepHeaderSaved.document_number');
        if ($headerDoc) {
            \App\Models\Inspection\InspectionProblem::where('inspection_report_document_number', $headerDoc)->delete();
            foreach ($this->problems as $p) {
                if (empty($p['type']) || empty($p['time'])) {
                    continue;
                }
                \App\Models\Inspection\InspectionProblem::create([
                    'inspection_report_document_number' => $headerDoc,
                    'type' => $p['type'],
                    'time' => $p['time'],
                    'cycle_time' => (int) $p['cycle_time'],
                    'remarks' => $p['remarks'] ?? null,
                ]);
            }
        }

        session()->put('stepProblemSaved', $this->problems);
        $this->savedAt = now()->toIso8601String();
        session()->put('stepProblemSaved_meta.savedAt', $this->savedAt);

        $this->sessionSaved = $this->problems;
        $this->buildBaselineMap();
        $this->isSaved = true;

        $this->dispatch('stepProblemSaved');
        $this->dispatch('nextStep');
        $this->dispatch('toast', message: 'Daftar kendala & loss time shift berhasil disimpan!');
    }

    public function resetStep(): void
    {
        $this->problems = [];
        $headerDoc = session('stepHeaderSaved.document_number');
        if ($headerDoc) {
            \App\Models\Inspection\InspectionProblem::where('inspection_report_document_number', $headerDoc)->delete();
        }

        session()->forget('stepProblemSaved');
        session()->forget('stepProblemSaved_meta');

        $this->sessionSaved = [];
        $this->baselineByKey = [];
        $this->savedAt = null;
        $this->isSaved = true;

        $this->resetValidation();

        $this->dispatch('problemsReset');
        $this->dispatch('toast', message: 'Daftar kendala shift di-reset!');
    }

    private function getShiftTimes(int $shift): array
    {
        $shifts = config('mes.shifts', []);
        $shiftData = $shifts[$shift] ?? null;

        if ($shiftData && ! empty($shiftData['start']) && ! empty($shiftData['end'])) {
            return [$shiftData['start'], $shiftData['end']];
        }

        return ['07:30', '15:30'];
    }

    public function getPeriodForTime(string $time): int
    {
        $shift = (int) ($this->shift ?? session('stepHeaderSaved.shift', 1));
        [$shiftStart, $shiftEnd] = $this->getShiftTimes($shift);

        $tz = config('mes.timezone', 'Asia/Jakarta');
        $start = \Carbon\Carbon::createFromTimeString($shiftStart, $tz);
        $end = \Carbon\Carbon::createFromTimeString($shiftEnd, $tz);

        if ($shift === 3 && $end->lessThan($start)) {
            $end->addDay();
        }

        $cleanTime = substr(trim($time), 0, 5);
        $target = \Carbon\Carbon::createFromTimeString($cleanTime ?: '07:30', $tz);
        if ($shift === 3 && $target->lessThan($start)) {
            $target->addDay();
        }

        for ($p = 1; $p <= 4; $p++) {
            $pStart = $start->copy()->addHours(($p - 1) * 2);
            $pEnd = ($p === 4) ? $end : $start->copy()->addHours($p * 2);
            if ($target->betweenIncluded($pStart, $pEnd)) {
                return $p;
            }
        }

        return 1;
    }

    public function render(): View
    {
        $shift = (int) ($this->shift ?? session('stepHeaderSaved.shift', 1));
        [$shiftStart, $shiftEnd] = $this->getShiftTimes($shift);

        $tz = config('mes.timezone', 'Asia/Jakarta');
        $start = \Carbon\Carbon::createFromTimeString($shiftStart, $tz);
        $end = \Carbon\Carbon::createFromTimeString($shiftEnd, $tz);
        if ($shift === 3 && $end->lessThan($start)) {
            $end->addDay();
        }

        $periodRecap = [];
        for ($p = 1; $p <= 4; $p++) {
            $offset = $p - 1;
            $pStart = $start->copy()->addHours($offset * 2);
            $pEnd = ($p === 4) ? $end : $pStart->copy()->addHours(2);
            if ($pEnd->greaterThan($end)) {
                $pEnd = $end;
            }

            $periodRecap[$p] = [
                'period' => $p,
                'time_range' => $pStart->format('H:i') . ' - ' . $pEnd->format('H:i'),
                'problems' => [],
                'loss_time' => 0,
            ];
        }

        foreach ($this->problems as $idx => $prob) {
            $pNum = $this->getPeriodForTime($prob['time'] ?? '07:30');
            $periodRecap[$pNum]['problems'][] = array_merge($prob, ['index' => $idx]);
            $periodRecap[$pNum]['loss_time'] += (int) ($prob['cycle_time'] ?? 0);
        }

        $totalProblems = count($this->problems);
        $totalLossTime = collect($this->problems)->sum(fn ($p) => (int) ($p['cycle_time'] ?? 0));

        return view('livewire.inspection.form.step-problem', [
            'periodRecap' => $periodRecap,
            'totalProblems' => $totalProblems,
            'totalLossTime' => $totalLossTime,
        ]);
    }
}
