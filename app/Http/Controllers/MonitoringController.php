<?php

namespace App\Http\Controllers;

use App\Models\ProductionScannedData;

class MonitoringController extends Controller
{
    // Halaman pilih SPK
    public function index()
    {
        // Ambil daftar SPK unik
        $spkList = ProductionScannedData::select('spk_code')->distinct()->get();

        return view('monitoring.indexShowScannedData', compact('spkList'));
    }

    // Show detail SPK
    public function show($spk)
    {
        // Ambil data scanned + hourly remarks
        $data = ProductionScannedData::with([
            'ParentDailyItemCode.hourlyRemarks.ngDetails.ngType',
            'ParentDailyItemCode.user',
        ])
            ->where('spk_code', $spk)
            ->get();

        // dd($data);
        return view('monitoring.showScannedData', compact('data', 'spk'));
    }
}
