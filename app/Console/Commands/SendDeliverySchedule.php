<?php

namespace App\Console\Commands;

use App\Mail\DeliveryScheduleMail;
use App\Services\DeliveryAlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDeliverySchedule extends Command
{
    protected $signature = 'delivery:send';

    protected $description = 'Send H+1 H+2 H+3 delivery schedule';

    public function handle(DeliveryAlertService $service)
    {
        $data = $service->getNextThreeDays();

        if ($data->wip->isEmpty() && $data->final->isEmpty()) {
            $this->info('No delivery data found.');

            return;
        }

        Mail::to([
            'andyco@daijo.co.id',
            'budiman@daijo.co.id',
            'naufal@daijo.co.id',
            'sukur@daijo.co.id', //mas fadel
            'andriani@daijo.co.id',
            'anik@daijo.co.id',
            'sriyati@daijo.co.id',
            'erizal@daijo.co.id',
            'andika@daijo.co.id',
            'timo@daijo.co.id',
            'bayu_setiadji@daijo.co.id',
            'djkarawang_200@daijo.co.id',
            'andreas@daijo.co.id',
        ])->send(new DeliveryScheduleMail($data));

        $this->info('Delivery schedule email sent successfully!');
    }
}
