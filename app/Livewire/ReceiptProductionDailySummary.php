<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

class ReceiptProductionDailySummary extends Component
{
    #[Url]
    public string $date = '';

    #[Url]
    public string $search = '';

    #[Url]
    public string $warehouse = ''; // '', 'FFI', 'KRFFI'

    #[Url]
    public string $status = ''; // '', 'sent', 'pending'

    #[Url]
    public string $viewMode = 'spk'; // 'spk' or 'id'

    public array $expandedSpks = [];

    public function mount(): void
    {
        Carbon::setLocale('id');
        if (empty($this->date)) {
            $this->date = request()->query('date', now()->timezone('Asia/Jakarta')->format('Y-m-d'));
        }
    }

    public function toggleSpk(string $spk): void
    {
        if (isset($this->expandedSpks[$spk])) {
            unset($this->expandedSpks[$spk]);
        } else {
            $this->expandedSpks[$spk] = true;
        }
    }

    public function getSummaryDataProperty(): array
    {
        $targetDate = $this->date ?: now()->timezone('Asia/Jakarta')->format('Y-m-d');

        $query = DB::table('production_summary')
            ->where('production_summary.created_date', $targetDate)
            ->whereIn('production_summary.warehouse', ['FFI', 'KRFFI'])
            ->when($this->warehouse, fn ($q) => $q->where('production_summary.warehouse', $this->warehouse));

        $summaries = $query
            ->select(
                'production_summary.id',
                'production_summary.spk_code',
                'production_summary.total_quantity',
                'production_summary.warehouse',
                'production_summary.label',
                'production_summary.sap_sent',
                'production_summary.sap_sent_at',
                'production_summary.created_date',
                'production_summary.created_at'
            )
            ->orderBy('production_summary.spk_code', 'asc')
            ->orderBy('production_summary.id', 'asc')
            ->get();

        if ($summaries->isEmpty()) {
            return [
                'target_date' => $targetDate,
                'stats' => [
                    'total_spk' => 0,
                    'total_qty' => 0,
                    'sent_qty' => 0,
                    'pending_qty' => 0,
                    'total_batches' => 0,
                ],
                'by_spk' => [],
                'by_id' => [],
            ];
        }

        $summaryIds = $summaries->pluck('id')->toArray();
        $spkCodes = $summaries->pluck('spk_code')->unique()->values()->toArray();

        // Map item codes from production_scanned_data
        $itemCodesMap = DB::table('production_scanned_data')
            ->whereIn('summary_id', $summaryIds)
            ->groupBy('summary_id')
            ->select('summary_id', DB::raw('MIN(item_code) as item_code'))
            ->pluck('item_code', 'summary_id')
            ->toArray();

        // Fallback item codes from spk_masters
        $spkMasters = DB::table('spk_masters')
            ->whereIn('spk_number', $spkCodes)
            ->pluck('item_code', 'spk_number')
            ->toArray();

        // Enrich individual rows
        $enrichedRows = $summaries->map(function ($row) use ($itemCodesMap, $spkMasters) {
            $itemCode = $itemCodesMap[$row->id] ?? ($spkMasters[$row->spk_code] ?? '—');
            $sentTime = $row->sap_sent_at
                ? Carbon::parse($row->sap_sent_at)->timezone('Asia/Jakarta')->format('H:i') . ' WIB'
                : ($row->created_at ? Carbon::parse($row->created_at)->timezone('Asia/Jakarta')->format('H:i') . ' WIB' : '—');

            $statusLabel = match ((int) $row->sap_sent) {
                1 => 'Terkirim',
                2 => 'Proses',
                3 => 'Gagal',
                99 => 'Diabaikan',
                default => 'Belum Terkirim',
            };

            $statusClass = match ((int) $row->sap_sent) {
                1 => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                2 => 'bg-amber-50 text-amber-800 border-amber-200',
                3 => 'bg-rose-50 text-rose-800 border-rose-200',
                99 => 'bg-slate-50 text-slate-600 border-slate-200',
                default => 'bg-orange-50 text-orange-800 border-orange-200',
            };

            return (object) [
                'id' => $row->id,
                'spk_code' => $row->spk_code,
                'item_code' => $itemCode,
                'warehouse' => $row->warehouse,
                'total_quantity' => (int) $row->total_quantity,
                'sap_sent' => (int) $row->sap_sent,
                'status_label' => $statusLabel,
                'status_class' => $statusClass,
                'sap_sent_at' => $row->sap_sent_at,
                'time_formatted' => $sentTime,
                'created_date' => $row->created_date,
            ];
        });

        // Group by SPK
        $grouped = $enrichedRows->groupBy('spk_code')->map(function ($items, $spk) {
            $first = $items->first();
            $totalQty = (int) $items->sum('total_quantity');
            $sentItems = $items->where('sap_sent', 1);
            $sentQty = (int) $sentItems->sum('total_quantity');
            $failedCount = $items->where('sap_sent', 3)->count();
            $totalCount = $items->count();
            $sentCount = $sentItems->count();

            $status = 'pending';
            $statusLabel = 'Belum Terkirim';
            $statusClass = 'bg-orange-50 text-orange-800 border-orange-200';

            if ($sentCount === $totalCount) {
                $status = 'sent';
                $statusLabel = 'Terkirim';
                $statusClass = 'bg-emerald-50 text-emerald-800 border-emerald-200';
            } elseif ($sentCount > 0) {
                $status = 'partial';
                $statusLabel = "Sebagian Terkirim ({$sentCount}/{$totalCount})";
                $statusClass = 'bg-blue-50 text-blue-800 border-blue-200';
            } elseif ($failedCount > 0) {
                $status = 'failed';
                $statusLabel = 'Gagal';
                $statusClass = 'bg-rose-50 text-rose-800 border-rose-200';
            }

            $ids = $items->pluck('id')->toArray();
            $times = $items->pluck('time_formatted')->filter(fn ($t) => $t !== '—')->unique()->values()->toArray();

            return [
                'spk_code' => $spk,
                'item_code' => $first->item_code,
                'warehouse' => $first->warehouse,
                'total_quantity' => $totalQty,
                'sent_quantity' => $sentQty,
                'pending_quantity' => $totalQty - $sentQty,
                'ids' => $ids,
                'id_list_str' => '#' . implode(', #', $ids),
                'batch_count' => $totalCount,
                'sent_count' => $sentCount,
                'status' => $status,
                'status_label' => $statusLabel,
                'status_class' => $statusClass,
                'times' => $times,
                'times_str' => ! empty($times) ? implode(', ', $times) : '—',
                'batches' => $items,
            ];
        });

        // Calculate KPI stats before search/status filter
        $stats = [
            'total_spk' => $grouped->count(),
            'total_qty' => (int) $enrichedRows->sum('total_quantity'),
            'sent_qty' => (int) $enrichedRows->where('sap_sent', 1)->sum('total_quantity'),
            'pending_qty' => (int) $enrichedRows->where('sap_sent', '!=', 1)->sum('total_quantity'),
            'total_batches' => $enrichedRows->count(),
        ];

        // Apply Search filter
        $search = strtolower(trim($this->search));
        $filteredGrouped = $grouped;
        $filteredRows = $enrichedRows;

        if ($search !== '') {
            $filteredGrouped = $filteredGrouped->filter(function ($spkItem) use ($search) {
                $matchSpk = str_contains(strtolower($spkItem['spk_code']), $search);
                $matchItem = str_contains(strtolower($spkItem['item_code']), $search);
                $matchId = collect($spkItem['ids'])->contains(fn ($id) => str_contains((string) $id, $search));

                return $matchSpk || $matchItem || $matchId;
            });

            $filteredRows = $filteredRows->filter(function ($row) use ($search) {
                return str_contains(strtolower($row->spk_code), $search)
                    || str_contains(strtolower($row->item_code), $search)
                    || str_contains((string) $row->id, $search);
            });
        }

        // Apply Status filter
        if ($this->status === 'sent') {
            $filteredGrouped = $filteredGrouped->filter(fn ($item) => $item['status'] === 'sent');
            $filteredRows = $filteredRows->filter(fn ($row) => $row->sap_sent === 1);
        } elseif ($this->status === 'pending') {
            $filteredGrouped = $filteredGrouped->filter(fn ($item) => in_array($item['status'], ['pending', 'partial', 'failed']));
            $filteredRows = $filteredRows->filter(fn ($row) => $row->sap_sent !== 1);
        }

        return [
            'target_date' => $targetDate,
            'stats' => $stats,
            'by_spk' => $filteredGrouped->values()->all(),
            'by_id' => $filteredRows->values()->all(),
        ];
    }

    public function render()
    {
        return view('livewire.receipt-production-daily-summary', [
            'summaryData' => $this->summaryData,
        ]);
    }
}

