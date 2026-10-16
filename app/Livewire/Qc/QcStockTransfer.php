<?php

namespace App\Livewire\Qc;

use App\Models\QcTransferLog;
use App\Services\QcTransferService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class QcStockTransfer extends Component
{
    use WithPagination;

    public string $plant = 'karawang'; // 'karawang' (KRFFI) or 'kbn' (FFI)

    public string $filterDate = '';

    public string $filterSpk = '';

    public string $filterItemCode = '';

    public string $filterWarehouse = '';

    public string $filterQcStatus = 'pending'; // 'pending' (0,2), 'completed' (1), 'all'

    public int $perPage = 25;

    public array $expandedRows = [];

    public array $rowDetails = [];

    public array $ngInputs = []; // [scanned_data_id => ng_qty]

    public array $remarksInputs = []; // [scanned_data_id => remarks]

    public array $processingRows = []; // [id => true]

    public function mount(): void
    {
        Carbon::setLocale('id');
        // Keep filterDate empty by default so all pending QC items display
        $this->filterDate = '';
    }

    public function updatingFilterDate(): void
    {
        $this->resetPage();
    }

    public function updatingFilterSpk(): void
    {
        $this->resetPage();
    }

    public function updatingFilterItemCode(): void
    {
        $this->resetPage();
    }

    public function updatingFilterWarehouse(): void
    {
        $this->resetPage();
    }

    public function updatingFilterQcStatus(): void
    {
        $this->resetPage();
    }

    protected function baseQuery()
    {
        $targetWarehouse = ($this->plant === 'kbn') ? 'FFI' : 'KRFFI';

        return DB::table('production_summary')
            ->where('production_summary.sap_sent', 1) // Must be receipted to SAP first
            ->where('production_summary.warehouse', $targetWarehouse)
            ->when($this->filterDate, fn ($q) => $q->where('production_summary.created_date', $this->filterDate))
            ->when($this->filterSpk, function ($q) {
                $term = trim($this->filterSpk);
                $q->where(function ($sub) use ($term) {
                    $sub->where('production_summary.spk_code', 'like', "%{$term}%")
                        ->orWhereExists(function ($sub2) use ($term) {
                            $sub2->select(DB::raw(1))
                                ->from('production_scanned_data')
                                ->whereColumn('production_scanned_data.summary_id', 'production_summary.id')
                                ->where('production_scanned_data.label', 'like', "%{$term}%");
                        });
                });
            })
            ->when($this->filterQcStatus === 'pending', function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNull('production_summary.qc_status')
                        ->orWhereIn('production_summary.qc_status', [0, 2]);
                });
            })
            ->when($this->filterQcStatus === 'completed', fn ($q) => $q->where('production_summary.qc_status', 1))
            ->when($this->filterItemCode, function ($q) {
                $term = trim($this->filterItemCode);
                $q->whereExists(function ($sub) use ($term) {
                    $sub->select(DB::raw(1))
                        ->from('production_scanned_data')
                        ->whereColumn('production_scanned_data.summary_id', 'production_summary.id')
                        ->where('production_scanned_data.item_code', 'like', "%{$term}%");
                });
            });
    }

    public function getSummariesProperty()
    {
        $paginated = $this->baseQuery()
            ->select(
                'production_summary.id',
                'production_summary.spk_code',
                'production_summary.total_quantity',
                'production_summary.warehouse',
                'production_summary.label',
                'production_summary.sap_sent',
                'production_summary.sap_sent_at',
                'production_summary.qc_status',
                'production_summary.created_date',
                'production_summary.created_at'
            )
            ->orderBy('production_summary.created_date', 'desc')
            ->orderBy('production_summary.id', 'desc')
            ->paginate($this->perPage);

        $summaryIds = $paginated->pluck('id')->toArray();

        if (empty($summaryIds)) {
            return $paginated;
        }

        // Fetch scanned boxes (item_code, total_boxes, labels) in one single indexed query
        $scannedBoxes = DB::table('production_scanned_data')
            ->whereIn('summary_id', $summaryIds)
            ->select('id', 'summary_id', 'item_code', 'label', 'quantity')
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('summary_id');

        $inspectedCountsMap = QcTransferLog::whereIn('production_summary_id', $summaryIds)
            ->groupBy('production_summary_id')
            ->select('production_summary_id', DB::raw('COUNT(*) as inspected_boxes'))
            ->pluck('inspected_boxes', 'production_summary_id')
            ->toArray();

        foreach ($paginated->items() as $item) {
            $boxes = $scannedBoxes->get($item->id, collect());
            $item->item_code = $boxes->first()?->item_code ?? '—';
            $item->total_boxes = $boxes->count();
            $item->inspected_boxes = $inspectedCountsMap[$item->id] ?? 0;

            $labelsList = $boxes->pluck('label')->filter(fn ($l) => $l !== null && $l !== '')->values()->toArray();
            $item->box_labels_all = $labelsList;
            $item->box_labels_summary = $this->formatBoxLabelsSummary($labelsList);
        }

        return $paginated;
    }

    protected function formatBoxLabelsSummary(array $labels): string
    {
        if (empty($labels)) {
            return '—';
        }

        $cleanLabels = array_values(array_unique(array_filter($labels, fn ($l) => $l !== null && $l !== '')));
        if (empty($cleanLabels)) {
            return '—';
        }

        $allNumeric = true;
        $nums = [];
        foreach ($cleanLabels as $lbl) {
            if (is_numeric($lbl)) {
                $nums[] = (int) $lbl;
            } else {
                $allNumeric = false;
                break;
            }
        }

        if ($allNumeric) {
            sort($nums);
            $count = count($nums);
            if ($count === 1) {
                return "Box #{$nums[0]}";
            }
            $min = $nums[0];
            $max = $nums[$count - 1];
            // Consecutive sequence
            if (($max - $min + 1) === $count) {
                return "Box #{$min} - #{$max}";
            }
            if ($count <= 3) {
                return 'Box #' . implode(', #', $nums);
            }

            return "Box #{$min}..#{$max} ({$count} Box)";
        }

        if (count($cleanLabels) === 1) {
            return "Box {$cleanLabels[0]}";
        }
        if (count($cleanLabels) <= 3) {
            return 'Box: ' . implode(', ', $cleanLabels);
        }

        return 'Box: ' . implode(', ', array_slice($cleanLabels, 0, 2)) . '... (+' . (count($cleanLabels) - 2) . ')';
    }

    public function getStatsProperty()
    {
        $targetWarehouse = ($this->plant === 'kbn') ? 'FFI' : 'KRFFI';
        $cacheKey = "qc_stats_{$this->plant}_{$this->filterDate}";

        return cache()->remember($cacheKey, now()->addSeconds(60), function () use ($targetWarehouse) {
            $base = DB::table('production_summary')
                ->where('sap_sent', 1)
                ->where('warehouse', $targetWarehouse)
                ->when($this->filterDate, fn ($q) => $q->where('created_date', $this->filterDate));

            $res = (clone $base)
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN qc_status IS NULL OR qc_status = 0 THEN 1 ELSE 0 END) as uninspected,
                    SUM(CASE WHEN qc_status = 2 THEN 1 ELSE 0 END) as partial,
                    SUM(CASE WHEN qc_status = 1 THEN 1 ELSE 0 END) as completed,
                    SUM(total_quantity) as total_qty
                ')
                ->first();

            return [
                'total' => (int) ($res->total ?? 0),
                'uninspected' => (int) ($res->uninspected ?? 0),
                'partial' => (int) ($res->partial ?? 0),
                'completed' => (int) ($res->completed ?? 0),
                'total_qty' => (int) ($res->total_qty ?? 0),
            ];
        });
    }

    public function clearStatsCache(): void
    {
        cache()->forget("qc_stats_{$this->plant}_{$this->filterDate}");
        cache()->forget("qc_stats_{$this->plant}_");
    }

    public function toggleDetail(int $summaryId): void
    {
        if (isset($this->expandedRows[$summaryId])) {
            unset($this->expandedRows[$summaryId]);
            unset($this->rowDetails[$summaryId]);

            return;
        }

        $this->expandedRows[$summaryId] = true;
        $this->loadRowDetails($summaryId);
    }

    private function loadRowDetails(int $summaryId): void
    {
        $boxes = DB::table('production_scanned_data')
            ->where('summary_id', $summaryId)
            ->select('id', 'spk_code', 'item_code', 'quantity', 'label', 'user', 'created_at')
            ->orderBy('id', 'asc')
            ->get();

        $logs = QcTransferLog::with('inspector:id,name')
            ->where('production_summary_id', $summaryId)
            ->get()
            ->keyBy('scanned_data_id');

        $details = [];
        foreach ($boxes as $box) {
            $log = $logs[$box->id] ?? null;
            $details[] = [
                'id' => $box->id,
                'spk_code' => $box->spk_code,
                'item_code' => $box->item_code,
                'quantity' => (int) $box->quantity,
                'label' => $box->label,
                'user' => $box->user,
                'created_at' => Carbon::parse($box->created_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i:s'),
                'is_inspected' => $log !== null,
                'log' => $log ? [
                    'id' => $log->id,
                    'ok_qty' => $log->ok_qty,
                    'ng_qty' => $log->ng_qty,
                    'ok_to_warehouse' => $log->ok_to_warehouse,
                    'ng_to_warehouse' => $log->ng_to_warehouse,
                    'ok_sap_status' => $log->ok_sap_status,
                    'ok_sap_error' => $log->ok_sap_error,
                    'ng_sap_status' => $log->ng_sap_status,
                    'ng_sap_error' => $log->ng_sap_error,
                    'remarks' => $log->remarks,
                    'inspected_at' => $log->created_at ? Carbon::parse($log->created_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') : '-',
                    'inspector_name' => $log->inspector?->name,
                ] : null,
            ];

            // Initialize default NG input to 0 if not set
            if (! isset($this->ngInputs[$box->id])) {
                $this->ngInputs[$box->id] = 0;
            }
        }

        $this->rowDetails[$summaryId] = $details;
    }

    public function submitSingleBox(int $scannedDataId, int $summaryId, ?int $directNgQty = null, ?string $directRemarks = null, ?QcTransferService $service = null): void
    {
        $service = $service ?? app(QcTransferService::class);
        $key = 'box_' . $scannedDataId;
        $this->processingRows[$key] = true;

        try {
            $ngQty = $directNgQty !== null ? max(0, $directNgQty) : (int) ($this->ngInputs[$scannedDataId] ?? 0);
            $remarks = $directRemarks !== null ? $directRemarks : ($this->remarksInputs[$scannedDataId] ?? null);
            $userId = Auth::id();
            $isKbn = ($this->plant === 'kbn');

            $res = $service->processSingleBoxInspection($scannedDataId, $ngQty, $userId, $remarks, $isKbn);

            if ($res['success']) {
                $this->clearStatsCache();
                $this->dispatch('push-notification', [
                    'status' => 'success',
                    'message' => $res['message'],
                ]);
                $this->loadRowDetails($summaryId);
            } else {
                $this->dispatch('push-notification', [
                    'status' => 'error',
                    'message' => $res['message'],
                ]);
            }
        } catch (\Throwable $e) {
            $this->dispatch('push-notification', [
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        } finally {
            unset($this->processingRows[$key]);
        }
    }

    public function submitWholeSummary(int $summaryId, array $directBoxNgMap = [], ?QcTransferService $service = null): void
    {
        $service = $service ?? app(QcTransferService::class);
        $key = 'summary_' . $summaryId;
        $this->processingRows[$key] = true;

        try {
            if (! isset($this->rowDetails[$summaryId])) {
                $this->loadRowDetails($summaryId);
            }

            $boxNgMap = [];
            if (! empty($directBoxNgMap)) {
                // Sanitize map from client: [ scannedDataId => ngQty ]
                foreach ($directBoxNgMap as $bId => $ng) {
                    $boxNgMap[(int) $bId] = max(0, (int) $ng);
                }
            } else {
                foreach ($this->rowDetails[$summaryId] as $box) {
                    if (! $box['is_inspected']) {
                        $boxId = $box['id'];
                        $boxNgMap[$boxId] = (int) ($this->ngInputs[$boxId] ?? 0);
                    }
                }
            }

            if (empty($boxNgMap)) {
                $this->dispatch('push-notification', [
                    'status' => 'warning',
                    'message' => 'Semua box pada summary ini sudah selesai diinspeksi.',
                ]);

                return;
            }

            $userId = Auth::id();
            $isKbn = ($this->plant === 'kbn');
            $res = $service->processSummaryInspection($summaryId, $boxNgMap, $userId, null, $isKbn);

            $this->clearStatsCache();

            if ($res['success']) {
                $this->dispatch('push-notification', [
                    'status' => 'success',
                    'message' => $res['message'],
                ]);
            } else {
                $this->dispatch('push-notification', [
                    'status' => 'warning',
                    'message' => $res['message'],
                ]);
            }

            $this->loadRowDetails($summaryId);
        } catch (\Throwable $e) {
            $this->dispatch('push-notification', [
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        } finally {
            unset($this->processingRows[$key]);
        }
    }

    public function retryTransfer(int $logId, QcTransferService $service): void
    {
        try {
            $log = QcTransferLog::find($logId);
            if (! $log) {
                $this->dispatch('push-notification', ['status' => 'error', 'message' => 'Log tidak ditemukan']);

                return;
            }

            $res = $service->executeSapTransfers($log);

            $this->clearStatsCache();

            if ($res['ok_success'] && $res['ng_success']) {
                $this->dispatch('push-notification', ['status' => 'success', 'message' => "Retry transfer log #{$logId} berhasil."]);
            } else {
                $msg = implode(', ', $res['messages']);
                $this->dispatch('push-notification', ['status' => 'error', 'message' => "Retry transfer log #{$logId} gagal: {$msg}"]);
            }

            if (isset($this->rowDetails[$log->production_summary_id])) {
                $this->loadRowDetails($log->production_summary_id);
            }
        } catch (\Throwable $e) {
            $this->dispatch('push-notification', ['status' => 'error', 'message' => 'Error retry: ' . $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.qc.qc-stock-transfer');
    }
}
