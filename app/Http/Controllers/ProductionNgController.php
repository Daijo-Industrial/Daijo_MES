<?php

namespace App\Http\Controllers;

use App\Models\ProductionNgType;
use Illuminate\Http\Request;

class ProductionNgController extends Controller
{
    public function index()
    {
        $ngTypes = ProductionNgType::orderBy('ng_type')->get(); // urutin biar rapi

        return view('production_ng_index', compact('ngTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'ng_type' => 'required|string|max:255|unique:production_ng_types,ng_type',
        ]);

        ProductionNgType::create([
            'ng_type' => $request->ng_type,
        ]);

        return redirect()->back()->with('success', 'NG Type berhasil ditambahkan.');
    }

    public function destroy($id)
    {
        ProductionNgType::findOrFail($id)->delete();

        return redirect()->back()->with('success', 'NG Type berhasil dihapus.');
    }
}
