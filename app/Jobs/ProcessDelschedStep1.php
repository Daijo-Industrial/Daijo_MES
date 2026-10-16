<?php

namespace App\Jobs;

use App\Models\Delivery\delsched_delfilter;
use App\Models\Delivery\delsched_delsum;
use App\Models\Delivery\delsched_solist;
use App\Models\Delivery\DelschedFinal;
use App\Models\MasterListItem;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessDelschedStep1 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    public $tries = 1;

    public function handle()
    {
        $this->updateLog('delsched_main', 'step1', 'running', 'Step 1: Truncating tables...');

        try {
            DB::table('delsched_final')->truncate();
            DB::table('delsched_solist')->truncate();
            DB::table('delsched_delfilter')->truncate();
            DB::table('delsched_delsum')->truncate();
            DB::table('delsched_stock')->truncate();
            DB::table('delsched_finalwip')->truncate();
            DB::table('delsched_stockwip')->truncate();

            $this->updateLog('delsched_main', 'step1', 'running', 'Step 1: Processing sap_delsched...');

            $startOfMonth = Carbon::now()->startOfMonth();

            $tab_sap_delsched = DB::table('sap_delsched')
                ->where('delivery_date', '>=', $startOfMonth)
                ->orderBy('delivery_date', 'asc')
                ->orderBy('item_code', 'asc')
                ->get();

            foreach ($tab_sap_delsched as $sap_delsched) {
                $val_item_code_i = $sap_delsched->item_code;
                $val_delivery_date_i = $sap_delsched->delivery_date;
                $val_delivery_qty_i = $sap_delsched->delivery_qty;
                $val_so_number_i = $sap_delsched->so_number;

                $tab_sap_inventoryfg = DB::table('sap_inventory_fg')->where('item_code', $val_item_code_i)->first();
                if (is_null($tab_sap_inventoryfg)) {
                    Log::error("No sap_inventory_fg found for item_code: {$val_item_code_i}");
                    continue;
                }

                $val_item_name = $tab_sap_inventoryfg->item_name;
                $val_packaging = $tab_sap_inventoryfg->packaging;
                $val_standar_packaging = $tab_sap_inventoryfg->standar_packing;
                $val_process_owner = $tab_sap_inventoryfg->process_owner;

                if ($val_process_owner == 'INJ') {
                    $val_departement = 390;
                } elseif ($val_process_owner == 'SEC') {
                    $val_departement = 361;
                } else {
                    $val_departement = 362;
                }

                $tab_sap_customer = MasterListItem::with('customer')
                    ->where('item_code', $val_item_code_i)
                    ->first();

                if (empty($tab_sap_customer->item_code)) {
                    $val_customer_code = '';
                    $val_customer_name = '';
                } else {
                    $val_customer_code = $tab_sap_customer->customer_code;
                    $val_customer_name = $tab_sap_customer->customer?->customer_name;
                }

                DelschedFinal::insert([
                    'delivery_date' => $val_delivery_date_i,
                    'item_code' => $val_item_code_i,
                    'item_name' => $val_item_name,
                    'delivery_qty' => $val_delivery_qty_i,
                    'so_number' => $val_so_number_i,
                    'doc_status' => 'O',
                    'packaging_code' => $val_packaging,
                    'standar_pack' => $val_standar_packaging,
                    'customer_code' => $val_customer_code,
                    'customer_name' => $val_customer_name,
                    'departement' => $val_departement,
                ]);
            }

            // Process sap_delso
            $this->updateLog('delsched_main', 'step1', 'running', 'Step 1: Processing sap_delso...');

            $tab_sap_delso = DB::table('sap_delso')
                ->orderBy('doc_num', 'asc')
                ->orderBy('item_no', 'asc')
                ->get();

            foreach ($tab_sap_delso as $sap_delso) {
                delsched_solist::insert([
                    'so_number' => $sap_delso->doc_num,
                    'so_status' => $sap_delso->doc_status,
                    'item_code' => $sap_delso->item_no,
                    'so_qty' => $sap_delso->quantity,
                    'delivered_qty' => $sap_delso->delivered_qty,
                    'row_status' => $sap_delso->row_status,
                ]);
            }

            // Process sap_delactual
            $this->updateLog('delsched_main', 'step1', 'running', 'Step 1: Processing sap_delactual...');

            $tab_sap_delactual = DB::table('sap_delactual')->get();

            foreach ($tab_sap_delactual as $sap_delactual) {
                $val_so_num_iii = $sap_delactual->so_num;

                $tab_delsched_solist_iii = DB::table('delsched_solist')->where('so_number', $val_so_num_iii)->first();

                $rcd_status = 'O';
                if (! empty($tab_delsched_solist_iii->so_status) && $tab_delsched_solist_iii->so_status != 'O') {
                    $rcd_status = 'C';
                }

                if ($rcd_status == 'O') {
                    delsched_delfilter::insert([
                        'item_code' => $sap_delactual->item_no,
                        'delivery_date' => $sap_delactual->delivery_date,
                        'quantity' => $sap_delactual->quantity,
                        'so_number' => $val_so_num_iii,
                    ]);
                }
            }

            // Process delsum
            $this->updateLog('delsched_main', 'step1', 'running', 'Step 1: Processing delsum...');

            $tab_delsched_delfilter = DB::table('delsched_delfilter')->select('item_code')->distinct()->get();

            foreach ($tab_delsched_delfilter as $delsched_delfilter) {
                $val_item_code_iv = $delsched_delfilter->item_code;
                $sum_qty = DB::table('delsched_delfilter')->where('item_code', $val_item_code_iv)->sum('quantity');

                delsched_delsum::insert([
                    'item_code' => $val_item_code_iv,
                    'quantity' => $sum_qty,
                    'total_after' => $sum_qty,
                ]);
            }

            $this->updateLog('delsched_main', 'step1', 'running', 'Step 1 selesai, Step 2 dijadwalkan...');

        } catch (\Exception $e) {
            $this->updateLog('delsched_main', 'step1', 'failed', 'Step 1 gagal: ' . $e->getMessage());
            Log::error('ProcessDelschedStep1 failed: ' . $e->getMessage());
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
