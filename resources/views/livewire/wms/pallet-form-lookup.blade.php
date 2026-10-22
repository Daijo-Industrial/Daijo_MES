<div class="p-6">
    <div class="max-w-6xl mx-auto">
        {{-- Header --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Pallet Detail Lookup</h1>
                <p class="text-gray-500 text-sm">Scan barcode ID palet untuk melihat rincian isi dan lokasi.</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('wms.pallet-form.create-delivery') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold flex items-center transition-all shadow-lg shadow-blue-100">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    NEW PALLET
                </a>
                <a href="{{ route('wms.pallet-form.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-semibold flex items-center transition-all">
                    VIEW HISTORY
                </a>
            </div>
        </div>

        {{-- Scan Section --}}
        <div class="bg-white p-8 rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 mb-8 overflow-hidden relative">
            <div class="max-w-xl mx-auto text-center">
                <div class="mb-6 inline-flex p-4 bg-blue-50 rounded-2xl">
                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                </div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">Scan Pallet ID</h2>
                <p class="text-gray-400 text-sm mb-6">Arahkan scanner ke barcode ID palet (PLT-XXXXX)</p>
                
                <div class="relative group">
                    <input type="text" wire:model.live="pallet_id" id="pallet_id" autofocus
                        placeholder="Scan Barcode Palet..."
                        class="w-full px-6 py-5 bg-gray-50 border-2 border-gray-100 rounded-2xl text-center text-2xl font-black text-blue-600 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition-all placeholder-gray-300">
                    
                    @if($pallet_id)
                        <button wire:click="clear" class="absolute right-4 top-1/2 -translate-y-1/2 p-2 text-gray-400 hover:text-red-500 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    @endif
                </div>

                @if (session()->has('error'))
                    <div class="mt-4 p-3 bg-red-50 text-red-600 rounded-xl text-sm font-bold animate-bounce">
                        {{ session('error') }}
                    </div>
                @endif
            </div>

            <div wire:loading wire:target="pallet_id" class="absolute inset-0 bg-white/60 backdrop-blur-[2px] flex items-center justify-center z-10">
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mb-2"></div>
                    <span class="text-blue-600 font-bold uppercase tracking-widest text-xs">Searching...</span>
                </div>
            </div>
        </div>

        {{-- Result Detail --}}
        @if($palletForm)
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                {{-- Pallet Info Card --}}
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-bold text-gray-800">Pallet Information</h3>
                            <div class="flex items-center space-x-2">
                                @if($palletForm->status === 'OUT')
                                    <span class="px-3 py-1 bg-red-600 text-white text-[10px] font-black rounded-full uppercase">OUT</span>
                                @else
                                    <span class="px-3 py-1 bg-green-600 text-white text-[10px] font-black rounded-full uppercase">STORED</span>
                                @endif
                                <span class="px-3 py-1 bg-blue-600 text-white text-[10px] font-black rounded-full uppercase">{{ $palletForm->pallet_id }}</span>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Item Utama</label>
                                <div class="text-gray-800 font-black text-lg leading-tight">{{ $palletForm->part_no }}</div>
                                <div class="text-gray-500 text-sm font-medium">{{ $palletForm->model_name }}</div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Prod Date</label>
                                    <div class="font-bold text-gray-700">{{ \Carbon\Carbon::parse($palletForm->prod_date)->format('d M Y') }}</div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Shift</label>
                                    <div class="font-bold text-gray-700">Shift {{ $palletForm->delivery_shift }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Total Qty</label>
                                    <div class="text-xl font-black text-blue-600">{{ number_format($palletForm->total_pallet_qty) }} <span class="text-xs text-gray-400 font-normal">PCS</span></div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Total Box</label>
                                    <div class="text-xl font-black text-gray-800">{{ $palletForm->box_qty }} <span class="text-xs text-gray-400 font-normal">BOX</span></div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Warehouse Location</label>
                                <div class="flex items-center p-3 bg-slate-800 rounded-xl text-white justify-between">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        <span class="font-bold text-lg">{{ $palletForm->position?->position_code ?? 'NOT MAPPED' }}</span>
                                    </div>
                                    @if($palletForm->is_overaged)
                                        <span class="px-2.5 py-1 bg-rose-600 text-white font-black text-[10px] rounded-lg animate-pulse" title="Tersimpan selama {{ $palletForm->age_days }} Hari di Gudang">
                                            ⚠️ {{ $palletForm->age_days }} HARI DI GUDANG
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Lead Time Timeline Widget -->
                            <div class="p-3.5 bg-indigo-50/80 border border-indigo-100 rounded-2xl space-y-2">
                                <span class="text-[10px] font-black text-indigo-900 uppercase tracking-widest block">⏱️ Putaway Timeline & Lead Time</span>
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <span class="text-[10px] text-gray-400 font-bold block uppercase">Scan Delivery:</span>
                                        <span class="font-bold text-gray-800">{{ $palletForm->created_at ? $palletForm->created_at->timezone('Asia/Jakarta')->format('d M Y H:i') : '-' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-gray-400 font-bold block uppercase">Masuk Rak:</span>
                                        <span class="font-bold text-gray-800">{{ $palletForm->assigned_at ? $palletForm->assigned_at->timezone('Asia/Jakarta')->format('d M Y H:i') : 'Belum Assign' }}</span>
                                    </div>
                                </div>
                                <div class="pt-2 border-t border-indigo-200/60 flex justify-between items-center text-xs font-bold">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-indigo-900">Lead Time:</span>
                                        <span class="px-2 py-0.5 bg-indigo-600 text-white font-black rounded-md font-mono text-[11px]">
                                            {{ $palletForm->putaway_lead_time }}
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-slate-500 font-normal text-[10px]">Umur Stok: </span>
                                        <span class="font-black {{ $palletForm->is_overaged ? 'text-rose-600' : 'text-slate-800' }}">{{ $palletForm->age_days }} Hari</span>
                                    </div>
                                </div>
                            </div>

                            @if($palletForm->remarks)
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Remarks</label>
                                    <div class="p-3 bg-orange-50 border border-orange-100 rounded-xl text-orange-700 text-sm">
                                        {{ $palletForm->remarks }}
                                    </div>
                                </div>
                            @endif

                            <div class="pt-4 border-t border-gray-100 flex flex-col space-y-2">
                                <a href="{{ route('wms.pallet-form.print', $palletForm->pallet_id) }}" target="_blank" 
                                    class="w-full py-3 bg-gray-800 hover:bg-black text-white text-center font-bold rounded-xl transition-all shadow-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                    REPRINT FORM
                                </a>
                                <button wire:click="deletePallet"
                                    wire:confirm="Apakah Anda yakin ingin menghapus Pallet {{ $palletForm->pallet_id }} beserta seluruh item di dalamnya? Tindakan ini tidak bisa dibatalkan."
                                    class="w-full py-3 bg-red-50 hover:bg-red-600 text-red-600 hover:text-white text-center font-bold rounded-xl transition-all border border-red-200 hover:border-transparent flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    DELETE PALLET
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Box Items Table --}}
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-50 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-gray-800">Box Item Details</h3>
                            <span class="px-3 py-1 bg-gray-100 text-gray-500 text-[10px] font-bold rounded-full">
                                {{ $palletForm->details->whereNull('deleted_at')->count() }} ACTIVE / {{ $palletForm->details->whereNotNull('deleted_at')->count() }} OUT
                            </span>
                        </div>
                        <div class="hidden md:block overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-gray-50 text-gray-900 font-black">
                                    <tr>
                                        <th class="px-6 py-3 text-[10px] font-black text-gray-400 uppercase">Item Info</th>
                                        <th class="px-6 py-3 text-[10px] font-black text-gray-400 uppercase">SPK / Label</th>
                                        <th class="px-6 py-3 text-[10px] font-black text-gray-400 uppercase text-right">Qty</th>
                                        <th class="px-6 py-3 text-[10px] font-black text-gray-400 uppercase">Warehouse</th>
                                        <th class="px-6 py-3 text-[10px] font-black text-gray-400 uppercase">Status / Tgl Keluar</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($palletForm->details as $item)
                                        @php
                                            $isOut = $item->deleted_at !== null || $palletForm->status === 'OUT';
                                            $outDate = $item->deleted_at ?? ($palletForm->status === 'OUT' ? $palletForm->updated_at : null);
                                        @endphp
                                        <tr class="hover:bg-blue-50/30 transition-colors {{ $isOut ? 'opacity-75 bg-gray-50/50 text-gray-500' : '' }}">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center">
                                                    <div class="font-black text-sm {{ $isOut ? 'text-gray-500 line-through' : 'text-gray-900' }}">{{ $item->part_no }}</div>
                                                    @if($isOut)
                                                        <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-800 text-[10px] font-bold rounded uppercase">OUT</span>
                                                    @endif
                                                </div>
                                                <div class="text-xs font-bold {{ $isOut ? 'text-gray-400 line-through' : 'text-gray-600' }}">{{ $item->model_name }}</div>
                                            </td>
                                            <td class="px-6 py-4">
                                                @if($item->is_no_label)
                                                    <span class="px-2 py-0.5 bg-orange-100 text-orange-700 text-xs font-black rounded uppercase border border-orange-200">No Label</span>
                                                @else
                                                    <div class="text-sm font-black {{ $isOut ? 'text-gray-500' : 'text-gray-900' }}">{{ $item->spk_no }}</div>
                                                    <div class="text-xs font-mono font-bold {{ $isOut ? 'text-gray-400' : 'text-gray-500' }}">{{ $item->label }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <span class="text-lg font-black {{ $isOut ? 'text-gray-400' : 'text-blue-700' }}">{{ number_format($item->qty) }}</span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="px-3 py-1 bg-slate-100 text-slate-800 text-sm font-black rounded-xl border border-slate-200">{{ $item->warehouse }}</span>
                                            </td>
                                            <td class="px-6 py-4">
                                                @if($isOut && $outDate)
                                                    <div class="flex flex-col">
                                                        <span class="text-xs font-bold text-red-600">
                                                            {{ \Carbon\Carbon::parse($outDate)->timezone('Asia/Jakarta')->format('d M Y') }}
                                                        </span>
                                                        <span class="text-[10px] font-medium text-red-400">
                                                            {{ \Carbon\Carbon::parse($outDate)->timezone('Asia/Jakarta')->format('H:i:s') }} WIB
                                                        </span>
                                                    </div>
                                                @else
                                                    <span class="px-2 py-0.5 bg-green-100 text-green-800 text-[10px] font-bold rounded uppercase">STORED</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Mobile view for lookup --}}
                        <div class="block md:hidden space-y-4">
                            @foreach($palletForm->details as $item)
                                @php
                                    $isOut = $item->deleted_at !== null || $palletForm->status === 'OUT';
                                    $outDate = $item->deleted_at ?? ($palletForm->status === 'OUT' ? $palletForm->updated_at : null);
                                @endphp
                                <div class="p-4 bg-white rounded-xl border {{ $isOut ? 'border-gray-200 bg-gray-50' : 'border-gray-200 shadow-sm' }} space-y-2">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider block">Part & Model</span>
                                            <span class="font-black text-gray-800 text-sm block {{ $isOut ? 'line-through text-gray-400' : '' }}">{{ $item->part_no }}</span>
                                            <span class="text-[10px] text-gray-500 block leading-tight {{ $isOut ? 'line-through text-gray-400' : '' }}">{{ $item->model_name }}</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider block">Kuantitas</span>
                                            <span class="text-blue-600 font-black text-base block">{{ number_format($item->qty) }} PCS</span>
                                        </div>
                                    </div>
                                    <hr class="border-gray-100">
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div>
                                            <span class="text-[9px] font-bold text-gray-400 uppercase block">SPK / Label</span>
                                            @if($item->is_no_label)
                                                <span class="px-2 py-0.5 bg-orange-100 text-orange-700 text-[10px] font-black rounded uppercase">No Label</span>
                                            @else
                                                <span class="font-bold text-gray-700 block font-mono">SPK: {{ $item->spk_no }}</span>
                                                <span class="text-[9px] text-gray-500 block font-mono">LBL: {{ $item->label }}</span>
                                            @endif
                                        </div>
                                        <div class="text-right">
                                            <span class="text-[9px] font-bold text-gray-400 uppercase block">Warehouse</span>
                                            <span class="px-2 py-0.5 bg-slate-100 text-slate-800 font-bold rounded uppercase inline-block border border-slate-200 mt-1">{{ $item->warehouse }}</span>
                                        </div>
                                    </div>
                                    @if($isOut && $outDate)
                                        <div class="mt-2 pt-2 border-t border-red-100 flex justify-between items-center text-xs">
                                            <span class="text-[9px] font-bold text-red-500 uppercase">Tgl Keluar:</span>
                                            <span class="font-bold text-red-600">{{ \Carbon\Carbon::parse($outDate)->timezone('Asia/Jakarta')->format('d M Y H:i:s') }} WIB</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @else
            {{-- Empty State --}}
            <div class="py-20 flex flex-col items-center justify-center opacity-20">
                <svg class="w-24 h-24 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                <p class="text-xl font-bold italic">Menunggu input pallet ID...</p>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            const input = document.getElementById('pallet_id');

            // Audio Feedback Logic
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            
            function playTone(frequency, duration, type = 'sine', volume = 0.1) {
                if (audioCtx.state === 'suspended') audioCtx.resume();
                const oscillator = audioCtx.createOscillator();
                const gainNode = audioCtx.createGain();

                oscillator.type = type;
                oscillator.frequency.setValueAtTime(frequency, audioCtx.currentTime);
                
                gainNode.gain.setValueAtTime(volume, audioCtx.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + duration/1000);

                oscillator.connect(gainNode);
                gainNode.connect(audioCtx.destination);

                oscillator.start();
                oscillator.stop(audioCtx.currentTime + duration/1000);
            }

            Livewire.on('scan-success', () => {
                playTone(1200, 200, 'sawtooth', 0.4);
                setTimeout(() => playTone(1500, 100, 'sawtooth', 0.3), 50);
            });

            Livewire.on('scan-error', () => {
                playTone(100, 400, 'square', 0.6);
                setTimeout(() => playTone(100, 400, 'square', 0.6), 500);
            });

            Livewire.on('select-pallet-id', () => {
                if (input) {
                    input.focus();
                    input.select();
                }
            });
            Livewire.on('focus-pallet-id', () => {
                if (input) input.focus();
            });
        });
    </script>
</div>
