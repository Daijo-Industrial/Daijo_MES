<?php

namespace App\Livewire\Report;

use App\Models\HourlyRemark;
use Carbon\Carbon;
use Livewire\Component;

class MachineActiveHours extends Component
{
    public $startMonth;

    public $endMonth;

    public $reportData = [];

    public function mount()
    {
        $this->startMonth = Carbon::now()->startOfMonth()->format('Y-m');
        $this->endMonth = Carbon::now()->format('Y-m');
    }

    public function calculate()
    {
        $startDate = Carbon::parse($this->startMonth)->startOfMonth()->toDateString();
        $endDate = Carbon::parse($this->endMonth)->endOfMonth()->toDateString();

        // Ambil data hourly remarks dalam range tanggal
        // Kita hitung jumlah unik (Tanggal + Jam Range) per Mesin
        $rawRecords = HourlyRemark::query()
            ->join('daily_item_codes', 'hourly_remarks.dic_id', '=', 'daily_item_codes.id')
            ->join('users', 'daily_item_codes.user_id', '=', 'users.id')
            ->whereBetween('daily_item_codes.schedule_date', [$startDate, $endDate])
            ->select(
                'users.name as machine_name',
                'daily_item_codes.schedule_date',
                'hourly_remarks.start_time',
                'hourly_remarks.end_time'
            )
            ->get();

        $grouped = $rawRecords->groupBy('machine_name')->map(function ($items) {
            // Group by Date and Time Range
            $byDateAndHour = $items->groupBy(function ($item) {
                $timeRange = Carbon::parse($item->start_time)->format('H:i') . '-' . Carbon::parse($item->end_time)->format('H:i');

                return $item->schedule_date . '|' . $timeRange;
            });

            $totalHours = $byDateAndHour->count();

            // Hitung per tanggal
            $dailyBreakdown = [];
            foreach ($byDateAndHour as $key => $records) {
                $date = explode('|', $key)[0];
                $dailyBreakdown[$date] = ($dailyBreakdown[$date] ?? 0) + 1;
            }

            // Urutkan tanggal dari yang terbaru
            krsort($dailyBreakdown);

            return [
                'total' => $totalHours,
                'days' => $dailyBreakdown,
            ];
        });

        $this->reportData = $grouped->sortKeys()->toArray();
    }

    public function exportExcel()
    {
        $this->calculate();

        $exportData = [];
        foreach ($this->reportData as $name => $data) {
            $exportData[] = [
                'machine_name' => $name,
                'hours' => $data['total'],
            ];
        }

        $filename = "Machine_Active_Hours_{$this->startMonth}_to_{$this->endMonth}.xlsx";

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\MachineActiveHoursExport($exportData),
            $filename
        );
    }

    public function render()
    {
        return view('livewire.report.machine-active-hours')
            ->layout('layouts.app'); // Sesuaikan dengan layout yang ada
    }
}
