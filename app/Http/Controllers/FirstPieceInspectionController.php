<?php

namespace App\Http\Controllers;

use App\Models\FirstPieceInspection;
use App\Models\SpWorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FirstPieceInspectionController extends Controller
{
    public const DEFAULT_CHECK_POINTS = [
        'Dirty Spray',
        'Over Spray',
        'Peel Off',
        'Rough Spray',
        'Shading Trace',
        'Under Spray',
        'Wet Spray',
    ];

    public function index(Request $request)
    {
        $query = FirstPieceInspection::query();

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }
        if ($request->filled('overall_judgement')) {
            $query->where('overall_judgement', $request->overall_judgement);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('model', 'LIKE', "%{$search}%")
                    ->orWhere('part_number', 'LIKE', "%{$search}%")
                    ->orWhere('part_name', 'LIKE', "%{$search}%");
            });
        }

        $inspections = $query->with('workOrder')->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(25)->withQueryString();

        return view('first_piece.index', compact('inspections'));
    }

    public function create(Request $request)
    {
        $inspection = new FirstPieceInspection([
            'date' => $request->get('date', date('Y-m-d')),
            'part_number' => $request->get('part_number', ''),
            'part_name' => $request->get('part_name', ''),
            'model' => $request->get('model', ''),
        ]);

        $workOrderId = $request->get('work_order_id');
        $workOrder = $workOrderId ? SpWorkOrder::find($workOrderId) : null;
        $defaultCheckPoints = self::DEFAULT_CHECK_POINTS;
        $chemicalProcesses = config('mes.chemical_processes', ['Painting', 'Printing', 'Silk Screen', 'Tampoprint', 'Cat']);
        $initialProcess = $workOrder ? $workOrder->process_prod : '';

        return view('first_piece.create', compact('inspection', 'defaultCheckPoints', 'workOrderId', 'workOrder', 'chemicalProcesses', 'initialProcess'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateInspection($request);

        if ($request->boolean('auto_approve')) {
            $validated['checked_by'] = auth()->user()->name;
            $validated['checked_at'] = now();
            // Automatically set prepared_by if empty
            if (empty($validated['prepared_by'])) {
                $validated['prepared_by'] = auth()->user()->name;
                $validated['prepared_at'] = now();
            }
        }

        DB::transaction(function () use ($request, $validated, &$inspection) {
            $inspection = FirstPieceInspection::create($validated);

            $this->handleAttachments($request, $inspection);
        });

        return redirect()->route('first-piece-inspections.index')
            ->with('success', 'First Piece Inspection logged successfully.');
    }

    public function show($id)
    {
        $inspection = FirstPieceInspection::with('attachments')->findOrFail($id);

        return view('first_piece.show', compact('inspection'));
    }

    public function edit($id)
    {
        $inspection = FirstPieceInspection::with(['attachments', 'workOrder'])->findOrFail($id);

        if ($inspection->checked_at !== null) {
            return redirect()->route('first-piece-inspections.show', $id)
                ->with('error', 'Inspection already checked by QC cannot be edited.');
        }

        $workOrderId = $inspection->work_order_id;
        $workOrder = $inspection->workOrder;
        $defaultCheckPoints = self::DEFAULT_CHECK_POINTS;
        $chemicalProcesses = config('mes.chemical_processes', ['Painting', 'Printing', 'Silk Screen', 'Tampoprint', 'Cat']);
        $initialProcess = $inspection->paint_code || $inspection->thinner_code || $inspection->ink_code || $inspection->viscosity ? 'Painting' : '';

        return view('first_piece.edit', compact('inspection', 'workOrderId', 'workOrder', 'defaultCheckPoints', 'chemicalProcesses', 'initialProcess'));
    }

    public function update(Request $request, $id)
    {
        $inspection = FirstPieceInspection::findOrFail($id);

        if ($inspection->checked_at !== null) {
            return redirect()->route('first-piece-inspections.show', $id)
                ->with('error', 'Inspection already checked by QC cannot be edited.');
        }

        $validated = $this->validateInspection($request);
        $validated['work_order_id'] = $validated['work_order_id'] ?? $inspection->work_order_id;

        DB::transaction(function () use ($request, $validated, $inspection) {
            $inspection->update($validated);

            // Handle deleted attachments
            if ($request->has('delete_attachments')) {
                foreach ($request->delete_attachments as $attachId) {
                    $attachment = $inspection->attachments()->find($attachId);
                    if ($attachment) {
                        Storage::disk('public')->delete($attachment->file_path);
                        $attachment->delete();
                    }
                }
            }

            $this->handleAttachments($request, $inspection);
        });

        return redirect()->route('first-piece-inspections.show', $inspection->id)
            ->with('success', 'First Piece Inspection updated successfully.');
    }

    public function destroy($id)
    {
        $inspection = FirstPieceInspection::with('attachments')->findOrFail($id);

        if ($inspection->checked_at !== null) {
            return redirect()->route('first-piece-inspections.show', $id)
                ->with('error', 'Inspection checked by QC cannot be deleted.');
        }

        foreach ($inspection->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $inspection->delete();

        return redirect()->route('first-piece-inspections.index')
            ->with('success', 'First Piece Inspection deleted successfully.');
    }

    public function sign(Request $request, $id, $role)
    {
        $inspection = FirstPieceInspection::findOrFail($id);
        $user = auth()->user();

        switch ($role) {
            case 'prepared':
                $inspection->update([
                    'prepared_by' => $user->name,
                    'prepared_at' => now(),
                ]);
                break;

            case 'checked':
                $inspection->update([
                    'checked_by' => $user->name,
                    'checked_at' => now(),
                ]);
                break;

            case 'approved':
                if (empty($inspection->checked_at)) {
                    return redirect()->back()->withErrors(['error' => 'QC Leader approval requires prior QC Inspector check.']);
                }
                $inspection->update([
                    'approved_by' => $user->name,
                    'approved_at' => now(),
                ]);
                break;

            default:
                return redirect()->back()->withErrors(['error' => 'Invalid approval role.']);
        }

        return redirect()->route('first-piece-inspections.show', $id)
            ->with('success', ucfirst($role) . ' signature applied successfully.');
    }

    public function checkApproval(Request $request)
    {
        $partNumber = $request->get('part_number');
        $date = $request->get('date');

        if (! $partNumber || ! $date) {
            return response()->json(['approved' => false, 'message' => 'part_number and date required']);
        }

        $inspection = FirstPieceInspection::where('part_number', $partNumber)
            ->whereDate('date', $date)
            ->orderBy('id', 'desc')
            ->first();

        if (! $inspection) {
            return response()->json(['approved' => false, 'inspection' => null]);
        }

        $isApproved = $inspection->overall_judgement === 'OK' && $inspection->checked_at !== null;

        return response()->json([
            'approved' => $isApproved,
            'inspection' => [
                'id' => $inspection->id,
                'overall_judgement' => $inspection->overall_judgement,
                'checked_by' => $inspection->checked_by,
                'checked_at' => $inspection->checked_at ? $inspection->checked_at->format('Y-m-d H:i') : null,
                'approved_by' => $inspection->approved_by,
            ],
        ]);
    }

    private function validateInspection(Request $request): array
    {
        $validated = $request->validate([
            'work_order_id' => 'nullable|exists:sp_work_orders,id',
            'date' => 'required|date',
            'model' => 'nullable|string',
            'part_name' => 'required|string',
            'part_number' => 'required|string',
            'paint_code' => 'nullable|string',
            'thinner_code' => 'nullable|string',
            'ink_code' => 'nullable|string',
            'viscosity' => 'nullable|string',
            'cycle_time' => 'nullable|string',
            'remark' => 'nullable|string',
            'check_results' => 'nullable|array',
            'check_results.*.check_point' => 'required|string',
            'check_results.*.method' => 'nullable|string',
            'check_results.*.result' => 'nullable|string',
            'check_results.*.judgement' => 'nullable|string',
        ]);

        // Evaluate overall judgement
        $overall = 'OK';
        if (isset($validated['check_results'])) {
            foreach ($validated['check_results'] as $res) {
                $judgement = strtoupper(trim($res['judgement'] ?? $res['result'] ?? 'NG'));
                if ($judgement === 'NG') {
                    $overall = 'NG';
                    break;
                }
            }
        }
        $validated['overall_judgement'] = $overall;

        return $validated;
    }

    private function handleAttachments(Request $request, FirstPieceInspection $inspection): void
    {
        if ($request->hasFile('qc_files')) {
            foreach ($request->file('qc_files') as $idx => $file) {
                if ($file->isValid()) {
                    $path = $file->store('qc-attachments/' . date('Y/m'), 'public');
                    $label = $request->input("qc_file_labels.{$idx}", 'First Piece Attachment');

                    $inspection->attachments()->create([
                        'file_path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'label' => $label,
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }
        }
    }
}
