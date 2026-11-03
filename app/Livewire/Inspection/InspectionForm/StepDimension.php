<?php

namespace App\Livewire\Inspection\InspectionForm;

use App\Models\File;
use App\Services\FileService;
use App\Traits\ClearsNestedSession;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class StepDimension extends Component
{
    use ClearsNestedSession, WithFileUploads;

    public array $dimensions = [];

    public $start_time;

    public $end_time;

    public $inspection_report_document_number;

    public $files = [];

    public $newAttachments = [];

    public string $periodKey = 'p1';

    public array $sessionSaved = [];

    public array $baselineByKey = [];

    public ?string $savedAt = null;

    public bool $isSaved = false;

    protected function norm($v)
    {
        return $v === '' ? null : $v;
    }

    protected function ensureRowKeys(): void
    {
        foreach ($this->dimensions as $i => $row) {
            if (! isset($row['row_key']) || ! $row['row_key']) {
                $this->dimensions[$i]['row_key'] = (string) Str::uuid();
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

    protected function rules(): array
    {
        $rules = [
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
        ];

        foreach ($this->dimensions as $i => $row) {
            $rules["dimensions.$i.inspection_report_document_number"] = 'required|string';
            $rules["dimensions.$i.lower_limit"] = 'required|numeric';
            $rules["dimensions.$i.upper_limit"] = [
                'required',
                'numeric',
                "gt:dimensions.$i.lower_limit",
            ];
            $rules["dimensions.$i.limit_uom"] = 'required|string';
            $rules["dimensions.$i.judgement"] = 'required|in:OK,NG';
            $rules["dimensions.$i.area"] = 'required|string';
            $rules["dimensions.$i.remarks"] = [
                'nullable',
                'string',
                Rule::requiredIf(($row['judgement'] ?? '') === 'NG'),
            ];

            $lower = $row['lower_limit'] ?? null;
            $upper = $row['upper_limit'] ?? null;
            $actualRules = ['required', 'numeric'];
            if (($row['judgement'] ?? '') === 'OK' && is_numeric($lower) && is_numeric($upper)) {
                $actualRules[] = "between:$lower,$upper";
            }
            $rules["dimensions.$i.actual_value"] = $actualRules;
        }

        return $rules;
    }

    public function mount($inspection_report_document_number = null): void
    {
        $this->inspection_report_document_number = $inspection_report_document_number
            ?: session('stepHeaderSaved.document_number');
        $this->periodKey = 'p' . session('stepDetailSaved.period', 1);

        $this->dimensions = session("stepDetailSaved.dimensions.{$this->periodKey}", []);
        $this->sessionSaved = session("stepDetailSaved.dimensions.{$this->periodKey}", []);
        $this->savedAt = session("stepDetailSaved.dimensions_meta.{$this->periodKey}.savedAt");
        $this->isSaved = ! empty($this->dimensions);

        $this->ensureRowKeys();
        $this->buildBaselineMap();

        if ($this->dimensions) {
            $first = $this->dimensions[0];
            if (! empty($first['start_datetime'])) {
                $this->start_time = Carbon::parse($first['start_datetime'])->format('H:i');
            }
            if (! empty($first['end_datetime'])) {
                $this->end_time = Carbon::parse($first['end_datetime'])->format('H:i');
            }
        }

        if (empty($this->start_time)) {
            $period = session('stepDetailSaved.period', 1);
            $st = session("stepDetailSaved.details.p{$period}.start_datetime");
            $this->start_time = $st ? Carbon::parse($st)->format('H:i') : '07:30';
        }
        if (empty($this->end_time)) {
            $period = session('stepDetailSaved.period', 1);
            $et = session("stepDetailSaved.details.p{$period}.end_datetime");
            $this->end_time = $et ? Carbon::parse($et)->format('H:i') : '09:30';
        }

        $this->loadFiles();
    }

    public function loadFiles(): void
    {
        if (class_exists(File::class) && $this->inspection_report_document_number) {
            $this->files = File::where('item_code', $this->inspection_report_document_number)->get();
        }
    }

    public function addDimension(): void
    {
        $this->dimensions[] = [
            'row_key' => (string) Str::uuid(),
            'inspection_report_document_number' => $this->inspection_report_document_number,
            'lower_limit' => '',
            'upper_limit' => '',
            'limit_uom' => 'mm',
            'actual_value' => '',
            'judgement' => 'OK',
            'area' => '',
            'remarks' => '',
        ];
    }

    public function removeDimension($index): void
    {
        unset($this->dimensions[$index]);
        $this->dimensions = array_values($this->dimensions);
    }

    public function updated($property, $value = null): void
    {
        $this->validateOnly($property);

        if (Str::endsWith($property, '.judgement')) {
            $index = (int) Str::between($property, 'dimensions.', '.judgement');
            if ($value !== 'NG') {
                $this->dimensions[$index]['remarks'] = '';
            }
        }
    }

    public function saveStep(): void
    {
        if (! empty($this->dimensions)) {
            $this->validate();

            foreach ($this->dimensions as $i => $row) {
                $this->dimensions[$i]['start_datetime'] = Carbon::parse($this->start_time)->format('Y-m-d H:i:s');
                $this->dimensions[$i]['end_datetime'] = Carbon::parse($this->end_time)->format('Y-m-d H:i:s');
                if ($this->dimensions[$i]['judgement'] === 'OK') {
                    unset($this->dimensions[$i]['remarks']);
                }
            }
        }

        session()->put("stepDetailSaved.dimensions.{$this->periodKey}", $this->dimensions);
        $this->savedAt = now()->toIso8601String();
        session()->put("stepDetailSaved.dimensions_meta.{$this->periodKey}.savedAt", $this->savedAt);

        $this->sessionSaved = $this->dimensions;
        $this->buildBaselineMap();
        $this->isSaved = ! empty($this->dimensions);

        $this->dispatch('toast', message: 'Dimension measurements berhasil disimpan!');
        $this->dispatch('dimensionsSaved', savedAt: $this->savedAt);
        $this->dispatch('dimensionsSaved')->to(StepDetail::class);
    }

    public function resetStep(): void
    {
        $this->dimensions = [];
        $this->resetValidation();

        $this->forgetNestedKey('stepDetailSaved.dimensions', $this->periodKey);
        $this->forgetNestedKey('stepDetailSaved.dimensions_meta', $this->periodKey);

        $this->sessionSaved = [];
        $this->baselineByKey = [];
        $this->savedAt = null;
        $this->isSaved = false;

        $this->dispatch('toast', message: 'Dimension measurements di-reset!');
        $this->dispatch('dimensionsReset');
        $this->dispatch('dimensionsReset')->to(StepDetail::class);
    }

    public function render(): View
    {
        return view('livewire.inspection.form.step-dimension');
    }
}
