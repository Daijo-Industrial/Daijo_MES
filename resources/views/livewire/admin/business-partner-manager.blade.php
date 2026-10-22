<div class="space-y-6">
    <!-- Notifications & Feedback -->
    @if ($uploadMessage)
        <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r shadow-sm">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-emerald-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <div class="text-sm font-semibold text-emerald-800">{{ $uploadMessage }}</div>
            </div>
        </div>
    @endif

    @if ($uploadError)
        <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-r shadow-sm">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-rose-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div class="text-sm font-semibold text-rose-800">{{ $uploadError }}</div>
            </div>
        </div>
    @endif

    <!-- Top Action & Filter Toolbar -->
    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm space-y-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <!-- Search Input -->
            <div class="relative flex-1 max-w-md">
                <input wire:model.live.debounce.300ms="search" type="text"
                    placeholder="Cari kode BP, nama, type, group..."
                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm pl-10 pr-4 py-2">
                <div class="absolute left-3 top-2.5 text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>

            <!-- Upload Button -->
            <div class="flex items-center space-x-3">
                <button wire:click="openUploadModal"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm px-4 py-2 rounded-lg shadow-sm transition inline-flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    Upload List Vendor / BP Active (.xls / .xlsx)
                </button>
            </div>
        </div>

        <!-- Category & Group Tabs -->
        <div class="flex flex-wrap items-center gap-2 border-t pt-4">
            <span class="text-xs font-bold text-gray-500 uppercase mr-2">Filter SAP Group:</span>
            
            <button wire:click="setGroupFilter('ALL')"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 {{ $groupFilter === 'ALL' ? 'bg-gray-900 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                <span>Semua</span>
                <span class="ml-1 px-1.5 py-0.5 text-[10px] rounded-full {{ $groupFilter === 'ALL' ? 'bg-gray-700 text-gray-200' : 'bg-gray-200 text-gray-700' }}">{{ number_format($counts['ALL']) }}</span>
            </button>

            <button wire:click="setGroupFilter('100')"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 {{ $groupFilter === '100' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100' }}">
                <span>Customer (Group 100)</span>
                <span class="ml-1 px-1.5 py-0.5 text-[10px] rounded-full {{ $groupFilter === '100' ? 'bg-emerald-700 text-emerald-100' : 'bg-emerald-200 text-emerald-900' }}">{{ number_format($counts['100']) }}</span>
            </button>

            <button wire:click="setGroupFilter('101')"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 {{ $groupFilter === '101' ? 'bg-blue-600 text-white shadow-sm' : 'bg-blue-50 text-blue-800 hover:bg-blue-100' }}">
                <span>Vendor Lokal (Group 101)</span>
                <span class="ml-1 px-1.5 py-0.5 text-[10px] rounded-full {{ $groupFilter === '101' ? 'bg-blue-700 text-blue-100' : 'bg-blue-200 text-blue-900' }}">{{ number_format($counts['101']) }}</span>
            </button>

            <button wire:click="setGroupFilter('102')"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 {{ $groupFilter === '102' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-indigo-50 text-indigo-800 hover:bg-indigo-100' }}">
                <span>Vendor Import (Group 102)</span>
                <span class="ml-1 px-1.5 py-0.5 text-[10px] rounded-full {{ $groupFilter === '102' ? 'bg-indigo-700 text-indigo-100' : 'bg-indigo-200 text-indigo-900' }}">{{ number_format($counts['102']) }}</span>
            </button>
        </div>
        <div class="flex flex-wrap items-center gap-2 border-t pt-4">
            <span class="text-xs font-bold text-gray-500 uppercase mr-2">Sektor:</span>

            <button wire:click="setIndustryFilter('ALL')"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 {{ $industryFilter === 'ALL' ? 'bg-gray-800 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                <span>Semua Sektor</span>
            </button>

            <button wire:click="setIndustryFilter('AUTOMOTIVE')"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 {{ $industryFilter === 'AUTOMOTIVE' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100' }}">
                <span>Automotive</span>
                <span class="ml-1 px-1.5 py-0.5 text-[10px] rounded-full {{ $industryFilter === 'AUTOMOTIVE' ? 'bg-emerald-700 text-emerald-100' : 'bg-emerald-200 text-emerald-900' }}">{{ number_format($counts['AUTOMOTIVE']) }}</span>
            </button>

            <button wire:click="setIndustryFilter('ELECTRONICS')"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 {{ $industryFilter === 'ELECTRONICS' ? 'bg-blue-600 text-white shadow-sm' : 'bg-blue-50 text-blue-800 hover:bg-blue-100' }}">
                <span>Electronics</span>
                <span class="ml-1 px-1.5 py-0.5 text-[10px] rounded-full {{ $industryFilter === 'ELECTRONICS' ? 'bg-blue-700 text-blue-100' : 'bg-blue-200 text-blue-900' }}">{{ number_format($counts['ELECTRONICS']) }}</span>
            </button>

            <button wire:click="setIndustryFilter('MOULDING')"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 {{ $industryFilter === 'MOULDING' ? 'bg-amber-600 text-white shadow-sm' : 'bg-amber-50 text-amber-800 hover:bg-amber-100' }}">
                <span>Moulding</span>
                <span class="ml-1 px-1.5 py-0.5 text-[10px] rounded-full {{ $industryFilter === 'MOULDING' ? 'bg-amber-700 text-amber-100' : 'bg-amber-200 text-amber-900' }}">{{ number_format($counts['MOULDING']) }}</span>
            </button>
        </div>

    </div>

    <!-- Data Table Card -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-xs text-left">
                <thead class="bg-gray-50 text-gray-700 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">BP Code</th>
                        <th class="px-4 py-3">Nama Business Partner</th>
                        <th class="px-4 py-3 text-center">Group Code</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3">Sektor / Industri</th>
                        <th class="px-4 py-3">Type Produksi</th>
                        <th class="px-4 py-3">Alias (Foreign Name)</th>
                        <th class="px-4 py-3">Sales Employee</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-gray-800">
                    @forelse ($businessPartners as $bp)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 font-mono font-bold text-gray-900 whitespace-nowrap">
                                {{ $bp->bp_code }}
                            </td>
                            <td class="px-4 py-3 font-semibold text-gray-900">
                                {{ $bp->bp_name }}
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded font-mono font-bold text-[11px] bg-gray-100 text-gray-800 border border-gray-300">
                                    {{ $bp->group_code ?: '-' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if ($bp->category === 'CUSTOMER')
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                        CUSTOMER
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-blue-800 border border-blue-300">
                                        VENDOR
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <select wire:change="updateIndustry({{ $bp->id }}, $event.target.value)"
                                    class="text-xs font-bold rounded-lg border py-1 pl-2 pr-6 focus:ring-blue-500 focus:border-blue-500 cursor-pointer transition
                                        {{ $bp->industry === 'AUTOMOTIVE' ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : ($bp->industry === 'ELECTRONICS' ? 'bg-blue-50 text-blue-800 border-blue-300' : ($bp->industry === 'MOULDING' ? 'bg-amber-50 text-amber-800 border-amber-300' : 'bg-gray-50 text-gray-700 border-gray-300')) }}">
                                    <option value="AUTOMOTIVE" {{ $bp->industry === 'AUTOMOTIVE' ? 'selected' : '' }}>Automotive</option>
                                    <option value="ELECTRONICS" {{ $bp->industry === 'ELECTRONICS' ? 'selected' : '' }}>Electronics</option>
                                    <option value="MOULDING" {{ $bp->industry === 'MOULDING' ? 'selected' : '' }}>Moulding</option>
                                    <option value="GENERAL" {{ $bp->industry === 'GENERAL' ? 'selected' : '' }}>General</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if ($bp->type)
                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-300">
                                        {{ $bp->type }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if ($bp->foreign_name)
                                    <span class="font-bold text-gray-700 bg-gray-100 px-2 py-0.5 rounded border border-gray-300">
                                        {{ $bp->foreign_name }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                                @if ($bp->sales_employee)
                                    <span class="font-medium text-gray-800">
                                        {{ $bp->sales_employee }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-gray-500 font-semibold">
                                Tidak ada data Business Partner yang sesuai filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t bg-gray-50">
            {{ $businessPartners->links() }}
        </div>
    </div>

    <!-- Upload Modal -->
    @if ($showUploadModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 max-w-lg w-full p-6 space-y-5 animate-in fade-in duration-200">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        Upload List Vendor / BP Active SAP
                    </h3>
                    <button wire:click="closeUploadModal" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <p class="text-xs text-gray-600 leading-relaxed">
                    Upload file export SAP Business One (format <code class="bg-gray-100 px-1 py-0.5 rounded font-mono font-bold text-blue-600">.xls</code> atau <code class="bg-gray-100 px-1 py-0.5 rounded font-mono font-bold text-blue-600">.xlsx</code>). 
                    Sistem akan memetakan <strong>Group Code</strong> (<code class="font-bold text-emerald-700">100 = Customer</code>, <code class="font-bold text-blue-700">101/102 = Vendor</code>) dan menyinkronkan seluruh akun Customer ke <em>Master Customer Delivery</em>.
                </p>

                <form wire:submit.prevent="uploadFile" class="space-y-4">
                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-blue-500 transition bg-gray-50/50">
                        <input type="file" wire:model="file" id="file" accept=".xls,.xlsx,.csv" class="hidden">
                        <label for="file" class="cursor-pointer block">
                            <svg class="w-10 h-10 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="text-sm font-semibold text-blue-600 hover:underline">Pilih File Spreadsheet</span>
                            <span class="block text-xs text-gray-500 mt-1">Maksimal 20MB (XLS, XLSX, CSV)</span>
                        </label>
                        @if ($file)
                            <div class="mt-3 text-xs font-semibold text-emerald-700 bg-emerald-50 py-1.5 px-3 rounded-lg border border-emerald-200 inline-block">
                                {{ $file->getClientOriginalName() }}
                            </div>
                        @endif
                    </div>

                    @error('file')
                        <p class="text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror

                    <!-- Upload State -->
                    <div wire:loading wire:target="file" class="text-xs text-blue-600 font-semibold">
                        Mengunggah file ke server...
                    </div>
                    <div wire:loading wire:target="uploadFile" class="text-xs text-blue-600 font-semibold">
                        Sedang memproses dan menyinkronkan database... Harap tunggu sebentar.
                    </div>

                    <div class="flex justify-end space-x-3 pt-3 border-t">
                        <button type="button" wire:click="closeUploadModal"
                            class="px-4 py-2 text-xs font-bold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                            Batal
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition disabled:opacity-50">
                            Proses & Sinkronkan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
