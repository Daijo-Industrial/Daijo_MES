@props(['rows'])

<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50 text-center">
            <tr>
                <th class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider w-1/4">Appearance</th>
                <th class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider w-1/4">Weight</th>
                <th class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider w-1/4">Fitting Test</th>
                <th class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider w-1/4">Remarks</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
            @forelse ($rows as $row)
                @php
                    $isOk = strtolower($row->appearance ?? '') === 'ok';
                @endphp
                <tr class="{{ $isOk ? 'hover:bg-gray-50' : 'bg-red-50 hover:bg-red-100/70' }} transition">
                    <td class="px-4 py-2.5 text-center">
                        @if ($isOk)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800">
                                ✓ OK
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-800">
                                ✗ {{ $row->appearance ?: 'NG' }}
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-center text-gray-700 font-mono">
                        {{ $row->weight !== null ? rtrim(rtrim(number_format((float) $row->weight, 2), '0'), '.') : '-' }}
                        <span class="text-xs text-gray-400 font-sans">{{ $row->weight_uom }}</span>
                    </td>
                    <td class="px-4 py-2.5 text-center text-gray-700">
                        {{ $row->fitting_test ?: '-' }}
                    </td>
                    <td class="px-4 py-2.5 text-center text-gray-600">
                        {{ $row->remarks ?: '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-4 text-center text-xs text-gray-400">
                        No first piece inspection recorded for this period.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
