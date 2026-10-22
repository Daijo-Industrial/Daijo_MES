<?php

namespace App\Jobs;

use App\Models\Delivery\delsched_stock;
use Carbon\Carbon;
use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessDelschedStep4 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    public $tries = 1;

    public function handle()
    {
        $this->updateLog('delsched_main', 'step4', 'running', 'Step 4: Kalkulasi stock...');

        try {
            // ===========================
            // TRUNCATE delsched_stock DULU
            // sama persis seperti controller step4
            // bedanya kita truncate di sini karena async
            // ===========================
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('delsched_stock')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // ===========================
            // INSERT STOCK PER ITEM
            // persis sama seperti controller
            // ===========================
            $tab_delsched_final_item = DB::table('delsched_final')
                ->select('item_code')
                ->distinct()
                ->get();

            foreach ($tab_delsched_final_item as $delsched_final_item) {
                $val_item_code = $delsched_final_item->item_code;

                $tab_sap_inventoryfg = DB::table('sap_inventory_fg')
                    ->where('item_code', '=', $val_item_code)
                    ->first();

                if (! $tab_sap_inventoryfg) {
                    Log::warning("Step4: sap_inventory_fg not found for item_code: {$val_item_code}");
                    continue;
                }

                $val_stock = $tab_sap_inventoryfg->stock;

                delsched_stock::insert([
                    'item_code' => $val_item_code,
                    'quantity' => $val_stock,
                    'total_after' => $val_stock,
                ]);
            }

            // ===========================
            // KALKULASI FINAL
            // persis sama seperti controller
            // ===========================
            $tab_delsched_final = DB::table('delsched_final')
                ->orderBy('id', 'asc')
                ->get();

            foreach ($tab_delsched_final as $delsched_final) {
                $today = Carbon::today();
                $final_id = $delsched_final->id;
                $dept = $delsched_final->departement;
                $item_code = $delsched_final->item_code;
                $delivery_date = Carbon::parse($delsched_final->delivery_date);
                $outstanding = $delsched_final->outstanding;
                $deliver_qty = $delsched_final->delivery_qty;
                $delivered = $delsched_final->delivered;

                // GET STOCK (LIVE VALUE)
                $stock = DB::table('delsched_stock')
                    ->where('item_code', $item_code)
                    ->first();

                if (! $stock) {
                    continue;
                }

                $stock_id = $stock->id;
                $stock_qty = $stock->quantity;
                $total_after = $stock->total_after;

                // CEK CLOSE
                if ($deliver_qty == $delivered) {
                    DB::table('delsched_final')->where('id', $final_id)->update([
                        'stock' => $stock_qty,
                        'balance' => $total_after,
                        'status' => 'SUCCESS',
                        'remark' => 'FINISHED',
                    ]);
                    continue;
                }

                // HITUNG SISA STOCK
                if ($total_after >= $outstanding) {
                    $cal_outstanding_st = 0;
                    $cal_total_after_now = $total_after - $outstanding;
                    $stock_ready = true;
                } else {
                    $cal_outstanding_st = $outstanding - $total_after;
                    $cal_total_after_now = $total_after - $outstanding;
                    $stock_ready = false;
                }

                // TENTUKAN DAY LIMIT
                $day_limit = 0;
                if ($dept == 390) {
                    $day_limit = 2;
                } elseif (in_array($dept, [361, 362])) {
                    $day_limit = 3;
                }

                $limit_date = $today->copy()->addDays($day_limit);

                // CEK STATUS & REMARK
                if ($delivery_date->lte($limit_date)) {
                    $rcd_status = $stock_ready ? 'WARNING' : 'DANGER';
                    $rcd_remark = $stock_ready ? 'PAST DUE DATE - STOCK READY' : 'PAST DUE DATE - NO STOCK';
                } else {
                    $rcd_status = $stock_ready ? 'MUTED' : 'INFO';
                    $rcd_remark = $stock_ready ? 'OPEN - STOCK READY' : 'OPEN - NO STOCK';
                }

                // UPDATE STOCK BERJALAN
                DB::table('delsched_stock')->where('id', $stock_id)->update([
                    'total_after' => $cal_total_after_now,
                ]);

                // UPDATE FINAL
                DB::table('delsched_final')->where('id', $final_id)->update([
                    'stock' => $stock_qty,
                    'balance' => $cal_total_after_now,
                    'outstanding_stk' => $cal_outstanding_st,
                    'status' => $rcd_status,
                    'remark' => $rcd_remark,
                ]);
            }

            // UPDATE TIMESTAMP
            $now = new DateTime;
            $now->modify('+420 minutes');
            DB::table('uti_date_list')->where('id', '13')->update([
                'updated_at' => $now,
            ]);

            $this->updateLog('delsched_main', 'step4', 'done', 'Semua proses delsched selesai!');

        } catch (\Exception $e) {
            $this->updateLog('delsched_main', 'step4', 'failed', 'Step 4 gagal: ' . $e->getMessage());
            Log::error('ProcessDelschedStep4 failed: ' . $e->getMessage());
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
