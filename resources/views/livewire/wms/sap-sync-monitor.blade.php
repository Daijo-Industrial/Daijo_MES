<div class="p-6 bg-gray-50 min-h-screen" @if($stats['pending'] > 0) wire:poll.3s @endif>
    <div class="max-w-7xl mx-auto">
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-black text-gray-900 tracking-tighter uppercase italic">
                    WMS <span class="text-blue-600">SAP</span> Sync Monitor
                </h1>
                <p class="text-sm text-gray-500 font-bold uppercase tracking-widest mt-1">
                    Batch Header Processing System - Inventory Transfer
                </p>
            </div>
            
            <div class="flex items-center gap-3">
                <button wire:click="testEndpoint" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-lg shadow-blue-500/20 active:scale-95 flex items-center space-x-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span>Test SAP Endpoint</span>
                </button>
                <button wire:click="retryAllFailed" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-lg shadow-red-500/20 active:scale-95">
                    Retry All Failed
                </button>
            </div>
        </div>

        @if (session()->has('message'))
            <div class="mb-6 bg-emerald-100 border-l-4 border-emerald-500 p-4 rounded-r-2xl shadow-sm flex items-center space-x-3">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <p class="text-emerald-800 text-xs font-bold">{{ session('message') }}</p>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-6 bg-red-100 border-l-4 border-red-500 p-4 rounded-r-2xl shadow-sm space-y-1">
                <div class="flex items-center space-x-2 text-red-800 font-bold text-xs uppercase">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Error Tes Koneksi / Endpoint SAP</span>
                </div>
                <p class="text-red-700 text-xs font-mono break-all font-semibold pl-7">{{ session('error') }}</p>
            </div>
        @endif

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
            @foreach([
                ['label' => 'Total Pallets', 'value' => $stats['total'], 'color' => 'blue'],
                ['label' => 'Synced', 'value' => $stats['success'], 'color' => 'green'],
                ['label' => 'Failed', 'value' => $stats['failed'], 'color' => 'red'],
                ['label' => 'Pending', 'value' => $stats['pending'], 'color' => 'orange'],
            ] as $stat)
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center justify-center text-center">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ $stat['label'] }}</span>
                    <span class="text-3xl font-black text-{{ $stat['color'] }}-600 tracking-tighter">{{ $stat['value'] }}</span>
                </div>
            @endforeach
        </div>

        {{-- Search & Filter --}}
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-6 flex flex-col md:flex-row gap-4">
            <div class="flex-1 relative">
                <input wire:model.live="search" type="text" placeholder="Search Pallet ID or Part No..." class="w-full bg-gray-50 border-gray-200 rounded-xl px-10 py-2.5 text-sm font-bold focus:ring-blue-500 focus:border-blue-500">
                <svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <select wire:model.live="statusFilter" class="bg-gray-50 border-gray-200 rounded-xl px-4 py-2.5 text-sm font-bold focus:ring-blue-500 focus:border-blue-500 min-w-[200px]">
                <option value="">All Statuses</option>
                <option value="0">Pending</option>
                <option value="1">Success</option>
                <option value="2">Failed</option>
            </select>
        </div>

        {{-- Main Table --}}
        <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100">
                        <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Pallet ID</th>
                        <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Part No / Model</th>
                        <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Boxes</th>
                        <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Status</th>
                        <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Last Sync</th>
                        <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($pallets as $pallet)
                        <tr class="hover:bg-gray-50/80 transition-colors group">
                            <td class="py-4 px-6">
                                <span class="text-sm font-black text-gray-900 tracking-tight">{{ $pallet->pallet_id }}</span>
                                <div class="text-[9px] font-bold text-gray-400 uppercase mt-0.5">{{ $pallet->created_at->timezone('Asia/Jakarta')->format('d M Y H:i') }}</div>
                            </td>
                            <td class="py-4 px-6">
                                <span class="text-sm font-black text-blue-600 tracking-tight">{{ $pallet->part_no }}</span>
                                <div class="text-[10px] font-bold text-gray-500 truncate max-w-[200px]">{{ $pallet->model_name }}</div>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-700 text-[10px] font-black">
                                    {{ $pallet->details_count ?: ($pallet->box_qty ?: 0) }} BOXES
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                @if($pallet->sap_sync_status == 1)
                                    <span class="inline-flex items-center px-3 py-1 bg-green-100 text-green-700 text-[10px] font-black rounded-full uppercase tracking-tighter">
                                        SUCCESS
                                    </span>
                                @elseif($pallet->sap_sync_status == 2)
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="inline-flex items-center px-3 py-1 bg-red-100 text-red-700 text-[10px] font-black rounded-full uppercase tracking-tighter">
                                            FAILED
                                        </span>
                                        <span class="text-[8px] text-red-500 font-bold italic leading-tight max-w-[120px] break-words">
                                            {{ $pallet->sap_error_msg }}
                                        </span>
                                    </div>
                                @elseif($pallet->sap_sync_status == 4)
                                    <span class="inline-flex items-center px-3 py-1 bg-gray-200 text-gray-600 text-[10px] font-black rounded-full uppercase tracking-tighter">
                                        IGNORED
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 bg-orange-100 text-orange-700 text-[10px] font-black rounded-full uppercase tracking-tighter animate-pulse">
                                        PENDING
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                @if($pallet->sap_sync_at)
                                    <div class="text-[10px] font-black text-gray-600">{{ $pallet->sap_sync_at->timezone('Asia/Jakarta')->format('d M Y H:i:s') }}</div>
                                    @if($pallet->sap_sync_duration)
                                        <span class="inline-flex items-center text-[9px] font-extrabold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-200 mt-1">
                                            ⏱️ {{ number_format($pallet->sap_sync_duration, 2) }} detik
                                        </span>
                                    @endif
                                @else
                                    <span class="text-[10px] font-bold text-gray-300 italic">Never</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="flex justify-end gap-1">
                                    <button wire:click="viewDetails('{{ $pallet->pallet_id }}')" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="View Details">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </button>
                                    
                                    @php $isDone = in_array($pallet->sap_sync_status, [1, 4]); @endphp

                                    @if(!$isDone)
                                        <button wire:click="markAsSynced('{{ $pallet->pallet_id }}')"
                                            wire:confirm="Tandai pallet {{ $pallet->pallet_id }} sebagai SUKSES di SAP? Gunakan ini jika sudah diverifikasi masuk di SAP."
                                            class="p-2 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all" title="Tandai Sudah Masuk di SAP (Force Success)">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        </button>
                                    @endif

                                    <button wire:click="retrySync('{{ $pallet->pallet_id }}')" 
                                        @if($isDone) disabled @endif
                                        class="p-2 {{ $isDone ? 'text-gray-200 cursor-not-allowed' : 'text-gray-400 hover:text-green-600 hover:bg-green-50' }} rounded-lg transition-all" title="Retry Sync ke SAP">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                    </button>

                                    <button wire:click="ignorePallet('{{ $pallet->pallet_id }}')"
                                        wire:confirm="Abaikan palet ini? Data tidak akan dikirim ke SAP."
                                        @if($isDone) disabled @endif
                                        class="p-2 {{ $isDone ? 'text-gray-200 cursor-not-allowed' : 'text-gray-400 hover:text-red-600 hover:bg-red-50' }} rounded-lg transition-all" title="Ignore Pallet">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-gray-500 font-bold italic">No pallets found matching your criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $pallets->links() }}
        </div>
    </div>

    {{-- Detail Modal --}}
    @if($showDetails)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" wire:click="closeDetails"></div>
            
            <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[85vh] overflow-hidden flex flex-col">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-black text-gray-900 tracking-tighter uppercase italic">Pallet Details: <span class="text-blue-600">{{ $selectedPalletId }}</span></h3>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">Individual Box Data Prepared for SAP Array</p>
                    </div>
                    <button wire:click="closeDetails" class="p-2 hover:bg-gray-100 rounded-xl transition-all">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-6">
                    <table class="w-full text-left">
                        <thead class="sticky top-0 bg-white">
                            <tr class="bg-gray-50 border-b border-gray-100">
                                <th class="py-3 px-4 text-[9px] font-black text-gray-400 uppercase tracking-widest">SPK No</th>
                                <th class="py-3 px-4 text-[9px] font-black text-gray-400 uppercase tracking-widest">Part No</th>
                                <th class="py-3 px-4 text-[9px] font-black text-gray-400 uppercase tracking-widest text-center">Qty</th>
                                <th class="py-3 px-4 text-[9px] font-black text-gray-400 uppercase tracking-widest">Warehouse</th>
                                <th class="py-3 px-4 text-[9px] font-black text-gray-400 uppercase tracking-widest">Label</th>
                                <th class="py-3 px-4 text-[9px] font-black text-gray-400 uppercase tracking-widest text-center">Status</th>
                                <th class="py-3 px-4 text-[9px] font-black text-gray-400 uppercase tracking-widest">Error Message</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100" x-data="{ expandedSpk: null }">
                            @php
                                $groupedDetails = collect($palletDetails)->groupBy('spk_no');
                            @endphp

                            @foreach($groupedDetails as $spk_no => $details)
                                @php
                                    $first = $details->first();
                                    $successCount = $details->where('sap_sync_status', 1)->count();
                                    $failedCount = $details->where('sap_sync_status', 2)->count();
                                    $pendingCount = $details->whereIn('sap_sync_status', [0, 3])->count();
                                    $totalQty = $details->sum('qty');
                                @endphp
                                {{-- SPK Header Row --}}
                                <tr class="hover:bg-gray-50 cursor-pointer transition-colors" @click="expandedSpk === '{{ $spk_no }}' ? expandedSpk = null : expandedSpk = '{{ $spk_no }}'">
                                    <td class="py-4 px-4 font-black text-gray-900 text-sm">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="expandedSpk === '{{ $spk_no }}' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                            {{ $spk_no }}
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 font-bold text-blue-600 text-sm">{{ $first->part_no }}</td>
                                    <td class="py-4 px-4 text-center font-black text-gray-900 text-sm">{{ number_format($totalQty) }}</td>
                                    <td class="py-4 px-4 font-bold text-gray-500 uppercase text-[10px] text-center">{{ $first->warehouse ?: 'FFI' }}</td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="text-[10px] font-black text-gray-400">{{ $details->count() }} BOXES</span>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            @if($successCount > 0) <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-[9px] font-black">{{ $successCount }} OK</span> @endif
                                            @if($failedCount > 0) <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded text-[9px] font-black">{{ $failedCount }} FAIL</span> @endif
                                            @if($pendingCount > 0) <span class="px-2 py-0.5 bg-orange-100 text-orange-700 rounded text-[9px] font-black">{{ $pendingCount }} PEND</span> @endif
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            @if($failedCount > 0)
                                                <button wire:click.stop="retrySpk('{{ $selectedPalletId }}', '{{ $spk_no }}')" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-wider transition-all shadow-md active:scale-95">
                                                    Retry SPK
                                                </button>
                                            @endif
                                            @if($failedCount > 0)
                                                <span class="text-[9px] text-red-500 font-bold italic animate-pulse">Needs Attention</span>
                                            @elseif($pendingCount > 0)
                                                <span class="text-[9px] text-orange-500 font-bold italic animate-pulse">Pending Sync</span>
                                            @else
                                                <span class="text-[9px] text-green-500 font-bold">All Good</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                                {{-- Box Details Dropdown --}}
                                <tr x-show="expandedSpk === '{{ $spk_no }}'" x-cloak class="bg-gray-50/50">
                                    <td colspan="7" class="p-0">
                                        <div class="px-10 py-4 border-y border-gray-100">
                                            <div class="grid grid-cols-1 gap-2">
                                                @foreach($details as $box)
                                                    <div class="bg-white p-3 rounded-xl border {{ $box->sap_sync_status == 2 ? 'border-red-100 bg-red-50/30' : 'border-gray-100' }} flex items-center justify-between">
                                                        <div class="flex items-center gap-4">
                                                            <div class="px-2 py-1 bg-gray-100 rounded text-[9px] font-mono text-gray-500">{{ $loop->iteration }}</div>
                                                            <div class="flex flex-col">
                                                                <span class="text-[11px] font-mono font-bold text-gray-700 tracking-tight">{{ $box->label }}</span>
                                                                @if($box->sap_error_msg)
                                                                    <span class="text-[9px] text-red-600 font-bold italic mt-0.5">⚠️ {{ $box->sap_error_msg }}</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center gap-3">
                                                            <span class="text-[10px] font-black text-gray-900">{{ number_format($box->qty) }} PCS</span>
                                                            
                                                            @if($box->sap_sync_status == 1)
                                                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                                            @elseif($box->sap_sync_status == 4)
                                                                <span class="text-[9px] font-black text-gray-400 uppercase">Ignored</span>
                                                            @elseif($box->sap_sync_status == 2)
                                                                <div class="flex items-center gap-2">
                                                                    <button wire:click="markDetailAsSynced({{ $box->id }})" class="text-emerald-500 hover:text-emerald-700 transition-colors" title="Tandai item ini SUKSES di SAP">
                                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                                    </button>
                                                                    <button wire:click="ignoreDetail({{ $box->id }})" class="text-red-300 hover:text-red-500 transition-colors" title="Ignore this box">
                                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                                                    </button>
                                                                    <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                                                                </div>
                                                            @else
                                                                <div class="flex items-center gap-2">
                                                                    <button wire:click="markDetailAsSynced({{ $box->id }})" class="text-emerald-500 hover:text-emerald-700 transition-colors" title="Tandai item ini SUKSES di SAP">
                                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                                    </button>
                                                                    <div class="w-3 h-3 bg-orange-400 rounded-full animate-pulse"></div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-6 border-t border-gray-100 bg-gray-50/50 flex justify-end">
                    <button wire:click="closeDetails" class="bg-gray-900 text-white px-8 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-gray-800 transition-all">
                        Close Diagnostics
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Flash Message --}}
    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" class="fixed bottom-6 right-6 bg-gray-900 text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center gap-4 animate-bounce">
            <div class="bg-green-500 p-1 rounded-full">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <span class="text-xs font-black uppercase tracking-widest">{{ session('message') }}</span>
        </div>
    @endif
</div>
