<div class="p-3 sm:p-6 bg-[#F5F3EF] min-h-screen font-['IBM_Plex_Sans',sans-serif]">

    {{-- Toast Notification --}}
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('push-notification', (data) => {
                const payload = data[0] || data;
                const type = payload.status || 'info';
                const msg = payload.message || '';
                
                let bg = '#1F2937';
                if (type === 'success') {
                    bg = '#15803D';
                    for (let key in localStorage) {
                        if (key.startsWith('qc_draft_ng_')) {
                            localStorage.removeItem(key);
                        }
                    }
                }
                if (type === 'error') bg = '#B91C1C';
                if (type === 'warning') bg = '#C2410C';

                const toast = document.createElement('div');
                toast.style.cssText = `position:fixed; bottom:20px; right:20px; left:20px; max-width:400px; margin:0 auto; z-index:9999; background:${bg}; color:#fff; padding:12px 18px; border-radius:8px; font-weight:600; font-size:13px; shadow:0 10px 15px -3px rgba(0,0,0,0.3); transition:all 0.3s ease; text-align:center;`;
                toast.innerText = msg;
                document.body.appendChild(toast);
                setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 4000);
            });
        });
    </script>

    {{-- Header --}}
    <div class="mb-4 sm:mb-6">
        <div class="flex items-center gap-2 mb-1">
            <span class="text-[10px] sm:text-xs font-bold tracking-widest text-[#9A9590] uppercase">Quality Control</span>
            <span class="px-2 py-0.5 rounded text-[10px] font-extrabold {{ $plant === 'kbn' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">
                PLANT {{ strtoupper($plant) }}
            </span>
        </div>
        <h1 class="text-xl sm:text-2xl font-extrabold text-[#1A1816]">
            @if($plant === 'kbn')
                QC Stock Transfer - KBN (FFI → RJCT)
            @else
                QC Stock Transfer - Karawang (KRFFI → KRFG / KRRJCT)
            @endif
        </h1>
        @if($plant === 'kbn')
            <div class="mt-2.5 p-3 bg-amber-50/90 border-l-4 border-amber-500 text-amber-900 rounded-r-md text-xs font-medium flex items-center gap-2">
                <span class="text-base">ℹ️</span>
                <span><strong>Khusus KBN:</strong> Hanya barang <strong>Reject (NG)</strong> yang akan ditransfer ke gudang <strong>RJCT</strong> di SAP. Barang <strong>OK</strong> tidak akan diproses/ditransfer ke SAP.</span>
            </div>
        @else
            <div class="mt-2.5 p-3 bg-blue-50/90 border-l-4 border-blue-500 text-blue-900 rounded-r-md text-xs font-medium flex items-center gap-2">
                <span class="text-base">ℹ️</span>
                <span><strong>Khusus Karawang:</strong> Barang <strong>OK</strong> ditransfer ke <strong>KRFG</strong>, dan barang <strong>NG</strong> ditransfer ke <strong>KRRJCT</strong> di SAP.</span>
            </div>
        @endif
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5 sm:gap-3 mb-4 sm:mb-6">
        <div class="bg-white border border-[#E8E4DC] rounded-lg p-3 sm:p-4">
            <div class="text-[9px] sm:text-[10px] font-bold text-[#9A9590] tracking-wider uppercase mb-1">Total SPK Siap QC</div>
            <div class="text-18px sm:text-2xl font-black text-[#1A1816]">{{ number_format($this->stats['total']) }}</div>
        </div>
        <div class="bg-white border border-[#E8E4DC] border-l-4 border-l-[#F97316] rounded-lg p-3 sm:p-4">
            <div class="text-[9px] sm:text-[10px] font-bold text-[#9A9590] tracking-wider uppercase mb-1">Belum Diinspeksi</div>
            <div class="text-18px sm:text-2xl font-black text-[#C2410C]">{{ number_format($this->stats['uninspected']) }}</div>
        </div>
        <div class="bg-white border border-[#E8E4DC] border-l-4 border-l-[#F59E0B] rounded-lg p-3 sm:p-4">
            <div class="text-[9px] sm:text-[10px] font-bold text-[#9A9590] tracking-wider uppercase mb-1">Inspeksi Sebagian</div>
            <div class="text-18px sm:text-2xl font-black text-[#B45309]">{{ number_format($this->stats['partial']) }}</div>
        </div>
        <div class="bg-white border border-[#E8E4DC] border-l-4 border-l-[#22C55E] rounded-lg p-3 sm:p-4">
            <div class="text-[9px] sm:text-[10px] font-bold text-[#9A9590] tracking-wider uppercase mb-1">Selesai QC</div>
            <div class="text-18px sm:text-2xl font-black text-[#15803D]">{{ number_format($this->stats['completed']) }}</div>
        </div>
        <div class="bg-white border border-[#E8E4DC] rounded-lg p-3 sm:p-4 col-span-2 sm:col-span-1">
            <div class="text-[9px] sm:text-[10px] font-bold text-[#9A9590] tracking-wider uppercase mb-1">Total Qty Barang</div>
            <div class="text-18px sm:text-2xl font-black text-[#1A1816]">{{ number_format($this->stats['total_qty']) }} <span class="text-xs font-semibold text-[#78716C]">PCS</span></div>
        </div>
    </div>

    {{-- Filters Bar --}}
    <div class="bg-white border border-[#E8E4DC] rounded-lg p-3 sm:p-4 mb-4 sm:mb-6 flex flex-col sm:flex-row gap-3 items-stretch sm:items-end flex-wrap relative">
        <div class="flex flex-col gap-1 flex-1 min-w-[130px]">
            <label class="text-[10px] font-bold text-[#78716C] uppercase flex items-center justify-between">
                <span>Tanggal Summary</span>
                @if($filterDate)
                    <button wire:click="$set('filterDate', '')" class="text-[10px] text-amber-600 hover:text-amber-800 font-bold bg-none border-none p-0 cursor-pointer">Reset</button>
                @endif
            </label>
            <input type="date" wire:model.live="filterDate" class="border border-[#D6D3D1] rounded px-2.5 py-1.5 text-xs sm:text-sm font-semibold w-full focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
        </div>
        <div class="flex flex-col gap-1 flex-1 min-w-[130px]">
            <label class="text-[10px] font-bold text-[#78716C] uppercase flex items-center justify-between">
                <span>No SPK / Label Box</span>
                @if($filterSpk)
                    <button wire:click="$set('filterSpk', '')" class="text-[10px] text-amber-600 hover:text-amber-800 font-bold bg-none border-none p-0 cursor-pointer">Reset</button>
                @endif
            </label>
            <input type="text" wire:model.live.debounce.250ms="filterSpk" placeholder="Cari SPK atau Label Box..." class="border border-[#D6D3D1] rounded px-2.5 py-1.5 text-xs sm:text-sm font-semibold w-full focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
        </div>
        <div class="flex flex-col gap-1 flex-1 min-w-[130px]">
            <label class="text-[10px] font-bold text-[#78716C] uppercase flex items-center justify-between">
                <span>Item Code</span>
                @if($filterItemCode)
                    <button wire:click="$set('filterItemCode', '')" class="text-[10px] text-amber-600 hover:text-amber-800 font-bold bg-none border-none p-0 cursor-pointer">Reset</button>
                @endif
            </label>
            <input type="text" wire:model.live.debounce.250ms="filterItemCode" placeholder="Cari Item..." class="border border-[#D6D3D1] rounded px-2.5 py-1.5 text-xs sm:text-sm font-semibold w-full focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
        </div>
        <div class="flex flex-col gap-1 flex-1 min-w-[130px]">
            <label class="text-[10px] font-bold text-[#78716C] uppercase">Gudang Asal</label>
            <div class="px-3 py-1.5 bg-gray-100 border border-gray-300 rounded text-xs sm:text-sm font-extrabold text-gray-800">
                {{ $plant === 'kbn' ? 'FFI (KBN)' : 'KRFFI (Karawang)' }}
            </div>
        </div>
        <div class="flex flex-col gap-1 flex-1 min-w-[140px]">
            <label class="text-[10px] font-bold text-[#78716C] uppercase">Status QC</label>
            <select wire:model.live="filterQcStatus" class="border border-[#D6D3D1] rounded px-2.5 py-1.5 text-xs sm:text-sm font-semibold w-full focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                <option value="pending">Perlu QC (Belum & Sebagian)</option>
                <option value="completed">Selesai QC</option>
                <option value="all">Semua Status</option>
            </select>
        </div>
    </div>

    {{-- Main Container (Cards on Mobile, Table on Desktop) --}}
    <div class="bg-white border border-[#E8E4DC] rounded-lg overflow-hidden relative"
         wire:loading.class="opacity-60"
         wire:target="filterDate, filterSpk, filterItemCode, filterQcStatus, gotoPage, nextPage, previousPage">

        {{-- Desktop Table View (md:block) --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm border-collapse">
                <thead class="bg-[#F9F8F6] border-b border-[#E8E4DC] text-[10px] font-extrabold text-[#78716C] uppercase tracking-wider">
                    <tr>
                        <th class="p-3 w-10 text-center"></th>
                        <th class="p-3">SPK Code</th>
                        <th class="p-3">Item Code</th>
                        <th class="p-3 text-center">Label Box</th>
                        <th class="p-3 text-right">Total Qty</th>
                        <th class="p-3 text-center">Gudang Asal</th>
                        <th class="p-3 text-center">Progres QC</th>
                        <th class="p-3 text-center">Status QC</th>
                        <th class="p-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F3F0E9]">
                    @forelse($this->summaries as $item)
                        @php
                            $isExpanded = isset($expandedRows[$item->id]);
                            $qcStatus = $item->qc_status ?? 0;
                        @endphp
                        <tr class="hover:bg-amber-50/40 transition {{ $isExpanded ? 'bg-[#FEFCE8]' : 'bg-white' }}">
                            <td class="p-3 text-center">
                                <button wire:click="toggleDetail({{ $item->id }})" wire:loading.attr="disabled" class="bg-none border-none cursor-pointer text-sm font-bold text-[#78716C]">
                                    {{ $isExpanded ? '▼' : '►' }}
                                </button>
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                <div class="font-extrabold text-[#1A1816]">{{ $item->spk_code }}</div>
                                <div class="text-[11px] text-[#78716C] font-semibold mt-0.5 flex items-center gap-1">
                                    <span>📅</span> {{ $item->created_date ? \Carbon\Carbon::parse($item->created_date)->format('d/m/Y') : '-' }}
                                </div>
                            </td>
                            <td class="p-3 font-bold text-[#292524]">{{ $item->item_code }}</td>
                            <td class="p-3 text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold bg-amber-50 text-amber-900 border border-amber-200 shadow-xs" title="{{ !empty($item->box_labels_all) ? 'Box: ' . implode(', ', $item->box_labels_all) : '' }}">
                                    <span>📦</span> {{ $item->box_labels_summary }}
                                </span>
                            </td>
                            <td class="p-3 text-right font-extrabold text-[#2563EB]">{{ number_format($item->total_quantity) }}</td>
                            <td class="p-3 text-center">
                                <span class="px-2 py-0.5 rounded text-[11px] font-extrabold bg-[#E2E8F0] text-[#1E293B]">
                                    {{ $item->warehouse }}
                                </span>
                            </td>
                            <td class="p-3 text-center whitespace-nowrap">
                                <span class="font-bold text-xs">
                                    {{ $item->inspected_boxes }} / {{ $item->total_boxes }} Box
                                </span>
                            </td>
                            <td class="p-3 text-center">
                                @if($qcStatus == 1)
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-[#DCFCE7] text-[#15803D]">SELESAI</span>
                                @elseif($qcStatus == 2)
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-[#FEF3C7] text-[#B45309]">SEBAGIAN</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-[#FFEDD5] text-[#C2410C]">BELUM QC</span>
                                @endif
                            </td>
                            <td class="p-3 text-right whitespace-nowrap">
                                <button wire:click="toggleDetail({{ $item->id }})" wire:loading.attr="disabled" class="bg-[#1E293B] hover:bg-slate-700 active:scale-95 transition text-white border-none px-3 py-1.5 rounded text-xs font-bold cursor-pointer">
                                    <span wire:loading.remove wire:target="toggleDetail({{ $item->id }})">
                                        {{ $isExpanded ? 'Tutup Box' : 'Inspeksi Box' }}
                                    </span>
                                    <span wire:loading wire:target="toggleDetail({{ $item->id }})">
                                        ⏳ Buka...
                                    </span>
                                </button>
                            </td>
                        </tr>

                        {{-- Desktop Expanded Box Details View --}}
                        @if($isExpanded)
                            <tr>
                                <td colspan="9" class="p-4 bg-[#FFFDF5] border-b-2 border-[#FDE68A]">
                                    @include('livewire.qc.partials.box-details', ['item' => $item, 'qcStatus' => $qcStatus, 'isMobile' => false])
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-[#78716C] font-semibold">
                                Tidak ada data production summary yang memenuhi kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile Cards View (md:hidden) --}}
        <div class="block md:hidden divide-y divide-[#E8E4DC]">
            @forelse($this->summaries as $item)
                @php
                    $isExpanded = isset($expandedRows[$item->id]);
                    $qcStatus = $item->qc_status ?? 0;
                @endphp
                <div class="p-3 sm:p-4 {{ $isExpanded ? 'bg-[#FEFCE8]' : 'bg-white' }}">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <div class="text-xs font-extrabold text-[#1A1816]">{{ $item->spk_code }}</div>
                            <div class="text-xs font-bold text-[#292524]">{{ $item->item_code }}</div>
                            <div class="text-[11px] text-[#78716C] font-semibold mt-0.5 flex items-center gap-1">
                                <span>📅</span> {{ $item->created_date ? \Carbon\Carbon::parse($item->created_date)->format('d/m/Y') : '-' }}
                            </div>
                            <div class="mt-1">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-900 border border-amber-200">
                                    <span>📦</span> {{ $item->box_labels_summary }}
                                </span>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-[#E2E8F0] text-[#1E293B]">
                            {{ $item->warehouse }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center text-xs mb-3 bg-gray-50 p-2 rounded border border-gray-100">
                        <div>
                            <span class="text-[10px] text-gray-500 font-bold block">TOTAL QTY</span>
                            <span class="font-black text-[#2563EB]">{{ number_format($item->total_quantity) }} PCS</span>
                        </div>
                        <div class="text-center">
                            <span class="text-[10px] text-gray-500 font-bold block">PROGRES QC</span>
                            <span class="font-bold text-gray-800">{{ $item->inspected_boxes }} / {{ $item->total_boxes }} Box</span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-gray-500 font-bold block mb-0.5">STATUS</span>
                            @if($qcStatus == 1)
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-[#DCFCE7] text-[#15803D]">SELESAI</span>
                            @elseif($qcStatus == 2)
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-[#FEF3C7] text-[#B45309]">SEBAGIAN</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-[#FFEDD5] text-[#C2410C]">BELUM QC</span>
                            @endif
                        </div>
                    </div>

                    <button wire:click="toggleDetail({{ $item->id }})" wire:loading.attr="disabled" class="w-full bg-[#1E293B] hover:bg-slate-700 text-white border-none py-2 rounded text-xs font-bold cursor-pointer active:scale-98 transition">
                        <span wire:loading.remove wire:target="toggleDetail({{ $item->id }})">
                            {{ $isExpanded ? '▲ Tutup Rincian Box' : '▼ Inspeksi Box (' . $item->box_labels_summary . ')' }}
                        </span>
                        <span wire:loading wire:target="toggleDetail({{ $item->id }})">
                            ⏳ Memuat Rincian Box...
                        </span>
                    </button>

                    {{-- Mobile Expanded Box Details View --}}
                    @if($isExpanded)
                        <div class="mt-3 pt-3 border-t-2 border-[#FDE68A] bg-[#FFFDF5] -mx-3 -mb-3 p-3">
                            @include('livewire.qc.partials.box-details', ['item' => $item, 'qcStatus' => $qcStatus, 'isMobile' => true])
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-6 text-center text-[#78716C] font-semibold text-xs">
                    Tidak ada data production summary yang memenuhi kriteria filter.
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="p-3 border-t border-[#E8E4DC] bg-[#F9F8F6]">
            {{ $this->summaries->links() }}
        </div>
    </div>
</div>
