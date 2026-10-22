<?php

namespace App\Http\Controllers;

use App\Models\MasterItemPhoto;
use App\Models\MasterListItem;
use Illuminate\Http\Request;

class MasterItemPhotoController extends Controller
{
    // page utama
    public function index()
    {
        $items = MasterListItem::with('photo')->get();

        return view('master_items.index', compact('items'));
    }

    // upload foto
    public function upload(Request $request, $itemCode)
    {
        $request->validate([
            'photo' => 'required|mimes:jpg,jpeg,png',
        ]);

        $item = MasterListItem::where('item_code', $itemCode)->firstOrFail();

        // Simpan file di storage/public/item_photos
        $path = $request->file('photo')->store('item_photos', 'public');

        // cek apakah sudah ada
        $existing = MasterItemPhoto::where('item_code', $itemCode)->first();

        if ($existing) {
            // update foto
            $existing->photo_path = $path;
            $existing->save();
        } else {
            // create baru
            MasterItemPhoto::create([
                'item_code' => $item->item_code,
                'item_description' => $item->item_name,
                'standard_packaging' => $item->standart_packaging_list,
                'photo_path' => $path,
            ]);
        }

        return back()->with('success', 'Foto berhasil diupload.');
    }
}
