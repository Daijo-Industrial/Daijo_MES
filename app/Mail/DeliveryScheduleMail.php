<?php

namespace App\Mail;

use App\Exports\DelschedFinalExport;
use App\Exports\DelschedFinalWipExport;
use App\Livewire\DeliveryAnalysis;
use App\Models\ProductionPayable;
use Illuminate\Mail\Mailable;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;

class DeliveryScheduleMail extends Mailable
{
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $excelFinal = Excel::raw(new DelschedFinalExport, ExcelExcel::XLSX);
        $excelWip = Excel::raw(new DelschedFinalWipExport, ExcelExcel::XLSX);

        $this->data->payables = ProductionPayable::whereNull('deleted_at')
            ->orderBy('posting_date', 'desc')
            ->get();

        $deliveryAnalysis = app(DeliveryAnalysis::class);

        $this->data->shortfalls = $deliveryAnalysis->getEmailShortfallData();

        return $this->subject('Production Delivery Detail H+1 – H+3')
            ->view('mail.delivery-detail')
            ->attachData($excelFinal, 'delsched_final.xlsx', [
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->attachData($excelWip, 'delsched_wip.xlsx', [
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    }
}
