<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ProductionSummaryMonitor extends Component
{
    public string $filterDate = '';

    public string $filterWarehouse = '';

    public ?int $expandedLog = null;

    public function mount(): void
    {
        $this->filterDate = now()->format('Y-m-d');
    }

    public function toggleExpand(int $id): void
    {
        $this->expandedLog = $this->expandedLog === $id ? null : $id;
    }

    public function getSummaryLogsProperty()
    {
        $logs = DB::table('api_logs')
            ->where('api_name', 'PRODUCTION_SUMMARY')
            ->when(
                $this->filterDate,
                fn ($q) => $q->whereDate('created_at', $this->filterDate)
            )
            ->orderBy('created_at', 'desc')
            ->get();

        // Kumpulkan semua spk_codes dari semua logs sekaligus
        $allSpkCodes = [];
        $parsedLogs = [];

        foreach ($logs as $log) {
            $payload = json_decode($log->response_payload, true) ?? [];
            $summaries = $payload['summaries'] ?? [];

            // Filter warehouse di level summaries kalau ada filter
            if ($this->filterWarehouse) {
                $summaries = array_filter(
                    $summaries,
                    fn ($s) => strtoupper($s['warehouse'] ?? '') === strtoupper($this->filterWarehouse)
                );
                $summaries = array_values($summaries);
            }

            foreach ($summaries as $s) {
                if (! empty($s['spk_code'])) {
                    $allSpkCodes[] = $s['spk_code'];
                }
            }

            $parsedLogs[$log->id] = [
                'log' => $log,
                'payload' => $payload,
                'summaries' => $summaries,
            ];
        }

        // 1 query untuk semua item_codes
        $itemCodeMap = collect();
        if (! empty($allSpkCodes)) {
            $itemCodeMap = DB::table('production_scanned_data')
                ->whereIn('spk_code', array_unique($allSpkCodes))
                ->select('spk_code', DB::raw('MIN(item_code) as item_code'))
                ->groupBy('spk_code')
                ->get()
                ->keyBy('spk_code');
        }

        return collect($parsedLogs)->map(function ($parsed) use ($itemCodeMap) {
            $log = $parsed['log'];
            $payload = $parsed['payload'];
            $summaries = $parsed['summaries'];

            // Enrich summaries dengan item_code
            $enrichedSummaries = array_map(function ($s) use ($itemCodeMap) {
                $s['item_code'] = $itemCodeMap->get($s['spk_code'])?->item_code ?? '—';

                return $s;
            }, $summaries);

            return [
                'id' => $log->id,
                'created_at' => Carbon::parse($log->created_at)->timezone('Asia/Jakarta')->format('H:i:s'),
                'date' => Carbon::parse($log->created_at)->timezone('Asia/Jakarta')->format('Y-m-d'),
                'status' => $log->status,
                'message' => $log->message,
                'generated_at' => $payload['generated_at'] ?? null,
                'total_spk' => count($enrichedSummaries),
                'total_records' => collect($enrichedSummaries)->sum('records_count'),
                'label_used' => $payload['label_used'] ?? [],
                'summaries' => $enrichedSummaries,
                'total_qty' => collect($enrichedSummaries)->sum('total_quantity'),
            ];
        })->filter(fn ($log) => count($log['summaries']) > 0 || ! $this->filterWarehouse)
            ->values();
    }

    public function render()
    {
        return view('livewire.production-summary-monitor');
    }
}
