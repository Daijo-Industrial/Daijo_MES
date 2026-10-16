<?php

namespace App\Imports;

use App\Models\Setting\HolidaySchedule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class HolidayScheduleImport implements ToModel, WithHeadingRow
{
    /**
     * Define the logic for each row being imported.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {

        $cleanDate = rtrim($row['date'], "'");

        return new HolidaySchedule([
            'date' => \Carbon\Carbon::createFromFormat('d/m/Y', $cleanDate)->format('Y-m-d'),
            'description' => $row['description'],
            'injection' => $row['injection'],
            'second_process' => $row['second_process'],
            'assembly' => $row['assembly'],
            'moulding' => $row['moulding'],
            'half_day' => $row['half_day'],
        ]);
    }
}
