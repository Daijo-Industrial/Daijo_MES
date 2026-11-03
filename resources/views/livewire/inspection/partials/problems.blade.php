@props(['rows'])

<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50 text-center">
            <tr>
                <th class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider w-1/6">Time</th>
                <th class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider w-1/4">Type</th>
                <th class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider w-1/5">Cycle Time (s)</th>
                <th class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Remarks</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
            @forelse ($rows as $pb)
                @php
                    $t = strtolower($pb->type ?? '');
                    $isOk = str_contains($t, 'no problem');
                    $isQuality = str_contains($t, 'quality');
                    $isMachine = str_contains($t, 'machine');
                @endphp
                <tr class="{{ $isOk ? 'hover:bg-gray-50' : 'bg-amber-50/50 hover:bg-amber-100/60' }} transition">
                    <td class="px-4 py-2.5 text-center text-gray-700 font-mono text-xs">
                        {{ $pb->time }}
                    </td>
                    <td class="px-4 py-2.5 text-center">
                        @if ($isOk)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                ✓ No Problem
                            </span>
                        @elseif ($isQuality)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                ⚠ Quality
                            </span>
                        @elseif ($isMachine)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                ⚙ Machine
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                {{ $pb->type }}
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-center text-gray-700 font-mono">
                        {{ $pb->cycle_time !== null ? number_format((float) $pb->cycle_time) : '-' }}
                    </td>
                    <td class="px-4 py-2.5 text-gray-600">
                        {{ $pb->remarks ?: '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-4 text-center text-xs text-gray-400">
                        No problem records found for this shift.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
