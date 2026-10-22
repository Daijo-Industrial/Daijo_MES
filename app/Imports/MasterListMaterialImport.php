<?php

namespace App\Imports;

use App\Models\MasterListMaterial;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Events\ImportFailed;

class MasterListMaterialImport implements ShouldQueue, ToCollection, WithChunkReading, WithEvents, WithHeadingRow
{
    private string $batchId;

    public function __construct(string $batchId = '')
    {
        $this->batchId = $batchId ?: 'import_' . time();
    }

    public function getBatchId(): string
    {
        return $this->batchId;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function collection(Collection $rows)
    {
        $now = now();
        $upsertData = [];

        foreach ($rows as $row) {
            $rowArray = is_array($row) ? $row : (method_exists($row, 'toArray') ? $row->toArray() : (array) $row);

            $itemCode = $this->cleanValue(
                $this->getValueByKeys($rowArray, ['item_no', 'item_no.', 'item_code', 'item_number'], 1)
            );

            // Skip row if item_code is missing/empty or header label string
            if (! $itemCode || in_array(strtolower($itemCode), ['item_no', 'item no', 'item_no.', 'item no.', 'item_code', 'item code', '#', 'item number'])) {
                continue;
            }

            $itemDesc = $this->cleanValue(
                $this->getValueByKeys($rowArray, ['item_description', 'item_desc', 'description'], 2)
            );

            $supplier = $this->cleanValue(
                $this->getValueByKeys($rowArray, ['preferred_supplier', 'preferred', 'supplier'], 3)
            );

            $uom = $this->cleanValue(
                $this->getValueByKeys($rowArray, ['purchasing_uom', 'purchasing', 'uom'], 4)
            );

            $upsertData[] = [
                'item_code' => $itemCode,
                'item_description' => $itemDesc,
                'preferred_supplier' => $supplier,
                'purchasing_uom' => $uom,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($upsertData)) {
            MasterListMaterial::upsert(
                $upsertData,
                ['item_code'], // unique key for match
                ['item_description', 'preferred_supplier', 'purchasing_uom', 'updated_at']
            );
        }

        // Increment processed rows in cache for live status bar
        $cacheKey = 'mwh_import_progress_' . $this->batchId;
        $current = Cache::get($cacheKey, ['status' => 'processing', 'processed' => 0]);
        $current['processed'] = ($current['processed'] ?? 0) + count($upsertData);
        Cache::put($cacheKey, $current, 3600);
    }

    private function getValueByKeys(array $row, array $keys, int $index)
    {
        // 1. Check if any heading key exists in array
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                return $row[$key];
            }
        }

        // 2. Fallback to raw numeric array indices if not using HeadingRow
        $values = array_values($row);

        // If col 0 is '#', index is exact (col 1=Item No, col 2=Desc, col 3=Supplier, col 4=UoM)
        if (isset($values[0]) && is_numeric($values[0]) && array_key_exists($index, $values)) {
            return $values[$index];
        }

        // If col 0 is Item No (no '#' col), shifted index applies
        $shiftedIndex = $index - 1;
        if (array_key_exists($shiftedIndex, $values)) {
            return $values[$shiftedIndex];
        }

    }

    private function cleanValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $str = trim((string) $value);

        if ($str === '' || strtolower($str) === 'null') {
            return null;
        }

        return $str;
    }

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event) {
                Cache::put('mwh_import_progress_' . $this->batchId, [
                    'status' => 'processing',
                    'processed' => 0,
                    'started_at' => now()->toDateTimeString(),
                ], 3600);
            },

            AfterImport::class => function (AfterImport $event) {
                $cacheKey = 'mwh_import_progress_' . $this->batchId;
                $current = Cache::get($cacheKey, ['processed' => 0]);
                Cache::put($cacheKey, [
                    'status' => 'completed',
                    'processed' => $current['processed'] ?? 0,
                    'finished_at' => now()->toDateTimeString(),
                ], 3600);
            },

            ImportFailed::class => function (ImportFailed $event) {
                Log::error('MasterListMaterialImport failed: ' . $event->getException()->getMessage());
                Cache::put('mwh_import_progress_' . $this->batchId, [
                    'status' => 'failed',
                    'error' => $event->getException()->getMessage(),
                ], 3600);
            },
        ];
    }
}
