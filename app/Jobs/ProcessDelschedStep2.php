<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessDelschedStep2 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    public $tries = 1;

    public function handle()
    {
        $this->updateLog('delsched_main', 'step2', 'running', 'Step 2: Filter SO closed...');

        try {
            $tab_delsched_final = DB::table('delsched_final')
                ->where('so_number', '<>', '')
                ->get();

            foreach ($tab_delsched_final as $delsched_final) {
                $val_final_id = $delsched_final->id;
                $val_so_number = $delsched_final->so_number;
                $val_delivery_qty = $delsched_final->delivery_qty;

                $tab_solist = DB::table('delsched_solist')
                    ->where('so_number', $val_so_number)
                    ->first();
                $val_so_status = $tab_solist->so_status ?? null;

                if ($val_so_status == 'C') {
                    DB::table('delsched_final')->where('id', $val_final_id)->update([
                        'delivered' => $val_delivery_qty,
                        'outstanding' => 0,
                        'outstanding_stk' => 0,
                        'doc_status' => 'C',
                        'status' => 'success',
                    ]);
                }
            }

            $this->updateLog('delsched_main', 'step2', 'done', 'Step 2 selesai, Step 3 dijadwalkan...');

        } catch (\Exception $e) {
            $this->updateLog('delsched_main', 'step2', 'failed', 'Step 2 gagal: ' . $e->getMessage());
            Log::error('ProcessDelschedStep2 failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function updateLog($key, $step, $status, $message)
    {
        DB::table('delsched_process_logs')->updateOrInsert(
            ['process_key' => $key],
            ['current_step' => $step, 'status' => $status, 'message' => $message, 'updated_at' => now()]
        );
    }
}
