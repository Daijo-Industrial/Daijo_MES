<?php

namespace App\Http\Controllers;

use App\Models\Delivery\sapInventoryFg;
use App\Models\Delivery\sapInventoryMtr;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function showFgInventory(Request $request)
    {
        $query = sapInventoryFg::query();

        // Filter by Item Code
        if ($request->has('item_code') && $request->item_code) {
            $query->where('item_code', 'like', '%' . $request->item_code . '%');
        }

        // Filter by Item Name
        if ($request->has('item_name') && $request->item_name) {
            $query->where('item_name', 'like', '%' . $request->item_name . '%');
        }

        // Filter by Item Group
        if ($request->has('item_group') && $request->item_group) {
            $query->where('item_group', '=', $request->item_group);
        }

        $fgInventories = $query->get();

        return view('inventory.fg', compact('fgInventories'));
    }

    public function showMtrInventory(Request $request)
    {
        $query = sapInventoryMtr::query();

        // Filter by FG Code
        if ($request->has('fg_code') && $request->fg_code) {
            $query->where('fg_code', 'like', '%' . $request->fg_code . '%');
        }

        // Filter by Material Code
        if ($request->has('material_code') && $request->material_code) {
            $query->where('material_code', 'like', '%' . $request->material_code . '%');
        }

        // Filter by Material Name
        if ($request->has('material_name') && $request->material_name) {
            $query->where('material_name', 'like', '%' . $request->material_name . '%');
        }

        $mtrInventories = $query->get();

        return view('inventory.mtr', compact('mtrInventories'));
    }
}
