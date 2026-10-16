<?php

namespace App\Http\Controllers;

use App\Models\AssemblyDailyProcess;
use App\Models\MasterAllItem;
use Illuminate\Http\Request;

class AssemblyDailyController extends Controller
{
    public function index()
    {
        $datas = AssemblyDailyProcess::get();

        return view('assembly.index', compact('datas'));
    }

    public function create()
    {
        $schedule = AssemblyDailyProcess::get();

        return view('assembly.create');
    }

    public function searchItems(Request $request)
    {
        $query = $request->input('query');
        $items = MasterAllItem::where('item_code', 'like', "%{$query}%")
            ->orWhere('item_description', 'like', "%{$query}%")
            ->limit(50) // Limit results to improve performance
            ->get();

        return response()->json($items);
    }

    public function getItemDescription(Request $request)
    {
        $itemCode = $request->query('item_code');

        // Validate that the item code was provided
        if (! $itemCode) {
            return response()->json(['error' => 'Item code is required'], 400);
        }

        // Retrieve the item from the database
        $item = MasterAllItem::where('item_code', $itemCode)->first();

        // Check if the item exists
        if (! $item) {
            return response()->json(['error' => 'Item not found'], 404);
        }

        // Return the item description
        return response()->json(['item_description' => $item->item_description]);
    }

    public function store(Request $request)
    {

        $data = $request->validate([
            'plan_date' => 'required|date',
            'line' => 'required|array',
            'item_code' => 'required|array',
            'item_description' => 'required|array',
            'quantity' => 'required|array',
            'remark' => 'required|array',
        ]);

        // Loop through each entry based on the number of items
        foreach ($data['line'] as $index => $line) {
            AssemblyDailyProcess::create([
                'plan_date' => $data['plan_date'],
                'line' => $line,
                'item_code' => $data['item_code'][$index],
                'item_description' => $data['item_description'][$index],
                'quantity' => $data['quantity'][$index],
                'remark' => $data['remark'][$index],
            ]);
        }

        return redirect()->route('second.daily.process.index')
            ->with('success', 'Entries have been added successfully.');
    }
}
