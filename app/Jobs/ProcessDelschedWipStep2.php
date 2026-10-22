<?php

namespace App\Jobs;

use App\Models\Delivery\delsched_stockwip;
use Carbon\Carbon;
use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessDelschedWipStep2 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    public $tries = 1;

    public function handle()
    {
        $this->updateLog('delsched_wip', 'step2', 'running', 'WIP Step 2: Kalkulasi stock WIP...');

        try {
            // Build stock wip
            $tab_delsched_finalwip_item = DB::table('delsched_finalwip')->select('wip_code')->distinct()->get();

            foreach ($tab_delsched_finalwip_item as $item) {
                $tab_sap_inventoryfg = DB::table('sap_inventory_fg')->where('item_code', '=', $item->wip_code)->first();
                $val_stock = $tab_sap_inventoryfg->stock;

                delsched_stockwip::insert([
                    'item_code' => $item->wip_code,
                    'quantity' => $val_stock,
                    'total_after' => $val_stock,
                ]);
            }

            $tab_delsched_finalwip = DB::table('delsched_finalwip')->orderBy('id', 'asc')->get();

            foreach ($tab_delsched_finalwip as $delsched_finalwip) {
                $today = Carbon::today();
                $finalwip_id = $delsched_finalwip->id;
                $dept = $delsched_finalwip->departement;
                $wip_code = $delsched_finalwip->wip_code;
                $delivery_date = Carbon::parse($delsched_finalwip->delivery_date);
                $req_qty = $delsched_finalwip->req_quantity;
                $stock_wip = $delsched_finalwip->stock_wip;
                $outstanding_wip = $delsched_finalwip->outstanding_wip;

                $stockwip = DB::table('delsched_stockwip')->where('item_code', $wip_code)->first();
                if (! $stockwip) {
                    continue;
                }

                $stockwip_id = $stockwip->id;
                $stock_qty = $stockwip->quantity;
                $total_after = $stockwip->total_after;

                if ($req_qty == $stock_wip) {
                    DB::table('delsched_finalwip')->where('id', $finalwip_id)->update([
                        'stock_wip' => $stock_qty,
                        'balance_wip' => $total_after,
                        'status' => 'SUCCESS',
                        'remark' => 'SAFE',
                    ]);
                    continue;
                }

                if ($total_after >= $req_qty) {
                    $cal_total_after_now = $total_after - $outstanding_wip;
                    $stock_ready = true;
                    $cal_outstanding_wip_new = 0;
                } else {
                    $cal_total_after_now = $total_after - $req_qty;
                    $stock_ready = false;
                    $cal_outstanding_wip_new = $cal_total_after_now;
                }

                $day_limit = ($dept == 390) ? 7 : 4;
                $limit_date = $today->copy()->addDays($day_limit);

                if ($delivery_date->lte($limit_date)) {
                    $rcd_status = $stock_ready ? 'WARNING' : 'DANGER';
                    $rcd_remark = $stock_ready ? 'PAST DUE DATE - STOCK READY' : 'PAST DUE DATE - NO STOCK';
                } else {
                    $rcd_status = $stock_ready ? 'MUTED' : 'INFO';
                    $rcd_remark = $stock_ready ? 'OPEN - STOCK READY' : 'OPEN - NO STOCK';
                }

                DB::table('delsched_stockwip')->where('id', $stockwip_id)->update(['total_after' => $cal_total_after_now]);
                DB::table('delsched_finalwip')->where('id', $finalwip_id)->update([
                    'stock_wip' => $stock_qty,
                    'balance_wip' => $cal_total_after_now,
                    'outstanding_wip' => $cal_outstanding_wip_new,
                    'status' => $rcd_status,
                    'remark' => $rcd_remark,
                ]);
            }

            $now = new DateTime;
            $now->modify('+420 minutes');
            DB::table('uti_date_list')->where('id', '14')->update(['updated_at' => $now]);

            $this->updateLog('delsched_wip', 'step2', 'done', 'WIP semua step selesai!');

        } catch (\Exception $e) {
            $this->updateLog('delsched_wip', 'step2', 'failed', 'WIP Step 2 gagal: ' . $e->getMessage());
            Log::error('ProcessDelschedWipStep2 failed: ' . $e->getMessage());
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
