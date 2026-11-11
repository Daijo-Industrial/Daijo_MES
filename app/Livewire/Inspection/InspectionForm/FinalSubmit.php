<?php

namespace App\Livewire\Inspection\InspectionForm;

use App\Models\Inspection\DetailInspectionReport;
use App\Models\Inspection\FirstInspection;
use App\Models\Inspection\InspectionDimension;
use App\Models\Inspection\InspectionJudgement;
use App\Models\Inspection\InspectionPackaging;
use App\Models\Inspection\InspectionProblem;
use App\Models\Inspection\InspectionQuantity;
use App\Models\Inspection\InspectionReport;
use App\Models\Inspection\InspectionSampling;
use App\Models\Inspection\SecondInspection;
use App\Services\PeriodValidator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class FinalSubmit extends Component
{
    public array $headerData = [];

    public array $detailData = [];

    public array $firstData = [];

    public array $dimensionData = [];

    public array $secondData = [];

    public array $samplingData = [];

    public array $packagingData = [];

    public array $judgementData = [];

    public array $problemData = [];

    public array $quantityData = [];

    public bool $hasHoles = false;

    public array $holeReport = [];

    public array $uiData = [];

    private const HIDE_KEY_SUBSTRINGS = ['row_key', 'document_number'];

    private function stripHiddenKeys(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                foreach (self::HIDE_KEY_SUBSTRINGS as $needle) {
                    if (Str::contains(Str::lower($key), $needle)) {
                        continue 2;
                    }
                }
            }
            $out[$key] = is_array($value) ? $this->stripHiddenKeys($value) : $value;
        }

        return $out;
    }

    protected function buildUiData(): void
    {
        $raw = [
            'Detail Inspection' => $this->detailData,
            'First Piece' => $this->firstData,
            'Dimensi' => $this->dimensionData,
            'Second Inspection' => $this->secondData,
            'Sampling Box' => $this->samplingData,
            'Packaging' => $this->packagingData,
            'Judgement' => $this->judgementData,
        ];

        $this->uiData = $this->stripHiddenKeys($raw);
    }

    public function mount(): void
    {
        $this->headerData = session('stepHeaderSaved', []);

        $processedDetailData = [];
        if (! empty(session('stepDetailSaved.details'))) {
            foreach (session('stepDetailSaved.details') as $periodKey => $rawData) {
                $periodNumber = (int) substr($periodKey, 1);
                $processedDetailData[$periodKey] = [
                    'inspection_report_document_number' => $rawData['inspection_report_document_number'] ?? ($this->headerData['document_number'] ?? ''),
                    'document_number' => $rawData['document_number'] ?? '',
                    'period' => $periodNumber,
                    'start_datetime' => $rawData['start_datetime'] ?? null,
                    'end_datetime' => $rawData['end_datetime'] ?? null,
                ];
            }
        }

        $this->detailData = $processedDetailData;
        $this->firstData = session('stepDetailSaved.first_inspections', []);
        $this->dimensionData = session('stepDetailSaved.dimensions', []);
        $this->secondData = session('stepDetailSaved.second_inspections', []);
        $this->samplingData = session('stepDetailSaved.samples', []);
        $this->packagingData = session('stepDetailSaved.packagings', []);
        $this->judgementData = session('stepDetailSaved.judgements', []);
        $this->problemData = session('stepProblemSaved', []);

        $totalOutput = collect($this->secondData)
            ->pluck('lot_size_quantity')
            ->filter()
            ->sum(fn ($v) => (int) $v);

        $totalPass = collect($this->judgementData)->pluck('pass_quantity')->sum(fn ($v) => (int) $v);
        $totalReject = collect($this->judgementData)->pluck('reject_quantity')->sum(fn ($v) => (int) $v);

        $totalSample = collect($this->samplingData)
            ->flatMap(fn ($rows) => $rows)
            ->pluck('quantity')
            ->sum(fn ($v) => (int) $v);

        $totalNgSample = collect($this->samplingData)
            ->flatMap(fn ($rows) => $rows)
            ->pluck('ng_quantity')
            ->sum(fn ($v) => (int) $v);

        $passRate = $totalOutput ? round(($totalPass / $totalOutput) * 100, 2) : 0;
        $rejectRate = $totalOutput ? round(($totalReject / $totalOutput) * 100, 2) : 0;
        $ngSampleRate = $totalSample ? round(($totalNgSample / $totalSample) * 100, 2) : 0;

        $this->quantityData = [
            'total_output' => $totalOutput,
            'total_pass' => $totalPass,
            'total_reject' => $totalReject,
            'total_sample' => $totalSample,
            'total_ng_sample' => $totalNgSample,
            'pass_rate' => $passRate,
            'reject_rate' => $rejectRate,
            'ng_sample_rate' => $ngSampleRate,
        ];

        $this->holeReport = $this->computeHoleReport();
        $present = collect($this->detailData)
            ->keys()
            ->map(fn ($k) => (int) substr($k, 1));

        $this->hasHoles = collect($this->holeReport)->some(
            fn ($missingList) => collect($missingList)->intersect($present)->isNotEmpty()
        );

        $this->buildUiData();
    }

    protected function computeHoleReport(): array
    {
        $payload = [
            'first_inspections' => session('stepDetailSaved.first_inspections'),
            'second_inspections' => session('stepDetailSaved.second_inspections'),
            'samples' => session('stepDetailSaved.samples'),
            'packagings' => session('stepDetailSaved.packagings'),
            'judgements' => session('stepDetailSaved.judgements'),
        ];

        return PeriodValidator::missing($payload);
    }

    protected function splitCompletePeriods(): array
    {
        $allP = collect($this->detailData)
            ->keys()
            ->map(fn ($k) => (int) substr($k, 1))
            ->unique()
            ->sort()
            ->values();

        $required = [
            'detailData',
            'firstData',
            'secondData',
            'samplingData',
            'packagingData',
            'judgementData',
        ];

        $complete = [];
        $incomplete = [];

        foreach ($allP as $p) {
            $hasAll = collect($required)->every(function ($prop) use ($p) {
                return isset($this->{$prop}["p{$p}"]) && ! empty($this->{$prop}["p{$p}"]);
            });

            if ($hasAll) {
                $complete[] = $p;
            } else {
                $incomplete[] = $p;
            }
        }

        return [$complete, $incomplete];
    }

    public function submit()
    {
        [$completeP, $incompleteP] = $this->splitCompletePeriods();

        if (empty($completeP)) {
            $this->dispatch(
                'toast',
                message: 'Tidak ada periode yang lengkap terisi. Minimal satu periode harus lengkap.',
                type: 'error'
            );
            return;
        }

        foreach ([
            'detailData',
            'firstData',
            'dimensionData',
            'secondData',
            'samplingData',
            'packagingData',
            'judgementData',
        ] as $prop) {
            $this->{$prop} = collect($this->{$prop})
                ->only(array_map(fn ($p) => "p{$p}", $completeP))
                ->all();
        }

        DB::transaction(function () {
            InspectionReport::create($this->headerData);

            foreach ($this->detailData as $data) {
                DetailInspectionReport::create($data);
            }

            foreach ($this->firstData as $data) {
                FirstInspection::create($data);
            }

            foreach ($this->dimensionData as $value) {
                foreach ($value as $data) {
                    InspectionDimension::create($data);
                }
            }

            foreach ($this->secondData as $data) {
                SecondInspection::create($data);
            }

            foreach ($this->samplingData as $value) {
                foreach ($value as $data) {
                    InspectionSampling::create($data);
                }
            }

            foreach ($this->packagingData as $value) {
                foreach ($value as $data) {
                    InspectionPackaging::create($data);
                }
            }

            foreach ($this->judgementData as $data) {
                InspectionJudgement::create($data);
            }

            foreach ($this->problemData as $data) {
                InspectionProblem::create($data);
            }

            $quantityData = $this->quantityData;
            InspectionQuantity::create([
                'inspection_report_document_number' => $this->headerData['document_number'],
                'output_quantity' => $quantityData['total_output'],
                'pass_quantity' => $quantityData['total_pass'],
                'reject_quantity' => $quantityData['total_reject'],
                'sampling_quantity' => $quantityData['total_sample'],
                'ng_sample_quantity' => $quantityData['total_ng_sample'],
                'pass_rate' => $quantityData['pass_rate'],
                'reject_rate' => $quantityData['reject_rate'],
                'ng_sample_rate' => $quantityData['ng_sample_rate'],
            ]);
        });

        session()->forget([
            'stepHeaderSaved',
            'stepHeaderSavedAt',
            'stepHeaderSavedHash',
            'stepDetailSaved',
            'stepProblemSaved',
            'stepProblemSaved_meta',
            'lastStepVisited',
        ]);

        session()->flash('success', 'Laporan IPQC berhasil disimpan ke sistem MES!');
        return redirect()->route('inspection-reports.index');
    }

    public function render(): View
    {
        return view('livewire.inspection.form.final-submit');
    }
}
