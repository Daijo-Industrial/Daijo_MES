<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\FirstPieceInspection;
use App\Models\IpqcInspection;
use App\Models\MasterBusinessPartner;
use App\Models\MasterCustomerDelivery;
use App\Models\MasterListItem;
use App\Models\SecondProcessReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SecondProcessReportController extends Controller
{
    public function index(Request $request)
    {
        $query = SecondProcessReport::query();

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        // Discrete filters
        if ($request->filled('shift')) {
            $query->where('shift', $request->shift);
        }
        if ($request->filled('process_prod')) {
            $query->where('process_prod', $request->process_prod);
        }
        if ($request->filled('unit_line')) {
            $query->where('unit_line', 'LIKE', '%'.$request->unit_line.'%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Keyword search (model, part_number, customer, part_name)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('model', 'LIKE', "%{$search}%")
                    ->orWhere('part_number', 'LIKE', "%{$search}%")
                    ->orWhere('customer', 'LIKE', "%{$search}%")
                    ->orWhere('part_name', 'LIKE', "%{$search}%");
            });
        }

        // Summary stats from filtered query (before pagination)
        $summary = (clone $query)->selectRaw('
            COUNT(*) as total_reports,
            COALESCE(SUM(jumlah_output), 0) as total_output,
            COALESCE(SUM(jumlah_ok), 0) as total_ok,
            COALESCE(SUM(jumlah_ng), 0) as total_ng
        ')->first();

        $reports = $query->orderBy('date', 'desc')->paginate(25)->withQueryString();

        return view('second_process.index', compact('reports', 'summary'));
    }

    public function create(Request $request)
    {
        $report = new SecondProcessReport;
        
        // Auto-fill from dashboard parameters
        if ($request->has('unit_line')) {
            $report->unit_line = $request->input('unit_line');
        }
        if ($request->has('shift')) {
            $report->shift = $request->input('shift');
        }

        return view('second_process.create', compact('report'));
    }

    public function store(Request $request)
    {
        $report = new SecondProcessReport;
        $this->saveReport($request, $report);

        return redirect()->route('second-process-reports.index')
            ->with('success', 'Report created successfully.');
    }

    public function show($id)
    {
        $report = SecondProcessReport::with([
            'materials',
            'hourlyProductions',
            'manpowers',
            'ngRecords.hourlyDetails',
            'troubles',
        ])->findOrFail($id);

        $firstPiece = FirstPieceInspection::where('part_number', $report->part_number)
            ->whereDate('date', $report->date)
            ->orderBy('id', 'desc')
            ->first();

        // Look up linked IPQC inspection by natural keys
        $ipqcInspection = IpqcInspection::with(['records.attachments', 'attachments'])
            ->where('date', $report->date)
            ->where('part_number', $report->part_number)
            ->where('shift', $report->shift)
            ->where('unit_line', $report->unit_line)
            ->first();

        return view('second_process.show', compact('report', 'firstPiece', 'ipqcInspection'));
    }

    public function edit($id)
    {
        $report = SecondProcessReport::with([
            'materials',
            'hourlyProductions',
            'manpowers',
            'ngRecords.hourlyDetails',
            'troubles',
        ])->findOrFail($id);

        if ($report->status !== 'draft') {
            return redirect()->route('second-process-reports.show', $id)
                ->with('error', 'Only draft reports can be edited.');
        }

        if (auth()->user()->cannot('update', $report)) {
            return redirect()->route('second-process-reports.show', $id)
                ->with('error', 'You do not have permission to edit this draft report.');
        }

        return view('second_process.edit', compact('report'));
    }

    public function update(Request $request, $id)
    {
        $report = SecondProcessReport::findOrFail($id);

        if ($report->status !== 'draft') {
            return redirect()->route('second-process-reports.show', $id)
                ->with('error', 'Only draft reports can be updated.');
        }

        if (auth()->user()->cannot('update', $report)) {
            return redirect()->route('second-process-reports.show', $id)
                ->with('error', 'You do not have permission to edit this draft report.');
        }

        $this->saveReport($request, $report);

        return redirect()->route('second-process-reports.index')
            ->with('success', 'Report updated successfully.');
    }

    private function saveReport(Request $request, SecondProcessReport $report)
    {
        // Pre-sanitize materials: discard completely empty dynamic rows
        if ($request->has('materials') && is_array($request->materials)) {
            $cleanedMaterials = array_values(array_filter($request->materials, function ($mat) {
                if (!is_array($mat)) {
                    return false;
                }
                $hasName = !empty(trim((string) ($mat['item_name'] ?? '')));
                $hasLot = !empty(trim((string) ($mat['lot_number'] ?? '')));
                $hasVisco = !empty(trim((string) ($mat['visco'] ?? '')));
                $hasRatio = !empty(trim((string) ($mat['mixing_ratio'] ?? '')));
                $hasQty = isset($mat['qty']) && $mat['qty'] !== '' && (float) $mat['qty'] > 0;
                $hasUom = !empty(trim((string) ($mat['uom'] ?? '')));
                $hasSubType = !empty(trim((string) ($mat['sub_type'] ?? '')));

                return $hasName || $hasLot || $hasVisco || $hasRatio || $hasQty || $hasUom || $hasSubType;
            }));
            $request->merge(['materials' => $cleanedMaterials]);
        }

        // Pre-sanitize troubles: discard completely empty dynamic rows
        if ($request->has('troubles') && is_array($request->troubles)) {
            $cleanedTroubles = array_values(array_filter($request->troubles, function ($tr) {
                if (!is_array($tr)) {
                    return false;
                }
                $hasMasalah = !empty(trim((string) ($tr['masalah'] ?? '')));
                $hasPenanganan = !empty(trim((string) ($tr['penanganan'] ?? '')));
                $hasLossTime = (!empty($tr['loss_time_minutes']) && (int) $tr['loss_time_minutes'] > 0)
                    || !empty(trim((string) ($tr['loss_time'] ?? '')));
                $hasCategory = !empty(trim((string) ($tr['category'] ?? ''))) || !empty(trim((string) ($tr['penyebab'] ?? '')));

                return $hasMasalah || $hasPenanganan || $hasLossTime || $hasCategory;
            }));
            $request->merge(['troubles' => $cleanedTroubles]);
        }

        // Pre-sanitize and auto-convert customer input
        $rawCustomer = trim((string) $request->input('customer', ''));
        if ($rawCustomer === '' || $rawCustomer === '0' || $rawCustomer === '-' || strcasecmp($rawCustomer, 'n/a') === 0) {
            $normalizedCustomer = 'N/A';
        } else {
            // 1. Check in MasterBusinessPartner (CUSTOMER) by bp_name, bp_code, or foreign_name (alias)
            $bpCustomer = MasterBusinessPartner::customers()
                ->where(function ($q) use ($rawCustomer) {
                    $q->where('bp_name', $rawCustomer)
                        ->orWhere('bp_code', $rawCustomer)
                        ->orWhere('foreign_name', $rawCustomer);
                })
                ->first();

            if ($bpCustomer) {
                $normalizedCustomer = $bpCustomer->bp_name;
            } else {
                // 2. Fallback to MasterCustomerDelivery by customer_name or customer_code
                $customerByName = MasterCustomerDelivery::where('customer_name', $rawCustomer)->first();
                if ($customerByName) {
                    $normalizedCustomer = $customerByName->customer_name;
                } else {
                    $customerByCode = MasterCustomerDelivery::where('customer_code', $rawCustomer)->first();
                    if ($customerByCode) {
                        $normalizedCustomer = $customerByCode->customer_name;
                    } else {
                        $normalizedCustomer = $rawCustomer;
                    }
                }
            }
        }
        $request->merge(['customer' => $normalizedCustomer]);

        $customAttributes = [
            'date' => 'Tanggal (Date)',
            'unit_line' => 'Unit / Line',
            'shift' => 'Shift',
            'process_prod' => 'Proses Produksi (Process Prod)',
            'part_number' => 'Part Number',
            'part_name' => 'Part Name',
            'model' => 'Model',
            'customer' => 'Customer',
            'output_destination' => 'Tujuan Output',
            'materials.*.item_name' => 'Nama Item Material',
            'materials.*.qty' => 'Quantity Material',
            'materials.*.lot_number' => 'Lot Number Material',
            'materials.*.visco' => 'Viscosity Material',
            'materials.*.mixing_ratio' => 'Mixing Ratio Material',
            'sisa_input' => 'Sisa Input WIP',
            'sisa_input_remark' => 'Alasan / Remark Sisa Input',
            'troubles.*.loss_time_minutes' => 'Loss Time (menit)',
            'troubles.*.penanganan' => 'Penanganan Trouble',
            'troubles.*.masalah' => 'Deskripsi Masalah Trouble',
            'hourly.*.ok_qty' => 'Qty OK',
            'hourly.*.ng_qty' => 'Qty NG',
            'ngs.*.ng_input_item' => 'Detail Remark Defect (NG)',
            'ngs.*.ng_input_qty' => 'Kuantitas Remark Defect (NG)',
        ];

        $customMessages = [
            'materials.*.item_name.required' => 'Nama item material harus diisi jika baris material digunakan.',
            'part_number.required' => 'Part Number wajib diisi.',
            'unit_line.required' => 'Unit / Line wajib dipilih.',
            'process_prod.required' => 'Proses Produksi wajib dipilih.',
            'shift.required' => 'Shift wajib dipilih.',
            'date.required' => 'Tanggal wajib diisi.',
        ];

        $validated = $request->validate([
            // Header
            'date' => 'required|date',
            'unit_line' => 'required|string',
            'shift' => 'required|string',
            'process_prod' => 'required|string',
            'status' => 'nullable|string',
            'output_destination' => 'nullable|string|in:fg,buffing,next_process',
            'model' => 'nullable|string',
            'part_number' => 'required|string',
            'part_name' => 'nullable|string',
            'customer' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (
                        $value !== 'N/A' &&
                        !MasterBusinessPartner::customers()->where('bp_name', $value)->exists() &&
                        !MasterCustomerDelivery::where('customer_name', $value)->exists()
                    ) {
                        $fail('Kolom Customer harus berupa Nama Customer resmi yang terdaftar atau N/A.');
                    }
                },
            ],
            'target_per_hour' => 'nullable|integer',
            'jml_input_wip' => 'nullable|integer',
            'repairan' => 'nullable|integer',
            'jumlah_output' => 'nullable|integer',
            'sisa_input' => 'nullable|integer',
            'sisa_input_remark' => 'nullable|string',
            'jumlah_ok' => 'nullable|integer',
            'jumlah_ng' => 'nullable|integer',
            'ng_prosentase' => 'nullable|numeric',
            'jml_ng_lebur' => 'nullable|integer',
            // Footer
            'next_production_schedule' => 'nullable|array',
            'next_production_schedule.*' => 'nullable|string',
            'absent_employees' => 'nullable|string',
            'production_notes' => 'nullable|string',
            'ng_remarks' => 'nullable|string',
            'created_by_name' => 'nullable|string',
            'pqc_name' => 'nullable|string',
            'leader_name' => 'nullable|string',
            'acknowledged_by_name' => 'nullable|string',

            // Materials
            'materials' => 'nullable|array',
            'materials.*.type' => 'required|string',
            'materials.*.item_name' => 'required|string',
            'materials.*.lot_number' => 'nullable|string',
            'materials.*.visco' => 'nullable|string',
            'materials.*.qty' => 'nullable|numeric',
            'materials.*.uom' => 'nullable|string',
            'materials.*.mixing_ratio' => 'nullable|string',
            'materials.*.paint_type' => 'nullable|string',
            'materials.*.sub_type' => 'nullable|string',

            // Hourly Productions
            'hourly' => 'nullable|array',
            'hourly.*.hour_ke' => 'required|integer',
            'hourly.*.ok_qty' => 'nullable|integer',
            'hourly.*.ng_qty' => 'nullable|integer',
            'hourly.*.acumulasi_qty' => 'nullable|integer',
            'hourly.*.remark' => 'nullable|string',

            // Manpower
            'manpower' => 'nullable|array',
            'manpower.*.role' => 'required|string',
            'manpower.*.no' => 'required|integer',
            'manpower.*.name' => 'nullable|string',

            // NG Records
            'ngs' => 'nullable|array',
            'ngs.*.ng_category' => 'nullable|string',
            'ngs.*.ng_name' => 'required|string',
            'ngs.*.hours' => 'nullable|array',
            'ngs.*.hours.*' => 'nullable|integer',
            'ngs.*.total_ng' => 'nullable|integer',
            'ngs.*.ng_input_item' => 'nullable|string',
            'ngs.*.ng_input_qty' => 'nullable|integer',
            'ngs.*.remark' => 'nullable|string',

            // Troubles
            'troubles' => 'nullable|array',
            'troubles.*.penyebab' => 'nullable|string',
            'troubles.*.penanganan' => 'nullable|string',
            'troubles.*.loss_time' => 'nullable|string',
            'troubles.*.category' => 'nullable|string',
            'troubles.*.masalah' => 'nullable|string',
            'troubles.*.loss_time_minutes' => 'nullable|integer|min:0|max:1440',
        ], $customMessages, $customAttributes);

        // Enforce defect remarks for submitted reports when Total NG > 0
        $effectiveStatus = $validated['status'] ?? ($report->exists ? $report->status : 'draft');
        if ($effectiveStatus === 'submitted' && !empty($validated['ngs']) && is_array($validated['ngs'])) {
            $ngErrors = [];
            foreach ($validated['ngs'] as $idx => $ng) {
                $ngName = $ng['ng_name'] ?? ('Defect #' . ($idx + 1));
                $colTotal = 0;
                if (!empty($ng['hours']) && is_array($ng['hours'])) {
                    foreach ($ng['hours'] as $val) {
                        $colTotal += (int) $val;
                    }
                }

                if ($colTotal > 0) {
                    $rawItem = $ng['ng_input_item'] ?? null;
                    $normalized = $this->normalizeNgRemarkItem($rawItem);

                    if (empty($normalized['normalized_item'])) {
                        $ngErrors["ngs.{$idx}.ng_input_item"] = "Remark detail wajib diisi untuk defect {$ngName} karena terdapat {$colTotal} defect tercatat.";
                    } elseif (($normalized['total_qty'] ?? 0) !== $colTotal) {
                        $remQty = $normalized['total_qty'] ?? 0;
                        $ngErrors["ngs.{$idx}.ng_input_qty"] = "Total kuantitas remark untuk defect {$ngName} ({$remQty} pcs) harus sama dengan total defect tercatat ({$colTotal} pcs).";
                    }
                }
            }

            if (!empty($ngErrors)) {
                throw \Illuminate\Validation\ValidationException::withMessages($ngErrors);
            }
        }

        // Sanitize next_production_schedule: discard empty strings, nulls, and whitespace
        $cleanSchedule = is_array($validated['next_production_schedule'] ?? null)
            ? array_values(array_filter(
                array_map(fn($item) => is_string($item) ? trim($item) : $item, $validated['next_production_schedule']),
                fn($item) => !empty($item)
            ))
            : null;
        $validated['next_production_schedule'] = !empty($cleanSchedule) ? $cleanSchedule : null;

        // Default integer fields
        $validated['target_per_hour'] = $validated['target_per_hour'] ?? 0;
        $validated['jml_ng_lebur'] = $validated['jml_ng_lebur'] ?? 0;

        // Auto-calculate WIP and Repairan from part materials breakdown if provided
        $partMaterials = collect($validated['materials'] ?? [])->where('type', 'part');
        $breakdownWip = 0;
        $breakdownRepairan = 0;
        $hasPartBreakdown = false;

        foreach ($partMaterials as $mat) {
            $qty = (int) ($mat['qty'] ?? 0);
            $hasLot = !empty(trim((string) ($mat['lot_number'] ?? '')));
            if ($qty > 0 || $hasLot) {
                $hasPartBreakdown = true;
            }
            $itemName = strtolower((string) ($mat['item_name'] ?? ''));
            if (str_contains($itemName, 'repair')) {
                $breakdownRepairan += $qty;
            } else {
                $breakdownWip += $qty;
            }
        }

        if ($hasPartBreakdown) {
            $validated['jml_input_wip'] = $breakdownWip;
            $validated['repairan'] = $breakdownRepairan;
        } else {
            $validated['jml_input_wip'] = (int) ($validated['jml_input_wip'] ?? 0);
            $validated['repairan'] = (int) ($validated['repairan'] ?? 0);
        }

        // Server-side calculation of production totals
        $jumlah_ok = 0;
        if (isset($validated['hourly'])) {
            foreach ($validated['hourly'] as $hour) {
                $jumlah_ok += (int) ($hour['ok_qty'] ?? 0);
            }
        }

        $jumlah_ng = 0;
        if (isset($validated['ngs'])) {
            foreach ($validated['ngs'] as $key => $ng) {
                $rowTotal = 0;
                if (isset($ng['hours'])) {
                    foreach ($ng['hours'] as $val) {
                        $rowTotal += (int) $val;
                    }
                }
                $validated['ngs'][$key]['total_ng'] = $rowTotal;
                $jumlah_ng += $rowTotal;

                // Normalize remark item and quantity to canonical uppercase format
                $normalized = $this->normalizeNgRemarkItem($ng['ng_input_item'] ?? null);
                $validated['ngs'][$key]['ng_input_item'] = $normalized['normalized_item'];
                $validated['ngs'][$key]['ng_input_qty'] = $normalized['total_qty'];
            }
        }

        $jml_ng_lebur = (int) ($validated['jml_ng_lebur'] ?? 0);
        $jumlah_output = $jumlah_ok + $jumlah_ng + $jml_ng_lebur;
        $ng_prosentase = 0;
        if ($jumlah_output > 0) {
            $ng_prosentase = round(($jumlah_ng / $jumlah_output) * 100, 2);
        }

        $validated['jumlah_ok'] = $jumlah_ok;
        $validated['jumlah_ng'] = $jumlah_ng;
        $validated['jumlah_output'] = $jumlah_output;
        $validated['ng_prosentase'] = $ng_prosentase;

        // Auto-calculate remaining WIP / Material input balance (Output = OK + NG + Scrap)
        $totalInput = (int) ($validated['jml_input_wip'] ?? 0) + (int) ($validated['repairan'] ?? 0);
        $totalOutput = $jumlah_output;
        $sisaInput = $totalInput - $totalOutput;
        $validated['sisa_input'] = $sisaInput;

        // Auto-assign status
        $validated['status'] = $validated['status'] ?? ($report->exists ? $report->status : 'draft');
        $effectiveStatus = $validated['status'];

        // Enforce material input vs output reconciliation when submitting report
        if ($effectiveStatus === 'submitted') {
            if ($totalInput < $totalOutput) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'sisa_input' => "Total Output ({$totalOutput} pcs) tidak boleh melebihi Total Input ({$totalInput} pcs). Periksa kembali kuantitas WIP / Repairan di Tab 2 atau input hasil produksi di Tab 3.",
                ]);
            }

            if ($sisaInput > 0 && empty(trim((string) ($validated['sisa_input_remark'] ?? '')))) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'sisa_input_remark' => "Terdapat sisa input sebanyak {$sisaInput} pcs. Alasan / remark sisa input wajib diisi sebelum laporan disubmit.",
                ]);
            }
        }

        if (! $report->exists) {
            $validated['created_by_name'] = auth()->user()->name;
            if ($validated['status'] === 'submitted') {
                $validated['created_by_signed_at'] = now();
            } else {
                $validated['created_by_signed_at'] = null;
            }
            $validated['pqc_name'] = null;
            $validated['pqc_signed_at'] = null;
            $validated['leader_name'] = null;
            $validated['leader_signed_at'] = null;
            $validated['acknowledged_by_name'] = null;
            $validated['acknowledged_signed_at'] = null;
        } else {
            $validated['created_by_name'] = $report->created_by_name;
            $validated['created_by_signed_at'] = $report->created_by_signed_at;
            $validated['pqc_name'] = $report->pqc_name;
            $validated['pqc_signed_at'] = $report->pqc_signed_at;
            $validated['leader_name'] = $report->leader_name;
            $validated['leader_signed_at'] = $report->leader_signed_at;
            $validated['acknowledged_by_name'] = $report->acknowledged_by_name;
            $validated['acknowledged_signed_at'] = $report->acknowledged_signed_at;

            if ($validated['status'] === 'submitted' && empty($report->created_by_signed_at)) {
                $validated['created_by_signed_at'] = now();
            }
        }

        DB::transaction(function () use ($request, $report, $validated) {
            if ($report->exists) {
                // Delete old relations
                $report->materials()->delete();
                $report->hourlyProductions()->delete();
                $report->manpowers()->delete();
                $report->ngRecords()->delete();
                $report->troubles()->delete();

                $report->update($validated);
            } else {
                $report->fill($validated)->save();
            }

            // Create Materials
            if (isset($validated['materials'])) {
                foreach ($validated['materials'] as $material) {
                    // Item Paint is only recorded if process_prod is Painting or Repair
                    if (($material['type'] ?? '') === 'paint' && !in_array(($validated['process_prod'] ?? ''), ['Painting', 'Repair'])) {
                        continue;
                    }

                    $hasData = !empty(trim((string) ($material['lot_number'] ?? '')))
                        || (isset($material['qty']) && $material['qty'] !== '' && (float) $material['qty'] > 0)
                        || !empty(trim((string) ($material['visco'] ?? '')))
                        || !empty(trim((string) ($material['mixing_ratio'] ?? '')));

                    if (!empty(trim((string) ($material['item_name'] ?? ''))) && $hasData) {
                        $report->materials()->create($material);
                    }
                }
            }

            // Create Hourly Productions
            if (isset($validated['hourly'])) {
                foreach ($validated['hourly'] as $hour) {
                    $hour['ok_qty'] = (int) ($hour['ok_qty'] ?? 0);
                    $hour['ng_qty'] = (int) ($hour['ng_qty'] ?? 0);
                    $hour['acumulasi_qty'] = (int) ($hour['acumulasi_qty'] ?? 0);
                    $report->hourlyProductions()->create($hour);
                }
            }

            // Create Manpower
            if (isset($validated['manpower'])) {
                foreach ($validated['manpower'] as $mp) {
                    if (! empty($mp['name'])) {
                        $report->manpowers()->create($mp);
                    }
                }
            }

            // Create NG Records and hourly details
            if (isset($validated['ngs'])) {
                foreach ($validated['ngs'] as $ngData) {
                    if (! empty($ngData['ng_name'])) {
                        $ngRecord = $report->ngRecords()->create($ngData);

                        if (isset($ngData['hours'])) {
                            foreach ($ngData['hours'] as $hourKe => $val) {
                                $qty = (int) $val;
                                if ($qty > 0) {
                                    $ngRecord->hourlyDetails()->create([
                                        'hour_ke' => (int) $hourKe,
                                        'qty' => $qty,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }

            // Create Troubles
            if (isset($validated['troubles'])) {
                foreach ($validated['troubles'] as $trouble) {
                    $hasProblem = ! empty(trim($trouble['masalah'] ?? ''));
                    $hasCountermeasure = ! empty(trim($trouble['penanganan'] ?? ''));
                    $hasLossTime = ! empty($trouble['loss_time_minutes']) && (int) $trouble['loss_time_minutes'] > 0;

                    if ($hasProblem || $hasCountermeasure || $hasLossTime) {
                        $category = ! empty($trouble['penyebab'])
                            ? $trouble['penyebab']
                            : (! empty($trouble['category']) ? $trouble['category'] : 'Other');
                        $trouble['penyebab'] = $category;
                        $trouble['category'] = $category;

                        if (empty($trouble['loss_time_minutes']) && ! empty($trouble['loss_time'])) {
                            preg_match('/\d+/', $trouble['loss_time'], $matches);
                            $trouble['loss_time_minutes'] = isset($matches[0]) ? (int) $matches[0] : 0;
                        }
                        $mins = (int) ($trouble['loss_time_minutes'] ?? 0);
                        $trouble['loss_time_minutes'] = $mins;
                        if (empty($trouble['loss_time']) && $mins > 0) {
                            $trouble['loss_time'] = "{$mins} mins";
                        }
                        $report->troubles()->create($trouble);
                    }
                }
            }
        });
    }

    public function destroy($id)
    {
        $report = SecondProcessReport::findOrFail($id);
        $user = auth()->user();

        if ($user->cannot('delete', $report)) {
            return redirect()->back()->withErrors(['error' => 'You do not have permission to delete this report.']);
        }

        $report->delete();

        return redirect()->route('second-process-reports.index')
            ->with('success', 'Report deleted successfully.');
    }

    public function searchItems(Request $request)
    {
        $query = trim((string) ($request->get('query') ?: $request->get('q')));
        if ($query === '') {
            return response()->json([]);
        }

        $by = $request->get('by', 'all');

        $itemsQuery = MasterListItem::with(['customer', 'businessPartner']);

        if ($by === 'number' || $by === 'item_code') {
            $itemsQuery->where(function ($q) use ($query) {
                $q->where('item_code', 'LIKE', "%{$query}%")
                    ->orWhere('item_name', 'LIKE', "%{$query}%");
            })->orderByRaw("CASE WHEN item_code LIKE ? THEN 0 WHEN item_code LIKE ? THEN 1 ELSE 2 END", ["{$query}%", "%{$query}%"]);
        } elseif ($by === 'name' || $by === 'item_name') {
            $itemsQuery->where(function ($q) use ($query) {
                $q->where('item_name', 'LIKE', "%{$query}%")
                    ->orWhere('item_code', 'LIKE', "%{$query}%");
            })->orderByRaw("CASE WHEN item_name LIKE ? THEN 0 WHEN item_name LIKE ? THEN 1 ELSE 2 END", ["{$query}%", "%{$query}%"]);
        } else {
            $itemsQuery->where(function ($q) use ($query) {
                $q->where('item_code', 'LIKE', "%{$query}%")
                    ->orWhere('item_name', 'LIKE', "%{$query}%");
            });
        }

        $items = $itemsQuery->limit(25)
            ->get()
            ->map(function ($item) {
                $rawCust = $item->customer?->customer_name ?: $item->businessPartner?->bp_name;
                $custName = (!empty($rawCust) && $rawCust !== '0' && $rawCust !== '-') ? $rawCust : 'N/A';
                $rawModel = $item->project_code;
                $modelCode = (!empty($rawModel) && $rawModel !== '0' && $rawModel !== '-') ? $rawModel : 'N/A';
                return [
                    'id' => $item->id,
                    'item_code' => $item->item_code,
                    'item_name' => $item->item_name,
                    'item_description' => $item->item_name,
                    'project_code' => $modelCode,
                    'customer_name' => $custName,
                    'customer_code' => $item->customer_code,
                ];
            });

        return response()->json($items);
    }

    public function searchCustomers(Request $request)
    {
        $query = $request->get('query');
        if (! $query) {
            return response()->json([]);
        }

        // 1. Search MasterBusinessPartner where category = CUSTOMER (bp_name, bp_code, foreign_name)
        $bpResults = MasterBusinessPartner::customers()
            ->where(function ($q) use ($query) {
                $q->where('bp_name', 'LIKE', "%{$query}%")
                    ->orWhere('bp_code', 'LIKE', "%{$query}%")
                    ->orWhere('foreign_name', 'LIKE', "%{$query}%");
            })
            ->limit(20)
            ->get();

        if ($bpResults->isNotEmpty()) {
            $customers = $bpResults->map(function ($bp) {
                $details = array_filter([$bp->bp_code, $bp->foreign_name]);
                $subtext = !empty($details) ? ' (' . implode(' - ', $details) . ')' : '';
                return [
                    'id' => $bp->id,
                    'customer_code' => $bp->bp_code,
                    'customer_name' => $bp->bp_name,
                    'name' => $bp->bp_name,
                    'display_label' => $bp->bp_name . $subtext,
                ];
            });

            return response()->json($customers);
        }

        // 2. Fallback to MasterCustomerDelivery
        $customers = MasterCustomerDelivery::where('customer_name', 'LIKE', "%{$query}%")
            ->orWhere('customer_code', 'LIKE', "%{$query}%")
            ->limit(20)
            ->get()
            ->map(function ($cust) {
                return [
                    'id' => $cust->id,
                    'customer_code' => $cust->customer_code,
                    'customer_name' => $cust->customer_name,
                    'name' => $cust->customer_name,
                    'display_label' => $cust->customer_name . ' (' . $cust->customer_code . ')',
                ];
            });

        return response()->json($customers);
    }

    public function sign(Request $request, $id, $role)
    {
        $report = SecondProcessReport::findOrFail($id);
        $user = auth()->user();

        if ($user->cannot('sign', [$report, $role])) {
            $userRoleName = $user->role ? $user->role->name : 'No Role';
            return redirect()->back()->withErrors([
                'error' => "Role '{$userRoleName}' is not authorized to sign as " . ucfirst($role) . '.',
            ]);
        }

        switch ($role) {
            case 'checker':
                if ($report->status !== 'draft') {
                    return redirect()->back()->withErrors(['error' => 'Checker signature can only be applied to draft reports.']);
                }
                $report->update([
                    'created_by_name' => $user->name,
                    'created_by_signed_at' => now(),
                    'status' => 'submitted',
                ]);
                break;

            case 'pqc':
                if (! in_array($report->status, ['leader_approved', 'acknowledged', 'submitted'])) {
                    return redirect()->back()->withErrors(['error' => 'PQC signature can only be applied after Leader approval.']);
                }

                // Check First Piece Approval Gate
                $firstPiece = FirstPieceInspection::where('part_number', $report->part_number)
                    ->whereDate('date', $report->date)
                    ->orderBy('id', 'desc')
                    ->first();

                if (! $firstPiece || ! $firstPiece->isApproved()) {
                    return redirect()->back()->withErrors([
                        'error' => "Cannot sign PQC approval: First Piece Inspection for part '{$report->part_number}' on {$report->date} is not approved by QC.",
                    ]);
                }

                $updateData = [
                    'pqc_name' => $user->name,
                    'pqc_signed_at' => now(),
                ];
                if ($report->status === 'leader_approved') {
                    $updateData['status'] = 'pqc_approved';
                }
                $report->update($updateData);
                break;

            case 'leader':
                if (! in_array($report->status, ['submitted', 'pqc_approved'])) {
                    return redirect()->back()->withErrors(['error' => 'Leader signature can only be applied to submitted reports.']);
                }
                $report->update([
                    'leader_name' => $user->name,
                    'leader_signed_at' => now(),
                    'status' => 'leader_approved',
                ]);
                break;

            case 'acknowledged':
                if (! in_array($report->status, ['leader_approved', 'pqc_approved'])) {
                    return redirect()->back()->withErrors(['error' => 'Supervisor signature can only be applied after Leader approval.']);
                }
                $report->update([
                    'acknowledged_by_name' => $user->name,
                    'acknowledged_signed_at' => now(),
                    'status' => 'acknowledged',
                ]);
                break;

            default:
                return redirect()->back()->withErrors(['error' => 'Invalid approval role.']);
        }

        return redirect()->route('second-process-reports.show', $id)
            ->with('success', ucfirst($role).' signature applied successfully.');
    }

    public function reject(Request $request, $id)
    {
        $report = SecondProcessReport::findOrFail($id);
        $user = auth()->user();

        if ($user->cannot('reject', $report)) {
            return redirect()->back()->withErrors(['error' => 'You are not authorized to reject reports.']);
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $report->update([
            'status' => 'draft',
            'created_by_signed_at' => null,
            'pqc_name' => null,
            'pqc_signed_at' => null,
            'leader_name' => null,
            'leader_signed_at' => null,
            'acknowledged_by_name' => null,
            'acknowledged_signed_at' => null,
            'ng_remarks' => trim(($report->ng_remarks ? $report->ng_remarks."\n" : '').'Rejected by '.$user->name.': '.$request->rejection_reason),
        ]);

        return redirect()->route('second-process-reports.show', $id)
            ->with('success', 'Report was successfully rejected and returned to Draft.');
    }

    /**
     * Normalize NG remark item string to canonical UPPERCASE hyphenated categories
     * and consolidate quantities across identical intended categories.
     *
     * Example: "[2] ng-input | [3] Ng-Input | [1] debu cetakan"
     * Result:  ['normalized_item' => '[5] NG-INPUT | [1] DEBU-CETAKAN', 'total_qty' => 6]
     */
    protected function normalizeNgRemarkItem(?string $rawItem): array
    {
        if (empty($rawItem) || trim($rawItem) === '') {
            return ['normalized_item' => null, 'total_qty' => null];
        }

        $parts = explode(' | ', trim($rawItem));
        $categoryTotals = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $qty = 0;
            $name = $part;
            if (preg_match('/^\[(\d+)\]\s*(.*)$/', $part, $matches)) {
                $qty = (int) $matches[1];
                $name = trim($matches[2]);
            }

            // Normalize category name: collapse whitespace and underscores to '-', then UPPERCASE
            $cleanName = strtoupper(preg_replace('/[\s_]+/', '-', trim($name)));

            // Standardize presets
            if ($cleanName === 'NG-INPUT' || $cleanName === 'INPUT') {
                $cleanName = 'NG-INPUT';
            } elseif ($cleanName === 'NG-PROSES' || $cleanName === 'PROSES') {
                $cleanName = 'NG-PROSES';
            }

            if ($cleanName !== '') {
                if (!isset($categoryTotals[$cleanName])) {
                    $categoryTotals[$cleanName] = 0;
                }
                $categoryTotals[$cleanName] += $qty;
            }
        }

        if (empty($categoryTotals)) {
            return ['normalized_item' => null, 'total_qty' => null];
        }

        $serializedParts = [];
        $totalQty = 0;
        foreach ($categoryTotals as $catName => $catQty) {
            $totalQty += $catQty;
            if ($catQty > 0) {
                $serializedParts[] = "[{$catQty}] {$catName}";
            } else {
                $serializedParts[] = $catName;
            }
        }

        return [
            'normalized_item' => implode(' | ', $serializedParts),
            'total_qty' => $totalQty > 0 ? $totalQty : null,
        ];
    }
}
