<div class="space-y-4" x-data="{ showBatchModal: false }">
    <!-- Clean, Direct Top Filter Strip -->
    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm space-y-3">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <!-- Search bar -->
            <div class="w-full md:w-80 relative">
                <input wire:model.live.debounce.300ms="search" type="text" 
                       placeholder="Cari item code, user, nama part..." 
                       class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-xs pl-8 pr-7 py-2">
                <div class="absolute left-2.5 top-2.5 text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                @if($search !== '')
                    <button wire:click="$set('search', '')" type="button" class="absolute right-2 top-2 text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                @endif
            </div>

            <!-- Upload Batch Dropdown & Drilldown Modal Trigger -->
            <div class="flex items-center gap-2">
                <select wire:model.live="batchFilter" class="text-xs rounded-md border-gray-300 py-1.5 pl-2.5 pr-8 text-gray-700 focus:ring-blue-500 focus:border-blue-500 max-w-xs truncate">
                    <option value="">Semua Sesi Upload</option>
                    @foreach($batches as $b)
                        <option value="{{ $b->batch_code }}">
                            {{ $b->file_name }} ({{ $b->created_at->format('d/m/y H:i') }} - {{ $b->updated_count > 0 ? $b->updated_count . ' Tertimpa' : 'Aman (Baru)' }})
                        </option>
                    @endforeach
                </select>

                <button @click="showBatchModal = true" type="button" 
                        class="px-2.5 py-1.5 rounded-md border border-gray-300 bg-gray-50 hover:bg-gray-100 text-gray-700 text-xs font-semibold whitespace-nowrap transition">
                    Riwayat Sesi ({{ $batches->count() }})
                </button>
            </div>

            <!-- Back Link & Per Page -->
            <div class="flex items-center gap-2">
                <select wire:model.live="perPage" class="text-xs rounded-md border-gray-300 py-1.5 pl-2 pr-6 text-gray-700 focus:ring-blue-500 focus:border-blue-500">
                    <option value="10">10 baris</option>
                    <option value="25">25 baris</option>
                    <option value="50">50 baris</option>
                    <option value="100">100 baris</option>
                </select>

                <a href="{{ route('admin.master-list-manager') }}" 
                   class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-1.5 px-3 rounded-md text-xs transition inline-flex items-center gap-1">
                    &larr; Ke Master List
                </a>
            </div>
        </div>

        <!-- Filter Tab Pills -->
        <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-gray-100">
            <div class="flex items-center gap-1.5">
                <button type="button" 
                        wire:click="setActionFilter('updates')"
                        class="px-3 py-1 rounded-md text-xs font-bold transition flex items-center gap-1.5 border {{ 
                            $actionFilter === 'updates' 
                            ? 'bg-blue-600 text-white border-blue-600' 
                            : 'bg-white hover:bg-gray-50 text-gray-700 border-gray-300' 
                        }}">
                    <span>Perubahan Saja</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-extrabold {{ 
                        $actionFilter === 'updates' ? 'bg-blue-800 text-white' : 'bg-blue-100 text-blue-800' 
                    }}">
                        {{ number_format($tabCounts['updates']) }}
                    </span>
                </button>

                <button type="button" 
                        wire:click="setActionFilter('all')"
                        class="px-3 py-1 rounded-md text-xs font-bold transition flex items-center gap-1.5 border {{ 
                            $actionFilter === 'all' 
                            ? 'bg-blue-600 text-white border-blue-600' 
                            : 'bg-white hover:bg-gray-50 text-gray-700 border-gray-300' 
                        }}">
                    <span>Semua Log</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-extrabold {{ 
                        $actionFilter === 'all' ? 'bg-blue-800 text-white' : 'bg-gray-100 text-gray-700' 
                    }}">
                        {{ number_format($tabCounts['all']) }}
                    </span>
                </button>

                <button type="button" 
                        wire:click="setActionFilter('creates')"
                        class="px-3 py-1 rounded-md text-xs font-bold transition flex items-center gap-1.5 border {{ 
                            $actionFilter === 'creates' 
                            ? 'bg-blue-600 text-white border-blue-600' 
                            : 'bg-white hover:bg-gray-50 text-gray-700 border-gray-300' 
                        }}">
                    <span>Tambah Baru</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-extrabold {{ 
                        $actionFilter === 'creates' ? 'bg-blue-800 text-white' : 'bg-emerald-100 text-emerald-800' 
                    }}">
                        {{ number_format($tabCounts['creates']) }}
                    </span>
                </button>
            </div>

            @if($search !== '' || $actionFilter !== 'updates' || $batchFilter !== '')
                <button wire:click="clearFilters" type="button" class="text-xs text-blue-600 hover:text-blue-800 font-semibold underline">
                    Reset Filter
                </button>
            @endif
        </div>
    </div>

    <!-- Active Batch Strip (Only shown when a batch is selected) -->
    @if($activeBatch)
        <div class="px-4 py-2.5 bg-blue-50 border border-blue-200 rounded-lg flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
            <div class="text-blue-900 space-x-1">
                <span class="font-bold text-[11px] bg-blue-200/70 text-blue-900 px-1.5 py-0.5 rounded">MODE INVESTIGASI SESI UPLOAD</span>
                <span class="font-bold">{{ $activeBatch->file_name }}</span>
                <span class="text-blue-700 font-mono text-[11px]">({{ $activeBatch->created_at->format('d M Y, H:i') }} WIB oleh {{ $activeBatch->user->name ?? 'Admin' }})</span>
                <span class="text-gray-400">&bull;</span>
                <span class="font-semibold text-gray-700">{{ number_format($activeBatch->total_rows) }} baris:</span>
                <span class="text-emerald-700 font-bold">+{{ number_format($activeBatch->created_count) }} baru</span>
                <span class="text-amber-800 font-bold">⚡ {{ number_format($activeBatch->updated_count) }} diubah</span>
                @if($activeBatch->isProblematic())
                    <span class="text-rose-700 font-bold ml-1">(Indikasi Masalah Terdeteksi)</span>
                @endif
            </div>

            <button wire:click="clearBatchFilter" type="button" class="text-blue-700 hover:text-blue-900 font-bold underline shrink-0">
                Tutup Sesi Ini &times;
            </button>
        </div>
    @endif

    <!-- Logs Table -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-xs text-left">
                <thead class="bg-gray-50 text-gray-600 font-bold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="px-4 py-3 w-40">Waktu & User</th>
                        <th class="px-4 py-3 w-44">Item Code</th>
                        <th class="px-4 py-3 w-44">Aksi / Sumber</th>
                        <th class="px-4 py-3">Perubahan Nilai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white text-gray-700">
                    @forelse($logs as $log)
                        @php
                            $isCreate = in_array($log->action, \App\Livewire\Admin\MasterListLogs::CREATE_ACTIONS, true) || (empty($log->old_values) && !in_array($log->action, \App\Livewire\Admin\MasterListLogs::UPDATE_ACTIONS, true));
                            $isUpdate = !$isCreate;
                        @endphp
                        <tr class="hover:bg-gray-50/70 transition-colors">
                            <!-- Timestamp & User -->
                            <td class="px-4 py-3 align-top whitespace-nowrap">
                                <div class="font-semibold text-gray-900">{{ $log->created_at->format('d M Y, H:i') }}</div>
                                <div class="text-[11px] text-gray-500">{{ $log->user->name ?? 'System/API' }}</div>
                            </td>

                            <!-- Item Code -->
                            <td class="px-4 py-3 align-top">
                                <div class="font-mono font-bold text-gray-900 select-all">
                                    {{ $log->item_code }}
                                </div>
                            </td>

                            <!-- Action & Source File -->
                            <td class="px-4 py-3 align-top space-y-1">
                                <div>
                                    @if($log->action === 'inline_edit')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-800">
                                            Edit Manual
                                        </span>
                                    @elseif($log->action === 'excel_import_update')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                                            Sync Update
                                        </span>
                                    @elseif(str_contains($log->action, 'create'))
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                            Data Baru
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-700">
                                            {{ str_replace('_', ' ', $log->action) }}
                                        </span>
                                    @endif
                                </div>

                                @if($log->file_name || $log->upload_batch_id)
                                    <div class="text-[11px] text-gray-500 truncate max-w-[170px]" title="{{ $log->file_name ?? 'master_list_item.xls' }}">
                                        @if($log->upload_batch_id)
                                            <button wire:click="selectBatch('{{ $log->upload_batch_id }}')" 
                                                    type="button" 
                                                    class="text-blue-600 hover:text-blue-800 hover:underline font-mono text-[10px] text-left truncate block">
                                                {{ $log->file_name ?? 'File Excel' }}
                                            </button>
                                        @else
                                            <span class="text-gray-500 font-mono text-[10px]">{{ $log->file_name }}</span>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <!-- Diff Values -->
                            <td class="px-4 py-3 align-top">
                                @if($isCreate)
                                    <!-- Creation Entry -->
                                    <div class="space-y-1">
                                        <span class="text-emerald-700 text-xs font-medium">Item baru pertama kali didaftarkan</span>
                                        @if(is_array($log->new_values) && count($log->new_values) > 0)
                                            <details class="group mt-1">
                                                <summary class="cursor-pointer text-[11px] text-gray-500 hover:text-gray-800 font-medium select-none">
                                                    Lihat atribut awal ({{ count($log->new_values) }}) &darr;
                                                </summary>
                                                <div class="mt-2 grid grid-cols-2 sm:grid-cols-3 gap-1.5 p-2 bg-gray-50 rounded border border-gray-200 text-[11px]">
                                                    @foreach($log->new_values as $key => $val)
                                                        @php
                                                            $label = $fieldLabels[$key] ?? ucwords(str_replace('_', ' ', $key));
                                                            $displayVal = $val;
                                                            if ($key === 'customer_code') {
                                                                if ($val === '0' || $val === '-' || empty($val)) {
                                                                    $displayVal = '0 (N/A)';
                                                                } elseif (isset($customerMap[$val])) {
                                                                    $displayVal = $val . ' - ' . $customerMap[$val];
                                                                }
                                                            } elseif ($val === '' || $val === null) {
                                                                $displayVal = '-';
                                                            }
                                                        @endphp
                                                        <div class="bg-white px-2 py-1 rounded border border-gray-100">
                                                            <span class="text-gray-400 font-medium">{{ $label }}:</span>
                                                            <span class="text-gray-800 font-semibold ml-0.5 truncate">{{ $displayVal }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </details>
                                        @endif
                                    </div>
                                @else
                                    <!-- Update Diff Entry -->
                                    <div class="space-y-1.5">
                                        @php
                                            $changedKeys = is_array($log->new_values) ? array_keys($log->new_values) : [];
                                            $changeCount = count($changedKeys);
                                        @endphp

                                        <div class="text-[10px] font-bold text-gray-500 uppercase">
                                            {{ $changeCount }} Kolom Diperbarui
                                        </div>

                                        <div class="space-y-1">
                                            @forelse($log->new_values ?? [] as $key => $newVal)
                                                @php
                                                    $oldVal = $log->old_values[$key] ?? null;
                                                    $label = $fieldLabels[$key] ?? ucwords(str_replace('_', ' ', $key));

                                                    $oldDisplay = $oldVal;
                                                    $newDisplay = $newVal;

                                                    if ($key === 'customer_code') {
                                                        if ($oldVal === '0' || $oldVal === '-' || $oldVal === null || $oldVal === '') {
                                                            $oldDisplay = '0 (Unassigned / N/A)';
                                                        } elseif (isset($customerMap[$oldVal])) {
                                                            $oldDisplay = $oldVal . ' (' . $customerMap[$oldVal] . ')';
                                                        }

                                                        if ($newVal === '0' || $newVal === '-' || $newVal === null || $newVal === '') {
                                                            $newDisplay = '0 (Unassigned / N/A)';
                                                        } elseif (isset($customerMap[$newVal])) {
                                                            $newDisplay = $newVal . ' (' . $customerMap[$newVal] . ')';
                                                        }
                                                    } else {
                                                        if ($oldVal === '' || $oldVal === null) {
                                                            $oldDisplay = '(kosong)';
                                                        }
                                                        if ($newVal === '' || $newVal === null) {
                                                            $newDisplay = '(kosong)';
                                                        }
                                                    }
                                                @endphp

                                                <div class="flex flex-wrap items-center gap-1.5 text-xs py-0.5">
                                                    <span class="font-semibold text-gray-700 min-w-[120px]">{{ $label }}:</span>
                                                    <span class="text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200 line-through font-mono">
                                                        {{ $oldDisplay }}
                                                    </span>
                                                    <span class="text-gray-400 font-bold">&rarr;</span>
                                                    <span class="text-emerald-800 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-300 font-bold font-mono">
                                                        {{ $newDisplay }}
                                                    </span>
                                                </div>
                                            @empty
                                                <div class="text-xs text-gray-400 italic">Nilai detail tidak tersedia</div>
                                            @endforelse
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-8 text-gray-500">
                                Tidak ada log aktivitas ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-gray-500">
            <div>
                Menampilkan <span class="font-semibold text-gray-800">{{ $logs->firstItem() ?? 0 }}</span> sampai <span class="font-semibold text-gray-800">{{ $logs->lastItem() ?? 0 }}</span> dari <span class="font-semibold text-gray-800">{{ number_format($logs->total()) }}</span> log
            </div>
            <div>
                {{ $logs->links() }}
            </div>
        </div>
    </div>

    <!-- Drilldown Modal: Riwayat Sesi Upload (Only shown when user clicks "Riwayat Sesi") -->
    <div x-show="showBatchModal" 
         x-transition.opacity 
         class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 flex items-center justify-center p-4" 
         style="display: none;">
        <div @click.away="showBatchModal = false" class="bg-white rounded-xl shadow-xl max-w-2xl w-full p-5 space-y-4 max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between border-b pb-3">
                <h3 class="text-sm font-bold text-gray-900">Riwayat Sesi Upload Excel ({{ $batches->count() }} Sesi)</h3>
                <button @click="showBatchModal = false" type="button" class="text-gray-400 hover:text-gray-600 font-bold text-lg">&times;</button>
            </div>

            <div class="overflow-y-auto space-y-2.5 flex-1 pr-1">
                @forelse($batches as $batch)
                    @php
                        $isProblematic = $batch->isProblematic();
                        $isSelected = $batchFilter === $batch->batch_code;
                    @endphp
                    <div class="p-3 rounded-lg border flex flex-col sm:flex-row sm:items-center justify-between gap-3 {{ 
                        $isSelected 
                            ? 'bg-blue-50 border-blue-400' 
                            : ($isProblematic ? 'bg-amber-50/50 border-amber-300' : 'bg-white border-gray-200') 
                    }}">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-xs text-gray-900">{{ $batch->file_name }}</span>
                                @if($isProblematic)
                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-rose-100 text-rose-800">
                                        {{ $batch->updated_count }} Tertimpa
                                    </span>
                                @else
                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        Aman (Baru)
                                    </span>
                                @endif
                            </div>
                            <div class="text-[11px] text-gray-500">
                                <span>{{ $batch->created_at->format('d M Y, H:i') }} WIB</span>
                                <span class="mx-1">&bull;</span>
                                <span>oleh {{ $batch->user->name ?? 'Admin' }}</span>
                                <span class="mx-1">&bull;</span>
                                <span>{{ number_format($batch->total_rows) }} baris (+{{ number_format($batch->created_count) }} baru, {{ number_format($batch->updated_count) }} diubah)</span>
                            </div>
                        </div>

                        <div>
                            @if($isSelected)
                                <button wire:click="clearBatchFilter" @click="showBatchModal = false" type="button" class="px-3 py-1 text-xs font-bold bg-blue-600 text-white rounded">
                                    Aktif
                                </button>
                            @else
                                <button wire:click="selectBatch('{{ $batch->batch_code }}')" @click="showBatchModal = false" type="button" class="px-3 py-1 text-xs font-bold bg-gray-100 hover:bg-gray-200 text-gray-800 rounded">
                                    Pilih Sesi
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-xs text-gray-400 italic py-4 text-center">Belum ada sesi upload Excel yang tercatat.</div>
                @endforelse
            </div>

            <div class="border-t pt-3 flex justify-end">
                <button @click="showBatchModal = false" type="button" class="px-4 py-1.5 rounded-md text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
