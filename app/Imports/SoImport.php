<?php

namespace App\Imports;

use App\Models\SoData;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class SoImport extends DefaultValueBinder implements ToModel, WithCustomValueBinder
{
    /**
     * Bind value ensuring strings with trailing dots or numeric-like item codes are preserved as strings.
     * Prevents PhpSpreadsheet from casting '31099125084.' to float/int (which strips the trailing dot).
     */
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value) && str_ends_with($value, '.')) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function model(array $row)
    {
        $itemCode = isset($row[3]) ? trim((string) $row[3]) : '';

        // Check if a record with the same doc_num and item_code already exists
        $existingRecord = SoData::where('doc_num', $row[0])
            ->where('item_code', $itemCode)
            ->first();

        // If record exists, skip it by returning null
        if ($existingRecord) {
            if ($existingRecord->is_finish == 1 || $existingRecord->is_done == 1) {
                return; // Skip this record
            }
        }

        return new SoData([
            'doc_num' => (string) $row[0],
            'customer' => (string) $row[1],
            'posting_date' => $row[2],
            'item_code' => $itemCode,
            'item_name' => (string) $row[4],
            'quantity' => $row[5],
            'sales_uom' => $row[6],
            'packaging_quantity' => $row[7],
            'sales_pack' => $row[8],
            'is_finish' => 0,  // Automatically set to 0
            'is_done' => 0,  // Automatically set to 0
            'create_date' => $row[9],
            'update_date' => $row[10],
            'update_fulltime' => $row[11],
        ]);
    }

    public function deleteOldData()
    {
        // Delete records where both is_finish and is_done are 0
        SoData::where('is_finish', 0)
            ->where('is_done', 0)
            ->delete();
    }
}
