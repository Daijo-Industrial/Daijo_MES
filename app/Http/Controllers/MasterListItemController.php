<?php

namespace App\Http\Controllers;

use App\Models\Delivery\sapLineProduction;
use App\Models\MasterListItem;
use App\Models\User;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class MasterListItemController extends Controller
{
    public function index()
    {
        $users = User::whereBetween('id', [13, 62])->pluck('name', 'id');

        return view('master-list-item.index', compact('users'));
    }

    public function generateMachineList(Request $request)
    {
        $machineName = $request->machine_name;
        $machines = sapLineProduction::where('line_production', $machineName)->get();

        $qrcodes = [];
        $images = [];

        foreach ($machines as $machine) {
            // Generate QR code data
            $qrCodeData = $machine->item_code;

            $qrCode = new QrCode(
                data: $qrCodeData,
                errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                size: 100,
                margin: 5
            );

            // Create the QR code image with PngWriter
            $writer = new PngWriter;
            $qrCodeResult = $writer->write($qrCode);

            // Get the PNG image as a string and base64 encode it
            $qrcodes[$machine->item_code] = base64_encode($qrCodeResult->getString());

            // Get only the correct item photo (PNG format)
            $storagePath = storage_path('app/public/files/');
            $files = File::glob($storagePath . '*-' . $machine->item_code . '.png'); // Only PNG files

            // If a PNG file exists, store its path; otherwise, use a default image
            $images[$machine->item_code] = ! empty($files)
                ? str_replace(storage_path('app/public/'), 'storage/', $files[0])
                : asset('storage/default-image.png'); // Default image if not found
        }

        return view('master-list-item.machine-list', compact('machines', 'qrcodes', 'machineName', 'images'));
    }

    public function storeFromSap(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_code' => 'required|string',
            'item_name' => 'required|string',
            'tipe_mesin' => 'required|string',
            'standart_packaging_list' => 'required|integer',
            'setup_time_minute' => 'nullable|string',
            'pair' => 'nullable|string',
            'cavity' => 'required|integer',
            'customer_code' => 'nullable|string',
            'cycle_time' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'error' => $validator->errors()->first(),
            ], 422);
        }

        DB::beginTransaction();

        try {

            $existing = MasterListItem::where('item_code', $request->item_code)->first();

            if ($existing) {

                // Cek apakah ada perubahan
                $isChanged =
                    $existing->item_name != $request->item_name ||
                    $existing->tipe_mesin != $request->tipe_mesin ||
                    $existing->standart_packaging_list != $request->standart_packaging_list ||
                    $existing->setup_time_minute != $request->setup_time_minute ||
                    $existing->pair != $request->pair ||
                    $existing->cavity != $request->cavity ||
                    $existing->customer_code != $request->customer_code ||
                    $existing->cycle_time != $request->cycle_time;

                if ($isChanged) {
                    $existing->update($request->only([
                        'item_name',
                        'tipe_mesin',
                        'standart_packaging_list',
                        'setup_time_minute',
                        'pair',
                        'cavity',
                        'customer_code',
                        'cycle_time',
                    ]));

                    DB::commit();

                    return response()->json([
                        'message' => 'Item updated',
                    ], 200);
                }

                DB::commit();

                return response()->json([
                    'message' => 'No changes detected',
                ], 200);

            } else {

                MasterListItem::create($request->all());

                DB::commit();

                return response()->json([
                    'message' => 'Item inserted',
                ], 201);
            }

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Failed to process data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function manage()
    {
        return view('master_list_manager.index');
    }

    public function logs()
    {
        return view('master_list_manager.logs');
    }

    public function customerDeliveryManage()
    {
        return view('master_customer_delivery.index');
    }

    public function customerDeliveryLogs()
    {
        return view('master_customer_delivery.logs');
    }
}
