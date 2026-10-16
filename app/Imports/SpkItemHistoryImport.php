<?php

namespace App\Imports;

use App\Models\SpkItemHistory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Events\AfterChunk;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Events\ImportFailed;

class SpkItemHistoryImport implements ShouldQueue, ToModel, WithBatchInserts, WithChunkReading, WithCustomCsvSettings, WithEvents, WithStartRow
{
    private $cacheKey;

    private $delimiter;

    public function __construct(string $cacheKey = '', string $delimiter = "\t")
    {
        $this->cacheKey = $cacheKey;
        $this->delimiter = $delimiter;
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => $this->delimiter,
        ];
    }

    /**
     * Start reading from row 2 to skip the header.
     */
    public function startRow(): int
    {
        return 2;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // $row[0] = #
        // $row[1] = Document (spk_number)
        // $row[2] = Product No (item_code)

        if (! isset($row[1]) || ! isset($row[2])) {
            return; // Skip invalid rows
        }

        // Return a new model instance.
        // We set created_at and updated_at to null as requested.
        $model = new SpkItemHistory([
            'spk_number' => $row[1],
            'item_code' => $row[2],
        ]);

        // Disable timestamps to allow null values
        $model->timestamps = false;
        $model->created_at = null;
        $model->updated_at = null;

        return $model;
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event) {
                // Truncate the table before inserting new data
                DB::table('spk_item_histories')->truncate();

                if ($this->cacheKey) {
                    $totalRows = $event->reader->getTotalRows();
                    $total = array_values($totalRows)[0] ?? 0;
                    Cache::put($this->cacheKey . '_total', $total, now()->addHours(1));
                    Cache::put($this->cacheKey . '_processed', 0, now()->addHours(1));
                }
            },
            AfterChunk::class => function (AfterChunk $event) {
                if ($this->cacheKey) {
                    $processed = Cache::get($this->cacheKey . '_processed', 0);
                    $processed += $this->chunkSize();
                    Cache::put($this->cacheKey . '_processed', $processed, now()->addHours(1));
                }
            },
            AfterImport::class => function (AfterImport $event) {
                if ($this->cacheKey) {
                    Cache::put($this->cacheKey . '_status', 'finished', now()->addHours(1));
                }
            },
            ImportFailed::class => function (ImportFailed $event) {
                Log::error('SPK Item History Import failed: ' . $event->getException()->getMessage());
                if ($this->cacheKey) {
                    Cache::put($this->cacheKey . '_status', 'failed', now()->addHours(1));
                }
            },
        ];
    }
}
