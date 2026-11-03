<?php

namespace App\Livewire\Inspection\InspectionForm;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class StepDetail extends Component
{
    public $inspection_report_document_number;

    public $document_number;

    public int $period = 1;

    public $start_datetime;

    public $end_datetime;

    public $start_time;

    public $end_time;

    public $operator;

    public $inspector;

    public $shift;

    public $part_name;

    public $part_number;

    public string $periodKey = 'p1';

    public int $reloadToken = 0;

    public bool $savedDetails = false;

    public bool $savedFirst = false;

    public bool $savedDimensions = false;

    public bool $savedSecond = false;

    public bool $savedJudgement = false;

    protected $rules = [
        'inspection_report_document_number' => 'required|string',
        'document_number' => 'required|string',
        'period' => 'required|integer|min:1|max:4',
        'start_time' => 'required|date_format:H:i',
        'end_time' => 'required|date_format:H:i',
    ];

    protected $listeners = [
        'firstInspectionSaved' => 'refreshSavedFlags',
        'firstInspectionReset' => 'refreshSavedFlags',
        'dimensionsSaved' => 'refreshSavedFlags',
        'dimensionsReset' => 'refreshSavedFlags',
        'secondInspectionSaved' => 'refreshSavedFlags',
        'secondInspectionReset' => 'refreshSavedFlags',
        'samplingSaved' => 'refreshSavedFlags',
        'samplingReset' => 'refreshSavedFlags',
        'packagingSaved' => 'refreshSavedFlags',
        'packagingReset' => 'refreshSavedFlags',
        'judgementSaved' => 'refreshSavedFlags',
        'judgementReset' => 'refreshSavedFlags',
    ];

    public function updated($field): void
    {
        $this->validateOnly($field);
    }

    public function mount($inspection_report_document_number = null): void
    {
        $this->inspection_report_document_number = $inspection_report_document_number
            ?: session('stepHeaderSaved.document_number');

        $saved = session('stepDetailSaved');
        $this->period = (int) data_get($saved, 'period', 1);

        $this->generateDocumentNumber();

        if ($saved) {
            foreach ($saved as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
            if (! empty($saved['start_datetime'])) {
                $this->start_time = Carbon::parse($saved['start_datetime'])->format('H:i');
            }
            if (! empty($saved['end_datetime'])) {
                $this->end_time = Carbon::parse($saved['end_datetime'])->format('H:i');
            }
        }

        $this->shift = session('stepHeaderSaved.shift') ?? null;
        $this->operator = session('stepHeaderSaved.operator') ?? 'N/A';
        $this->inspector = session('stepHeaderSaved.inspector') ?? 'N/A';
        $this->part_name = session('stepHeaderSaved.part_name') ?? 'N/A';
        $this->part_number = session('stepHeaderSaved.part_number') ?? 'N/A';

        if ($this->shift) {
            $this->updatedPeriod();
        }
        $this->saveStep();
        $this->refreshSavedFlags();
    }

    public function updatedPeriod(): void
    {
        $shift = (int) ($this->shift ?? 1);
        $period = (int) ($this->period ?? 1);

        $shiftTimes = [
            1 => ['07:30', '15:30'],
            2 => ['15:30', '23:30'],
            3 => ['23:30', '07:30'],
        ];

        $periodOffset = $period - 1;
        [$shiftStart, $shiftEnd] = $shiftTimes[$shift] ?? ['07:30', '15:30'];

        $start = Carbon::createFromTimeString($shiftStart);
        $end = Carbon::createFromTimeString($shiftEnd);

        if ($shift === 3 && $end->lessThan($start)) {
            $end->addDay();
        }

        $startPeriod = $start->copy()->addHours($periodOffset * 2);
        $endPeriod = $startPeriod->copy()->addHours(2);

        if ($endPeriod->greaterThan($end)) {
            $endPeriod = $end;
        }

        $this->start_datetime = $startPeriod->format('Y-m-d H:i:s');
        $this->end_datetime = $endPeriod->format('Y-m-d H:i:s');
        $this->start_time = $startPeriod->format('H:i');
        $this->end_time = $endPeriod->format('H:i');
    }

    public function selectPeriod(int $q): void
    {
        $this->period = $q;
        $this->updatedPeriod();
        $this->generateDocumentNumber();
        $this->saveStep();
        $this->computeSaveFlags();
    }

    private function generateDocumentNumber(): void
    {
        $this->periodKey = $this->periodKey();
        if (session("stepDetailSaved.details.$this->periodKey.document_number")) {
            $this->document_number = session("stepDetailSaved.details.$this->periodKey.document_number");
        } else {
            $this->document_number = 'DETAIL-' . now()->format('Ymd-His') . '-' . strtoupper(Str::random(4));
        }
    }

    private function periodKey(?int $period = null): string
    {
        $p = $period ?? ($this->period ?? 1);

        return 'p' . $p;
    }

    public function computeSaveFlags(): void
    {
        $pk = $this->periodKey();
        $bag = session('stepDetailSaved', []);
        $this->savedDetails = ! empty(data_get($bag, "details.$pk"));
        $this->savedFirst = ! empty(data_get($bag, "first_inspections.$pk"));
        $this->savedDimensions = ! empty(data_get($bag, "dimensions.$pk"));
        $this->savedSecond = ! empty(data_get($bag, "second_inspections.$pk")) &&
            ! empty(data_get($bag, "samples.$pk")) &&
            ! empty(data_get($bag, "packagings.$pk"));
        $this->savedJudgement = ! empty(data_get($bag, "judgements.$pk"));
    }

    public function refreshSavedFlags(): void
    {
        $this->computeSaveFlags();
    }

    public function saveStep(): void
    {
        $this->validate();
        $data = session('stepDetailSaved', []);
        $data['shift'] = session('stepHeaderSaved.shift') ?? ($data['shift'] ?? null);
        $data['period'] = $this->period ?? ($data['period'] ?? 1);

        $this->periodKey = 'p' . $this->period;

        $data['details'][$this->periodKey] = [
            'document_number' => $this->document_number,
            'inspection_report_document_number' => $this->inspection_report_document_number,
            'period' => $this->period,
            'start_datetime' => $this->start_datetime,
            'end_datetime' => $this->end_datetime,
        ];

        session(['stepDetailSaved' => $data]);
        $this->reloadToken++;
        $this->computeSaveFlags();
    }

    public function resetStep(bool $clearOnlyCurrentPeriod = true): void
    {
        $saved = session('stepDetailSaved', []);
        $this->reset(['start_datetime', 'end_datetime', 'start_time', 'end_time']);

        if ($clearOnlyCurrentPeriod) {
            $pk = $this->periodKey($this->period);
            $sections = [
                'details',
                'first_inspections',
                'dimensions',
                'second_inspections',
                'samples',
                'packagings',
                'judgements',
            ];

            foreach ($sections as $section) {
                if (isset($saved[$section][$pk])) {
                    unset($saved[$section][$pk]);
                }
            }

            $saved['period'] = $this->period;
            session(['stepDetailSaved' => $saved]);
        } else {
            session()->forget('stepDetailSaved');
        }

        $this->updatedPeriod();
        $this->generateDocumentNumber();
        $this->reloadToken++;
        $this->dispatch('toast', message: 'Data periode berhasil di-reset!');
        $this->refreshSavedFlags();
    }

    public function render(): View
    {
        return view('livewire.inspection.form.step-detail');
    }
}
