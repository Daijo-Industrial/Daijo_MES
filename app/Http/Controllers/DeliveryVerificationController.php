<?php

namespace App\Http\Controllers;

use App\Models\MasterItemPhoto;
use App\Models\ProductionScannedData;
use Illuminate\Http\Request;

// HOLD // should be fitur store untuk check item / scan item lalu muncul detail tentang barang tresebut
class DeliveryVerificationController extends Controller
{
    // Tampilkan halaman form scan
    public function index()
    {
        return view('delivery-verification.index');
    }

    // Proses scan / submit barcode
    public function check(Request $request)
    {

        $request->validate([
            'spk' => 'required|string',
        ]);

        // 1. Cari SPK di ProductionScannedData
        $scannedData = ProductionScannedData::where('spk_code', $request->spk)->first();

        if (! $scannedData) {
            return redirect()->back()->withErrors(['error' => 'SPK tidak ditemukan di data produksi.']);
        }

        // 2. Ambil item_code dari hasil scan
        $itemCode = $scannedData->item_code;

        // 3. Cari foto item berdasarkan item_code
        $itemPhoto = MasterItemPhoto::where('item_code', $itemCode)->first();

        // 4. Kirim ke view menggunakan item_code hasil lookup
        return view('delivery-verification.index', [
            'item' => $itemPhoto,
            'item_code' => $itemCode,
        ]);
    }
}
