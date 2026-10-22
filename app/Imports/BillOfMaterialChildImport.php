<?php

namespace App\Imports;

use App\Models\Production\PRD_BillOfMaterialChild;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class BillOfMaterialChildImport implements ToCollection
{
    protected $bomParentId;

    public function __construct($bomParentId)
    {
        $this->bomParentId = $bomParentId;
    }

    public function collection(Collection $rows)
    {
        // Skip the first row if it's a header
        $isHeader = true;

        foreach ($rows as $row) {
            if ($isHeader) {
                $isHeader = false;
                continue;
            }

            // Get the latest id from the table
            $latestId = PRD_BillOfMaterialChild::max('id') + 1;

            PRD_BillOfMaterialChild::create([
                'id' => $latestId,
                'parent_id' => $this->bomParentId,
                'item_code' => $row[0], // Assuming first column in Excel is material name
                'item_description' => $row[1], // Assuming second column is quantity
                'quantity' => $row[2], // Assuming second column is quantity
                'measure' => $row[3], // Assuming second column is quantity
                'status' => 'Not Started',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
