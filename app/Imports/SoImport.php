<?php

namespace App\Imports;

use App\Models\SoData;
use Maatwebsite\Excel\Concerns\ToModel;

class SoImport implements ToModel
{
    public function model(array $row)
    {
        // // Check if a record with the same doc_num and item_code already exists
        $existingRecord = SoData::where('doc_num', $row[0])
            ->where('item_code', $row[3])
            ->first();

        // If record exists, skip it by returning null
        if ($existingRecord) {
            // \Log::info('Duplicate found for doc_num: ' . $row[0] . ' and item_code: ' . $row[3]);

            if ($existingRecord->is_finish == 1 || $existingRecord->is_done == 1) {
                // \Log::debug('Skipping record for doc_num: ' . $row[0] . ' and item_code: ' . $row[3] . ' because it is finished or done.');
                return; // Skip this record
            }
        }

        // dd($row);

        return new SoData([
            'doc_num' => $row[0],
            'customer' => $row[1],
            'posting_date' => $row[2],
            'item_code' => $row[3],
            'item_name' => $row[4],
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
