<?php

namespace App\Console\Commands;

use App\Jobs\ProcessDelschedStep1;
use App\Jobs\ProcessDelschedStep2;
use App\Jobs\ProcessDelschedStep3;
use App\Jobs\ProcessDelschedStep4;
use App\Jobs\ProcessDelschedWipStep1;
use App\Jobs\ProcessDelschedWipStep2;
use Illuminate\Console\Command;

class RunDeliveryScheduleCommand extends Command
{
    protected $signature = 'delsched:run';

    protected $description = 'Jalankan proses Delivery Schedule & WIP';

    public function handle()
    {
        $this->info('🚀 Memulai Delivery Schedule...');

        $jobs = [
            'ProcessDelschedStep1' => ProcessDelschedStep1::class,
            'ProcessDelschedStep2' => ProcessDelschedStep2::class,
            'ProcessDelschedStep3' => ProcessDelschedStep3::class,
            'ProcessDelschedStep4' => ProcessDelschedStep4::class,
            'ProcessDelschedWipStep1' => ProcessDelschedWipStep1::class,
            'ProcessDelschedWipStep2' => ProcessDelschedWipStep2::class,
        ];

        foreach ($jobs as $name => $jobClass) {
            $start = microtime(true);

            $jobClass::dispatch();

            $duration = round(microtime(true) - $start, 4);
            $this->info("✅ {$name} dispatched — {$duration}s");
        }

        $this->info('🎉 Semua job berhasil di-dispatch.');
    }
}
