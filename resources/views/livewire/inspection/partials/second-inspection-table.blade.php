@props(['second'])

<div class="space-y-4">
    {{-- Lot Size Qty --}}
    <div class="bg-gray-50 rounded p-3 flex items-center justify-between">
        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Lot Size Quantity:</span>
        <div class="font-mono text-sm font-bold text-gray-900">
            @forelse ($second as $row)
                @if (is_null($row->lot_size_quantity))
                    <span class="text-gray-400 font-normal italic">Skipped for this period</span>
                @else
                    {{ number_format($row->lot_size_quantity) }} pcs
                @endif
            @empty
                <span class="text-gray-400 font-normal italic">—</span>
            @endforelse
        </div>
    </div>

    {{-- Sampling Data --}}
    @php $sampling = $second->flatMap->samplingData; @endphp
    <div>
        <h4 class="text-xs font-bold text-indigo-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            Box Sampling Checks
        </h4>
        <div class="overflow-x-auto border border-gray-200 rounded">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-center">
                    <tr>
                        <th class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase">Quantity</th>
                        <th class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase">Box Label</th>
                        <th class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase">Appearance</th>
                        <th class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase">NG Quantity</th>
                        <th class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($sampling as $s)
                        @php $isOk = strtolower($s->appearance ?? '') === 'ok'; @endphp
                        <tr class="{{ $isOk ? 'hover:bg-gray-50' : 'bg-red-50' }} text-center transition">
                            <td class="px-3 py-2 font-mono font-medium text-gray-900">{{ number_format($s->quantity) }}</td>
                            <td class="px-3 py-2 font-mono text-gray-700">{{ $s->box_label ?: '-' }}</td>
                            <td class="px-3 py-2">
                                @if ($isOk)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800">✓ OK</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-800">✗ NG</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 font-mono {{ ($s->ng_quantity ?? 0) > 0 ? 'text-red-600 font-bold' : 'text-gray-500' }}">
                                {{ $s->ng_quantity ?? 0 }}
                            </td>
                            <td class="px-3 py-2 text-gray-600 text-xs">{{ $s->remarks ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-3 text-center text-xs text-gray-400">No sampling records.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Packaging Data --}}
    @php $pack = $second->flatMap->packagingData; @endphp
    <div>
        <h4 class="text-xs font-bold text-indigo-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
            </svg>
            Packaging Verification
        </h4>
        <div class="overflow-x-auto border border-gray-200 rounded">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-center">
                    <tr>
                        <th class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase">SNP</th>
                        <th class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase">Box Label</th>
                        <th class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase">Judgement</th>
                        <th class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($pack as $p)
                        @php $isOk = strtolower($p->judgement ?? '') === 'ok'; @endphp
                        <tr class="{{ $isOk ? 'hover:bg-gray-50' : 'bg-red-50' }} text-center transition">
                            <td class="px-3 py-2 font-mono font-medium text-gray-900">{{ number_format($p->snp) }}</td>
                            <td class="px-3 py-2 font-mono text-gray-700">{{ $p->box_label ?: '-' }}</td>
                            <td class="px-3 py-2">
                                @if ($isOk)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800">✓ OK</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-800">✗ NG</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-600 text-xs">{{ $p->remarks ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-3 text-center text-xs text-gray-400">No packaging records.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
