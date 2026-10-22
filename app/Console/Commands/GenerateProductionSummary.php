<?php

namespace App\Console\Commands;

use App\Models\ProductionScannedData;
use App\Models\ProductionSummary;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateProductionSummary extends Command
{
    protected $signature = 'summary:generate';

    protected $description = 'Generate production summary every 5 minutes';

    public function handle()
    {
        DB::beginTransaction();
        try {
            // Ambil data scanned yang belum diproses dengan LOCK agar tidak diambil proses lain
            $unprocessedData = ProductionScannedData::where('processed', false)
                ->whereIn('warehouse', ['FFI', 'KRFFI'])
                ->whereNull('summary_id')
                ->lockForUpdate() // <--- Lock baris ini sampai transaksi selesai
                ->get();

            if ($unprocessedData->isEmpty()) {
                $this->info('No new data to process.');

                return;
            }

            // Deduplicate: jika ada record dengan spk_code + label yang sama, ambil yang pertama (ID terkecil), sisanya di-skip
            $unprocessedData = $unprocessedData->unique(function ($item) {
                return $item->spk_code . '||' . $item->label;
            });

            // Group per SPK + Warehouse + Date + 10-Minute interval block
            $summaries = $unprocessedData->groupBy(function ($item) {
                $createdAt = Carbon::parse($item->created_at);
                $minuteBlock = floor($createdAt->minute / 10) * 10;
                $timeWindow = $createdAt->copy()->minute($minuteBlock)->second(0)->format('H:i');

                return $item->spk_code . '||' . $item->warehouse . '||' . $createdAt->toDateString() . '||' . $timeWindow;
            });

            $processedIds = [];
            $allPayloads = [];

            foreach ($summaries as $groupKey => $group) {
                $total_quantity = $group->sum('quantity');
                $first = $group->first();

                // SELALU BIKIN BARU (sesuai request)
                $summary = ProductionSummary::create([
                    'spk_code' => $first->spk_code,
                    'total_quantity' => $total_quantity,
                    'warehouse' => $first->warehouse,
                    'label' => 'all',
                    'created_date' => $first->created_at->toDateString(),
                    'sap_sent' => 0,
                    'sap_sent_at' => null,
                ]);

                $groupIds = $group->pluck('id')->toArray();
                $processedIds = array_merge($processedIds, $groupIds);

                // Langsung ikat ke summary baru dan tandai sudah diproses
                ProductionScannedData::whereIn('id', $groupIds)
                    ->update([
                        'processed' => true,
                        'summary_id' => $summary->id,
                    ]);

                \Log::info('Summary created', [
                    'spk_code' => $first->spk_code,
                    'summary_id' => $summary->id,
                    'warehouse' => $first->warehouse,
                    'date' => $first->created_at->toDateString(),
                    'records' => count($groupIds),
                    'qty' => $total_quantity,
                ]);

                // Tambahkan ke payload api_logs agar mudah dicari
                $allPayloads[] = [
                    'summary_id' => $summary->id,
                    'spk_code' => $first->spk_code,
                    'total_quantity' => $total_quantity,
                    'warehouse' => $first->warehouse,
                    'label' => 'all',
                    'used_label' => $group->pluck('label')->unique()->values()->toArray(),
                    'created_date' => $first->created_at->toDateString(),
                    'records_count' => count($groupIds),
                ];
            }

            DB::table('api_logs')->insert([
                'api_name' => 'PRODUCTION_SUMMARY',
                'method' => 'INTERNAL',
                'endpoint' => 'summary:generate',
                'request_payload' => json_encode(['processed_ids' => $processedIds], JSON_PRETTY_PRINT),
                'response_payload' => json_encode([
                    'generated_at' => now()->toDateTimeString(),
                    'total_spk' => count($allPayloads),
                    'total_records' => count($processedIds),
                    'label_used' => collect($allPayloads)->pluck('label')->unique()->values(),
                    'summaries' => $allPayloads,
                ], JSON_PRETTY_PRINT),
                'status_code' => 200,
                'status' => 'success',
                'message' => 'Generated ' . count($allPayloads) . ' summaries from ' . count($processedIds) . ' records',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            $this->info('Production summary generated and data marked as processed.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error generating summary: ' . $e->getMessage());
        }
    }
}
