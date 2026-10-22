<?php

namespace App\Http\Controllers;

use App\Models\FirstPieceInspection;
use App\Models\SpWorkOrder;
use Illuminate\Http\Request;

class SpWorkOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = SpWorkOrder::with(['creator', 'sessions.operator']);

        if ($request->filled('date_from')) {
            $query->whereDate('planned_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('planned_date', '<=', $request->date_to);
        }
        if ($request->filled('unit_line')) {
            $query->where('unit_line', $request->unit_line);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('wo_number', 'LIKE', "%{$search}%")
                    ->orWhere('part_number', 'LIKE', "%{$search}%")
                    ->orWhere('part_name', 'LIKE', "%{$search}%")
                    ->orWhere('customer', 'LIKE', "%{$search}%");
            });
        }

        $workOrders = $query->orderBy('planned_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        $partNumbers = $workOrders->pluck('part_number')->filter()->unique();
        $firstPieceMap = FirstPieceInspection::whereIn('part_number', $partNumbers)
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('part_number');

        $lines = array_values(config('mes.sp_lines', []));
        $processes = ['Assembly', 'Painting', 'Buffing', 'Amplas', 'Trimming', 'Printing', 'Packing', 'Rework', 'Repair'];

        return view('sp_work_orders.index', compact('workOrders', 'firstPieceMap', 'lines', 'processes'));
    }

    public function create()
    {
        $woNumber = SpWorkOrder::generateWoNumber();
        $lines = array_values(config('mes.sp_lines', []));
        $processes = ['Assembly', 'Painting', 'Buffing', 'Amplas', 'Trimming', 'Printing', 'Packing', 'Rework', 'Repair'];

        return view('sp_work_orders.create', compact('woNumber', 'lines', 'processes'));
    }

    public function store(Request $request)
    {
        $isDraft = $request->input('action') === 'draft';

        if ($isDraft) {
            $validated = $request->validate([
                'wo_number' => 'required|string|unique:sp_work_orders,wo_number',
                'planned_date' => 'nullable|date',
                'unit_line' => 'required|string',
                'process_prod' => 'required|string',
                'part_number' => 'required|string',
                'part_name' => 'required|string',
                'model' => 'nullable|string',
                'customer' => 'nullable|string',
                'target_qty' => 'nullable|integer|min:0',
            ]);

            $validated['planned_date'] = $validated['planned_date'] ?? now()->toDateString();
            $cust = $request->input('customer');
            $validated['customer'] = (! empty($cust) && $cust !== '0' && $cust !== '-') ? $cust : 'N/A';
            $mod = $request->input('model');
            $validated['model'] = (! empty($mod) && $mod !== '0' && $mod !== '-') ? $mod : 'N/A';
            $validated['target_qty'] = $validated['target_qty'] ?? 0;
            $validated['status'] = 'draft';
        } else {
            $validated = $request->validate([
                'wo_number' => 'required|string|unique:sp_work_orders,wo_number',
                'planned_date' => 'required|date',
                'unit_line' => 'required|string',
                'process_prod' => 'required|string',
                'part_number' => 'required|string',
                'part_name' => 'required|string',
                'model' => 'nullable|string',
                'customer' => 'nullable|string',
                'target_qty' => 'required|integer|min:1',
            ]);
            $cust = $request->input('customer');
            $validated['customer'] = (! empty($cust) && $cust !== '0' && $cust !== '-') ? $cust : 'N/A';
            $mod = $request->input('model');
            $validated['model'] = (! empty($mod) && $mod !== '0' && $mod !== '-') ? $mod : 'N/A';
            $validated['status'] = 'planned';
        }

        $validated['created_by'] = auth()->id();

        $wo = SpWorkOrder::create($validated);

        $msg = $isDraft
            ? "Work Order {$wo->wo_number} saved as draft."
            : "Work Order {$wo->wo_number} created and released successfully.";

        return redirect()->route('sp-work-orders.show', $wo->id)->with('success', $msg);
    }

    public function show($id)
    {
        $workOrder = SpWorkOrder::with(['creator', 'sessions.operator', 'sessions.productionEntries', 'sessions.rejectEntries', 'sessions.downtimeEntries', 'sessions.manpowerEntries'])->findOrFail($id);

        $firstPiece = $workOrder->latestFirstPieceInspection
            ?? FirstPieceInspection::where('part_number', $workOrder->part_number)
                ->whereDate('date', $workOrder->planned_date)
                ->orderBy('id', 'desc')
                ->first()
            ?? FirstPieceInspection::where('part_number', $workOrder->part_number)
                ->orderBy('id', 'desc')
                ->first();

        return view('sp_work_orders.show', compact('workOrder', 'firstPiece'));
    }

    public function edit($id)
    {
        $workOrder = SpWorkOrder::findOrFail($id);

        if ($workOrder->status !== 'draft') {
            return redirect()->route('sp-work-orders.show', $id)
                ->with('error', 'Only draft Work Orders can be edited. Revert to draft first if no production has started.');
        }

        $lines = array_values(config('mes.sp_lines', []));
        $processes = ['Assembly', 'Painting', 'Buffing', 'Amplas', 'Trimming', 'Printing', 'Packing', 'Rework', 'Repair'];

        return view('sp_work_orders.edit', compact('workOrder', 'lines', 'processes'));
    }

    public function update(Request $request, $id)
    {
        $workOrder = SpWorkOrder::findOrFail($id);

        if ($workOrder->status !== 'draft') {
            return redirect()->route('sp-work-orders.show', $id)
                ->with('error', 'Only draft Work Orders can be edited.');
        }

        $isDraft = $request->input('action') === 'draft';

        if ($isDraft) {
            $validated = $request->validate([
                'planned_date' => 'nullable|date',
                'unit_line' => 'required|string',
                'process_prod' => 'required|string',
                'part_number' => 'required|string',
                'part_name' => 'required|string',
                'model' => 'nullable|string',
                'customer' => 'nullable|string',
                'target_qty' => 'nullable|integer|min:0',
            ]);
        } else {
            $validated = $request->validate([
                'planned_date' => 'required|date',
                'unit_line' => 'required|string',
                'process_prod' => 'required|string',
                'part_number' => 'required|string',
                'part_name' => 'required|string',
                'model' => 'nullable|string',
                'customer' => 'nullable|string',
                'target_qty' => 'required|integer|min:1',
            ]);
            $validated['status'] = 'planned';
        }

        $cust = $request->input('customer');
        $validated['customer'] = (! empty($cust) && $cust !== '0' && $cust !== '-') ? $cust : 'N/A';
        $mod = $request->input('model');
        $validated['model'] = (! empty($mod) && $mod !== '0' && $mod !== '-') ? $mod : 'N/A';

        $workOrder->update($validated);

        $msg = $isDraft
            ? "Draft Work Order {$workOrder->wo_number} updated."
            : "Work Order {$workOrder->wo_number} updated and released to production.";

        return redirect()->route('sp-work-orders.show', $workOrder->id)->with('success', $msg);
    }

    public function release($id)
    {
        $workOrder = SpWorkOrder::findOrFail($id);

        if ($workOrder->status !== 'draft') {
            return redirect()->route('sp-work-orders.show', $id)
                ->with('error', 'Only draft Work Orders can be released.');
        }

        if ($workOrder->target_qty < 1) {
            return redirect()->route('sp-work-orders.edit', $id)
                ->with('error', 'Please complete Target Quantity before releasing to production.');
        }

        $cust = $workOrder->customer;
        $cleanCust = (! empty($cust) && $cust !== '0' && $cust !== '-') ? $cust : 'N/A';
        $mod = $workOrder->model;
        $cleanMod = (! empty($mod) && $mod !== '0' && $mod !== '-') ? $mod : 'N/A';

        $workOrder->update([
            'customer' => $cleanCust,
            'model' => $cleanMod,
            'status' => 'planned',
        ]);

        return redirect()->route('sp-work-orders.show', $workOrder->id)
            ->with('success', "Work Order {$workOrder->wo_number} released to production successfully.");
    }

    public function revertToDraft($id)
    {
        $workOrder = SpWorkOrder::with('sessions')->findOrFail($id);

        if ($workOrder->status !== 'planned') {
            return redirect()->route('sp-work-orders.show', $id)
                ->with('error', 'Only planned Work Orders can be reverted to draft.');
        }

        if ($workOrder->sessions->count() > 0) {
            return redirect()->route('sp-work-orders.show', $id)
                ->with('error', 'Cannot revert Work Order to draft because production sessions have already been logged.');
        }

        $workOrder->update(['status' => 'draft']);

        return redirect()->route('sp-work-orders.show', $workOrder->id)
            ->with('success', "Work Order {$workOrder->wo_number} reverted to draft mode.");
    }

    public function destroy($id)
    {
        $workOrder = SpWorkOrder::findOrFail($id);

        if (! in_array($workOrder->status, ['draft', 'planned'])) {
            return redirect()->back()
                ->with('error', 'Cannot delete Work Orders that are already in progress or completed.');
        }

        $workOrder->delete();

        return redirect()->route('sp-work-orders.index')
            ->with('success', 'Work Order deleted successfully.');
    }
}
