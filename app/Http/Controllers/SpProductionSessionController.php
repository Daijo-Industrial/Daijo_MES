<?php

namespace App\Http\Controllers;

use App\Models\FirstPieceInspection;
use App\Models\SpDowntimeEntry;
use App\Models\SpProductionSession;
use App\Models\SpWorkOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpProductionSessionController extends Controller
{
    public function start(Request $request, $workOrderId)
    {
        $workOrder = SpWorkOrder::findOrFail($workOrderId);

        if (in_array($workOrder->status, ['completed', 'cancelled'])) {
            return redirect()->route('sp-work-orders.show', $workOrderId)
                ->with('error', 'Cannot start production on a completed or cancelled Work Order.');
        }

        // Check if active running session already exists for this WO
        $existingSession = SpProductionSession::where('work_order_id', $workOrderId)
            ->where('status', 'running')
            ->first();

        if ($existingSession) {
            return redirect()->route('app.sp-sessions.show', $existingSession->id)
                ->with('info', 'Resuming active production session.');
        }

        $isBypassed = false;
        $bypassReason = null;

        if ($request->boolean('bypass_qc')) {
            $request->validate([
                'bypass_reason' => 'required|string|min:3|max:255',
            ]);
            $isBypassed = true;
            $bypassReason = $request->input('bypass_reason');
        } else {
            // Validate QC-Approved First Piece Inspection before starting session (latest for part)
            $firstPiece = FirstPieceInspection::latestForPart($workOrder->part_number)->first();

            if (! $firstPiece || ! $firstPiece->isApproved()) {
                $reason = ! $firstPiece
                    ? 'No First Piece Inspection record found.'
                    : ($firstPiece->overall_judgement !== 'OK' ? "Overall judgement is {$firstPiece->overall_judgement}." : 'Pending QC signature.');

                return redirect()->back()
                    ->with('error', "Cannot start production: First Piece Inspection for Part {$workOrder->part_number} is not approved by QC ({$reason}).");
            }
        }

        $session = SpProductionSession::create([
            'work_order_id' => $workOrder->id,
            'operator_id' => auth()->id(),
            'unit_line' => $workOrder->unit_line,
            'shift' => $this->getCurrentShift(Carbon::now(config('mes.timezone', 'Asia/Jakarta'))),
            'status' => 'running',
            'started_at' => now(),
            'is_qc_bypassed' => $isBypassed,
            'qc_bypass_reason' => $bypassReason,
            'qc_bypassed_at' => $isBypassed ? now() : null,
            'qc_bypassed_by' => $isBypassed ? auth()->id() : null,
        ]);

        $workOrder->update(['status' => 'in_progress']);

        $message = $isBypassed
            ? "Production started with Emergency QC Bypass for Work Order {$workOrder->wo_number}."
            : "Production started for Work Order {$workOrder->wo_number}.";

        return redirect()->route('app.sp-sessions.show', $session->id)
            ->with('success', $message);
    }

    public function show($id)
    {
        $session = SpProductionSession::findOrFail($id);
        $session->recalculateTotals();

        $session->load([
            'workOrder',
            'operator',
            'productionEntries' => fn ($q) => $q->orderBy('recorded_at', 'desc'),
            'rejectEntries' => fn ($q) => $q->orderBy('created_at', 'desc'),
            'reworkEntries' => fn ($q) => $q->orderBy('created_at', 'desc'),
            'downtimeEntries' => fn ($q) => $q->orderBy('created_at', 'desc'),
            'inputEntries' => fn ($q) => $q->orderBy('created_at', 'desc'),
            'manpowerEntries' => fn ($q) => $q->orderBy('created_at', 'desc'),
        ]);

        $defectTypes = [
            'Flash',
            'Scratch',
            'Short Shot',
            'Sink Mark',
            'Burn Mark',
            'Assembly Defect',
            'Printing Defect',
            'Color Mis-match',
            'Contamination',
            'Others',
        ];

        $downtimeReasons = [
            'Waiting for Material',
            'Machine Breakdown',
            'Quality Hold',
            'Change Model',
            'No Operator',
            'Meeting',
            'Power Failure',
            'Maintenance',
        ];

        $sessionTotals = $this->getSessionTotalsArray($session);

        return view('sp_production.record', compact('session', 'defectTypes', 'downtimeReasons', 'sessionTotals'));
    }

    public function addProduction(Request $request, $id)
    {
        $session = SpProductionSession::findOrFail($id);

        if ($session->status !== 'running') {
            return response()->json(['error' => 'Cannot log production on a finished session.'], 422);
        }

        $validated = $request->validate([
            'good_qty' => 'required|integer|min:1',
            'remarks' => 'nullable|string',
        ]);

        $newOutput = $validated['good_qty'];

        $currentOutput = $session->total_good + $session->total_reject;
        if (($currentOutput + $newOutput) > $session->total_input) {
            $availableWip = max(0, $session->total_input - $currentOutput);

            return response()->json([
                'error' => "Total output cannot exceed available Input WIP ({$availableWip} Pcs available out of {$session->total_input} Pcs total). Please log Input WIP first.",
            ], 422);
        }

        $entry = $session->productionEntries()->create([
            'good_qty' => $validated['good_qty'],
            'recorded_at' => now(),
            'remarks' => $validated['remarks'] ?? null,
        ]);

        $session->recalculateTotals();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Production recorded successfully.',
                'entry' => $entry,
                'totals' => $this->getSessionTotalsArray($session),
            ]);
        }

        return redirect()->back()->with('success', 'Production recorded successfully.');
    }

    public function addReject(Request $request, $id)
    {
        $session = SpProductionSession::findOrFail($id);

        if ($session->status !== 'running') {
            return response()->json(['error' => 'Session is not running.'], 422);
        }

        $isAjax = $request->expectsJson() || $request->ajax() || $request->wantsJson();

        // Multi-defect batch payload
        $defects = $request->input('defects');
        if (is_array($defects) && count($defects) > 0) {
            $validated = $request->validate([
                'defects' => 'required|array|min:1',
                'defects.*.defect_type' => 'required|string',
                'defects.*.quantity' => 'required|integer|min:1',
                'defects.*.cause' => 'nullable|string',
                'remarks' => 'nullable|string',
            ]);

            $totalRequestedQty = array_sum(array_column($validated['defects'], 'quantity'));
            $currentOutput = $session->total_good + $session->total_reject;

            if (($currentOutput + $totalRequestedQty) > $session->total_input) {
                $availableWip = max(0, $session->total_input - $currentOutput);

                return response()->json([
                    'error' => "Total output cannot exceed available Input WIP ({$availableWip} Pcs available out of {$session->total_input} Pcs total). Please log Input WIP first.",
                ], 422);
            }

            $createdEntries = [];
            foreach ($validated['defects'] as $item) {
                $entry = $session->rejectEntries()->create([
                    'defect_type' => trim($item['defect_type']),
                    'quantity' => (int) $item['quantity'],
                    'cause' => isset($item['cause']) ? trim($item['cause']) : null,
                    'remarks' => isset($validated['remarks']) ? trim($validated['remarks']) : null,
                ]);
                $createdEntries[] = $entry;
            }

            $session->recalculateTotals();

            return response()->json([
                'success' => true,
                'message' => count($createdEntries) . ' defect record(s) logged successfully.',
                'entries' => $createdEntries,
                'entry' => $createdEntries[0] ?? null,
                'totals' => $this->getSessionTotalsArray($session),
            ]);
        }

        // Single defect fallback
        $validated = $request->validate([
            'defect_type' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'cause' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        $currentOutput = $session->total_good + $session->total_reject;
        if (($currentOutput + $validated['quantity']) > $session->total_input) {
            $availableWip = max(0, $session->total_input - $currentOutput);
            if ($isAjax) {
                return response()->json([
                    'error' => "Total output cannot exceed available Input WIP ({$availableWip} Pcs available out of {$session->total_input} Pcs total). Please log Input WIP first.",
                ], 422);
            }

            return redirect()->back()->with('error', "Total output cannot exceed available Input WIP ({$availableWip} Pcs available).");
        }

        $entry = $session->rejectEntries()->create($validated);
        $session->recalculateTotals();

        if ($isAjax) {
            return response()->json([
                'success' => true,
                'message' => 'Defect recorded successfully.',
                'entry' => $entry,
                'totals' => $this->getSessionTotalsArray($session),
            ]);
        }

        return redirect()->back()->with('success', 'Defect recorded successfully.');
    }

    public function addRework(Request $request, $id)
    {
        $session = SpProductionSession::findOrFail($id);

        if ($session->status !== 'running') {
            return response()->json(['error' => 'Session is not running.'], 422);
        }

        $isAjax = $request->expectsJson() || $request->ajax() || $request->wantsJson();

        // Mode A: Issue to Rework Bench (Cycle 1)
        if ($request->has('issue_qty') || ($request->has('input_qty') && ! $request->has('recovered_qty') && ! $request->has('scrapped_qty'))) {
            $validated = $request->validate([
                'issue_qty' => 'nullable|integer|min:1',
                'input_qty' => 'nullable|integer|min:1',
                'remarks' => 'nullable|string',
            ]);

            $qty = (int) ($validated['issue_qty'] ?? $validated['input_qty']);
            $rawReject = (int) $session->rejectEntries()->sum('quantity');
            $internalReworkIn = (int) $session->reworkEntries()
                ->where(function ($q) {
                    $q->where('remarks', 'not like', 'From Reworkable Input%')
                        ->orWhereNull('remarks');
                })
                ->sum('input_qty');
            $availableDefectStock = max(0, $rawReject - $internalReworkIn);

            if ($qty > $availableDefectStock) {
                return response()->json([
                    'error' => "Cannot issue more than available defect stock ({$availableDefectStock} Pcs available out of {$rawReject} Pcs total logged defects).",
                ], 422);
            }

            $entry = $session->reworkEntries()->create([
                'input_qty' => $qty,
                'recovered_qty' => 0,
                'scrapped_qty' => 0,
                'remarks' => $validated['remarks'] ?? 'Issued to Rework Bench',
            ]);
            $session->recalculateTotals();

            if ($isAjax) {
                return response()->json([
                    'success' => true,
                    'message' => "{$qty} Pcs issued to Rework Bench successfully.",
                    'entry' => $entry,
                    'totals' => $this->getSessionTotalsArray($session),
                ]);
            }

            return redirect()->back()->with('success', "{$qty} Pcs issued to Rework Bench.");
        }

        // Mode B: Log Rework Outcome (Cycle 2 or Single-Step Fallback)
        $validated = $request->validate([
            'input_qty' => 'nullable|integer|min:0',
            'recovered_qty' => 'required|integer|min:0',
            'scrapped_qty' => 'required|integer|min:0',
            'remarks' => 'nullable|string',
        ]);

        $totalOutcome = $validated['recovered_qty'] + $validated['scrapped_qty'];
        if ($totalOutcome < 1) {
            return response()->json(['error' => 'Please enter at least 1 recovered or scrapped piece.'], 422);
        }

        $pendingReworkWip = max(0, $session->total_rework_in - ($session->total_rework_recovered + (int) $session->total_scrap));

        $inputQty = (int) ($validated['input_qty'] ?? 0);
        if ($inputQty > 0) {
            if ($inputQty > $session->total_reject) {
                return response()->json(['error' => "Cannot rework more than total logged defects ({$session->total_reject} Pcs)."], 422);
            }
            if ($totalOutcome > $inputQty) {
                return response()->json(['error' => 'Recovered + Scrapped quantity cannot exceed Rework Input quantity.'], 422);
            }

            $entry = $session->reworkEntries()->create([
                'input_qty' => $inputQty,
                'recovered_qty' => $validated['recovered_qty'],
                'scrapped_qty' => $validated['scrapped_qty'],
                'remarks' => $validated['remarks'] ?? null,
            ]);
        } else {
            if ($totalOutcome > $pendingReworkWip && $session->total_rework_in > 0) {
                return response()->json(['error' => "Outcome ({$totalOutcome} Pcs) cannot exceed pending Rework Bench WIP ({$pendingReworkWip} Pcs)."], 422);
            }

            $effectiveInput = ($session->total_rework_in === 0) ? $totalOutcome : 0;

            $entry = $session->reworkEntries()->create([
                'input_qty' => $effectiveInput,
                'recovered_qty' => $validated['recovered_qty'],
                'scrapped_qty' => $validated['scrapped_qty'],
                'remarks' => $validated['remarks'] ?? 'Rework Outcome Logged',
            ]);
        }

        $session->recalculateTotals();

        if ($isAjax) {
            return response()->json([
                'success' => true,
                'message' => 'Rework outcome recorded successfully.',
                'entry' => $entry,
                'totals' => $this->getSessionTotalsArray($session),
            ]);
        }

        return redirect()->back()->with('success', 'Rework recorded successfully.');
    }

    public function addDowntime(Request $request, $id)
    {
        $session = SpProductionSession::findOrFail($id);

        if ($session->status !== 'running') {
            return response()->json(['error' => 'Session is not running.'], 422);
        }

        $validated = $request->validate([
            'reason' => 'required|string',
            'start_time' => 'required|date_format:H:i',
            'resume_time' => 'required|date_format:H:i',
            'remarks' => 'nullable|string',
        ]);

        $tz = config('mes.timezone', 'Asia/Jakarta');
        $sessionDate = $session->started_at?->setTimezone($tz)->format('Y-m-d') ?? Carbon::now($tz)->format('Y-m-d');

        $startDT = Carbon::createFromFormat('Y-m-d H:i', "{$sessionDate} {$validated['start_time']}", $tz);
        $resumeDT = Carbon::createFromFormat('Y-m-d H:i', "{$sessionDate} {$validated['resume_time']}", $tz);

        if ($resumeDT->lt($startDT)) {
            $resumeDT->addDay();
        }

        $duration = (int) $startDT->diffInMinutes($resumeDT);

        $entry = $session->downtimeEntries()->create([
            'reason' => $validated['reason'],
            'start_time' => $startDT->utc(),
            'resume_time' => $resumeDT->utc(),
            'duration_minutes' => $duration,
            'remarks' => $validated['remarks'] ?? null,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Downtime recorded successfully.',
                'entry' => $entry,
                'totals' => [
                    'downtime_minutes' => $session->downtimeEntries()->sum('duration_minutes'),
                    'downtime_count' => $session->downtimeEntries()->count(),
                ],
            ]);
        }

        return redirect()->back()->with('success', 'Downtime recorded successfully.');
    }

    public function addInput(Request $request, $id)
    {
        $session = SpProductionSession::findOrFail($id);

        if ($session->status !== 'running') {
            return response()->json(['error' => 'Session is not running.'], 422);
        }

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'source' => 'nullable|string',
            'pallet_number' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        $source = in_array(strtolower($validated['source'] ?? ''), ['reworkable', 'rework']) ? 'reworkable' : ($validated['source'] ?? 'wip');

        $entry = $session->inputEntries()->create([
            'quantity' => $validated['quantity'],
            'source' => $source,
            'pallet_number' => $validated['pallet_number'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
        ]);

        $reworkEntry = null;
        if ($source === 'reworkable') {
            $reworkEntry = $session->reworkEntries()->create([
                'input_qty' => $validated['quantity'],
                'recovered_qty' => 0,
                'scrapped_qty' => 0,
                'remarks' => "From Reworkable Input #{$entry->id}" . (! empty($validated['pallet_number']) ? " ({$validated['pallet_number']})" : ''),
            ]);
        }

        $session->recalculateTotals();

        $entryLabel = $entry->source === 'reworkable' ? 'Reworkable' : 'Input WIP';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$entryLabel} quantity recorded successfully.",
                'entry' => $entry,
                'rework_entry' => $reworkEntry,
                'totals' => $this->getSessionTotalsArray($session),
            ]);
        }

        return redirect()->back()->with('success', "{$entryLabel} quantity recorded successfully.");
    }

    public function addManpower(Request $request, $id)
    {
        $session = SpProductionSession::findOrFail($id);

        if ($session->status !== 'running') {
            return response()->json(['error' => 'Session is not running.'], 422);
        }

        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'operator_name' => 'required|string|max:255',
            'employee_no' => 'nullable|string|max:100',
            'role' => 'required|string|max:100',
        ]);

        $entry = $session->manpowerEntries()->create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Team member added successfully.',
                'entry' => $entry,
            ]);
        }

        return redirect()->back()->with('success', 'Team member added successfully.');
    }

    public function removeManpower(Request $request, $id, $manpowerId)
    {
        $session = SpProductionSession::findOrFail($id);

        if ($session->status !== 'running') {
            return response()->json(['error' => 'Session is not running.'], 422);
        }

        $manpower = $session->manpowerEntries()->findOrFail($manpowerId);
        $manpower->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Team member removed successfully.',
            ]);
        }

        return redirect()->back()->with('success', 'Team member removed successfully.');
    }

    public function finish(Request $request, $id)
    {
        $session = SpProductionSession::with('workOrder')->findOrFail($id);

        if ($request->filled('remarks')) {
            $session->update(['remarks' => $request->input('remarks')]);
        }

        return redirect()->route('app.sp-sessions.closeout', $session->id);
    }

    public function closeout($id)
    {
        $session = SpProductionSession::findOrFail($id);
        $session->recalculateTotals();

        $session->load([
            'workOrder',
            'operator',
            'materials',
            'downtimeEntries',
            'manpowerEntries',
            'inputEntries' => fn ($q) => $q->orderBy('created_at', 'asc')->orderBy('id', 'asc'),
        ]);

        $troubleCategories = ['Man', 'Mesin', 'Part', 'PPS', 'Lingkungan'];

        return view('sp_production.closeout', compact('session', 'troubleCategories'));
    }

    public function submitCloseout(Request $request, $id)
    {
        $session = SpProductionSession::with(['workOrder', 'downtimeEntries'])->findOrFail($id);

        // Pre-sanitize next_production_schedule: discard null, empty string, or whitespace-only items
        $rawSchedule = $request->input('next_production_schedule');
        $cleanedSchedule = null;
        if (is_array($rawSchedule)) {
            $filtered = array_values(array_filter(
                array_map(fn ($item) => is_string($item) ? trim($item) : $item, $rawSchedule),
                fn ($item) => ! empty($item)
            ));
            $cleanedSchedule = ! empty($filtered) ? $filtered : null;
        }

        // Pre-filter materials: discard untouched preset rows where lot_number, qty, visco, and mixing_ratio are all empty/null
        $rawMaterials = $request->input('materials');
        $filteredMaterials = [];
        if (is_array($rawMaterials)) {
            foreach ($rawMaterials as $mat) {
                if (! is_array($mat)) {
                    continue;
                }
                $hasLot = ! empty(trim((string) ($mat['lot_number'] ?? '')));
                $hasQty = isset($mat['qty']) && $mat['qty'] !== '' && (float) $mat['qty'] > 0;
                $hasVisco = ! empty(trim((string) ($mat['visco'] ?? '')));
                $hasRatio = ! empty(trim((string) ($mat['mixing_ratio'] ?? '')));

                // Keep row if any operational consumption data is provided
                if ($hasLot || $hasQty || $hasVisco || $hasRatio) {
                    $filteredMaterials[] = $mat;
                }
            }
        }

        $request->merge([
            'next_production_schedule' => $cleanedSchedule,
            'materials' => ! empty($filteredMaterials) ? $filteredMaterials : null,
        ]);

        $troubleCategories = ['Man', 'Mesin', 'Part', 'PPS', 'Lingkungan'];
        $hasDowntime = $session->downtimeEntries->isNotEmpty();

        $rules = [
            'production_notes' => 'nullable|string|max:2000',
            'ng_remarks' => 'nullable|string|max:2000',
            'absent_employees' => 'nullable|string|max:500',
            'next_production_schedule' => 'nullable|array',
            'next_production_schedule.*' => 'required|string|max:255',
            'output_destination' => 'required|string|in:fg,buffing,next_process',
            'remarks' => 'nullable|string|max:2000',
            // Materials
            'materials' => 'nullable|array',
            'materials.*.type' => 'required|string|in:paint,part',
            'materials.*.item_name' => 'required|string|max:255',
            'materials.*.lot_number' => 'nullable|string|max:100',
            'materials.*.visco' => 'nullable|string|max:100',
            'materials.*.mixing_ratio' => 'nullable|string|max:100',
            'materials.*.qty' => 'nullable|numeric|min:0',
            'materials.*.uom' => 'nullable|string|max:50',
            // Downtime enrichment
            'troubles' => $hasDowntime ? 'required|array' : 'nullable|array',
        ];

        if ($hasDowntime) {
            $rules['troubles.*.downtime_id'] = 'required|integer|exists:sp_downtime_entries,id';
            $rules['troubles.*.category'] = 'required|string|in:' . implode(',', $troubleCategories);
            $rules['troubles.*.countermeasure'] = 'nullable|string|max:1000';
        } else {
            $rules['troubles.*.downtime_id'] = 'nullable|integer';
            $rules['troubles.*.category'] = 'nullable|string';
            $rules['troubles.*.countermeasure'] = 'nullable|string|max:1000';
        }

        $messages = [
            'output_destination.required' => 'Please select an Output Destination (FG, Buffing, or Next Process Area).',
            'output_destination.in' => 'The selected Output Destination is invalid.',
            'troubles.required' => 'Please categorize all downtime events logged during this session.',
            'troubles.*.category.required' => 'Downtime category is required for all logged downtime events.',
            'troubles.*.category.in' => 'Selected downtime category is invalid.',
            'materials.*.item_name.required' => 'Item name is required for all recorded materials.',
            'materials.*.qty.numeric' => 'Material quantity must be a valid number.',
            'materials.*.qty.min' => 'Material quantity cannot be negative.',
        ];

        $validated = $request->validate($rules, $messages);

        DB::transaction(function () use ($session, $validated, $cleanedSchedule) {
            $session->update([
                'status' => 'completed',
                'finished_at' => $session->finished_at ?? now(),
                'production_notes' => $validated['production_notes'] ?? null,
                'ng_remarks' => $validated['ng_remarks'] ?? null,
                'absent_employees' => $validated['absent_employees'] ?? null,
                'next_production_schedule' => $cleanedSchedule,
                'output_destination' => $validated['output_destination'] ?? null,
                'remarks' => $validated['remarks'] ?? $session->remarks,
            ]);

            // Save materials
            $session->materials()->delete();
            foreach (($validated['materials'] ?? []) as $mat) {
                if (! empty($mat['item_name'])) {
                    $session->materials()->create($mat);
                }
            }

            // Enrich downtime entries with category + countermeasure
            foreach (($validated['troubles'] ?? []) as $trouble) {
                if (! empty($trouble['downtime_id'])) {
                    SpDowntimeEntry::where('id', $trouble['downtime_id'])
                        ->where('session_id', $session->id)
                        ->update([
                            'category' => $trouble['category'] ?? null,
                            'countermeasure' => $trouble['countermeasure'] ?? null,
                        ]);
                }
            }

            if ($session->workOrder) {
                $wo = $session->workOrder;
                $cumulativeGood = $wo->sessions()->sum('total_good');

                if ($cumulativeGood >= $wo->target_qty) {
                    $wo->update(['status' => 'completed']);
                } else {
                    $wo->update(['status' => 'planned']);
                }
            }
        });

        $spLines = config('mes.sp_lines', []);
        $lineSlug = array_search($session->unit_line, $spLines) ?: \Illuminate\Support\Str::slug($session->unit_line);

        return redirect()->route('sp-sessions.line-gateway', [
            'lineSlug' => $lineSlug,
            'date' => $session->started_at?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'shift' => $session->shift ?? 1,
        ])->with('success', 'Production session close-out submitted and shift completed successfully!');
    }

    /**
     * Bookmarkable per-line gateway.
     * Shows all assigned WOs for this line with per-WO session state.
     */
    public function lineGateway(Request $request, string $lineSlug)
    {
        // Resolve slug -> display name from config
        $spLines = config('mes.sp_lines', []);

        $legacyAliases = [
            'buffing' => 'buffing-1',
            'packing' => 'packing-1',
            'area-buffing' => 'buffing-1',
            'area-packing' => 'packing-1',
            'Area Buffing' => 'buffing-1',
            'Area Packing' => 'packing-1',
        ];

        if (isset($legacyAliases[$lineSlug])) {
            return redirect()->route('sp-sessions.line-gateway', array_merge($request->query(), ['lineSlug' => $legacyAliases[$lineSlug]]));
        }

        $line = $spLines[$lineSlug] ?? null;

        if (! $line) {
            // Fallback check if user passed exact line name (e.g. Line A)
            $foundSlug = array_search($lineSlug, $spLines);
            if ($foundSlug !== false) {
                return redirect()->route('sp-sessions.line-gateway', array_merge($request->query(), ['lineSlug' => $foundSlug]));
            }
            abort(404, "Unknown line: {$lineSlug}");
        }

        // Active date & shift filter (locked to today)
        $now = Carbon::now(config('mes.timezone', 'Asia/Jakarta'));
        $date = $now->format('Y-m-d');
        $currentShift = $this->getCurrentShift($now);

        $shift = (int) $request->query('shift', $currentShift);
        $selectedShift = $request->query('shift', (string) $shift);
        $currentShiftConfig = config("mes.sp_shifts.{$shift}", config("mes.shifts.{$shift}", []));

        // Work Orders for this line (active planned or in_progress only; draft and completed excluded)
        $workOrdersQuery = SpWorkOrder::with(['sessions' => fn ($q) => $q->orderByDesc('started_at')])
            ->where('unit_line', $line)
            ->whereIn('status', ['planned', 'in_progress'])
            ->where(function ($q) use ($date) {
                $q->whereDate('planned_date', $date)
                    ->orWhereHas('sessions', fn ($s) => $s->where('status', 'running'));
            });

        $workOrders = $workOrdersQuery->orderByDesc('id')->get();

        // Fetch First Piece Inspections for line work orders (latest per part, date-agnostic)
        $partNumbers = $workOrders->pluck('part_number')->filter()->unique();
        $firstPieceMap = FirstPieceInspection::whereIn('part_number', $partNumbers)
            ->latest('id')
            ->get()
            ->unique('part_number')
            ->keyBy('part_number');

        // Fetch quick bypass reasons (default factory presets + historical DB reasons)
        $defaultBypassPresets = collect([
            'Urgent Delivery Run - Pre-approved by Supervisor',
            'Supervisor Verbal Approval Given',
            'Repeat Order - Same Machine & Tooling Setup',
            'QC Team Walking Floor / Delayed Inspection',
        ]);

        $historicalBypassReasons = SpProductionSession::where('is_qc_bypassed', true)
            ->whereNotNull('qc_bypass_reason')
            ->distinct()
            ->pluck('qc_bypass_reason');

        $quickBypassReasons = $defaultBypassPresets->merge($historicalBypassReasons)->filter()->unique()->values();

        return view('sp_production.line_gateway', compact(
            'line',
            'lineSlug',
            'date',
            'shift',
            'selectedShift',
            'currentShiftConfig',
            'currentShift',
            'workOrders',
            'firstPieceMap',
            'spLines',
            'quickBypassReasons'
        ));
    }

    private function getCurrentShift(Carbon $now)
    {
        $shifts = config('mes.sp_shifts', config('mes.shifts', []));
        $currentTime = $now->format('H:i');

        foreach ($shifts as $shiftId => $schedule) {
            $start = $schedule['start'];
            $end = $schedule['end'];

            if ($start > $end) {
                if ($currentTime >= $start || $currentTime <= $end) {
                    return $shiftId;
                }
            } else {
                if ($currentTime >= $start && $currentTime <= $end) {
                    return $shiftId;
                }
            }
        }

        return 1;
    }

    public function pause($id)
    {
        $session = SpProductionSession::findOrFail($id);

        if ($session->status !== 'running') {
            return response()->json(['error' => 'Only running sessions can be paused.'], 422);
        }

        if (! \Illuminate\Support\Facades\Schema::hasColumn('sp_production_sessions', 'paused_at')) {
            return response()->json(['error' => 'The database column "paused_at" is missing. Please run php artisan migrate on your server.'], 422);
        }

        $session->paused_at = now();
        $session->save();

        return response()->json([
            'success' => true,
            'message' => 'Line paused. Breakdown / Stop timer started.',
            'paused_at' => $session->paused_at->toIso8601String(),
        ]);
    }

    public function resume(Request $request, $id)
    {
        $session = SpProductionSession::findOrFail($id);

        if (! \Illuminate\Support\Facades\Schema::hasColumn('sp_production_sessions', 'paused_at')) {
            return response()->json(['error' => 'The database column "paused_at" is missing. Please run php artisan migrate on your server.'], 422);
        }

        if (! $session->paused_at) {
            return response()->json(['error' => 'Session is not currently paused.'], 422);
        }

        $validated = $request->validate([
            'reason' => 'required|string',
            'remarks' => 'nullable|string',
        ]);

        $pausedAt = $session->paused_at;
        $now = now();
        $durationMinutes = max(1, (int) round($pausedAt->diffInMinutes($now)));

        $downtime = $session->downtimeEntries()->create([
            'reason' => $validated['reason'],
            'start_time' => $pausedAt,
            'resume_time' => $now,
            'duration_minutes' => $durationMinutes,
            'remarks' => $validated['remarks'] ?? null,
        ]);

        $session->paused_at = null;
        $session->save();
        $session->recalculateTotals();

        return response()->json([
            'success' => true,
            'message' => "Line resumed. Logged {$durationMinutes}m downtime for '{$downtime->reason}'.",
            'downtime' => $downtime,
            'totals' => $this->getSessionTotalsArray($session),
        ]);
    }

    public function deleteProductionEntry($sessionId, $entryId)
    {
        $session = SpProductionSession::findOrFail($sessionId);
        if ($session->status !== 'running') {
            return response()->json(['error' => 'Cannot delete entries on a finished session.'], 422);
        }

        $entry = $session->productionEntries()->findOrFail($entryId);
        $entry->delete();
        $session->recalculateTotals();

        return response()->json([
            'success' => true,
            'message' => 'Production entry removed successfully.',
            'totals' => $this->getSessionTotalsArray($session),
        ]);
    }

    public function deleteRejectEntry($sessionId, $entryId)
    {
        $session = SpProductionSession::findOrFail($sessionId);
        if ($session->status !== 'running') {
            return response()->json(['error' => 'Cannot delete entries on a finished session.'], 422);
        }

        $entry = $session->rejectEntries()->findOrFail($entryId);
        $entry->delete();
        $session->recalculateTotals();

        return response()->json([
            'success' => true,
            'message' => 'Defect entry removed successfully.',
            'totals' => $this->getSessionTotalsArray($session),
        ]);
    }

    public function deleteDowntimeEntry($sessionId, $entryId)
    {
        $session = SpProductionSession::findOrFail($sessionId);
        if ($session->status !== 'running') {
            return response()->json(['error' => 'Cannot delete entries on a finished session.'], 422);
        }

        $entry = $session->downtimeEntries()->findOrFail($entryId);
        $entry->delete();
        $session->recalculateTotals();

        return response()->json([
            'success' => true,
            'message' => 'Downtime entry removed successfully.',
            'totals' => $this->getSessionTotalsArray($session),
        ]);
    }

    public function deleteReworkEntry($sessionId, $entryId)
    {
        $session = SpProductionSession::findOrFail($sessionId);
        if ($session->status !== 'running') {
            return response()->json(['error' => 'Cannot delete entries on a finished session.'], 422);
        }

        $entry = $session->reworkEntries()->findOrFail($entryId);

        if ($entry->input_qty > 0) {
            $processedRework = $session->total_rework_recovered + (int) $session->total_scrap;
            $remainingReworkIn = $session->total_rework_in - $entry->input_qty;
            if ($processedRework > $remainingReworkIn) {
                return response()->json([
                    'error' => 'Cannot delete rework entry: parts have already been processed or recovered on the rework bench.',
                ], 422);
            }
        }

        $deletedInputEntryId = null;
        if (preg_match('/^From Reworkable Input #(\d+)/', $entry->remarks ?? '', $matches)) {
            $inputId = (int) $matches[1];
            $linkedInput = $session->inputEntries()->find($inputId);
            if ($linkedInput) {
                $session->refresh();
                $totalOutput = $session->total_good + $session->total_reject;
                $remainingInput = $session->total_input - $linkedInput->quantity;
                if ($totalOutput > $remainingInput) {
                    return response()->json([
                        'error' => "Cannot delete entry: {$totalOutput} Pcs of output have already been produced, which exceeds remaining input ({$remainingInput} Pcs).",
                    ], 422);
                }

                $linkedInput->delete();
                $deletedInputEntryId = $inputId;
            }
        }

        $entry->delete();
        $session->recalculateTotals();

        return response()->json([
            'success' => true,
            'message' => 'Rework entry removed successfully.',
            'deleted_input_id' => $deletedInputEntryId,
            'totals' => $this->getSessionTotalsArray($session),
        ]);
    }

    public function deleteInputEntry($sessionId, $entryId)
    {
        $session = SpProductionSession::findOrFail($sessionId);
        if ($session->status !== 'running') {
            return response()->json(['error' => 'Cannot delete entries on a finished session.'], 422);
        }

        $entry = $session->inputEntries()->findOrFail($entryId);

        $totalOutput = $session->total_good + $session->total_reject;
        $remainingInput = $session->total_input - $entry->quantity;
        if ($totalOutput > $remainingInput) {
            return response()->json([
                'error' => "Cannot delete input: {$totalOutput} Pcs of output have already been produced, which exceeds remaining input ({$remainingInput} Pcs).",
            ], 422);
        }

        $deletedReworkEntryId = null;
        if ($entry->source === 'reworkable') {
            $linkedRework = $session->reworkEntries()
                ->where('remarks', 'like', "From Reworkable Input #{$entry->id}%")
                ->first();

            if ($linkedRework) {
                $session->refresh();
                $processedRework = $session->total_rework_recovered + (int) $session->total_scrap;
                $remainingReworkIn = $session->total_rework_in - $linkedRework->input_qty;
                if ($processedRework > $remainingReworkIn) {
                    return response()->json([
                        'error' => 'Cannot delete reworkable input: parts have already been processed or recovered on the rework bench.',
                    ], 422);
                }
                $deletedReworkEntryId = $linkedRework->id;
                $linkedRework->delete();
            }
        }

        $entry->delete();
        $session->recalculateTotals();

        return response()->json([
            'success' => true,
            'message' => 'Input entry removed successfully.',
            'deleted_rework_id' => $deletedReworkEntryId,
            'totals' => $this->getSessionTotalsArray($session),
        ]);
    }

    private function getSessionTotalsArray(SpProductionSession $session): array
    {
        $directGood = (int) $session->productionEntries()->sum('good_qty');
        $reworkIn = (int) $session->total_rework_in;
        $reworkRecovered = (int) $session->total_rework_recovered;
        $reworkScrapped = (int) $session->total_scrap;
        $reworkPending = max(0, $reworkIn - ($reworkRecovered + $reworkScrapped));
        $rawReject = (int) $session->rejectEntries()->sum('quantity');

        $inputWip = (int) $session->inputEntries()->where(function ($q) {
            $q->where('source', '!=', 'reworkable')->orWhereNull('source');
        })->sum('quantity');
        $inputReworkable = (int) $session->inputEntries()->where('source', 'reworkable')->sum('quantity');

        $internalReworkIn = (int) $session->reworkEntries()
            ->where(function ($q) {
                $q->where('remarks', 'not like', 'From Reworkable Input%')
                    ->orWhereNull('remarks');
            })
            ->sum('input_qty');

        return [
            'good' => $session->total_good,
            'direct_good' => $directGood,
            'reject' => $session->total_reject,
            'raw_reject' => $rawReject,
            'input' => $session->total_input,
            'input_wip' => $inputWip,
            'input_reworkable' => $inputReworkable,
            'yield' => $session->yield,
            'downtime_minutes' => (int) ($session->downtimeEntries()->sum('duration_minutes') ?? 0),
            'downtime_count' => (int) ($session->downtimeEntries()->count() ?? 0),
            'rework_in' => $reworkIn,
            'internal_rework_in' => $internalReworkIn,
            'rework_recovered' => $reworkRecovered,
            'rework_scrapped' => $reworkScrapped,
            'rework_pending' => $reworkPending,
        ];
    }
}
