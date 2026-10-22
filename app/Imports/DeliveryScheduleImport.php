<?php

namespace App\Imports;

use App\Models\Delivery\DeliveryScheduleNew;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DeliveryScheduleImport implements ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    private $existingCodes;

    public function __construct()
    {
        // Pre-fetch all existing codes into an associative array for O(1) fast in-memory lookup
        $this->existingCodes = DeliveryScheduleNew::pluck('code')->flip()->all();
    }

    public function model(array $row)
    {
        return new DeliveryScheduleNew([
            'code' => $this->generateUniqueCode(),
            'so_number' => $row['so_number'] ?? null,
            'customer_code' => $row['customer_code'] ?? null,
            'delivery_date' => $this->convertExcelDate($row['delivery_date']),
            'item_code' => $row['item_code'],
            'delivery_quantity' => $row['delivery_quantity'],
        ]);
    }

    private function generateUniqueCode()
    {
        do {
            $code = strtoupper(Str::random(6)); // Generates a 6-character random uppercase string
        } while (isset($this->existingCodes[$code]));

        // Track the newly generated code in memory so subsequent rows in this import batch do not collide
        $this->existingCodes[$code] = true;

        return $code;
    }

    private function convertExcelDate($excelDate)
    {
        // Check if date is numeric (Excel stores dates as numbers)
        if (is_numeric($excelDate)) {
            return Carbon::createFromFormat('Y-m-d', \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($excelDate)->format('Y-m-d'));
        }

        // Otherwise, assume it's a valid date string and return it
        return Carbon::parse($excelDate)->format('Y-m-d');
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
