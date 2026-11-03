@props(['rows'])

<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50 text-center">
            <tr>
                <th class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Area / Section</th>
                <th class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Lower Limit</th>
                <th class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Upper Limit</th>
                <th class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actual Value</th>
                <th class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Judgement</th>
                <th class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Remarks</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
            @forelse ($rows as $row)
                @php
                    $isOk = strtolower($row->judgement ?? '') === 'ok';
                @endphp
                <tr class="{{ $isOk ? 'hover:bg-gray-50' : 'bg-red-50 hover:bg-red-100/70' }} transition">
                    <td class="px-4 py-2.5 text-center font-medium text-gray-900">
                        {{ $row->area ?: '-' }}
                    </td>
                    <td class="px-4 py-2.5 text-center text-gray-700 font-mono">
                        {{ $row->lower_limit !== null ? rtrim(rtrim(number_format((float) $row->lower_limit, 2), '0'), '.') : '-' }}
                        <span class="text-xs text-gray-400 font-sans">{{ $row->limit_uom }}</span>
                    </td>
                    <td class="px-4 py-2.5 text-center text-gray-700 font-mono">
                        {{ $row->upper_limit !== null ? rtrim(rtrim(number_format((float) $row->upper_limit, 2), '0'), '.') : '-' }}
                        <span class="text-xs text-gray-400 font-sans">{{ $row->limit_uom }}</span>
                    </td>
                    <td class="px-4 py-2.5 text-center font-mono font-semibold {{ $isOk ? 'text-gray-900' : 'text-red-700' }}">
                        {{ $row->actual_value !== null ? $row->actual_value : '-' }}
                    </td>
                    <td class="px-4 py-2.5 text-center">
                        @if ($isOk)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800">
                                ✓ OK
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-800">
                                ✗ NG
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-center text-gray-600">
                        {{ $row->remarks ?: '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-4 text-center text-xs text-gray-400">
                        No dimensional measurement data recorded.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
