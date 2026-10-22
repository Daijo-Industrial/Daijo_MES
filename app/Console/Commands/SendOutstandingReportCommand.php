<?php

namespace App\Console\Commands;

use App\Exports\OutstandingReportExport;
use App\Mail\SendOutstandingReport;
use App\Models\Delivery\DelschedFinal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

class SendOutstandingReportCommand extends Command
{
    protected $signature = 'report:send-outstanding';

    protected $description = 'Send daily outstanding report at 9 AM';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $rows = DelschedFinal::all();

        Excel::store(new OutstandingReportExport($rows), 'outstanding_report.xlsx');

        // $recipients = ['budiman@daijo.co.id', 'andriani@daijo.co.id', 'sriyati@daijo.co.id', 'anik@daijo.co.id', 'erizal@daijo.co.id', 'tina@daijo.co.id', 'sukur@daijo.co.id', 'andyco@daijo.co.id', 'naufal@daijo.co.id', 'timo@daijo.co.id'];
        $recipients = ['timo@daijo.co.id'];
        Mail::to($recipients) // You can use multiple recipients with array if needed
            ->send(new SendOutstandingReport);

        $this->info('Outstanding report sent successfully.');
    }
}
