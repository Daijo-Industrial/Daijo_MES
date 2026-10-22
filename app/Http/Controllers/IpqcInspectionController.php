<?php

namespace App\Http\Controllers;

use App\Models\IpqcCheckItem;
use App\Models\IpqcInspection;
use App\Models\IpqcInspectionRecord;
use App\Models\IpqcMeasurementConfig;
use App\Models\MasterListItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IpqcInspectionController extends Controller
{
    public function index(Request $request)
    {
        $query = IpqcInspection::query();

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }
        if ($request->filled('part_number')) {
            $query->where('part_number', 'like', '%' . $request->part_number . '%');
        }
        if ($request->filled('shift')) {
            $query->where('shift', $request->shift);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('part_number', 'like', "%{$search}%")
                    ->orWhere('part_name', 'like', "%{$search}%")
                    ->orWhere('customer', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('unit_line', 'like', "%{$search}%");
            });
        }

        $inspections = $query->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(25)->withQueryString();

        return view('ipqc.index', compact('inspections'));
    }

    public function create()
    {
        $inspection = new IpqcInspection;
        $ipqcCheckItems = IpqcCheckItem::active()->ordered()->get();
        $ipqcMeasurements = IpqcMeasurementConfig::active()->ordered()->get();

        return view('ipqc.create', compact('inspection', 'ipqcCheckItems', 'ipqcMeasurements'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateInspection($request);

        return DB::transaction(function () use ($validated, $request) {
            $inspectionData = $this->calculateTotals($validated);
            $inspectionData['created_by'] = auth()->id();

            $inspection = IpqcInspection::create($inspectionData);
            $this->saveRecordsAndFiles($inspection, $validated['ipqc'] ?? [], $request);

            return redirect()->route('ipqc-inspections.edit', $inspection->id)
                ->with('success', 'IPQC Inspection created successfully. You can continue editing.');
        });
    }

    public function show($id)
    {
        $inspection = IpqcInspection::with(['records.attachments', 'attachments', 'creator'])->findOrFail($id);

        return view('ipqc.show', compact('inspection'));
    }

    public function edit($id)
    {
        $inspection = IpqcInspection::with('records.attachments')->findOrFail($id);

        if ($inspection->status !== 'ongoing') {
            return redirect()->route('ipqc-inspections.show', $id)
                ->with('error', 'Cannot edit a completed inspection.');
        }

        $ipqcCheckItems = IpqcCheckItem::active()->ordered()->get();
        $ipqcMeasurements = IpqcMeasurementConfig::active()->ordered()->get();

        return view('ipqc.edit', compact('inspection', 'ipqcCheckItems', 'ipqcMeasurements'));
    }

    public function update(Request $request, $id)
    {
        $inspection = IpqcInspection::findOrFail($id);

        if ($inspection->status !== 'ongoing') {
            return redirect()->route('ipqc-inspections.show', $id)
                ->with('error', 'Cannot update a completed inspection.');
        }

        $validated = $this->validateInspection($request);

        return DB::transaction(function () use ($validated, $request, $inspection) {
            $inspectionData = $this->calculateTotals($validated);
            if ($request->has('status') && $request->status === 'completed') {
                $inspectionData['status'] = 'completed';
            }
            $inspection->update($inspectionData);

            // Delete old records (attachments cascade or handled if polymorphic relations aren't cascaded by DB - though usually they are or we manually manage)
            // But we will just delete the records. In Laravel, if cascade delete is not on polymorphic, we might leave orphans,
            // however it's standard to either manually delete or leave them if files are kept.
            // A safer way is to delete old records.
            // We will just delete records. The migration has cascadeOnDelete for inspection_id.
            $inspection->records()->delete();
            $this->saveRecordsAndFiles($inspection, $validated['ipqc'] ?? [], $request);

            $route = $inspection->status === 'completed'
                ? route('ipqc-inspections.show', $inspection->id)
                : route('ipqc-inspections.edit', $inspection->id);

            return redirect($route)->with('success', 'IPQC Inspection updated successfully.');
        });
    }

    public function searchItems(Request $request)
    {
        $term = $request->query('q', '');

        $items = MasterListItem::where(function ($query) use ($term) {
            $query->where('item_number', 'like', "%{$term}%")
                ->orWhere('item_name', 'like', "%{$term}%")
                ->orWhere('model', 'like', "%{$term}%")
                ->orWhere('customer', 'like', "%{$term}%");
        })
            ->limit(20)
            ->get();

        $results = $items->map(function ($item) {
            return [
                'id' => $item->item_number,
                'text' => $item->item_number . ' - ' . $item->item_name,
                'item_name' => $item->item_name,
                'model' => $item->model,
                'customer' => $item->customer,
            ];
        });

        return response()->json(['results' => $results]);
    }

    private function validateInspection(Request $request)
    {
        return $request->validate([
            'date' => 'required|date',
            'part_number' => 'required|string',
            'shift' => 'required|string',
            'unit_line' => 'required|string',
            'process_prod' => 'nullable|string',
            'model' => 'nullable|string',
            'part_name' => 'nullable|string',
            'customer' => 'nullable|string',
            'lot_color' => 'nullable|string',
            'std_glossy' => 'nullable|string',
            'std_viscosity' => 'nullable|string',
            'std_oven_temp' => 'nullable|string',
            'product_color' => 'nullable|string',
            'app_sample' => 'nullable|string',
            'selected_measurements' => 'nullable|array',
            'inspector_name' => 'nullable|string',
            'checker_name' => 'nullable|string',
            'status' => 'nullable|string',

            'ipqc' => 'nullable|array',
            'ipqc.*.hour_ke' => 'required|integer',
            'ipqc.*.fitting_test' => 'nullable|string',
            'ipqc.*.tape_test_judgement' => 'nullable|string',
            'ipqc.*.appearance_checks' => 'nullable|array',
            'ipqc.*.condition_checks' => 'nullable|array',
            'ipqc.*.measurements' => 'nullable|array',
            'ipqc.*.output_qty' => 'nullable|integer',
            'ipqc.*.sample_qty' => 'nullable|integer',
            'ipqc.*.reject_sample_qty' => 'nullable|integer',
            'ipqc.*.pass_qty' => 'nullable|integer',
            'ipqc.*.reject_qty' => 'nullable|integer',
            'ipqc.*.judgement' => 'nullable|string',
        ]);
    }

    private function calculateTotals(array $validated)
    {
        $totals = [
            'total_output' => 0,
            'total_sample' => 0,
            'total_reject_sample' => 0,
            'total_reject_rate' => 0,
            'total_pass' => 0,
            'total_reject' => 0,
        ];

        if (! empty($validated['ipqc'])) {
            foreach ($validated['ipqc'] as $record) {
                $totals['total_output'] += (int) ($record['output_qty'] ?? 0);
                $totals['total_sample'] += (int) ($record['sample_qty'] ?? 0);
                $totals['total_reject_sample'] += (int) ($record['reject_sample_qty'] ?? 0);
                $totals['total_pass'] += (int) ($record['pass_qty'] ?? 0);
                $totals['total_reject'] += (int) ($record['reject_qty'] ?? 0);
            }
            if ($totals['total_sample'] > 0) {
                $totals['total_reject_rate'] = ($totals['total_reject_sample'] / $totals['total_sample']) * 100;
            }
        }

        $baseData = collect($validated)->except(['ipqc'])->toArray();

        return array_merge($baseData, $totals);
    }

    private function saveRecordsAndFiles(IpqcInspection $inspection, array $records, Request $request)
    {
        foreach ($records as $index => $recordData) {
            $recordData['inspection_id'] = $inspection->id;
            // Clean up arrays if needed or let Eloquent cast handle it
            $record = IpqcInspectionRecord::create($recordData);

            // Handle file uploads for this record
            if ($request->hasFile("ipqc.{$index}.files")) {
                foreach ($request->file("ipqc.{$index}.files") as $file) {
                    $path = $file->store('ipqc-files', 'public');
                    $record->attachments()->create([
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }
        }
    }
}
