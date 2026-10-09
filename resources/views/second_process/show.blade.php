<x-app-layout>
    <div class="py-6 no-print">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex justify-between">
            <a href="{{ route('second-process-reports.index') }}"
                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded transition">
                &larr; Back to List
            </a>
            <div class="space-x-2">
                <button onclick="window.print()"
                    class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition">
                    Print Report
                </button>
                @can('update', $report)
                    <a href="{{ route('second-process-reports.edit', $report->id) }}"
                        class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded transition">
                        Edit Report
                    </a>
                @endcan
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 no-print mb-4">
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative text-sm"
                role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 no-print mb-4">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative text-sm"
                role="alert">
                <span class="block sm:inline">{{ $errors->first() }}</span>
            </div>
        </div>
    @endif

    <!-- Workflow Approval Banner -->
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 no-print mb-6">
        <div
            class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
            <div>
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Report Status</h3>
                <div class="flex items-center space-x-2 mt-1">
                    @if ($report->status === 'draft')
                        <span
                            class="px-2.5 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-800 uppercase">Draft</span>
                        <span class="text-xs text-gray-500">Only editable by operator. Needs submission.</span>
                    @elseif($report->status === 'submitted')
                        <span
                            class="px-2.5 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800 uppercase">Submitted</span>
                        <span class="text-xs text-gray-500">Pending Team Leader review.</span>
                    @elseif($report->status === 'leader_approved')
                        <span
                            class="px-2.5 py-1 text-xs font-semibold rounded bg-orange-100 text-orange-800 uppercase">Leader
                            Approved</span>
                        <span class="text-xs text-gray-500">Pending Supervisor acknowledgment (PQC review optional).</span>
                    @elseif($report->status === 'pqc_approved')
                        <span
                            class="px-2.5 py-1 text-xs font-semibold rounded bg-yellow-100 text-yellow-800 uppercase">PQC
                            Approved</span>
                        <span class="text-xs text-gray-500">Pending Supervisor acknowledgment.</span>
                    @elseif($report->status === 'acknowledged')
                        <span
                            class="px-2.5 py-1 text-xs font-semibold rounded bg-green-100 text-green-800 uppercase">Acknowledged</span>
                        <span class="text-xs text-gray-500">Fully approved and finalized.</span>
                    @endif
                </div>
            </div>

            <div class="flex items-center space-x-2">
                @if ($report->status === 'draft')
                    @can('sign', [$report, 'checker'])
                        <form action="{{ route('second-process-reports.sign', [$report->id, 'checker']) }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow transition text-sm">
                                Submit Report
                            </button>
                        </form>
                    @endcan
                @elseif($report->status === 'submitted')
                    @can('sign', [$report, 'leader'])
                        <form action="{{ route('second-process-reports.sign', [$report->id, 'leader']) }}" method="POST"
                            class="inline">
                            @csrf
                            <button type="submit"
                                class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-4 rounded shadow transition text-sm mr-2">
                                Sign as Leader
                            </button>
                        </form>
                    @endcan
                    @can('reject', $report)
                        <button onclick="document.getElementById('reject-dialog').showModal()"
                            class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded shadow transition text-sm">
                            Reject
                        </button>
                    @endcan
                @elseif($report->status === 'leader_approved' || $report->status === 'pqc_approved')
                    @if (!$report->pqc_signed_at)
                        @can('sign', [$report, 'pqc'])
                            <form action="{{ route('second-process-reports.sign', [$report->id, 'pqc']) }}" method="POST"
                                class="inline">
                                @csrf
                                <button type="submit"
                                    class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded shadow transition text-sm mr-2">
                                    Sign as PQC (Optional)
                                </button>
                            </form>
                        @endcan
                    @endif
                    @can('sign', [$report, 'acknowledged'])
                        <form action="{{ route('second-process-reports.sign', [$report->id, 'acknowledged']) }}"
                            method="POST" class="inline">
                            @csrf
                            <button type="submit"
                                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded shadow transition text-sm mr-2">
                                Acknowledge (Supervisor)
                            </button>
                        </form>
                    @endcan
                    @can('reject', $report)
                        <button onclick="document.getElementById('reject-dialog').showModal()"
                            class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded shadow transition text-sm">
                            Reject
                        </button>
                    @endcan
                @elseif($report->status === 'acknowledged' && !$report->pqc_signed_at)
                    @can('sign', [$report, 'pqc'])
                        <form action="{{ route('second-process-reports.sign', [$report->id, 'pqc']) }}" method="POST"
                            class="inline">
                            @csrf
                            <button type="submit"
                                class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded shadow transition text-sm mr-2">
                                Sign as PQC (Optional)
                            </button>
                        </form>
                    @endcan
                @endif
            </div>
        </div>
    </div>

    <!-- Rejection Dialog -->
    <dialog id="reject-dialog"
        class="rounded-lg p-6 shadow-2xl border border-gray-300 w-full max-w-md backdrop:bg-gray-900/50">
        <form action="{{ route('second-process-reports.reject', $report->id) }}" method="POST">
            @csrf
            <h3 class="text-sm font-bold text-gray-900 mb-2">Reject Production Report</h3>
            <p class="text-[11px] text-gray-500 mb-4 leading-normal font-medium">Please provide a brief reason for
                rejecting this report. The report status will return to Draft and signatures will be cleared.</p>
            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Rejection Reason</label>
                <textarea name="rejection_reason" rows="3" required
                    class="w-full text-xs rounded border-gray-300 focus:border-red-500 focus:ring-red-500"
                    placeholder="E.g., NG quantities on hour 4 mismatch..."></textarea>
            </div>
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="document.getElementById('reject-dialog').close()"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-3 py-1.5 rounded text-xs font-bold transition">
                    Cancel
                </button>
                <button type="submit"
                    class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded text-xs font-bold shadow-sm transition">
                    Submit Rejection
                </button>
            </div>
        </form>
    </dialog>

    <!-- First Piece Gate Status Card -->
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 no-print mb-6">
        <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center space-x-3">
                @if($firstPiece && $firstPiece->isApproved())
                    <div class="p-2.5 rounded-full bg-green-100 text-green-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider">First Piece Gate Status</h4>
                        <div class="text-sm font-bold text-green-800 flex items-center gap-2">
                            <span>✅ Approved by QC Inspector ({{ $firstPiece->checked_by }})</span>
                            <span class="text-xs font-normal text-gray-500">on {{ $firstPiece->checked_at ? $firstPiece->checked_at->format('d/m/Y H:i') : '' }}</span>
                        </div>
                    </div>
                @else
                    <div class="p-2.5 rounded-full bg-amber-100 text-amber-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider">First Piece Gate Status</h4>
                        <div class="text-sm font-bold text-amber-800">
                            ⚠️ First Piece Inspection Pending / Not Approved by QC
                        </div>
                    </div>
                @endif
            </div>

            <div>
                @if($firstPiece)
                    <a href="{{ route('first-piece-inspections.show', $firstPiece->id) }}" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-xs transition inline-block">
                        View First Piece Inspection &rarr;
                    </a>
                @else
                    <a href="{{ route('first-piece-inspections.create', ['part_number' => $report->part_number, 'date' => $report->date, 'part_name' => $report->part_name, 'model' => $report->model]) }}" target="_blank" class="bg-amber-600 hover:bg-amber-700 text-white font-bold py-2 px-4 rounded text-xs transition inline-block">
                        + Create First Piece Inspection &rarr;
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Printable Area -->
    @php
        $maxHour = max(1, $report->hourlyProductions->max('hour_ke'));
        $hours = range(1, $maxHour);
    @endphp
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 pb-12 print-area">
        <div class="bg-white shadow-xl rounded-lg overflow-hidden border border-gray-200 p-6 space-y-6">

            <!-- Header Table style -->
            <div
                class="border-2 border-black grid grid-cols-1 md:grid-cols-4 divide-y md:divide-y-0 md:divide-x divide-black text-sm">
                <div class="p-4 flex flex-col justify-center items-center col-span-2">
                    <h1 class="text-xl font-black text-center tracking-tight">PT. DAIJO INDUSTRIAL</h1>
                    <p class="text-xs font-bold text-center mt-1">Second Process Departement</p>
                </div>
                <div class="p-2 grid grid-cols-2 gap-x-2 text-[11px] leading-tight col-span-2">
                    <div class="font-bold">No. Dokumen</div>
                    <div>: DI-F-P/PR/07/SP-001</div>
                    <div class="font-bold">Tgl. Dikeluarkan</div>
                    <div>: 04 Januari 2023</div>
                    <div class="font-bold">Mulai berlaku</div>
                    <div>: 08 Desember 2025</div>
                    <div class="font-bold">Revisi / Hal</div>
                    <div>: 2 / 1 of 1</div>
                </div>
            </div>

            <div class="text-center mt-4">
                <h2 class="text-xl font-bold uppercase tracking-wider underline">Laporan Produksi Harian</h2>
            </div>

            <!-- Header Fields -->
            <table class="w-full border-collapse border border-black text-xs">
                <tbody>
                    <tr>
                        <td class="border border-black p-2 font-bold bg-gray-50 w-1/6">Tanggal</td>
                        <td class="border border-black p-2 w-1/4">{{ $report->date }}</td>
                        <td class="border border-black p-2 font-bold bg-gray-50 w-1/6">Model</td>
                        <td class="border border-black p-2 w-1/4">{{ $report->model }}</td>
                        <td class="border border-black p-2 font-bold bg-gray-50 w-1/12">Target/Jam</td>
                        <td class="border border-black p-2">{{ $report->target_per_hour }}</td>
                    </tr>
                    <tr>
                        <td class="border border-black p-2 font-bold bg-gray-50">Unit / Line</td>
                        <td class="border border-black p-2">{{ $report->unit_line }}</td>
                        <td class="border border-black p-2 font-bold bg-gray-50">Part Number</td>
                        <td class="border border-black p-2">{{ $report->part_number }}</td>
                        <td class="border border-black p-2 font-bold bg-gray-50">Jml Input WIP</td>
                        <td class="border border-black p-2">{{ $report->jml_input_wip }}</td>
                    </tr>
                    <tr>
                        <td class="border border-black p-2 font-bold bg-gray-50">Shift</td>
                        <td class="border border-black p-2">{{ $report->shift }}</td>
                        <td class="border border-black p-2 font-bold bg-gray-50">Part Name</td>
                        <td class="border border-black p-2">{{ $report->part_name }}</td>
                        <td class="border border-black p-2 font-bold bg-gray-50">Repairan</td>
                        <td class="border border-black p-2">{{ $report->repairan }}</td>
                    </tr>
                    <tr>
                        <td class="border border-black p-2 font-bold bg-gray-50">Proses Prod</td>
                        <td class="border border-black p-2">{{ $report->process_prod }}</td>
                        <td class="border border-black p-2 font-bold bg-gray-50">Customer</td>
                        <td class="border border-black p-2">{{ $report->customer }}</td>
                        <td class="border border-black p-2 font-bold bg-gray-50">Jumlah Output</td>
                        <td class="border border-black p-2 font-semibold">{{ $report->jumlah_output }}</td>
                    </tr>
                    <tr>
                        <td class="border border-black p-2 font-bold bg-gray-50">Status</td>
                        <td class="border border-black p-2 uppercase font-semibold text-blue-700">
                            {{ $report->status ?: 'draft' }}</td>
                        <td class="border border-black p-2 font-bold bg-gray-50">Tujuan Output</td>
                        <td class="border border-black p-2 uppercase font-semibold text-indigo-700">
                            {{ $report->output_destination ?: '-' }}</td>
                        <td class="border border-black p-2 font-bold bg-gray-50">NG Remarks</td>
                        <td class="border border-black p-2">{{ $report->ng_remarks ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td colspan="4" class="border border-black"></td>
                        <td class="border border-black p-2 font-bold bg-gray-50 text-green-700">Jumlah OK</td>
                        <td class="border border-black p-2 text-green-700 font-bold">{{ $report->jumlah_ok }}</td>
                    </tr>
                    <tr>
                        <td colspan="4" class="border border-black"></td>
                        <td class="border border-black p-2 font-bold bg-gray-50 text-red-700">Jumlah NG</td>
                        <td class="border border-black p-2 text-red-700 font-bold">{{ $report->jumlah_ng }}</td>
                    </tr>
                    <tr>
                        <td colspan="4" class="border border-black"></td>
                        <td class="border border-black p-2 font-bold bg-gray-50 text-red-700">NG Prosentase</td>
                        <td class="border border-black p-2 text-red-700 font-bold">{{ $report->ng_prosentase }}%</td>
                    </tr>
                    <tr>
                        <td colspan="4" class="border border-black"></td>
                        <td class="border border-black p-2 font-bold bg-gray-50">Jml NG Lebur</td>
                        <td class="border border-black p-2">{{ $report->jml_ng_lebur }}</td>
                    </tr>
                    <tr>
                        <td colspan="4" class="border border-black"></td>
                        <td class="border border-black p-2 font-bold bg-gray-50">Sisa Input</td>
                        <td class="border border-black p-2 font-bold {{ ($report->sisa_input ?? 0) > 0 ? 'text-amber-700' : (($report->sisa_input ?? 0) < 0 ? 'text-red-700' : 'text-green-700') }}">
                            {{ $report->sisa_input ?? 0 }} pcs
                            @if (($report->sisa_input ?? 0) === 0)
                                <span class="text-[10px] font-normal text-green-700 ml-1">(Ideal)</span>
                            @endif
                        </td>
                    </tr>
                    @if (!empty($report->sisa_input_remark))
                    <tr>
                        <td colspan="4" class="border border-black"></td>
                        <td class="border border-black p-2 font-bold bg-gray-50">Remark Sisa Input</td>
                        <td class="border border-black p-2 text-xs italic">{{ $report->sisa_input_remark }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>

            <!-- Materials & Results section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <!-- Materials Column -->
                <div class="space-y-4">
                    <h3 class="text-sm font-bold border-b border-black pb-1">Material</h3>

                    <table class="w-full border-collapse border border-black text-[11px]">
                        <thead class="bg-gray-100 font-bold">
                            <tr>
                                <th class="border border-black p-1 text-left">Item Paint</th>
                                <th class="border border-black p-1">Lot Number</th>
                                <th class="border border-black p-1">Visco</th>
                                <th class="border border-black p-1">Mixing Ratio</th>
                                <th class="border border-black p-1">Qty</th>
                                <th class="border border-black p-1">UOM</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($report->materials->where('type', 'paint') as $material)
                                <tr>
                                    <td class="border border-black p-1 font-semibold">{{ $material->item_name }}</td>
                                    <td class="border border-black p-1 text-center">{{ $material->lot_number }}</td>
                                    <td class="border border-black p-1 text-center">{{ $material->visco }}</td>
                                    <td class="border border-black p-1 text-center font-mono">
                                        {{ $material->mixing_ratio ?: '-' }}</td>
                                    <td class="border border-black p-1 text-center">{{ $material->qty }}</td>
                                    <td class="border border-black p-1 text-center">{{ $material->uom ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="border border-black p-2 text-center text-gray-400 italic">
                                        No paint materials recorded
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <table class="w-full border-collapse border border-black text-[11px]">
                        <thead class="bg-gray-100 font-bold">
                            <tr>
                                <th class="border border-black p-1 text-left">Item Parts</th>
                                <th class="border border-black p-1">Lot Number</th>
                                <th class="border border-black p-1">Qty</th>
                                <th class="border border-black p-1">UOM</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($report->materials->where('type', 'part') as $material)
                                <tr>
                                    <td class="border border-black p-1 font-semibold">{{ $material->item_name }}</td>
                                    <td class="border border-black p-1 text-center">{{ $material->lot_number }}</td>
                                    <td class="border border-black p-1 text-center">{{ $material->qty }}</td>
                                    <td class="border border-black p-1 text-center">{{ $material->uom ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="border border-black p-2 text-center text-gray-400 italic">
                                        No item parts recorded
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Hourly Production Column -->
                <div class="space-y-4">
                    <h3 class="text-sm font-bold border-b border-black pb-1">Hasil Produksi</h3>
                    <table class="w-full border-collapse border border-black text-[11px] text-center">
                        <thead class="bg-gray-100 font-bold">
                            <tr>
                                <th class="border border-black p-1">Jam ke</th>
                                <th class="border border-black p-1">OK</th>
                                <th class="border border-black p-1">Acumulasi</th>
                                <th class="border border-black p-1">NG</th>
                                <th class="border border-black p-1">Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($hours as $hour)
                                @php
                                    $hData = $report->hourlyProductions->where('hour_ke', $hour)->first();
                                @endphp
                                <tr>
                                    <td class="border border-black p-1 font-bold">{{ $hour }}</td>
                                    <td class="border border-black p-1">
                                        {{ $hData && $hData->ok_qty !== null ? $hData->ok_qty : '-' }}</td>
                                    <td class="border border-black p-1 font-semibold">
                                        {{ $hData && $hData->acumulasi_qty !== null ? $hData->acumulasi_qty : '-' }}
                                    </td>
                                    <td class="border border-black p-1 text-red-600 font-semibold">
                                        {{ $hData && $hData->ng_qty !== null ? $hData->ng_qty : '-' }}</td>
                                    <td class="border border-black p-1 text-left px-2">
                                        {{ $hData && $hData->remark ? $hData->remark : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>

            <!-- Manpower Section -->
            <div>
                <h3 class="text-sm font-bold border-b border-black pb-1 mb-2">Manpower (MP)</h3>
                @php
                    $mpGroups = $report->manpowers->sortBy('no')->groupBy('role');
                @endphp
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 text-[11px]">
                    @forelse($mpGroups as $role => $members)
                        <div class="border border-black">
                            <div class="bg-gray-100 p-1 font-bold text-center border-b border-black capitalize">
                                {{ $role }}
                            </div>
                            <ul class="divide-y divide-black p-1">
                                @foreach ($members as $mp)
                                    <li class="py-1 px-2">{{ $mp->no }}. {{ $mp->name ?: '-' }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @empty
                        <p class="text-gray-400 col-span-full text-center py-2">No manpower recorded.</p>
                    @endforelse
                </div>
            </div>

            <!-- NG Produksi table -->
            <div>
                <h3 class="text-sm font-bold border-b border-black pb-1 mb-2">NG Produksi / Jam</h3>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse border border-black text-[11px] text-center">
                        <thead class="bg-gray-100 font-bold">
                            <tr>
                                <th class="border border-black p-1 text-left">ITEMS NG</th>
                                @foreach ($hours as $h)
                                    <th class="border border-black p-1 w-8">{{ $h }}</th>
                                @endforeach
                                <th class="border border-black p-1 w-16 font-bold text-red-600">Total NG</th>
                                <th class="border border-black p-1 text-left">NG Input (Item NG / Qty)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $defaultNgs = $report->ngRecords->pluck('ng_name')->unique()->toArray();
                            @endphp
                            @foreach ($defaultNgs as $ngName)
                                @php
                                    $ng = $report->ngRecords->where('ng_name', $ngName)->first();
                                @endphp
                                <tr>
                                    <td class="border border-black p-1 text-left font-bold">{{ $ngName }}</td>
                                    @foreach ($hours as $h)
                                        @php
                                            $detail = $ng ? $ng->hourlyDetails->where('hour_ke', $h)->first() : null;
                                        @endphp
                                        <td class="border border-black p-1">
                                            {{ $detail && $detail->qty !== null ? $detail->qty : '-' }}</td>
                                    @endforeach
                                    <td class="border border-black p-1 font-bold text-red-600">
                                        {{ $ng && $ng->total_ng ? $ng->total_ng : '-' }}</td>
                                    <td class="border border-black p-1 text-left px-2">
                                        @if ($ng && $ng->ng_input_item)
                                            @php
                                                $prettified = preg_replace(
                                                    '/\[(\d+)\]\s*([^\|]+)/',
                                                    '$1x $2',
                                                    $ng->ng_input_item,
                                                );
                                                $prettified = str_replace(' | ', ', ', $prettified);
                                            @endphp
                                            {{ $prettified }} ({{ $ng->ng_input_qty }})
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Troubles section -->
            <div>
                <h3 class="text-sm font-bold border-b border-black pb-1 mb-2">Trouble Report</h3>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[500px] md:min-w-0 border-collapse border border-black text-[11px]">
                        <thead class="bg-gray-100 font-bold">
                            <tr>
                                <th class="border border-black p-1 w-10 text-center">#</th>
                                <th class="border border-black p-1 w-2/5">Masalah (Problem)</th>
                                <th class="border border-black p-1 w-1/5">Category</th>
                                <th class="border border-black p-1 w-2/5">Penanganan (Countermeasure)</th>
                                <th class="border border-black p-1 w-24 text-center">Loss Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($report->troubles as $idx => $trouble)
                                <tr>
                                    <td class="border border-black p-2 text-center font-bold">{{ $idx + 1 }}</td>
                                    <td class="border border-black p-2">{{ $trouble->masalah ?: '-' }}</td>
                                    <td class="border border-black p-2 font-bold">{{ $trouble->penyebab ?: ($trouble->category ?: '-') }}</td>
                                    <td class="border border-black p-2">{{ $trouble->penanganan ?: '-' }}</td>
                                    <td class="border border-black p-2 text-center">
                                        @if ($trouble->loss_time_minutes)
                                            {{ $trouble->loss_time_minutes }} mins
                                        @else
                                            {{ $trouble->loss_time ?: '-' }}
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="border border-black p-3 text-center text-gray-500 italic">
                                        No trouble / downtime reported for this shift.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-100 font-bold">
                            <tr>
                                <td colspan="4" class="border border-black p-1.5 text-right uppercase tracking-wider">Total Loss Time:</td>
                                <td class="border border-black p-1.5 text-center font-bold">
                                    {{ $report->troubles->sum('loss_time_minutes') }} mins
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Notes & Footer signature -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <div class="space-y-2 border border-black p-3">
                    <div>
                        <span class="font-bold uppercase">Catatan Produksi:</span>
                        <p class="mt-1 whitespace-pre-wrap">{{ $report->production_notes ?: '-' }}</p>
                    </div>
                    <div class="pt-2 border-t border-gray-300">
                        <span class="font-bold uppercase">Karyawan Tidak Hadir:</span>
                        <p class="mt-1">{{ $report->absent_employees ?: '-' }}</p>
                    </div>
                </div>
                <div class="flex flex-col justify-between border border-black p-3">
                    <div>
                        <span class="font-bold uppercase">Jadwal Produksi Selanjutnya:</span>
                        <div class="mt-1">
                            @if (is_array($report->next_production_schedule))
                                <ol class="list-decimal pl-4 space-y-1">
                                    @foreach ($report->next_production_schedule as $sch)
                                        @if (!empty($sch))
                                            <li>{{ $sch }}</li>
                                        @endif
                                    @endforeach
                                </ol>
                            @elseif(is_string($report->next_production_schedule))
                                <pre class="font-sans text-xs whitespace-pre-wrap">{{ $report->next_production_schedule }}</pre>
                            @else
                                -
                            @endif
                        </div>
                    </div>

                    <!-- Linked IPQC Inspection -->
                    @if($ipqcInspection)
                        <div class="pt-6 border-t-2 border-black mt-6">
                            <div class="text-center font-bold text-sm uppercase underline mb-3">IPQC INSPECTION REPORT (SECONDARY PROCESS)</div>
                            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 flex flex-col md:flex-row justify-between items-center gap-3 no-print">
                                <div class="flex items-center space-x-3">
                                    <div class="p-2 rounded-full {{ $ipqcInspection->overall_judgement === 'NG' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold text-purple-800">IPQC Inspection Linked</div>
                                        <div class="text-[11px] text-gray-600">
                                            Status: <span class="font-bold {{ $ipqcInspection->status === 'completed' ? 'text-green-700' : 'text-blue-700' }} uppercase">{{ $ipqcInspection->status }}</span>
                                            &middot; Overall: <span class="font-bold {{ $ipqcInspection->overall_judgement === 'NG' ? 'text-red-700' : 'text-green-700' }}">{{ $ipqcInspection->overall_judgement ?? 'OK' }}</span>
                                            &middot; {{ $ipqcInspection->records->count() }} period(s) recorded
                                        </div>
                                    </div>
                                </div>
                                <a href="{{ route('ipqc-inspections.show', $ipqcInspection->id) }}" target="_blank"
                                    class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded text-xs transition inline-block">
                                    View Full Inspection &rarr;
                                </a>
                            </div>

                            <!-- Inline summary table (print-friendly) -->
                            <div class="mt-3 overflow-x-auto">
                                <table class="w-full border-collapse border border-black text-[10px] text-center">
                                    <thead>
                                        <tr class="bg-gray-100 font-bold">
                                            <th class="border border-black p-1">Period</th>
                                            <th class="border border-black p-1">Fitting Test</th>
                                            <th class="border border-black p-1">Tape Test</th>
                                            <th class="border border-black p-1">Output</th>
                                            <th class="border border-black p-1">Sample</th>
                                            <th class="border border-black p-1">Rej Samp</th>
                                            <th class="border border-black p-1">Rej %</th>
                                            <th class="border border-black p-1">Pass Qty</th>
                                            <th class="border border-black p-1">Rej Qty</th>
                                            <th class="border border-black p-1">Judgement</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($ipqcInspection->records->sortBy('hour_ke') as $rec)
                                            <tr>
                                                <td class="border border-black p-1 font-bold">Hour {{ $rec->hour_ke }}</td>
                                                <td class="border border-black p-1">{{ $rec->fitting_test ?? 'OK' }}</td>
                                                <td class="border border-black p-1 font-bold {{ ($rec->tape_test_judgement ?? '') === 'NG' ? 'text-red-600' : 'text-green-700' }}">{{ $rec->tape_test_judgement ?? 'OK' }}</td>
                                                <td class="border border-black p-1 font-bold">{{ number_format($rec->output_qty) }}</td>
                                                <td class="border border-black p-1">{{ number_format($rec->sample_qty) }}</td>
                                                <td class="border border-black p-1 font-bold text-red-600">{{ number_format($rec->reject_sample_qty) }}</td>
                                                <td class="border border-black p-1 font-bold text-red-600">{{ number_format($rec->reject_rate, 2) }}%</td>
                                                <td class="border border-black p-1 text-green-700 font-bold">{{ number_format($rec->pass_qty) }}</td>
                                                <td class="border border-black p-1 text-red-700 font-bold">{{ number_format($rec->reject_qty) }}</td>
                                                <td class="border border-black p-1 font-extrabold {{ ($rec->judgement ?? '') === 'NG' ? 'text-red-700' : 'text-green-700' }}">{{ $rec->judgement ?? 'OK' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-gray-100 font-bold">
                                            <td colspan="3" class="border border-black p-1 text-right">TOTAL / OVERALL:</td>
                                            <td class="border border-black p-1 font-black">{{ number_format($ipqcInspection->total_output) }}</td>
                                            <td class="border border-black p-1 font-black">{{ number_format($ipqcInspection->total_sample) }}</td>
                                            <td class="border border-black p-1 font-black text-red-600">{{ number_format($ipqcInspection->total_reject_sample) }}</td>
                                            <td class="border border-black p-1 font-black text-red-600">{{ number_format($ipqcInspection->total_reject_rate, 2) }}%</td>
                                            <td class="border border-black p-1 font-black text-green-700">{{ number_format($ipqcInspection->total_pass) }}</td>
                                            <td class="border border-black p-1 font-black text-red-700">{{ number_format($ipqcInspection->total_reject) }}</td>
                                            <td class="border border-black p-1 font-black {{ ($ipqcInspection->overall_judgement ?? '') === 'NG' ? 'text-red-700' : 'text-green-700' }}">{{ $ipqcInspection->overall_judgement ?? 'OK' }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    @else
                        <div class="pt-4 border-t border-gray-300 mt-4">
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center text-xs text-gray-500 no-print">
                                <p class="font-bold text-gray-600 mb-1">No IPQC Inspection Linked</p>
                                <p>No IPQC inspection found for this report's date ({{ $report->date }}), part ({{ $report->part_number }}), shift ({{ $report->shift }}), and line ({{ $report->unit_line }}).</p>
                            </div>
                        </div>
                    @endif

                    <!-- Signatures display -->
                    <div class="grid grid-cols-4 gap-2 text-center pt-4 border-t border-black mt-4 text-[10px]">
                        <div class="flex flex-col justify-between h-24">
                            <span class="font-bold">Dibuat (Checker)</span>
                            <div>
                                @if ($report->created_by_signed_at)
                                    <div
                                        class="text-[8px] font-bold text-blue-600 uppercase tracking-wider border border-dashed border-blue-600 rounded px-1 py-0.5 max-w-xs mx-auto mb-1 leading-none">
                                        DIGITALLY SIGNED</div>
                                    <span
                                        class="underline font-semibold block leading-tight">{{ $report->created_by_name }}</span>
                                    <span
                                        class="text-gray-500 text-[9px]">{{ \Carbon\Carbon::parse($report->created_by_signed_at)->format('d/m/Y H:i') }}</span>
                                @else
                                    <span class="underline font-semibold block text-gray-400">Not Signed</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-col justify-between h-24">
                            <span class="font-bold">Diperiksa (Leader)</span>
                            <div>
                                @if ($report->leader_signed_at)
                                    <div
                                        class="text-[8px] font-bold text-orange-600 uppercase tracking-wider border border-dashed border-orange-600 rounded px-1 py-0.5 max-w-xs mx-auto mb-1 leading-none">
                                        DIGITALLY SIGNED</div>
                                    <span
                                        class="underline font-semibold block leading-tight">{{ $report->leader_name }}</span>
                                    <span
                                        class="text-gray-500 text-[9px]">{{ \Carbon\Carbon::parse($report->leader_signed_at)->format('d/m/Y H:i') }}</span>
                                @else
                                    <span class="underline font-semibold block text-gray-400">Not Signed</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-col justify-between h-24">
                            <span class="font-bold">PQC (Quality Lane)</span>
                            <div>
                                @if ($report->pqc_signed_at)
                                    <div
                                        class="text-[8px] font-bold text-yellow-600 uppercase tracking-wider border border-dashed border-yellow-600 rounded px-1 py-0.5 max-w-xs mx-auto mb-1 leading-none">
                                        DIGITALLY SIGNED</div>
                                    <span
                                        class="underline font-semibold block leading-tight">{{ $report->pqc_name }}</span>
                                    <span
                                        class="text-gray-500 text-[9px]">{{ \Carbon\Carbon::parse($report->pqc_signed_at)->format('d/m/Y H:i') }}</span>
                                @else
                                    <span class="underline font-semibold block text-gray-400">Not Signed (Optional)</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-col justify-between h-24">
                            <span class="font-bold">Mengetahui (Supervisor)</span>
                            <div>
                                @if ($report->acknowledged_signed_at)
                                    <div
                                        class="text-[8px] font-bold text-green-600 uppercase tracking-wider border border-dashed border-green-600 rounded px-1 py-0.5 max-w-xs mx-auto mb-1 leading-none">
                                        DIGITALLY SIGNED</div>
                                    <span
                                        class="underline font-semibold block leading-tight">{{ $report->acknowledged_by_name }}</span>
                                    <span
                                        class="text-gray-500 text-[9px]">{{ \Carbon\Carbon::parse($report->acknowledged_signed_at)->format('d/m/Y H:i') }}</span>
                                @else
                                    <span class="underline font-semibold block text-gray-400">Not Signed</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Embedded Print Stylesheet -->
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white !important;
                color: black !important;
                font-family: 'Courier New', Courier, monospace !important;
            }

            .print-area {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }

            .bg-white {
                box-shadow: none !important;
                border: none !important;
            }

            header,
            nav,
            .lg\:pl-64,
            .fixed {
                display: none !important;
            }

            .lg\:pl-64 {
                padding-left: 0 !important;
            }

            main {
                padding: 0 !important;
                margin: 0 !important;
            }
        }
    </style>
</x-app-layout>
