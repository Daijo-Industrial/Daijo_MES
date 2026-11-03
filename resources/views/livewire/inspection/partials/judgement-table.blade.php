@props(['judgement'])

<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50 text-center">
            <tr>
                <th class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider w-1/2">Pass Quantity</th>
                <th class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider w-1/2">Reject Quantity</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
            @forelse ($judgement as $j)
                <tr class="text-center hover:bg-gray-50 transition">
                    <td class="px-4 py-3 font-mono font-bold text-green-700 text-base">
                        {{ number_format($j->pass_quantity) }} <span class="text-xs font-normal text-gray-500">pcs</span>
                    </td>
                    <td class="px-4 py-3 font-mono font-bold {{ $j->reject_quantity > 0 ? 'text-red-700' : 'text-gray-500' }} text-base">
                        {{ number_format($j->reject_quantity) }} <span class="text-xs font-normal text-gray-500">pcs</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="px-4 py-4 text-center text-xs text-gray-400">
                        No period judgement summary recorded.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
