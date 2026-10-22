<?php

namespace App\Http\Controllers;

ini_set('max_execution_time', 100000);

use App\DataTables\DeliveryNewTableDataTable;
use App\Exports\DeliveryScheduleExport;
use App\Exports\DelschedFinalExport;
use App\Imports\DeliveryScheduleImport;
use App\Models\ApiLog;
use App\Models\Delivery\DeliveryScheduleNew;
use App\Models\Delivery\delsched_delfilter;
use App\Models\Delivery\delsched_delsum;
use App\Models\Delivery\delsched_final;
use App\Models\Delivery\delsched_finalwip;
use App\Models\Delivery\delsched_solist;
use App\Models\Delivery\delsched_stock;
use App\Models\Delivery\delsched_stockwip;
use App\Models\Delivery\DelschedFinal;
use App\Models\Delivery\DelschedFinalWip;
use App\Models\Delivery\SapDelsched;
use App\Models\Delivery\sapInventoryFg;
use App\Models\Delivery\sapInventoryMtr;
use App\Models\Delivery\SapReject;
use App\Models\MasterDeliverySchedule;
use App\Models\MasterListItem;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\Process\Process;

class DeliveryScheduleController extends Controller
{
    public function index(DeliveryNewTableDataTable $dataTable)
    {

        $latestSyncRejectTime = ApiLog::where('api_name', 'SyncDelivery:RejectService')
            ->latest('created_at')
            ->value('created_at'); // ambil 1 kolom aja

        // convert ke WIB (Jakarta)
        $latestSyncRejectTimeJakarta = $latestSyncRejectTime
            ? Carbon::parse($latestSyncRejectTime)
                ->timezone('Asia/Jakarta')
            : null;

        $utiDateList = DB::table('uti_date_list')->find(13);

        return $dataTable->render('business.dsnewindex', compact('utiDateList', 'latestSyncRejectTimeJakarta'));
    }

    public function indexfinal()
    {
        $utiDateList = DB::table('uti_date_list')->find(13);

        return view('business.dsnewindexwip', compact('utiDateList'));
    }

    public function indexraw()
    {
        return view('business.rawdelsched');
    }

    public function averageschedule()
    {
        $data = SapDelsched::all();

        $rejectdatas = SapReject::all();

        // Mengelompokkan data berdasarkan bulan dan item_code, kemudian menghitung total quantity
        $today = now();
        $currentMonth = $today->format('Y-m');

        // Filter the data to only include the current month
        $data = $data->filter(function ($item) use ($currentMonth) {
            return Carbon::parse($item->delivery_date)->format('Y-m') === $currentMonth;
        });

        // Group the data by month and item_code, then calculate total quantity
        $itemCounts = $data->groupBy(function ($item) {
            return Carbon::parse($item->delivery_date)->format('Y-m');
        })->map(function ($group) {
            return $group->groupBy('item_code')->map(function ($itemGroup) {
                // Count unique item codes
                return $itemGroup->count();
            });
        });

        // Fetch the SapInventoryMtr data
        $inventoryData = sapInventoryFg::all();

        // Map the inventory data based on fg_code
        $inventoryMap = $inventoryData->keyBy('item_code');

        // Combine the grouped data with the inventory data
        $result = $itemCounts->map(function ($group) use ($inventoryMap, $rejectdatas) {
            return $group->map(function ($count, $itemCode) use ($inventoryMap, $rejectdatas) {
                $inventory = $inventoryMap->get($itemCode);

                // Initialize an array to hold stock and item_name
                $inventoryInfo = [
                    'in_stock' => null,
                    'item_name' => null,
                    'warehouse' => null,
                ];

                // If inventory data exists, populate stock and item_name
                if ($inventory) {
                    $inventoryInfo['in_stock'] = $inventory->stock;
                    // Assuming item_name is a field in the inventory model or related model
                    $inventoryInfo['item_name'] = $inventory->item_name; // Adjust as per your actual field name
                    $inventoryInfo['warehouse'] = $inventory->warehouse;

                    foreach ($rejectdatas as $reject) {
                        if ($reject->item_no === $inventory->item_code) {
                            $inventoryInfo['in_stock'] -= $reject->in_stock; // Adjust field name as needed

                        }
                    }
                }

                return $inventoryInfo;
            });
        });

        // dd($result);
        // // Calculate total quantities for the current month
        $totalQuantities = $data->groupBy(function ($item) {
            return Carbon::parse($item->delivery_date)->format('Y-m');
        })->map(function ($group) {
            return $group->groupBy('item_code')->map(function ($itemGroup) {
                return $itemGroup->sum('delivery_qty');
            });
        });
        // dd($totalQuantities);

        return view('business.averageschedule', compact('data', 'itemCounts', 'totalQuantities', 'result'));
    }

    // public function step1()
    // {
    //     DB::table('delsched_final')->truncate();
    // 	DB::table('delsched_solist')->truncate();
    // 	DB::table('delsched_delfilter')->truncate();
    // 	DB::table('delsched_delsum')->truncate();
    // 	DB::table('delsched_stock')->truncate();
    // 	DB::table('delsched_finalwip')->truncate();
    // 	DB::table('delsched_stockwip')->truncate();

    // 	// old delsched
    //     $tab_sap_delsched = DB::table('sap_delsched')->orderBy('delivery_date','asc')->orderBy('item_code','asc')->get();

    // 	//new master delsched
    // 	// $tab_sap_delsched = DB::table('master_delivery_schedule')->orderBy('tanggal','asc')->orderBy('item_code','asc')->get();

    //     foreach($tab_sap_delsched as $sap_delsched){

    // 		$val_item_code_i = $sap_delsched->item_code;
    // 		$val_delivery_date_i = $sap_delsched->delivery_date;
    // 		$val_delivery_qty_i = $sap_delsched->delivery_qty;
    // 		$val_so_number_i = $sap_delsched->so_number;

    // 		//pakai master delivery schedule
    // 		// $val_item_code_i = $sap_delsched->item_code;
    // 		// $val_delivery_date_i = $sap_delsched->tanggal;
    // 		// $val_delivery_qty_i = $sap_delsched->quantity;
    // 		// $val_so_number_i = $sap_delsched->so_num;

    // 		$tab_sap_inventoryfg = DB::table('sap_inventory_fg')->where('item_code',$val_item_code_i)->first();
    // 		if (is_null($tab_sap_inventoryfg)) {
    // 			// Log the item_code that returned null for further debugging
    // 			Log::error("No sap_inventory_fg found for item_code: {$val_item_code_i}");

    // 			// Optionally, continue with the next iteration if no record is found
    // 			continue;
    // 		}
    // 		$val_item_name = $tab_sap_inventoryfg->item_name;
    // 		$val_packaging = $tab_sap_inventoryfg->packaging;
    // 		$val_standar_packaging = $tab_sap_inventoryfg->standar_packing;
    // 		$val_process_owner = $tab_sap_inventoryfg->process_owner;
    // 		if($val_process_owner == 'INJ'){
    // 			$val_departement = 390;
    // 		} elseif($val_process_owner == 'SEC'){
    // 			$val_departement = 361;
    // 		} else {
    // 			$val_departement = 362;
    // 		}

    // 		//old code
    // 		// $tab_sap_customer = DB::table('sap_inventory_fg')->where('item_code',$val_item_code_i)->first();

    // 		$tab_sap_customer = MasterListItem::with('customer')
    // 		->where('item_code', $val_item_code_i)
    // 		->first();
    // 		if(empty($tab_sap_customer->item_code)){
    // 			$val_customer_code = '';
    // 			$val_customer_name = '';
    // 		} else {
    // 			$val_customer_code = $tab_sap_customer->customer_code;
    // 			$val_customer_name = $tab_sap_customer->customer?->customer_name;
    // 		}

    // 		$ins_final = array('delivery_date' => $val_delivery_date_i,
    // 		'item_code' => $val_item_code_i,
    // 		'item_name' => $val_item_name,
    // 		'delivery_qty' => $val_delivery_qty_i,
    // 		'so_number' => $val_so_number_i,
    // 		'doc_status' => 'O',
    // 		'packaging_code' => $val_packaging,
    // 		'standar_pack' => $val_standar_packaging,
    // 		'customer_code' => $val_customer_code,
    // 		'customer_name' => $val_customer_name,
    // 		'departement' => $val_departement);

    // 		DelschedFinal::insert($ins_final);
    // 	}

    //     $tab_sap_delso = DB::table('sap_delso')->orderBy('doc_num','asc')->orderBy('item_no','asc')->get();

    // 	foreach($tab_sap_delso as $sap_delso){

    // 		$val_so_num_ii = $sap_delso->doc_num;
    // 		$val_so_status_ii = $sap_delso->doc_status;
    // 		$val_item_code_ii = $sap_delso->item_no;
    // 		$val_so_quantity_ii = $sap_delso->quantity;
    // 		$val_delivered_qty_ii = $sap_delso->delivered_qty;
    // 		$val_row_status_ii = $sap_delso->row_status;

    // 		$ins_solist = array('so_number' => $val_so_num_ii, 'so_status' => $val_so_status_ii, 'item_code' => $val_item_code_ii, 'so_qty' => $val_so_quantity_ii, 'delivered_qty' => $val_delivered_qty_ii, 'row_status' => $val_row_status_ii);
    // 		delsched_solist::insert($ins_solist);

    // 	}

    //     $tab_sap_delactual = DB::table('sap_delactual')->get();

    // 	foreach($tab_sap_delactual as $sap_delactual){

    // 		$val_item_code_iii = $sap_delactual->item_no;
    // 		$val_delivery_date_iii = $sap_delactual->delivery_date;
    // 		$val_quantity_iii = $sap_delactual->quantity;
    // 		$val_so_num_iii = $sap_delactual->so_num;

    // 		$tab_delsched_solist_iii = DB::table('delsched_solist')->where('so_number',$val_so_num_iii)->first();
    // 		if(empty($tab_delsched_solist_iii->so_status)){
    // 			$rcd_status = 'O';
    // 		} else {
    // 			if($tab_delsched_solist_iii->so_status == 'O'){
    // 				$rcd_status = 'O';
    // 			} else {
    // 				$rcd_status = 'C';
    // 			}
    // 		}

    // 		if($rcd_status == 'O'){
    // 			$ins_delfilter = array('item_code' => $val_item_code_iii, 'delivery_date' => $val_delivery_date_iii, 'quantity' => $val_quantity_iii, 'so_number' => $val_so_num_iii);
    // 			delsched_delfilter::insert($ins_delfilter);
    // 		}
    // 	}

    //     $tab_delsched_delfilter = DB::table('delsched_delfilter')->select('item_code')->distinct()->get();

    // 	foreach($tab_delsched_delfilter as $delsched_delfilter){

    // 		$val_item_code_iv = $delsched_delfilter->item_code;

    // 		$sum_delsched_delfilter_qty = DB::table('delsched_delfilter')->where('item_code',$val_item_code_iv)->sum('quantity');

    // 		$ins_delsum = array('item_code' => $val_item_code_iv, 'quantity' => $sum_delsched_delfilter_qty, 'total_after' => $sum_delsched_delfilter_qty);
    // 		delsched_delsum::insert($ins_delsum);
    // 	}

    // 	//Close 1.4

    // 	return redirect()->route('deslsched.step2');
    // }

    // public function step2()
    // {
    //     //2. Filter delsched_final by SO yang close
    // 	$tab_delsched_final = DB::table('delsched_final')->where('so_number','<>','')->get();

    // 	foreach($tab_delsched_final as $delsched_final){
    // 		$val_final_id = $delsched_final->id;
    // 		$val_so_number = $delsched_final->so_number;
    // 		$val_delivery_qty = $delsched_final->delivery_qty;

    // 		$tab_solist = DB::table('delsched_solist')->where('so_number',$val_so_number)->first();
    // 		$val_so_status = $tab_solist->so_status ?? null;

    // 		if($val_so_status == 'C'){

    // 			$update_final = DB::table('delsched_final')->where('id', $val_final_id)->update([
    // 				'delivered' => $val_delivery_qty,
    // 				'outstanding' => 0,
    // 				'outstanding_stk' => 0,
    // 				'doc_status' => 'C',
    // 				'status' => 'success',
    // 			]);

    // 		}

    // 	}

    // 	return redirect()->route('deslsched.step3');;
    // }

    // public function step3()
    // {
    //     	//3. Filter delsched dengan pengurangan delivery yang sudah dilakukan dari delsum
    // 	$tab_delsched_final = DB::table('delsched_final')->where('doc_status','=','O')->orderBy('id','asc')->get();

    // 	foreach($tab_delsched_final as $delsched_final){
    // 		$val_final_id = $delsched_final->id;
    // 		$val_item_code = $delsched_final->item_code;
    // 		$val_delivery_qty = $delsched_final->delivery_qty;

    // 		$tab_delsched_delsum = DB::table('delsched_delsum')->where('item_code',$val_item_code)->first();

    // 		if(empty($tab_delsched_delsum->item_code)){
    // 			$val_total_after = 0;
    // 		} else {
    // 			$val_total_after = $tab_delsched_delsum->total_after;
    // 		}

    // 		if($val_total_after <= 0){

    // 			$update_final = DB::table('delsched_final')->where('id', $val_final_id)->update([
    // 				'delivered' => 0,
    // 				'outstanding' => $val_delivery_qty,
    // 				'outstanding_stk' => $val_delivery_qty,
    // 				'status' => 'danger',
    // 			]);

    // 		} else {

    // 			if($val_total_after >= $val_delivery_qty){

    // 				$cal_outstanding = 0;
    // 				$cal_total_after_now = $val_total_after - $val_delivery_qty;

    // 				if(empty($tab_delsched_delsum)){
    // 				} else {
    // 					$val_delsum_id = $tab_delsched_delsum->id;
    // 					$update_delsum = DB::table('delsched_delsum')->where('id', $val_delsum_id)->update([
    // 						'total_after' => $cal_total_after_now,
    // 					]);
    // 				}

    // 				$update_final = DB::table('delsched_final')->where('id', $val_final_id)->update([
    // 					'delivered' => $val_delivery_qty,
    // 					'outstanding' => 0,
    // 					'outstanding_stk' => 0,
    // 					'status' => 'success',
    // 					'remark' => 'finished'
    // 				]);

    // 			} else {

    // 				$cal_outstanding = $val_delivery_qty - $val_total_after;
    // 				$outstanding = $val_delivery_qty - $cal_outstanding;
    // 				$cal_total_after_now = 0;

    // 				if(empty($tab_delsched_delsum)){
    // 				} else {
    // 					$val_delsum_id = $tab_delsched_delsum->id;
    // 					$update_delsum = DB::table('delsched_delsum')->where('id', $val_delsum_id)->update([
    // 						'total_after' => $cal_total_after_now,
    // 					]);
    // 				}

    // 				$update_final = DB::table('delsched_final')->where('id', $val_final_id)->update([
    // 					'delivered' => $cal_outstanding,
    // 					'outstanding' => $outstanding,
    // 					'outstanding_stk' => $outstanding,
    // 					'status' => 'warning',
    // 				]);

    // 			}
    // 		}

    // 	}

    // 	return redirect()->route('deslsched.step4');
    // }

    // public function step4()
    // {

    // 	$tab_delsched_final_item = DB::table('delsched_final')->select('item_code')->distinct()->get();

    // 	foreach($tab_delsched_final_item as $delsched_final_item){

    // 		$val_item_code = $delsched_final_item->item_code;

    // 		$tab_sap_inventoryfg = DB::table('sap_inventory_fg')->where('item_code','=',$val_item_code)->first();
    // 		$val_stock = $tab_sap_inventoryfg->stock;

    // 		$ins_stock = array('item_code' => $val_item_code, 'quantity' => $val_stock, 'total_after' => $val_stock);
    // 		delsched_stock::insert($ins_stock);

    // 	}

    // 	$tab_delsched_final = DB::table('delsched_final')
    // 		->orderBy('id','asc')
    // 		->get();

    // 	foreach ($tab_delsched_final as $delsched_final) {

    // 		$today = Carbon::today();

    // 		$final_id      = $delsched_final->id;
    // 		$dept          = $delsched_final->departement;
    // 		$item_code     = $delsched_final->item_code;
    // 		$delivery_date = Carbon::parse($delsched_final->delivery_date);
    // 		$outstanding   = $delsched_final->outstanding;
    // 		$deliver_qty   = $delsched_final->delivery_qty;
    // 		$delivered     = $delsched_final->delivered;

    // 		// ======================
    // 		// GET STOCK (LIVE VALUE)
    // 		// ======================
    // 		$stock = DB::table('delsched_stock')
    // 			->where('item_code', $item_code)
    // 			->first();

    // 		if (!$stock) continue;

    // 		$stock_id    = $stock->id;
    // 		$stock_qty   = $stock->quantity;
    // 		$total_after = $stock->total_after;

    // 		// ======================
    // 		// CEK CLOSE
    // 		// ======================
    // 		if ($deliver_qty == $delivered) {

    // 			DB::table('delsched_final')->where('id', $final_id)->update([
    // 				'stock'   => $stock_qty,
    // 				'balance' => $total_after,
    // 				'status'  => 'SUCCESS',
    // 				'remark'  => 'FINISHED'
    // 			]);

    // 			continue;
    // 		}

    // 		// ======================
    // 		// HITUNG SISA STOCK
    // 		// ======================
    // 		if ($total_after >= $outstanding) {

    // 			$cal_outstanding_st = 0;
    // 			$cal_total_after_now = $total_after - $outstanding;
    // 			$stock_ready = true;

    // 		} else {

    // 			$cal_outstanding_st = $outstanding - $total_after;
    // 			$cal_total_after_now = $total_after - $outstanding; // bisa minus
    // 			$stock_ready = false;
    // 		}

    // 		// ======================
    // 		// TENTUKAN DAY LIMIT
    // 		// ======================
    // 		$day_limit = 0;

    // 		if ($dept == 390) {
    // 			$day_limit = 2;
    // 		} elseif (in_array($dept, [361, 362])) {
    // 			$day_limit = 3;
    // 		}

    // 		$limit_date = $today->copy()->addDays($day_limit);

    // 		// ======================
    // 		// CEK STATUS & REMARK
    // 		// ======================
    // 		if ($delivery_date->lte($limit_date)) {

    // 			// ===== PAST DUE =====
    // 			if ($stock_ready) {
    // 				$rcd_status = 'WARNING';
    // 				$rcd_remark = 'PAST DUE DATE - STOCK READY';
    // 			} else {
    // 				$rcd_status = 'DANGER';
    // 				$rcd_remark = 'PAST DUE DATE - NO STOCK';
    // 			}

    // 		} else {

    // 			// ===== OPEN =====
    // 			if ($stock_ready) {
    // 				$rcd_status = 'MUTED';
    // 				$rcd_remark = 'OPEN - STOCK READY';
    // 			} else {
    // 				$rcd_status = 'INFO';
    // 				$rcd_remark = 'OPEN - NO STOCK';
    // 			}
    // 		}

    // 		// ======================
    // 		// UPDATE STOCK BERJALAN
    // 		// ======================
    // 		DB::table('delsched_stock')->where('id', $stock_id)->update([
    // 			'total_after' => $cal_total_after_now
    // 		]);

    // 		// ======================
    // 		// UPDATE FINAL
    // 		// ======================
    // 		DB::table('delsched_final')->where('id', $final_id)->update([
    // 			'stock'           => $stock_qty,
    // 			'balance'         => $cal_total_after_now,
    // 			'outstanding_stk' => $cal_outstanding_st,
    // 			'status'          => $rcd_status,
    // 			'remark'          => $rcd_remark,
    // 		]);
    // 	}

    // 	$now = new DateTime();
    // 	$now->modify('+420 minutes');

    // 	$tb_datelist = DB::table('uti_date_list')->where('id','13')->update([
    // 		'updated_at' => $now,
    // 	]);

    // 	return redirect()->route('indexds');
    // }

    // public function step1wip()
    // {
    //     //0. Format data dalam table tertentu
    // 	DB::table('delsched_finalwip')->truncate();
    // 	DB::table('delsched_stockwip')->truncate();

    // 	//1. Menarik data dari final menjadi wip ke dalam finalwip
    // 	$tab_delsched_final = DB::table('delsched_final')->where('status','=','danger')->orWhere('status','=','light')->orderBy('id','asc')->get();

    // 	foreach($tab_delsched_final as $delsched_final){
    // 		$val_id = $delsched_final->id;
    // 		$val_delivery_date = $delsched_final->delivery_date;
    // 		$val_so_number = $delsched_final->so_number;
    // 		$val_customer_code = $delsched_final->customer_code;
    // 		$val_customer_name = $delsched_final->customer_name;
    // 		$val_item_code = $delsched_final->item_code;
    // 		$val_item_name = $delsched_final->item_name;
    // 		$val_outstanding_stk = $delsched_final->outstanding_stk;

    // 		$tab_sap_bom_wip_check = DB::table('sap_bom_wip')->where('fg_code','=',$val_item_code)->first();

    // 		if(empty($tab_sap_bom_wip_check->fg_code)){
    // 		} else {
    // 			$tab_sap_bom_wip = DB::table('sap_bom_wip')->where('fg_code','=',$val_item_code)->get();

    // 			foreach($tab_sap_bom_wip as $sap_bom_wip){
    // 				$val_semi_first = $sap_bom_wip->semi_first;
    // 				$val_semi_second = $sap_bom_wip->semi_second;
    // 				$val_semi_third = $sap_bom_wip->semi_third;
    // 				$val_bom_qty_first = $sap_bom_wip->qty_first;
    // 				$val_bom_qty_second = $sap_bom_wip->qty_second;
    // 				$val_bom_qty_third = $sap_bom_wip->qty_third;
    // 				$val_level = $sap_bom_wip->level;

    // 				if($val_level == 3){

    // 					$rcd_bom_qty = $val_bom_qty_first*$val_bom_qty_second*$val_bom_qty_third;
    // 					$cal_req_qty = $rcd_bom_qty*$val_outstanding_stk;
    // 					$rcd_wip = 	$val_semi_third;

    // 				} elseif($val_level == 2){

    // 					$rcd_bom_qty = $val_bom_qty_first*$val_bom_qty_second;
    // 					$cal_req_qty = $rcd_bom_qty*$val_outstanding_stk;
    // 					$rcd_wip = 	$val_semi_second;

    // 				} else {

    // 					$rcd_bom_qty = $val_bom_qty_first;
    // 					$cal_req_qty = $rcd_bom_qty*$val_outstanding_stk;
    // 					$rcd_wip = 	$val_semi_first;

    // 				}

    // 				$tab_sap_inventory_fg = DB::table('sap_inventory_fg')->where('item_code','=',$rcd_wip)->first();

    // 				$val_wip_name = $tab_sap_inventory_fg->item_name;
    // 				$val_stock = $tab_sap_inventory_fg->stock;
    // 				$val_process_owner = $tab_sap_inventory_fg->process_owner;
    // 				if($val_process_owner == 'INJ'){
    // 					$val_departement = 390;
    // 				} elseif($val_process_owner == 'SEC'){
    // 					$val_departement = 361;
    // 				} else {
    // 					$val_departement = 362;
    // 				}

    // 				$ins_finalwip = array('fglink_id' => $val_id, 'delivery_date' => $val_delivery_date, 'so_number' => $val_so_number, 'customer_code' => $val_customer_code,'customer_name' => $val_customer_name,
    // 				'item_code' => $val_item_code, 'item_name' => $val_item_name, 'outstanding_del' => $val_outstanding_stk, 'wip_code' => $rcd_wip, 'wip_name' => $val_wip_name, 'departement' => $val_departement,
    // 				'bom_level' => $val_level, 'bom_quantity' => $rcd_bom_qty, 'req_quantity' => $cal_req_qty, 'stock_wip' => $val_stock, 'balance_wip' => $val_stock, 'status' => 'light');
    // 				DelschedFinalWip::insert($ins_finalwip);

    // 			}

    // 			/*
    // 			$ins_finalwip = array('delivery_date' => $val_delivery_date, 'so_number' => $val_so_number, 'customer_code' => $val_customer_code,
    // 			'customer_name' => $val_customer_name, 'item_code' => $val_item_code, 'item_name' => $val_item_name, 'outstanding_del' => $val_outstanding_stk);
    // 			delsched_finalwip::insert($ins_finalwip);
    // 			*/

    // 		}
    // 	}

    // 	return redirect()->route('delschedwip.step2');
    // }

    // public function step2wip()
    // {
    //     //2. Filter delsched_finalwip dengan penarikan dari stok
    // 	$tab_delsched_finalwip_item = DB::table('delsched_finalwip')->select('wip_code')->distinct()->get();

    // 	foreach($tab_delsched_finalwip_item as $delsched_finalwip_item){

    // 		$val_wip_code = $delsched_finalwip_item->wip_code;

    // 		$tab_sap_inventoryfg = DB::table('sap_inventory_fg')->where('item_code','=',$val_wip_code)->first();
    // 		$val_stock = $tab_sap_inventoryfg->stock;

    // 		$ins_stockwip = array('item_code' => $val_wip_code, 'quantity' => $val_stock, 'total_after' => $val_stock);
    // 		delsched_stockwip::insert($ins_stockwip);

    // 	}

    // 	// $tab_delsched_finalwip = DB::table('delsched_finalwip')->orderBy('id','asc')->get();

    // 	// foreach($tab_delsched_finalwip as $delsched_finalwip){

    // 	// 	$date_now = Carbon::now();

    // 	// 	$val_finalwip_id = $delsched_finalwip->id;
    // 	// 	$val_wip_code_finalwip = $delsched_finalwip->wip_code;
    // 	// 	$val_delivery_date = $delsched_finalwip->delivery_date;
    // 	// 	$val_req_quantity = $delsched_finalwip->req_quantity;
    // 	// 	$val_outstanding_wip = $delsched_finalwip->outstanding_wip;

    // 	// 	$tab_delsched_stockwip = DB::table('delsched_stockwip')->where('item_code','=',$val_wip_code_finalwip)->first();
    // 	// 	$val_stockwip_id = $tab_delsched_stockwip->id;
    // 	// 	$val_stockwip_new = $tab_delsched_stockwip->quantity;
    // 	// 	$val_total_after = $tab_delsched_stockwip->total_after;

    // 	// 	if($val_total_after < 0){

    // 	// 		$cal_outstanding_wip_new = $val_req_quantity;
    // 	// 		$cal_total_after_now = $val_total_after-$val_req_quantity;

    // 	// 		if($val_delivery_date <= $date_now){
    // 	// 				$rcd_status = 'danger';
    // 	// 		} else {
    // 	// 			$rcd_status = 'light';
    // 	// 		}

    // 	// 	} else {
    // 	// 		if($val_total_after >= $val_req_quantity){

    // 	// 			$cal_outstanding_wip_new = 0;
    // 	// 			$cal_total_after_now = $val_total_after-$val_req_quantity;

    // 	// 			if($val_delivery_date <= $date_now){
    // 	// 					$rcd_status = 'danger';
    // 	// 			} else {
    // 	// 				$rcd_status = 'light';
    // 	// 			}

    // 	// 		} else {

    // 	// 			$cal_outstanding_wip_new = $val_req_quantity-$val_total_after;
    // 	// 			$cal_total_after_now = $val_total_after-$val_req_quantity;

    // 	// 			if($val_delivery_date <= $date_now){
    // 	// 				$rcd_status = 'danger';
    // 	// 			} else {
    // 	// 				$rcd_status = 'light';
    // 	// 			}

    // 	// 		}
    // 	// 	}

    // 	// 	$update_stock = DB::table('delsched_stockwip')->where('id', $val_stockwip_id)->update([
    // 	// 			'total_after' => $cal_total_after_now,
    // 	// 		]);

    // 	// 	$update_final = DB::table('delsched_finalwip')->where('id', $val_finalwip_id)->update([
    // 	// 		'stock_wip' => $val_stockwip_new,
    // 	// 		'balance_wip' => $cal_total_after_now,
    // 	// 		'outstanding_wip' => $cal_outstanding_wip_new,
    // 	// 		'status' => $rcd_status,
    // 	// 	]);
    // 	// }

    // 	$tab_delsched_finalwip = DB::table('delsched_finalwip')
    // 		->orderBy('id','asc')
    // 		->get();

    // 	foreach ($tab_delsched_finalwip as $delsched_finalwip) {

    // 		$today = Carbon::today();

    // 		$finalwip_id     = $delsched_finalwip->id;
    // 		$dept            = $delsched_finalwip->departement;
    // 		$wip_code        = $delsched_finalwip->wip_code;
    // 		$delivery_date   = Carbon::parse($delsched_finalwip->delivery_date);
    // 		$req_qty         = $delsched_finalwip->req_quantity;
    // 		$stock_wip       = $delsched_finalwip->stock_wip;
    // 		$outstanding_wip = $delsched_finalwip->outstanding_wip;

    // 		// ======================
    // 		// GET STOCK WIP
    // 		// ======================
    // 		$stockwip = DB::table('delsched_stockwip')
    // 			->where('item_code', $wip_code)
    // 			->first();

    // 		if (!$stockwip) continue;

    // 		$stockwip_id   = $stockwip->id;
    // 		$stock_qty     = $stockwip->quantity;
    // 		$total_after   = $stockwip->total_after;

    // 		// ======================
    // 		// CEK CLOSE
    // 		// ======================
    // 		if ($req_qty == $stock_wip) {

    // 			DB::table('delsched_finalwip')->where('id', $finalwip_id)->update([
    // 				'stock_wip'   => $stock_qty,
    // 				'balance_wip' => $total_after,
    // 				'status'  => 'SUCCESS',
    // 				'remark'  => 'SAFE'
    // 			]);

    // 			continue;
    // 		}

    // 		// ======================
    // 		// HITUNG STOCK BERJALAN
    // 		// ======================
    // 		if ($total_after >= $req_qty) {

    // 			$cal_outstanding_wip = 0;
    // 			$cal_total_after_now = $total_after - $outstanding_wip;
    // 			$stock_ready = true;
    // 			$cal_outstanding_wip_new = 0;

    // 		} else {

    // 			$cal_total_after_now = $total_after-$req_qty;

    // 			$stock_ready = false;
    // 			$cal_outstanding_wip_new = $cal_total_after_now;
    // 		}

    // 		// ======================
    // 		// TENTUKAN DAY LIMIT
    // 		// ======================
    // 		$day_limit = 0;

    // 		if ($dept == 390) {
    // 			$day_limit = 7;
    // 		} elseif (in_array($dept, [361, 362])) {
    // 			$day_limit = 4;
    // 		}

    // 		$limit_date = $today->copy()->addDays($day_limit);

    // 		// ======================
    // 		// CEK STATUS & REMARK
    // 		// ======================
    // 		if ($delivery_date->lte($limit_date)) {

    // 			// ===== PAST DUE =====
    // 			if ($stock_ready) {
    // 				$rcd_status = 'WARNING';
    // 				$rcd_remark = 'PAST DUE DATE - STOCK READY';
    // 			} else {
    // 				$rcd_status = 'DANGER';
    // 				$rcd_remark = 'PAST DUE DATE - NO STOCK';
    // 			}

    // 		} else {

    // 			// ===== OPEN =====
    // 			if ($stock_ready) {
    // 				$rcd_status = 'MUTED';
    // 				$rcd_remark = 'OPEN - STOCK READY';
    // 			} else {
    // 				$rcd_status = 'INFO';
    // 				$rcd_remark = 'OPEN - NO STOCK';
    // 			}
    // 		}

    // 		// ======================
    // 		// UPDATE STOCK WIP
    // 		// ======================
    // 		DB::table('delsched_stockwip')->where('id', $stockwip_id)->update([
    // 			'total_after' => $cal_total_after_now
    // 		]);

    // 		// ======================
    // 		// UPDATE FINAL WIP
    // 		// ======================
    // 		DB::table('delsched_finalwip')->where('id', $finalwip_id)->update([
    // 			'stock_wip'            => $stock_qty,
    // 			'balance_wip'          => $cal_total_after_now,
    // 			'outstanding_wip'  => $cal_outstanding_wip_new,
    // 			'status'           => $rcd_status,
    // 			'remark'           => $rcd_remark,
    // 		]);
    // 	}

    // 	$now = new DateTime();
    // 	$now->modify('+420 minutes');

    // 	$tb_datelist = DB::table('uti_date_list')->where('id','14')->update([
    // 		'updated_at' => $now,
    // 	]);

    // 	$this->statusFinish();

    // 	return redirect()->route('indexfinalwip');
    // }

    // // =============================================
    // // CEK STATUS PROSES (Polling dari frontend)
    // // =============================================
    // public function checkStatus(Request $request)
    // {
    //     $key = $request->get('key', 'delsched_main');
    //     $log = DB::table('delsched_process_logs')->where('process_key', $key)->first();

    //     if (!$log) {
    //         return response()->json(['status' => 'idle', 'message' => 'Belum ada proses', 'step' => '-']);
    //     }

    //     return response()->json([
    //         'status'  => $log->status,
    //         'step'    => $log->current_step,
    //         'message' => $log->message,
    //         'updated' => $log->updated_at,
    //     ]);
    // }

    // public function statusFinish()
    // {
    // 	 // Retrieve all records from the delsched_final table
    // 	 $datas = delsched_final::get();

    // 	 $datas2 = delsched_finalwip::get();

    // 	 $today = Carbon::today();
    //  dd($today);

    // foreach ($datas as $data) {
    // 	 // Check if doc_status is 'C' or if delivery_qty is equal to delivered
    // 	 if ($data->doc_status === 'C' || $data->delivery_qty === $data->delivered) {
    // 		// Update the status to 'Finish' if the doc_status is 'C' or if delivery_qty equals delivered
    // 		$data->status = 'Finish';
    // 	} elseif ($data->doc_status === 'O' && $today->diffInDays($data->delivery_date, false) == 2) {
    // 		// Update the status to 'Danger' if the doc_status is 'O' and the delivery_date is 2 days from today
    // 		$data->status = 'Danger';
    // 	} elseif ($data->doc_status === 'O' && $today->diffInDays($data->delivery_date, false) == -2) {
    // 		// Update the status to 'Warning' if the doc_status is 'O' and the delivery_date was 2 days ago
    // 		$data->status = 'Warning';
    // 	} else {
    // 		// For all other conditions, set the status to 'Warning'
    // 		$data->status = 'Open';
    // 	}

    // 	$data->save();
    // }

    // foreach ($datas2 as $dataw) {
    // 	// Check if doc_status is 'C' or if delivery_qty is equal to delivered

    // 	if($dataw->balance_wip < 0) {
    // 		// Update the status to 'Finish'
    // 	   $dataw->status = 'Warning';
    //    	}

    // 	if($dataw->balance_wip > 0) {
    // 		 // Update the status to 'Finish'
    // 		$dataw->status = 'Finish';
    // 	}

    // 	if($dataw->balance_wip < 0 && $today->diffInDays($dataw->delivery_date, false) == 5) {
    // 		// Update the status to 'Danger'
    // 		$dataw->status = 'Danger';
    // 	}

    // 	//    if($today->diffInDays($dataw->delivery_date, false) == -5) {
    // 	// 	   // Update the status to 'Warning'
    // 	// 	   $dataw->status = 'Warning';
    // 	//    }

    // 	$dataw->save();
    // }

    // 	 // Output the updated data for verification
    // 	 return redirect()->route('indexfinalwip');
    // }

    public function exportToExcel()
    {
        return Excel::download(new DelschedFinalExport, 'delschedfinal.xlsx');
    }

    public function dashboardUser(Request $request)
    {
        // Get today's date
        $today = Carbon::today();

        // Get user input (days from today)
        $daysFromToday = $request->input('days', 0); // Assuming the input is sent as 'days'

        // Calculate the end date (today + user input days)
        $endDate = $today->copy()->addDays($daysFromToday);

        // dd($today, $endDate);

        // Query the database
        // Get all records where the delivery_date is within the range from today to the end date
        // And include records with status "warning" or "danger" with delivery_date before today
        $datas = DelschedFinal::where(function ($query) use ($today, $endDate) {
            // Delivery dates between today and end date
            $query->whereBetween('delivery_date', [$today, $endDate]);
        })
            ->orWhere(function ($query) use ($today) {
                // Delivery dates before today with status "warning" or "danger"
                $query->where('delivery_date', '<', $today)
                    ->whereIn('status', ['warning', 'danger']);
            })
            // Order results by custom status priority: "danger" first, then "warning", then the rest
            ->orderByRaw("FIELD(status, 'danger', 'warning') DESC")
            ->orderBy('delivery_date')  // You can also order by delivery_date if needed
            ->paginate(10)
            ->appends(['days' => $daysFromToday]);

        // Debugging: Display the fetched data

        return view('business.dashboard', compact('datas'));
    }

    public function deliveryManagement()
    {
        return view('business.delivery_management');
    }

    public function deleteDeliveryScheduleData(Request $request)
    {
        // Get the month and year from the request
        $selectedMonth = $request->input('month');
        $selectedYear = $request->input('year');

        // Convert the selected month and year to a date (1st day of the selected month)
        $startDate = Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Delete records from delsched_final based on the deliver_date range
        \DB::table('delsched_final')
            ->whereBetween('delivery_date', [$startDate, $endDate])
            ->delete();

        // Delete records from sap_delsched based on the deliver_date range
        \DB::table('sap_delsched')
            ->whereBetween('delivery_date', [$startDate, $endDate])
            ->delete();

        // Optionally, you can return a response or redirect
        return redirect()->back()->with('success', 'Delivery schedules deleted successfully.');
    }

    public function deliveryScheduleNewIndex(Request $request)
    {
        // Get filters from request
        $selectedYear = $request->input('year', date('Y'));
        $selectedMonth = $request->input('month', date('m'));
        $customerCode = $request->input('customer_code', '');

        // Get the number of days in the selected month
        $daysInMonth = Carbon::create($selectedYear, $selectedMonth)->daysInMonth;

        // Get unique customers for the selected month & year
        $customers = DeliveryScheduleNew::whereYear('delivery_date', $selectedYear)
            ->whereMonth('delivery_date', $selectedMonth)
            ->distinct()
            ->pluck('customer_code');

        // Query delivery data
        $query = DeliveryScheduleNew::whereYear('delivery_date', $selectedYear)
            ->whereMonth('delivery_date', $selectedMonth);

        // Apply customer filter if selected
        if (! empty($customerCode)) {
            $query->where('customer_code', $customerCode);
        }

        $deliveries = $query->get();

        // Group data by item_code and day
        $deliveryData = [];
        foreach ($deliveries as $delivery) {
            $itemCode = $delivery->item_code;
            $day = Carbon::parse($delivery->delivery_date)->day;

            if (! isset($deliveryData[$itemCode])) {
                $deliveryData[$itemCode] = array_fill(1, $daysInMonth, null);
            }

            // Sum quantities if same item_code and day
            $deliveryData[$itemCode][$day] = ($deliveryData[$itemCode][$day] ?? 0) + $delivery->delivery_quantity;
        }

        return view('business.newdeliveryindex', compact('selectedYear', 'selectedMonth', 'customerCode', 'customers', 'daysInMonth', 'deliveryData'));
    }

    public function exportDeliverySchedule(Request $request)
    {
        // dd($request->all());
        $selectedYear = $request->input('year');
        $selectedMonth = $request->input('month');
        $customerCode = $request->input('customer_code', '');

        // Export the data with two sheets
        return Excel::download(new DeliveryScheduleExport($selectedYear, $selectedMonth, $customerCode), 'delivery_schedule.xlsx');
    }

    public function exportTemplate(Request $request)
    {
        // Define the headings for the template
        $headings = [
            'SO Number',
            'Customer Code',
            'Delivery Date',
            'Item Code',
            'Delivery Quantity',
        ];

        // Create an array with example data
        $data = [
            // Example data row
            ['200412', '', '2024-04-01', 'GCOVA4521231', 200],
        ];

        // Use Laravel Excel to generate the Excel file
        return Excel::download(new class($data, $headings) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithTitle
        {
            private $data;

            private $headings;

            public function __construct($data, $headings)
            {
                $this->data = $data;
                $this->headings = $headings;
            }

            public function array(): array
            {
                return $this->data;
            }

            public function headings(): array
            {
                return $this->headings;
            }

            public function title(): string
            {
                return 'Delivery Schedule Template';
            }
        }, 'delivery_schedule_template.xlsx');
    }

    // public function importDeliverySchedule(Request $request)
    // {
    //     $request->validate([
    //         'file' => 'required|mimes:xlsx,xls'
    //     ]);

    //     Excel::import(new DeliveryScheduleImport, $request->file('file'));

    //     return redirect()->back()->with('success', 'Delivery schedule imported successfully.');
    // }

    public function importDeliverySchedule(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        // Store the uploaded file in storage/app/temp/
        $uploadedFile = $request->file('file');
        $fileName = $uploadedFile->getClientOriginalName();
        $filePath = storage_path("app/temp/{$fileName}");

        // Ensure directories exist
        if (! file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0777, true);
        }
        if (! file_exists(storage_path('app/temp/output'))) {
            mkdir(storage_path('app/temp/output'), 0777, true);
        }

        // Move the uploaded file to storage
        $uploadedFile->move(storage_path('app/temp/'), $fileName);

        // Define paths
        $pythonPath = env('PYTHON_PATH', 'python');
        $scriptPath = base_path('scripts/process_excel.py'); // Your Python script path
        $outputFilePath = storage_path("app/temp/output/processed_{$fileName}");

        // Run Python script with Process
        $process = new Process([$pythonPath, $scriptPath, $filePath, $outputFilePath]);
        $process->run();

        // Check for errors
        if (! $process->isSuccessful()) {
            throw new \Exception('Python script execution failed: ' . $process->getErrorOutput());
        }

        // Import the processed Excel file into the database using Laravel Excel
        Excel::import(new DeliveryScheduleImport, $outputFilePath);

        // Delete the processed file so next upload generates new data
        if (file_exists($outputFilePath)) {
            unlink($outputFilePath);
        }

        return redirect()->back()->with('success', 'Delivery schedule imported successfully.');
    }

    public function storeNewDelivery(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'customer_code' => 'required|string|max:255',
            'item_code' => 'required|string|max:255',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020',
            'so_num' => 'nullable|string|max:255',
            'schedules' => 'required|array',
            'schedules.*.date' => 'required|integer|between:1,31',
            'schedules.*.quantity' => 'required|integer|min:0',
        ], [
            'customer_code.required' => 'Customer code wajib diisi',
            'item_code.required' => 'Item code wajib diisi',
            'month.required' => 'Bulan wajib dipilih',
            'year.required' => 'Tahun wajib diisi',
            'schedules.required' => 'Schedule wajib diisi',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $inserted = 0;

            foreach ($request->schedules as $schedule) {
                // Skip jika quantity 0 atau kosong
                if (! isset($schedule['quantity']) || $schedule['quantity'] <= 0) {
                    continue;
                }

                // Buat tanggal lengkap
                $tanggal = Carbon::createFromDate(
                    $request->year,
                    $request->month,
                    $schedule['date']
                )->format('Y-m-d');

                // Insert ke database
                MasterDeliverySchedule::create([
                    'customer_code' => $request->customer_code,
                    'item_code' => $request->item_code,
                    'tanggal' => $tanggal,
                    'quantity' => $schedule['quantity'],
                    'so_num' => $request->so_num ?? null,
                ]);

                $inserted++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Berhasil menyimpan {$inserted} schedule delivery",
                'data' => [
                    'total_inserted' => $inserted,
                    'customer_code' => $request->customer_code,
                    'item_code' => $request->item_code,
                    'period' => Carbon::createFromDate($request->year, $request->month, 1)->format('F Y'),
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
