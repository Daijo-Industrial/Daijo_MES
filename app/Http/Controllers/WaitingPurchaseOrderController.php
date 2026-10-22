<?php

namespace App\Http\Controllers;

use App\Models\WaitingPurchaseOrder;
use App\Services\FileService;
use Illuminate\Http\Request;

class WaitingPurchaseOrderController extends Controller
{
    protected $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orders = WaitingPurchaseOrder::orderBy('status', 'asc')->get();
        $total = $orders->sum('price');

        return view('waiting_purchase_orders.index', compact('orders', 'total'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('waiting_purchase_orders.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'mold_name' => 'required|string|max:255',
            'process' => 'required|string|max:255',
            'price' => 'required|string',
            'quotation_no' => 'required|string|max:255',
            'remark' => 'nullable|string',
            'attached_files.*' => 'file|max:4096',
        ]);

        // dd($request->attached_files);

        // Convert price to numeric format
        $price = str_replace(',', '', $request->input('price'));

        // Create the record
        $waitingPurchaseOrder = WaitingPurchaseOrder::create([
            'mold_name' => $request->mold_name,
            'process' => $request->process,
            'price' => $price,
            'quotation_no' => $request->quotation_no,
            'remark' => $request->remark,
        ]);

        // Use the FileService to handle attached files
        if ($request->hasFile('attached_files')) {
            $this->fileService->uploadFiles($request->file('attached_files'), $waitingPurchaseOrder->doc_num);
        }

        return redirect()->route('waiting_purchase_orders.index')->with('success', 'Purchase Order created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $waitingPurchaseOrder = WaitingPurchaseOrder::find($id);

        return view('waiting_purchase_orders.show', compact('waitingPurchaseOrder'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $waitingPurchaseOrder = WaitingPurchaseOrder::find($id);

        return view('waiting_purchase_orders.edit', compact('waitingPurchaseOrder'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'mold_name' => 'required|string|max:255',
            'process' => 'required|string|max:255',
            'price' => 'required|string',
            'quotation_no' => 'required|string|max:255',
            'remark' => 'nullable|string',
        ]);

        // Find the existing record
        $waitingPurchaseOrder = WaitingPurchaseOrder::findOrFail($id);

        // Convert price to numeric format
        $price = str_replace(',', '', $request->input('price'));

        // Update the record
        $waitingPurchaseOrder->update([
            'mold_name' => $request->mold_name,
            'process' => $request->process,
            'price' => $price,
            'quotation_no' => $request->quotation_no,
            'remark' => $request->remark,
        ]);

        return redirect()->route('waiting_purchase_orders.index')->with('success', 'Purchase Order updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $waitingPurchaseOrder = WaitingPurchaseOrder::find($id);
        $waitingPurchaseOrder->delete();

        return redirect()->route('waiting_purchase_orders.index')->with('success', 'Purchase Order deleted successfully.');
    }

    /**
     * Change the specified resource status.
     */
    public function changeStatus(Request $request, string $id)
    {
        $waitingPurchaseOrder = WaitingPurchaseOrder::find($id);
        $waitingPurchaseOrder->status = $request->status;
        $waitingPurchaseOrder->save();

        return redirect()->route('waiting_purchase_orders.index')->with('success', 'Status changed successfully!');
    }
}
