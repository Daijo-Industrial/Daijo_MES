<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-4 no-print">
                <a href="{{ route('ipqc-inspections.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded transition text-sm">&larr; Back to List</a>
                <div class="flex gap-2">
                    @if($inspection->status === 'ongoing')
                        <a href="{{ route('ipqc-inspections.edit', $inspection->id) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded transition text-sm">Edit</a>
                    @endif
                    <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition text-sm">
                        Print Report
                    </button>
                </div>
            </div>

            <div class="bg-white p-8 shadow-sm border border-gray-200" id="print-area">
                <style>
                    @media print {
                        body { background: white !important; }
                        .no-print { display: none !important; }
                        #print-area { box-shadow: none !important; border: none !important; padding: 0 !important; max-width: 100% !important; }
                        .print-border { border: 1px solid black !important; }
                    }
                </style>

                <div class="text-center mb-6 border-b-2 border-black pb-4">
                    <h1 class="text-xl font-black uppercase">PT. DAIJO INDUSTRIAL</h1>
                    <h2 class="text-lg font-bold uppercase underline">IPQC Inspection Report</h2>
                    <div class="text-sm mt-2">
                        Status: 
                        @if($inspection->status === 'ongoing')
                            <span class="font-bold text-blue-600">ONGOING</span>
                        @else
                            <span class="font-bold text-green-600">COMPLETED</span>
                        @endif
                        | ID: #{{ $inspection->id }}
                    </div>
                </div>

                <!-- Context Header -->
                <table class="w-full border-collapse border border-black text-xs mb-6">
                    <tbody>
                        <tr>
                            <td class="border border-black p-2 font-bold bg-gray-50 w-1/6">DATE</td>
                            <td class="border border-black p-2 w-2/6">{{ \Carbon\Carbon::parse($inspection->date)->format('d-M-Y') }}</td>
                            <td class="border border-black p-2 font-bold bg-gray-50 w-1/6">CUSTOMER</td>
                            <td class="border border-black p-2 w-2/6">{{ $inspection->customer }}</td>
                        </tr>
                        <tr>
                            <td class="border border-black p-2 font-bold bg-gray-50">PART NUMBER</td>
                            <td class="border border-black p-2">{{ $inspection->part_number }}</td>
                            <td class="border border-black p-2 font-bold bg-gray-50">SHIFT</td>
                            <td class="border border-black p-2">Shift {{ $inspection->shift }}</td>
                        </tr>
                        <tr>
                            <td class="border border-black p-2 font-bold bg-gray-50">PART NAME</td>
                            <td class="border border-black p-2">{{ $inspection->part_name }}</td>
                            <td class="border border-black p-2 font-bold bg-gray-50">UNIT / LINE</td>
                            <td class="border border-black p-2">{{ $inspection->unit_line }}</td>
                        </tr>
                        <tr>
                            <td class="border border-black p-2 font-bold bg-gray-50">MODEL</td>
                            <td class="border border-black p-2">{{ $inspection->model }}</td>
                            <td class="border border-black p-2 font-bold bg-gray-50">PROCESS</td>
                            <td class="border border-black p-2">{{ $inspection->process_prod }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="font-bold text-sm uppercase mb-2">IPQC Setup Specification</div>
                <table class="w-full border-collapse border border-black text-xs mb-6">
                    <tbody>
                        <tr>
                            <td class="border border-black p-2 font-bold bg-gray-50">LOT COLOR</td>
                            <td class="border border-black p-2">{{ $inspection->lot_color ?? '-' }}</td>
                            <td class="border border-black p-2 font-bold bg-gray-50">STD GLOSSY</td>
                            <td class="border border-black p-2">{{ $inspection->std_glossy ?? '-' }}</td>
                            <td class="border border-black p-2 font-bold bg-gray-50">STD VISCOCITY</td>
                            <td class="border border-black p-2">{{ $inspection->std_viscosity ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="border border-black p-2 font-bold bg-gray-50">PRODUCT COLOR</td>
                            <td class="border border-black p-2">{{ $inspection->product_color ?? '-' }}</td>
                            <td class="border border-black p-2 font-bold bg-gray-50">STD OVEN TEMP</td>
                            <td class="border border-black p-2">{{ $inspection->std_oven_temp ?? '-' }}</td>
                            <td class="border border-black p-2 font-bold bg-gray-50">APP SAMPLE</td>
                            <td class="border border-black p-2">{{ $inspection->app_sample ?? 'YES' }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="font-bold text-sm uppercase mb-2">Inspection Records</div>
                <div class="overflow-x-auto mb-6">
                    <table class="w-full border-collapse border border-black text-[11px] text-center">
                        <thead>
                            <tr class="bg-gray-100 font-bold">
                                <th class="border border-black p-1.5">Period</th>
                                <th class="border border-black p-1.5">Fitting Test</th>
                                <th class="border border-black p-1.5">Tape Test</th>
                                <th class="border border-black p-1.5">Output</th>
                                <th class="border border-black p-1.5">Sample</th>
                                <th class="border border-black p-1.5">Rej Sample</th>
                                <th class="border border-black p-1.5">Rej Rate %</th>
                                <th class="border border-black p-1.5">Pass Qty</th>
                                <th class="border border-black p-1.5">Rej Qty</th>
                                <th class="border border-black p-1.5">Judgement</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($inspection->records as $record)
                                <tr>
                                    <td class="border border-black p-1.5 font-bold">Hour {{ $record->hour_ke }}</td>
                                    <td class="border border-black p-1.5">{{ $record->fitting_test ?? 'OK' }}</td>
                                    <td class="border border-black p-1.5 font-bold {{ $record->tape_test_judgement === 'NG' ? 'text-red-600' : 'text-green-700' }}">{{ $record->tape_test_judgement ?? 'OK' }}</td>
                                    <td class="border border-black p-1.5 font-bold">{{ number_format($record->output_qty) }}</td>
                                    <td class="border border-black p-1.5">{{ number_format($record->sample_qty) }}</td>
                                    <td class="border border-black p-1.5 font-bold text-red-600">{{ number_format($record->reject_sample_qty) }}</td>
                                    <td class="border border-black p-1.5 font-bold text-red-600">{{ number_format($record->reject_rate, 2) }}%</td>
                                    <td class="border border-black p-1.5 text-green-700 font-bold">{{ number_format($record->pass_qty) }}</td>
                                    <td class="border border-black p-1.5 text-red-700 font-bold">{{ number_format($record->reject_qty) }}</td>
                                    <td class="border border-black p-1.5 font-extrabold {{ $record->judgement === 'NG' ? 'text-red-700' : 'text-green-700' }}">{{ $record->judgement ?? 'OK' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="border border-black p-4 text-gray-500 italic">No inspection records yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($inspection->records->count() > 0)
                        <tfoot>
                            <tr class="bg-gray-100 font-bold">
                                <td colspan="3" class="border border-black p-1.5 text-right">TOTAL / OVERALL:</td>
                                <td class="border border-black p-1.5 font-black">{{ number_format($inspection->records->sum('output_qty')) }}</td>
                                <td class="border border-black p-1.5 font-black">{{ number_format($inspection->records->sum('sample_qty')) }}</td>
                                <td class="border border-black p-1.5 font-black text-red-600">{{ number_format($inspection->records->sum('reject_sample_qty')) }}</td>
                                @php
                                    $totalSample = $inspection->records->sum('sample_qty');
                                    $totalRej = $inspection->records->sum('reject_sample_qty');
                                    $overallRate = $totalSample > 0 ? ($totalRej / $totalSample) * 100 : 0;
                                @endphp
                                <td class="border border-black p-1.5 font-black text-red-600">{{ number_format($overallRate, 2) }}%</td>
                                <td class="border border-black p-1.5 font-black text-green-700">{{ number_format($inspection->records->sum('pass_qty')) }}</td>
                                <td class="border border-black p-1.5 font-black text-red-700">{{ number_format($inspection->records->sum('reject_qty')) }}</td>
                                <td class="border border-black p-1.5 font-black text-xl {{ $inspection->overall_judgement === 'NG' ? 'text-red-700' : 'text-green-700' }}">{{ $inspection->overall_judgement ?? 'OK' }}</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>

                @if($inspection->attachments && $inspection->attachments->count() > 0)
                    <div class="mb-6">
                        <div class="font-bold text-sm uppercase mb-2">QC Physical Proof Attachments</div>
                        <div class="grid grid-cols-4 gap-4">
                            @foreach($inspection->attachments as $att)
                                <div class="border border-black p-2 text-center text-xs">
                                    <img src="{{ $att->url }}" class="h-32 object-contain mx-auto mb-2">
                                    <div class="truncate">{{ $att->label ?? $att->original_name }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-8 text-center pt-8 mt-8 text-sm">
                    <div class="flex flex-col justify-between h-24">
                        <span class="font-bold uppercase">Inspector</span>
                        <div>
                            @if ($inspection->inspector_name)
                                <span class="underline font-bold text-lg block leading-tight">{{ $inspection->inspector_name }}</span>
                            @else
                                <span class="underline font-semibold block text-gray-400">Not Signed</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-col justify-between h-24">
                        <span class="font-bold uppercase">Checker (Leader)</span>
                        <div>
                            @if ($inspection->checker_name)
                                <span class="underline font-bold text-lg block leading-tight">{{ $inspection->checker_name }}</span>
                            @else
                                <span class="underline font-semibold block text-gray-400">Not Signed</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="text-[10px] text-gray-500 text-center mt-12 no-print">
                    Created at: {{ $inspection->created_at->format('d M Y H:i') }} | Last updated: {{ $inspection->updated_at->format('d M Y H:i') }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
