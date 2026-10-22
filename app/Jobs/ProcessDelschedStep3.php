<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessDelschedStep3 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    public $tries = 1;

    public function handle()
    {
        $this->updateLog('delsched_main', 'step3', 'running', 'Step 3: Kalkulasi delivery vs actual...');

        try {
            $tab_delsched_final = DB::table('delsched_final')
                ->where('doc_status', '=', 'O')
                ->orderBy('id', 'asc')
                ->get();

            foreach ($tab_delsched_final as $delsched_final) {
                $val_final_id = $delsched_final->id;
                $val_item_code = $delsched_final->item_code;
                $val_delivery_qty = $delsched_final->delivery_qty;

                $tab_delsched_delsum = DB::table('delsched_delsum')
                    ->where('item_code', $val_item_code)
                    ->first();

                if (empty($tab_delsched_delsum->item_code)) {
                    $val_total_after = 0;
                } else {
                    $val_total_after = $tab_delsched_delsum->total_after;
                }

                if ($val_total_after <= 0) {

                    DB::table('delsched_final')->where('id', $val_final_id)->update([
                        'delivered' => 0,
                        'outstanding' => $val_delivery_qty,
                        'outstanding_stk' => $val_delivery_qty,
                        'status' => 'danger',
                    ]);

                } else {

                    if ($val_total_after >= $val_delivery_qty) {

                        $cal_outstanding = 0;
                        $cal_total_after_now = $val_total_after - $val_delivery_qty;

                        if (! empty($tab_delsched_delsum)) {
                            DB::table('delsched_delsum')->where('id', $tab_delsched_delsum->id)->update([
                                'total_after' => $cal_total_after_now,
                            ]);
                        }

                        DB::table('delsched_final')->where('id', $val_final_id)->update([
                            'delivered' => $val_delivery_qty,
                            'outstanding' => 0,
                            'outstanding_stk' => 0,
                            'status' => 'success',
                            'remark' => 'finished',
                        ]);

                    } else {

                        $cal_outstanding = $val_delivery_qty - $val_total_after;
                        $outstanding = $val_delivery_qty - $cal_outstanding;
                        $cal_total_after_now = 0;

                        if (! empty($tab_delsched_delsum)) {
                            DB::table('delsched_delsum')->where('id', $tab_delsched_delsum->id)->update([
                                'total_after' => $cal_total_after_now,
                            ]);
                        }

                        DB::table('delsched_final')->where('id', $val_final_id)->update([
                            'delivered' => $cal_outstanding,
                            'outstanding' => $outstanding,
                            'outstanding_stk' => $outstanding,
                            'status' => 'warning',
                        ]);
                    }
                }
            }

            $this->updateLog('delsched_main', 'step3', 'done', 'Step 3 selesai, Step 4 dijadwalkan...');

        } catch (\Exception $e) {
            $this->updateLog('delsched_main', 'step3', 'failed', 'Step 3 gagal: ' . $e->getMessage());
            Log::error('ProcessDelschedStep3 failed: ' . $e->getMessage());
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
