<div class="border border-zinc-200 rounded-lg p-4 bg-zinc-50/70 space-y-3">
    <div class="flex items-center justify-between">
        <h4 class="text-xs font-bold text-zinc-800 uppercase tracking-wider flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full {{ $isSaved ? 'bg-emerald-500' : 'bg-zinc-400' }}"></span>
            Packaging & SNP Verification
        </h4>
        <button type="button"
                wire:click="addPackaging"
                class="px-2.5 py-1 text-xs font-semibold text-zinc-900 bg-white border border-zinc-300 hover:bg-zinc-100 rounded-lg shadow-sm transition flex items-center gap-1">
            <svg class="w-3.5 h-3.5 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Tambah Verifikasi Packaging</span>
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-zinc-200 text-xs bg-white rounded-lg border border-zinc-200">
            <thead class="bg-zinc-50 text-center font-semibold text-zinc-600">
                <tr>
                    <th class="px-2 py-2">No</th>
                    <th class="px-3 py-2 text-left">Label Box / No Box</th>
                    <th class="px-3 py-2 text-center">SNP (Standard Packaging Qty)</th>
                    <th class="px-2 py-2 text-center">Judgement</th>
                    <th class="px-3 py-2 text-left">Remarks</th>
                    <th class="px-2 py-2 w-8">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @foreach ($packagings as $i => $row)
                    @php $isNg = ($row['judgement'] ?? '') === 'NG'; @endphp
                    <tr class="hover:bg-zinc-50/50 transition">
                        <td class="px-2 py-2 text-center text-zinc-400 font-mono">{{ $i + 1 }}</td>
                        <td class="px-2 py-1.5">
                            <input type="text"
                                   wire:model.blur="packagings.{{ $i }}.box_label"
                                   placeholder="Contoh: BOX-01 / Lot #1"
                                   class="w-full text-xs rounded border-zinc-300 py-1.5 focus:ring-zinc-900 focus:border-zinc-900 @error('packagings.'.$i.'.box_label') border-rose-300 bg-rose-50 @enderror">
                        </td>
                        <td class="px-2 py-1.5 text-center">
                            <div class="flex justify-center">
                                <input type="number"
                                       wire:model.blur="packagings.{{ $i }}.snp"
                                       placeholder="Qty SNP"
                                       class="w-32 text-xs font-mono text-center rounded border-zinc-300 py-1.5 focus:ring-zinc-900 focus:border-zinc-900 @error('packagings.'.$i.'.snp') border-rose-300 bg-rose-50 @enderror">
                            </div>
                        </td>
                        <td class="px-2 py-1.5 text-center">
                            <div class="inline-flex rounded-md border border-zinc-300 p-0.5 bg-zinc-100 shadow-sm">
                                <button type="button"
                                        wire:click="setJudgement({{ $i }}, 'OK')"
                                        class="px-2.5 py-1 text-[11px] font-bold rounded transition {{ ($row['judgement'] ?? '') === 'OK' ? 'bg-emerald-600 text-white shadow-sm' : 'text-zinc-600 hover:text-zinc-900' }}">
                                    OK
                                </button>
                                <button type="button"
                                        wire:click="setJudgement({{ $i }}, 'NG')"
                                        class="px-2.5 py-1 text-[11px] font-bold rounded transition {{ ($row['judgement'] ?? '') === 'NG' ? 'bg-rose-600 text-white shadow-sm' : 'text-zinc-600 hover:text-zinc-900' }}">
                                    NG
                                </button>
                            </div>
                        </td>
                        <td class="px-2 py-1.5">
                            <input type="text"
                                   wire:model.blur="packagings.{{ $i }}.remarks"
                                   placeholder="{{ $isNg ? 'Wajib diisi jika NG' : 'Catatan packaging opsional' }}"
                                   class="w-full text-xs rounded border-zinc-300 py-1.5 focus:ring-zinc-900 focus:border-zinc-900 @error('packagings.'.$i.'.remarks') border-rose-300 bg-rose-50 @enderror">
                        </td>
                        <td class="px-2 py-1.5 text-center">
                            <button type="button"
                                    wire:click="removePackaging({{ $i }})"
                                    title="Hapus baris"
                                    class="text-rose-500 hover:text-rose-700 p-1 text-xs font-semibold">
                                Hapus
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>