<?php

namespace App\Jobs;

use App\Models\Delivery\DelschedFinalWip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessDelschedWipStep1 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    public $tries = 1;

    public function handle()
    {
        $this->updateLog('delsched_wip', 'step1', 'running', 'WIP Step 1: Truncate & build finalwip...');

        try {
            DB::table('delsched_finalwip')->truncate();
            DB::table('delsched_stockwip')->truncate();

            $tab_delsched_final = DB::table('delsched_final')
                ->where('status', '=', 'danger')
                ->orWhere('status', '=', 'light')
                ->orderBy('id', 'asc')
                ->get();

            foreach ($tab_delsched_final as $delsched_final) {
                $val_id = $delsched_final->id;
                $val_delivery_date = $delsched_final->delivery_date;
                $val_so_number = $delsched_final->so_number;
                $val_customer_code = $delsched_final->customer_code;
                $val_customer_name = $delsched_final->customer_name;
                $val_item_code = $delsched_final->item_code;
                $val_item_name = $delsched_final->item_name;
                $val_outstanding_stk = $delsched_final->outstanding_stk;

                $tab_sap_bom_wip_check = DB::table('sap_bom_wip')->where('fg_code', '=', $val_item_code)->first();

                if (empty($tab_sap_bom_wip_check->fg_code)) {
                    continue;
                }

                $tab_sap_bom_wip = DB::table('sap_bom_wip')->where('fg_code', '=', $val_item_code)->get();

                foreach ($tab_sap_bom_wip as $sap_bom_wip) {
                    $val_semi_first = $sap_bom_wip->semi_first;
                    $val_semi_second = $sap_bom_wip->semi_second;
                    $val_semi_third = $sap_bom_wip->semi_third;
                    $val_bom_qty_first = $sap_bom_wip->qty_first;
                    $val_bom_qty_second = $sap_bom_wip->qty_second;
                    $val_bom_qty_third = $sap_bom_wip->qty_third;
                    $val_level = $sap_bom_wip->level;

                    if ($val_level == 3) {
                        $rcd_bom_qty = $val_bom_qty_first * $val_bom_qty_second * $val_bom_qty_third;
                        $rcd_wip = $val_semi_third;
                    } elseif ($val_level == 2) {
                        $rcd_bom_qty = $val_bom_qty_first * $val_bom_qty_second;
                        $rcd_wip = $val_semi_second;
                    } else {
                        $rcd_bom_qty = $val_bom_qty_first;
                        $rcd_wip = $val_semi_first;
                    }

                    $cal_req_qty = $rcd_bom_qty * $val_outstanding_stk;

                    $tab_sap_inventory_fg = DB::table('sap_inventory_fg')->where('item_code', '=', $rcd_wip)->first();
                    $val_wip_name = $tab_sap_inventory_fg->item_name;
                    $val_stock = $tab_sap_inventory_fg->stock;
                    $val_process_owner = $tab_sap_inventory_fg->process_owner;

                    if ($val_process_owner == 'INJ') {
                        $val_departement = 390;
                    } elseif ($val_process_owner == 'SEC') {
                        $val_departement = 361;
                    } else {
                        $val_departement = 362;
                    }

                    DelschedFinalWip::insert([
                        'fglink_id' => $val_id,
                        'delivery_date' => $val_delivery_date,
                        'so_number' => $val_so_number,
                        'customer_code' => $val_customer_code,
                        'customer_name' => $val_customer_name,
                        'item_code' => $val_item_code,
                        'item_name' => $val_item_name,
                        'outstanding_del' => $val_outstanding_stk,
                        'wip_code' => $rcd_wip,
                        'wip_name' => $val_wip_name,
                        'departement' => $val_departement,
                        'bom_level' => $val_level,
                        'bom_quantity' => $rcd_bom_qty,
                        'req_quantity' => $cal_req_qty,
                        'stock_wip' => $val_stock,
                        'balance_wip' => $val_stock,
                        'status' => 'light',
                    ]);
                }
            }

            $this->updateLog('delsched_wip', 'step1', 'running', 'WIP Step 1 selesai, Step 2 dijadwalkan...');

        } catch (\Exception $e) {
            $this->updateLog('delsched_wip', 'step1', 'failed', 'WIP Step 1 gagal: ' . $e->getMessage());
            Log::error('ProcessDelschedWipStep1 failed: ' . $e->getMessage());
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
