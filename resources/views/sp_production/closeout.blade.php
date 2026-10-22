<x-operator-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-black text-xl text-slate-900 leading-tight uppercase tracking-wider">
                    Production Session Close-Out
                </h2>
                <p class="text-xs text-slate-500 font-medium">Finalize material consumption, downtime categorization, and shift handover</p>
            </div>
            <span class="text-xs font-black bg-blue-100 text-blue-900 px-3.5 py-1.5 rounded-xl border border-blue-200 uppercase tracking-wider shadow-xs">
                Session #{{ $session->id }}
            </span>
        </div>
    </x-slot>

    <div class="py-6 space-y-6 max-w-5xl mx-auto" x-data="closeoutForm()">
        @php
            $wo = $session->workOrder;
            $spLines = config('mes.sp_lines', []);
            $lineSlug = array_search($session->unit_line, $spLines) ?: \Illuminate\Support\Str::slug($session->unit_line);
            $gatewayUrl = route('sp-sessions.line-gateway', [
                'lineSlug' => $lineSlug,
                'date' => $session->started_at?->setTimezone(config('mes.timezone', 'Asia/Jakarta'))->format('Y-m-d') ?? \Carbon\Carbon::now(config('mes.timezone', 'Asia/Jakarta'))->format('Y-m-d'),
                'shift' => $session->shift ?? 1,
            ]);
            $directGood = (int) $session->productionEntries()->sum('good_qty');
            $finalScrap = max($session->total_reject, $session->total_scrap);
            $unusedWip = max(0, $session->total_input - ($session->total_good + $finalScrap));

            $defaultPartMaterials = [];
            $wipCounter = 1;
            $repairanCounter = 1;

            if ($session->inputEntries && $session->inputEntries->isNotEmpty()) {
                foreach ($session->inputEntries as $entry) {
                    $isRework = (($entry->source ?? '') === 'reworkable');
                    $name = $isRework ? ('Repairan ' . $repairanCounter++) : ('WIP ' . $wipCounter++);
                    $defaultPartMaterials[] = [
                        'item_name' => $name,
                        'lot_number' => $entry->pallet_number ?: ($entry->remarks ?: ''),
                        'qty' => $entry->quantity ?? '',
                        'uom' => 'Pcs',
                    ];
                }
            }

            if (empty($defaultPartMaterials)) {
                $defaultParts = config('mes.sp_default_part_materials', ['WIP 1', 'WIP 2', 'WIP 3', 'Repairan 1', 'Repairan 2', 'Repairan 3']);
                foreach ($defaultParts as $name) {
                    $defaultPartMaterials[] = [
                        'item_name' => $name,
                        'lot_number' => '',
                        'qty' => '',
                        'uom' => 'Pcs',
                    ];
                }
            }

            $defaultPaintMaterials = config('mes.sp_default_paint_materials', [
                'Paint Primer',
                'Hardener',
                'Paint Basecoat',
                'Hardener',
                'Paint Topcoat',
                'Hardener',
            ]);
        @endphp

        {{-- Flash Success Banner --}}
        @if(session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center justify-between text-emerald-950 text-xs font-bold shadow-xs">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        {{-- Hero Shift Completed Banner --}}
        <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 rounded-3xl p-6 text-white shadow-xl flex items-center justify-between gap-4 flex-wrap">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    @if($session->status === 'running')
                        <span class="px-2.5 py-0.5 rounded-md bg-amber-500/30 text-amber-300 border border-amber-400/40 text-[10px] font-black uppercase tracking-wider">
                            ⏳ SHIFT CLOSE-OUT IN PROGRESS
                        </span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-md bg-emerald-500/30 text-emerald-300 border border-emerald-400/40 text-[10px] font-black uppercase tracking-wider">
                            ✓ SESSION COMPLETED
                        </span>
                    @endif
                    <span class="text-xs font-mono text-slate-400">{{ $session->unit_line }} — Shift {{ $session->shift }}</span>
                </div>
                <h3 class="text-xl sm:text-2xl font-black tracking-tight text-white">
                    WO #{{ $wo->wo_number ?? 'N/A' }} <span class="text-slate-400 font-medium text-base">({{ $wo->part_name ?? 'Part' }})</span>
                </h3>
                <p class="text-xs text-slate-300 font-medium mt-1">
                    Operator: <strong class="text-white font-bold">{{ $session->operator->name ?? 'N/A' }}</strong> • Part No: <span class="font-mono text-slate-300">{{ $wo->part_number ?? '-' }}</span>
                </p>
            </div>

            <a href="{{ route('app.sp-sessions.show', $session->id) }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 active:bg-slate-900 text-slate-200 font-bold text-xs rounded-xl border border-slate-700 transition flex items-center gap-1.5 cursor-pointer">
                <span>← Back to Live Session</span>
            </a>
        </div>

        <form action="{{ route('app.sp-sessions.submit-closeout', $session->id) }}" method="POST" class="space-y-6">
            @csrf

            {{-- Validation Error Alert Banner --}}
            @if ($errors->any())
                <div class="p-5 bg-red-50 border-2 border-red-200 rounded-3xl text-red-950 text-xs shadow-md space-y-2.5">
                    <div class="flex items-center gap-2.5 font-black text-red-900 uppercase tracking-wider text-sm">
                        <span class="w-3 h-3 rounded-full bg-red-500 animate-ping"></span>
                        <span>Please correct the following before completing close-out:</span>
                    </div>
                    <ul class="list-disc pl-5 space-y-1 text-red-800 font-semibold">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- SECTION 1: Session Performance Summary (Readonly Scorecard) --}}
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-5">
                <div class="border-b border-slate-100 pb-3 flex justify-between items-center">
                    <div>
                        <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider">1. Shift Performance Scorecard</h3>
                        <p class="text-xs text-slate-500 font-medium">Read-only final session metrics captured during operation</p>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-emerald-800 bg-emerald-50 px-3 py-1 rounded-xl border border-emerald-200">
                        Final Audit View
                    </span>
                </div>

                {{-- Operational Metrics Scorecard Grid --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                    {{-- Target Qty --}}
                    <div class="bg-slate-50/80 p-3.5 rounded-2xl border border-slate-200/80">
                        <span class="block text-[10px] font-black text-slate-500 uppercase tracking-wider">Target Qty</span>
                        <span class="text-lg font-black text-slate-900 leading-tight block">{{ number_format($wo->target_qty ?? 0) }} Pcs</span>
                        <span class="text-[9px] font-bold text-slate-400">Work Order Target</span>
                    </div>

                    {{-- Total Input --}}
                    <div class="bg-blue-50/70 p-3.5 rounded-2xl border border-blue-200/80">
                        <span class="block text-[10px] font-black text-blue-700 uppercase tracking-wider">Total Input WIP</span>
                        <span class="text-lg font-black text-blue-950 leading-tight block">{{ number_format($session->total_input) }} Pcs</span>
                        <span class="text-[9px] font-bold text-blue-700/80">Received Stock</span>
                    </div>

                    {{-- Leftover WIP Stock --}}
                    <div class="bg-amber-50/70 p-3.5 rounded-2xl border border-amber-200/80">
                        <span class="block text-[10px] font-black text-amber-800 uppercase tracking-wider">Leftover WIP</span>
                        <span class="text-lg font-black text-amber-950 leading-tight block">{{ number_format($unusedWip) }} Pcs</span>
                        <span class="text-[9px] font-bold text-amber-700/80">Unprocessed Balance</span>
                    </div>

                    {{-- Good Output --}}
                    <div class="bg-emerald-50/70 p-3.5 rounded-2xl border border-emerald-200/80">
                        <span class="block text-[10px] font-black text-emerald-700 uppercase tracking-wider">Good Output</span>
                        <span class="text-lg font-black text-emerald-950 leading-tight block">{{ number_format($session->total_good) }} Pcs</span>
                        <span class="text-[9px] font-bold text-emerald-800/80 truncate block">{{ number_format($directGood) }} Direct • {{ number_format($session->total_rework_recovered) }} Rec.</span>
                    </div>

                    {{-- Final Scrap --}}
                    <div class="bg-red-50/70 p-3.5 rounded-2xl border border-red-200/80">
                        <span class="block text-[10px] font-black text-red-700 uppercase tracking-wider">Final Scrap</span>
                        <span class="text-lg font-black text-red-950 leading-tight block">{{ number_format($finalScrap) }} Pcs</span>
                        <span class="text-[9px] font-bold text-red-700/80 truncate block">
                            @if($session->total_scrap > 0 && ($finalScrap - $session->total_scrap) > 0)
                                {{ number_format($session->total_scrap) }} Scrapped • {{ number_format($finalScrap - $session->total_scrap) }} Line Defect
                            @elseif($session->total_scrap > 0)
                                {{ number_format($session->total_scrap) }} Scrapped on Bench
                            @else
                                Net Defect Write-off
                            @endif
                        </span>
                    </div>

                    {{-- Total Downtime --}}
                    <div class="bg-yellow-50/70 p-3.5 rounded-2xl border border-yellow-200/80">
                        <span class="block text-[10px] font-black text-yellow-800 uppercase tracking-wider">Total Downtime</span>
                        <span class="text-lg font-black text-yellow-950 leading-tight block">{{ number_format($session->downtimeEntries->sum('duration_minutes')) }} Mins</span>
                        <span class="text-[9px] font-bold text-yellow-700">{{ $session->downtimeEntries->count() }} Stoppage Log(s)</span>
                    </div>

                    {{-- Yield Rate --}}
                    <div class="bg-purple-50/70 p-3.5 rounded-2xl border border-purple-200/80 md:col-span-2">
                        <span class="block text-[10px] font-black text-purple-700 uppercase tracking-wider">Process Yield Rate</span>
                        <div class="flex items-baseline gap-2">
                            <span class="text-xl font-black text-purple-950">{{ number_format($session->yield, 1) }}%</span>
                            <span class="text-[10px] font-bold text-purple-700">({{ number_format($session->total_good) }} / {{ number_format($session->total_good + $finalScrap) }} Processed)</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SECTION 2: Material Consumption (Paint & Parts) --}}
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-6">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider">2. Material Consumption & Lot Tracking</h3>
                    <p class="text-xs text-slate-500 font-medium">Record lot numbers, viscosity, mixing ratio, and raw material quantities used during shift</p>
                </div>

                {{-- Paint Materials Sub-table --}}
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-[11px] font-black text-slate-700 uppercase tracking-wider">Paint Items (Viscosity & Mixing Ratio)</span>
                        <button type="button" @click="addPaintRow()" class="px-3.5 py-1.5 bg-blue-50 hover:bg-blue-100 active:bg-blue-200 text-blue-800 font-black text-xs rounded-xl border border-blue-200 transition cursor-pointer">
                            + Add Paint Item
                        </button>
                    </div>

                    <div class="overflow-x-auto border border-slate-200/80 rounded-2xl">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-[10px] font-black text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                <tr>
                                    <th class="px-3.5 py-2.5">Item Name</th>
                                    <th class="px-3.5 py-2.5">Lot Number</th>
                                    <th class="px-3 py-2.5 w-24">Viscosity</th>
                                    <th class="px-3 py-2.5 w-28">Mixing Ratio</th>
                                    <th class="px-3 py-2.5 w-24">Qty</th>
                                    <th class="px-3 py-2.5 w-20">UOM</th>
                                    <th class="px-2 py-2.5 text-center w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <template x-for="(mat, idx) in paintMaterials" :key="'paint-' + idx">
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-3.5 py-2">
                                            <input type="hidden" :name="'materials[' + mat.globalIndex + '][type]'" value="paint">
                                            <input type="text" :name="'materials[' + mat.globalIndex + '][item_name]'" x-model="mat.item_name" placeholder="Item Name" class="w-full text-xs font-bold rounded-xl border-slate-300 py-1.5 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-blue-500">
                                        </td>
                                        <td class="px-3.5 py-2">
                                            <input type="text" :name="'materials[' + mat.globalIndex + '][lot_number]'" x-model="mat.lot_number" placeholder="Lot #" class="w-full text-xs font-medium rounded-xl border-slate-300 py-1.5 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-blue-500 font-mono">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text" :name="'materials[' + mat.globalIndex + '][visco]'" x-model="mat.visco" placeholder="Visco" class="w-full text-xs font-medium rounded-xl border-slate-300 py-1.5 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-blue-500">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text" :name="'materials[' + mat.globalIndex + '][mixing_ratio]'" x-model="mat.mixing_ratio" placeholder="1:1.5" class="w-full text-xs font-medium rounded-xl border-slate-300 py-1.5 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-blue-500">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number" step="any" :name="'materials[' + mat.globalIndex + '][qty]'" x-model="mat.qty" placeholder="0" class="w-full text-xs font-bold rounded-xl border-slate-300 py-1.5 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-blue-500">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text" :name="'materials[' + mat.globalIndex + '][uom]'" x-model="mat.uom" placeholder="Kg/L" class="w-full text-xs font-medium rounded-xl border-slate-300 py-1.5 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-blue-500">
                                        </td>
                                        <td class="px-2 py-2 text-center">
                                            <button type="button" @click="removePaintRow(idx)" class="text-slate-400 hover:text-red-600 font-black text-base px-1 transition cursor-pointer" title="Remove">&times;</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Part / WIP Materials Sub-table --}}
                <div class="space-y-3 pt-2">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-black text-slate-700 uppercase tracking-wider">Item Parts / WIP Lots</span>
                            @if($session->inputEntries && $session->inputEntries->isNotEmpty())
                                <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-md">
                                    ✓ Pre-filled from {{ $session->inputEntries->count() }} Input Log{{ $session->inputEntries->count() > 1 ? 's' : '' }}
                                </span>
                            @endif
                        </div>
                        <button type="button" @click="addPartRow()" class="px-3.5 py-1.5 bg-blue-50 hover:bg-blue-100 active:bg-blue-200 text-blue-800 font-black text-xs rounded-xl border border-blue-200 transition cursor-pointer">
                            + Add Part / WIP Item
                        </button>
                    </div>

                    <div class="overflow-x-auto border border-slate-200/80 rounded-2xl">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-[10px] font-black text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                <tr>
                                    <th class="px-3.5 py-2.5">Item Name</th>
                                    <th class="px-3.5 py-2.5">Lot Number</th>
                                    <th class="px-3.5 py-2.5 w-28">Qty</th>
                                    <th class="px-3.5 py-2.5 w-24">UOM</th>
                                    <th class="px-2 py-2.5 text-center w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <template x-for="(mat, idx) in partMaterials" :key="'part-' + idx">
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-3.5 py-2">
                                            <input type="hidden" :name="'materials[' + mat.globalIndex + '][type]'" value="part">
                                            <input type="text" :name="'materials[' + mat.globalIndex + '][item_name]'" x-model="mat.item_name" placeholder="Item Name" class="w-full text-xs font-bold rounded-xl border-slate-300 py-1.5 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-blue-500">
                                        </td>
                                        <td class="px-3.5 py-2">
                                            <input type="text" :name="'materials[' + mat.globalIndex + '][lot_number]'" x-model="mat.lot_number" placeholder="Lot #" class="w-full text-xs font-medium rounded-xl border-slate-300 py-1.5 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-blue-500 font-mono">
                                        </td>
                                        <td class="px-3.5 py-2">
                                            <input type="number" step="any" :name="'materials[' + mat.globalIndex + '][qty]'" x-model="mat.qty" placeholder="0" class="w-full text-xs font-bold rounded-xl border-slate-300 py-1.5 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-blue-500">
                                        </td>
                                        <td class="px-3.5 py-2">
                                            <input type="text" :name="'materials[' + mat.globalIndex + '][uom]'" x-model="mat.uom" placeholder="Pcs/Box" class="w-full text-xs font-medium rounded-xl border-slate-300 py-1.5 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-blue-500">
                                        </td>
                                        <td class="px-2 py-2 text-center">
                                            <button type="button" @click="removePartRow(idx)" class="text-slate-400 hover:text-red-600 font-black text-base px-1 transition cursor-pointer" title="Remove">&times;</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- SECTION 3: Downtime Categorization & Shift Handover --}}
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-6">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider">3. Downtime Categorization & Shift Handover</h3>
                    <p class="text-xs text-slate-500 font-medium">Categorize downtime root causes, record countermeasures, and enter handover notes</p>
                </div>

                {{-- Trouble / Downtime Enrichment List --}}
                @if($session->downtimeEntries->isNotEmpty())
                    <div class="space-y-3">
                        <span class="text-[11px] font-black text-slate-700 uppercase tracking-wider block">Logged Downtime Events ({{ $session->downtimeEntries->count() }})</span>

                        <div class="border border-slate-200/80 rounded-2xl overflow-hidden divide-y divide-slate-100 bg-white">
                            @foreach($session->downtimeEntries as $idx => $dt)
                                <div class="p-4 space-y-3 bg-amber-50/30">
                                    <input type="hidden" name="troubles[{{ $idx }}][downtime_id]" value="{{ $dt->id }}">
                                    
                                    <div class="flex flex-wrap justify-between items-center text-xs gap-2">
                                        <div class="flex items-center gap-2">
                                            <span class="font-black text-slate-900">{{ $dt->reason }}</span>
                                            <span class="text-[10px] font-black bg-amber-100 text-amber-900 px-2 py-0.5 rounded-lg border border-amber-200">
                                                {{ $dt->duration_minutes }} mins
                                            </span>
                                        </div>
                                        <div class="text-[10px] font-mono text-slate-400">
                                            {{ $dt->start_time ? $dt->start_time->setTimezone(config('mes.timezone', 'Asia/Jakarta'))->format('H:i') : '-' }} – {{ $dt->resume_time ? $dt->resume_time->setTimezone(config('mes.timezone', 'Asia/Jakarta'))->format('H:i') : '-' }}
                                        </div>
                                    </div>

                                     <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                                        <div>
                                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Category *</label>
                                            <select name="troubles[{{ $idx }}][category]" class="w-full rounded-xl border-slate-300 text-xs font-bold text-slate-800 py-1.5 focus:ring-2 focus:ring-blue-500 bg-white @error("troubles.{$idx}.category") border-red-500 ring-1 ring-red-500 bg-red-50/20 @enderror">
                                                <option value="">-- Select Category --</option>
                                                @foreach($troubleCategories as $cat)
                                                    <option value="{{ $cat }}" {{ (old("troubles.{$idx}.category", $dt->category) == $cat) ? 'selected' : '' }}>{{ $cat }}</option>
                                                @endforeach
                                            </select>
                                            @error("troubles.{$idx}.category")
                                                <p class="text-[10px] text-red-600 font-bold mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Countermeasure (Penanganan)</label>
                                            <input type="text" name="troubles[{{ $idx }}][countermeasure]" value="{{ old("troubles.{$idx}.countermeasure", $dt->countermeasure) }}" placeholder="Describe action taken to resolve..." class="w-full rounded-xl border-slate-300 text-xs font-medium text-slate-800 py-1.5 focus:ring-2 focus:ring-blue-500 bg-white">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="text-xs text-slate-500 font-medium bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80">
                        ✓ No downtime events were logged during this session.
                    </div>
                @endif

                {{-- Notes & Schedule Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-xs">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[11px] font-black text-slate-700 uppercase tracking-wider mb-1">Production Notes</label>
                            <textarea name="production_notes" rows="3" placeholder="General production notes or remarks..." class="w-full rounded-2xl border-slate-300 text-xs font-medium focus:ring-2 focus:ring-blue-500 bg-slate-50/40 focus:bg-white">{{ old('production_notes', $session->production_notes ?? $session->remarks) }}</textarea>
                        </div>

                        <div>
                            <label class="block text-[11px] font-black text-slate-700 uppercase tracking-wider mb-1">NG Remarks</label>
                            <textarea name="ng_remarks" rows="2" placeholder="Remarks regarding defect causes..." class="w-full rounded-2xl border-slate-300 text-xs font-medium focus:ring-2 focus:ring-blue-500 bg-slate-50/40 focus:bg-white">{{ old('ng_remarks', $session->ng_remarks) }}</textarea>
                        </div>

                        <div>
                            <label class="block text-[11px] font-black text-slate-700 uppercase tracking-wider mb-1">Absent Employees</label>
                            <input type="text" name="absent_employees" value="{{ old('absent_employees', $session->absent_employees) }}" placeholder="e.g. John (Sakit), Jane (Izin)..." class="w-full rounded-2xl border-slate-300 text-xs font-medium focus:ring-2 focus:ring-blue-500 bg-slate-50/40 focus:bg-white py-2">
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-[11px] font-black text-slate-700 uppercase tracking-wider mb-1">Output Destination *</label>
                            <select name="output_destination" required class="w-full rounded-2xl border-slate-300 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-blue-500 bg-slate-50/40 focus:bg-white py-2 @error('output_destination') border-red-500 ring-1 ring-red-500 bg-red-50/20 @enderror">
                                <option value="">-- Select Destination --</option>
                                <option value="fg" {{ old('output_destination', $session->output_destination) == 'fg' ? 'selected' : '' }}>Finished Goods (FG)</option>
                                <option value="buffing" {{ old('output_destination', $session->output_destination) == 'buffing' ? 'selected' : '' }}>Buffing</option>
                                <option value="next_process" {{ old('output_destination', $session->output_destination) == 'next_process' ? 'selected' : '' }}>Next Process Area</option>
                            </select>
                            @error('output_destination')
                                <p class="text-[10px] text-red-600 font-bold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-black text-slate-700 uppercase tracking-wider mb-1">Next Production Schedule</label>
                            <div class="space-y-2">
                                @for($i = 0; $i < 4; $i++)
                                    @php
                                        $schVal = is_array($session->next_production_schedule) ? ($session->next_production_schedule[$i] ?? '') : '';
                                    @endphp
                                    <div class="flex items-center gap-2">
                                        <span class="text-slate-400 font-bold w-4 text-right">{{ $i + 1 }}.</span>
                                        <input type="text" name="next_production_schedule[]" value="{{ old("next_production_schedule.{$i}", $schVal) }}" placeholder="Next schedule item {{ $i + 1 }}" class="w-full rounded-2xl border-slate-300 text-xs font-medium focus:ring-2 focus:ring-blue-500 py-1.5 bg-slate-50/40 focus:bg-white">
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Form Actions Footer (Mandatory Close-Out) --}}
            <div class="flex justify-between items-center pt-3 border-t border-slate-200">
                <a href="{{ route('app.sp-sessions.show', $session->id) }}" class="px-5 py-3 bg-slate-100 hover:bg-slate-200 active:bg-slate-300 text-slate-700 font-bold text-xs rounded-2xl border border-slate-300 transition uppercase tracking-wider cursor-pointer">
                    ← Back to Live Session
                </a>

                <button type="submit" class="px-8 py-3.5 bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white font-black text-xs rounded-2xl shadow-lg transition uppercase tracking-wider cursor-pointer flex items-center gap-2">
                    <span>SUBMIT CLOSE-OUT REPORT & FINISH SHIFT</span>
                </button>
            </div>
        </form>
    </div>

    <script>
        function closeoutForm() {
            return {
                nextGlobalIndex: 0,
                paintMaterials: [],
                partMaterials: [],

                init() {
                    const existingMaterials = @json($session->materials);

                    if (existingMaterials && existingMaterials.length > 0) {
                        existingMaterials.forEach(m => {
                            const row = {
                                globalIndex: this.nextGlobalIndex++,
                                item_name: m.item_name || '',
                                lot_number: m.lot_number || '',
                                visco: m.visco || '',
                                mixing_ratio: m.mixing_ratio || '',
                                qty: m.qty || '',
                                uom: m.uom || ''
                            };
                            if (m.type === 'paint') {
                                this.paintMaterials.push(row);
                            } else {
                                this.partMaterials.push(row);
                            }
                        });
                    } else {
                        // Default Paint presets
                        const defaultPaints = @json($defaultPaintMaterials);
                        defaultPaints.forEach(name => {
                            this.paintMaterials.push({
                                globalIndex: this.nextGlobalIndex++,
                                item_name: name,
                                lot_number: '',
                                visco: '',
                                mixing_ratio: '',
                                qty: '',
                                uom: ''
                            });
                        });

                        // Default Part presets (derived from session input logs)
                        const defaultParts = @json($defaultPartMaterials);
                        defaultParts.forEach(part => {
                            this.partMaterials.push({
                                globalIndex: this.nextGlobalIndex++,
                                item_name: part.item_name || '',
                                lot_number: part.lot_number || '',
                                qty: part.qty !== '' ? part.qty : '',
                                uom: part.uom || 'Pcs'
                            });
                        });
                    }
                },

                addPaintRow() {
                    this.paintMaterials.push({
                        globalIndex: this.nextGlobalIndex++,
                        item_name: '',
                        lot_number: '',
                        visco: '',
                        mixing_ratio: '',
                        qty: '',
                        uom: ''
                    });
                },

                removePaintRow(idx) {
                    this.paintMaterials.splice(idx, 1);
                },

                addPartRow() {
                    this.partMaterials.push({
                        globalIndex: this.nextGlobalIndex++,
                        item_name: '',
                        lot_number: '',
                        qty: '',
                        uom: 'Pcs'
                    });
                },

                removePartRow(idx) {
                    this.partMaterials.splice(idx, 1);
                }
            };
        }
    </script>
</x-operator-layout>
