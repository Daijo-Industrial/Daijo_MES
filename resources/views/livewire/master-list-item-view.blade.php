<div style="font-family:'IBM Plex Sans',sans-serif; background:#F5F3EF; min-height:100vh; padding:24px;">

    {{-- Flash message --}}
    @if(session('success'))
    <div style="background:#D1FAE5; border:1px solid #6EE7B7; color:#065F46; padding:10px 16px;
                border-radius:3px; margin-bottom:16px; font-size:13px; font-weight:600;">
        ✓ {{ session('success') }}
    </div>
    @endif

    {{-- Header & Stats --}}
    <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="font-size:20px; font-weight:800; color:#1A1816; margin:0 0 2px 0;
                       font-family:'IBM Plex Mono',monospace; letter-spacing:-.5px;">
                MASTER LIST ITEM
            </h1>
            <p style="font-size:12px; color:#7A756E; margin:0;">
                Kelola item master dan relasi customer untuk Second Process & Delivery
            </p>
        </div>
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <button type="button" wire:click="$set('filterConnection', 'all')"
                style="padding:6px 12px; border-radius:3px; font-size:11px; font-weight:700; cursor:pointer; font-family:'IBM Plex Mono',monospace; border:1px solid {{ $filterConnection === 'all' ? '#1A1816' : '#D8D4CC' }}; background:{{ $filterConnection === 'all' ? '#1A1816' : '#fff' }}; color:{{ $filterConnection === 'all' ? '#fff' : '#1A1816' }}; transition:all 0.15s;">
                TOTAL: {{ number_format($this->counts['total']) }}
            </button>
            <button type="button" wire:click="$set('filterConnection', 'connected')"
                style="padding:6px 12px; border-radius:3px; font-size:11px; font-weight:700; cursor:pointer; font-family:'IBM Plex Mono',monospace; border:1px solid {{ $filterConnection === 'connected' ? '#059669' : '#A7F3D0' }}; background:{{ $filterConnection === 'connected' ? '#059669' : '#ECFDF5' }}; color:{{ $filterConnection === 'connected' ? '#fff' : '#065F46' }}; transition:all 0.15s;">
                ✓ TERHUBUNG: {{ number_format($this->counts['connected']) }}
            </button>
            <button type="button" wire:click="$set('filterConnection', 'unassigned')"
                style="padding:6px 12px; border-radius:3px; font-size:11px; font-weight:700; cursor:pointer; font-family:'IBM Plex Mono',monospace; border:1px solid {{ $filterConnection === 'unassigned' ? '#D97706' : '#FDE68A' }}; background:{{ $filterConnection === 'unassigned' ? '#D97706' : '#FFFBEB' }}; color:{{ $filterConnection === 'unassigned' ? '#fff' : '#92400E' }}; transition:all 0.15s;">
                ⚠️ BELUM TERHUBUNG: {{ number_format($this->counts['unassigned']) }}
            </button>
        </div>
    </div>

    {{-- Filter bar --}}
    <div style="display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap; align-items:center;">

        {{-- Search --}}
        <div style="position:relative; flex:1; min-width:200px;">
            <span style="position:absolute; left:10px; top:50%; transform:translateY(-50%);
                         color:#9A9590; font-size:13px;">⌕</span>
            <input wire:model.live.debounce.300ms="search"
                   placeholder="Search item code / name..."
                   style="width:100%; padding:8px 10px 8px 30px; border:1px solid #D8D4CC;
                          border-radius:3px; font-size:12px; background:#fff;
                          font-family:'IBM Plex Sans',sans-serif; color:#1A1816;
                          box-sizing:border-box;">
        </div>

        {{-- Filter Connection Status --}}
        <select wire:model.live="filterConnection"
                style="padding:8px 10px; border:1px solid #D8D4CC; border-radius:3px;
                       font-size:12px; background:#fff; font-family:'IBM Plex Sans',sans-serif;
                       color:#1A1816; min-width:160px;">
            <option value="all">Semua Status Relasi</option>
            <option value="connected">✓ Terhubung Customer</option>
            <option value="unassigned">⚠️ Belum Terhubung (N/A)</option>
        </select>

        {{-- Filter Customer --}}
        <select wire:model.live="filterCustomer"
                style="padding:8px 10px; border:1px solid #D8D4CC; border-radius:3px;
                       font-size:12px; background:#fff; font-family:'IBM Plex Sans',sans-serif;
                       color:#1A1816; min-width:160px;">
            <option value="">— All Customers</option>
            @foreach($this->customerList as $customer)
            <option value="{{ $customer->customer_code }}">[{{ $customer->customer_code }}] {{ $customer->customer_name }}</option>
            @endforeach
        </select>

        {{-- Filter Machine --}}
        <select wire:model.live="filterMachine"
                style="padding:8px 10px; border:1px solid #D8D4CC; border-radius:3px;
                       font-size:12px; background:#fff; font-family:'IBM Plex Sans',sans-serif;
                       color:#1A1816; min-width:140px;">
            <option value="">— All Machines</option>
            @foreach($this->machineList as $machine)
            <option value="{{ $machine }}">{{ $machine }}</option>
            @endforeach
        </select>

        {{-- Per page --}}
        <select wire:model.live="perPage"
                style="padding:8px 10px; border:1px solid #D8D4CC; border-radius:3px;
                       font-size:12px; background:#fff; font-family:'IBM Plex Sans',sans-serif;
                       color:#1A1816;">
            <option value="25">25 / page</option>
            <option value="50">50 / page</option>
            <option value="100">100 / page</option>
        </select>

        {{-- Add Item Button --}}
        <button wire:click="startAdd"
                style="background:#1A1816; color:#fff; border:none; padding:8px 16px;
                       border-radius:3px; font-size:11px; font-weight:800; cursor:pointer;
                       font-family:'IBM Plex Mono',monospace; letter-spacing:0.05em;">
            ✚ ADD NEW ITEM
        </button>

    </div>

    {{-- Table --}}
    <div style="overflow-x:auto; border:1px solid #D8D4CC; border-radius:3px; background:#fff;">
        <table style="border-collapse:collapse; width:100%; min-width:1100px;
                      font-family:'IBM Plex Mono',monospace;">
            <thead>
                <tr style="background:#1A1816; color:#fff;">
                    <th style="padding:10px 12px; text-align:left; font-size:9px; font-weight:700;
                               letter-spacing:.08em; text-transform:uppercase; white-space:nowrap;
                               border-right:1px solid #333; min-width:120px;">Item Code</th>
                    <th style="padding:10px 12px; text-align:left; font-size:9px; font-weight:700;
                               letter-spacing:.08em; text-transform:uppercase; white-space:nowrap;
                               border-right:1px solid #333; min-width:200px;">Item Name</th>
                    <th style="padding:10px 12px; text-align:left; font-size:9px; font-weight:700;
                               letter-spacing:.08em; text-transform:uppercase; white-space:nowrap;
                               border-right:1px solid #333; min-width:120px;">Customer</th>
                    <th style="padding:10px 12px; text-align:left; font-size:9px; font-weight:700;
                               letter-spacing:.08em; text-transform:uppercase; white-space:nowrap;
                               border-right:1px solid #333; min-width:110px;">Tipe Mesin</th>
                    <th style="padding:10px 12px; text-align:right; font-size:9px; font-weight:700;
                               letter-spacing:.08em; text-transform:uppercase; white-space:nowrap;
                               border-right:1px solid #333; min-width:80px;">Std Pkg</th>
                    <th style="padding:10px 12px; text-align:right; font-size:9px; font-weight:700;
                               letter-spacing:.08em; text-transform:uppercase; white-space:nowrap;
                               border-right:1px solid #333; min-width:90px;">Setup (min)</th>
                    <th style="padding:10px 12px; text-align:center; font-size:9px; font-weight:700;
                               letter-spacing:.08em; text-transform:uppercase; white-space:nowrap;
                               border-right:1px solid #333; min-width:80px;">Pair</th>
                    <th style="padding:10px 12px; text-align:right; font-size:9px; font-weight:700;
                               letter-spacing:.08em; text-transform:uppercase; white-space:nowrap;
                               border-right:1px solid #333; min-width:70px;">Cavity</th>
                    <th style="padding:10px 12px; text-align:right; font-size:9px; font-weight:700;
                               letter-spacing:.08em; text-transform:uppercase; white-space:nowrap;
                               border-right:1px solid #333; min-width:90px;">Cycle Time (s)</th>
                    <th style="padding:10px 12px; text-align:center; font-size:9px; font-weight:700;
                               letter-spacing:.08em; text-transform:uppercase; white-space:nowrap;
                               min-width:70px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @if($isAdding)
                {{-- ── Add row ── --}}
                <tr style="background:#EEF2FF; border-bottom:2px solid #4F46E5;">
                    {{-- Item Code --}}
                    <td style="padding:6px 8px; border-right:1px solid #C7D2FE;">
                        <input wire:model="addForm.item_code" type="text" placeholder="REQUIRED"
                               style="width:100%; padding:4px 6px; border:1px solid #4F46E5;
                                      border-radius:2px; font-size:11px;
                                      font-family:'IBM Plex Mono',monospace; box-sizing:border-box;">
                        @error('addForm.item_code') <div style="color:#DC2626; font-size:9px; margin-top:2px;">{{ $message }}</div> @enderror
                    </td>
                    {{-- Item Name --}}
                    <td style="padding:6px 8px; border-right:1px solid #C7D2FE;">
                        <input wire:model="addForm.item_name" type="text" placeholder="REQUIRED"
                               style="width:100%; padding:4px 6px; border:1px solid #4F46E5;
                                      border-radius:2px; font-size:11px;
                                      font-family:'IBM Plex Mono',monospace; box-sizing:border-box;">
                        @error('addForm.item_name') <div style="color:#DC2626; font-size:9px; margin-top:2px;">{{ $message }}</div> @enderror
                    </td>
                    {{-- Customer --}}
                    <td style="padding:6px 8px; border-right:1px solid #C7D2FE;">
                        <select wire:model="addForm.customer_code"
                                style="width:100%; padding:4px 6px; border:1px solid #4F46E5;
                                       border-radius:2px; font-size:11px; background:#fff;
                                       font-family:'IBM Plex Mono',monospace;">
                            <option value="">— Belum Terhubung (N/A)</option>
                            @foreach($this->customerList as $customer)
                            <option value="{{ $customer->customer_code }}">[{{ $customer->customer_code }}] {{ $customer->customer_name }}</option>
                            @endforeach
                        </select>
                        @error('addForm.customer_code') <div style="color:#DC2626; font-size:9px; margin-top:2px;">{{ $message }}</div> @enderror
                    </td>
                    {{-- Tipe Mesin --}}
                    <td style="padding:6px 8px; border-right:1px solid #C7D2FE;">
                        <input wire:model="addForm.tipe_mesin" type="text"
                               style="width:100%; padding:4px 6px; border:1px solid #4F46E5;
                                      border-radius:2px; font-size:11px;
                                      font-family:'IBM Plex Mono',monospace; box-sizing:border-box;">
                        @error('addForm.tipe_mesin') <div style="color:#DC2626; font-size:9px; margin-top:2px;">{{ $message }}</div> @enderror
                    </td>
                    {{-- Std Pkg --}}
                    <td style="padding:6px 8px; border-right:1px solid #C7D2FE;">
                        <input wire:model="addForm.standart_packaging_list" type="number"
                               style="width:100%; padding:4px 6px; border:1px solid #4F46E5;
                                      border-radius:2px; font-size:11px; text-align:right;
                                      font-family:'IBM Plex Mono',monospace; box-sizing:border-box;">
                        @error('addForm.standart_packaging_list') <div style="color:#DC2626; font-size:9px; margin-top:2px;">{{ $message }}</div> @enderror
                    </td>
                    {{-- Setup Time --}}
                    <td style="padding:6px 8px; border-right:1px solid #C7D2FE;">
                        <input wire:model="addForm.setup_time_minute" type="text"
                               style="width:100%; padding:4px 6px; border:1px solid #4F46E5;
                                      border-radius:2px; font-size:11px; text-align:right;
                                      font-family:'IBM Plex Mono',monospace; box-sizing:border-box;">
                        @error('addForm.setup_time_minute') <div style="color:#DC2626; font-size:9px; margin-top:2px;">{{ $message }}</div> @enderror
                    </td>
                    {{-- Pair --}}
                    <td style="padding:6px 8px; border-right:1px solid #C7D2FE;">
                        <input wire:model="addForm.pair" type="text"
                               style="width:100%; padding:4px 6px; border:1px solid #4F46E5;
                                      border-radius:2px; font-size:11px; text-align:center;
                                      font-family:'IBM Plex Mono',monospace; box-sizing:border-box;">
                        @error('addForm.pair') <div style="color:#DC2626; font-size:9px; margin-top:2px;">{{ $message }}</div> @enderror
                    </td>
                    {{-- Cavity --}}
                    <td style="padding:6px 8px; border-right:1px solid #C7D2FE;">
                        <input wire:model="addForm.cavity" type="number"
                               style="width:100%; padding:4px 6px; border:1px solid #4F46E5;
                                      border-radius:2px; font-size:11px; text-align:right;
                                      font-family:'IBM Plex Mono',monospace; box-sizing:border-box;">
                        @error('addForm.cavity') <div style="color:#DC2626; font-size:9px; margin-top:2px;">{{ $message }}</div> @enderror
                    </td>
                    {{-- Cycle Time --}}
                    <td style="padding:6px 8px; border-right:1px solid #C7D2FE;">
                        <input wire:model="addForm.cycle_time" type="number" step="0.01"
                               style="width:100%; padding:4px 6px; border:1px solid #4F46E5;
                                      border-radius:2px; font-size:11px; text-align:right;
                                      font-family:'IBM Plex Mono',monospace; box-sizing:border-box;">
                        @error('addForm.cycle_time') <div style="color:#DC2626; font-size:9px; margin-top:2px;">{{ $message }}</div> @enderror
                    </td>
                    {{-- Actions --}}
                    <td style="padding:6px 8px; text-align:center; white-space:nowrap;">
                        <button wire:click="saveAdd"
                                style="background:#4F46E5; color:#fff; border:none; padding:4px 10px;
                                       border-radius:2px; font-size:10px; font-weight:700; cursor:pointer;
                                       font-family:'IBM Plex Sans',sans-serif; margin-right:4px;">
                            CREATE
                        </button>
                        <button wire:click="cancelAdd"
                                style="background:#F5F3EF; color:#7A756E; border:1px solid #D8D4CC;
                                       padding:4px 10px; border-radius:2px; font-size:10px;
                                       font-weight:700; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">
                            ✕
                        </button>
                    </td>
                </tr>
                @endif

                @forelse($items as $item)

                @if($editingId === $item->id)
                {{-- ── Edit row ── --}}
                <tr style="background:#FFFBF0; border-bottom:2px solid #F59E0B;">
                    {{-- Item Code (readonly) --}}
                    <td style="padding:8px 12px; font-size:11px; font-weight:700; color:#1A1816;
                               border-right:1px solid #E8E4DC; white-space:nowrap;">
                        {{ $item->item_code }}
                    </td>
                    {{-- Item Name (readonly) --}}
                    <td style="padding:8px 12px; font-size:11px; color:#5A554E;
                               border-right:1px solid #E8E4DC;">
                        {{ $item->item_name }}
                    </td>
                    {{-- Customer --}}
                    <td style="padding:6px 8px; border-right:1px solid #E8E4DC;">
                        <select wire:model="editForm.customer_code"
                                style="width:100%; padding:4px 6px; border:1px solid #F59E0B;
                                       border-radius:2px; font-size:11px; background:#fff;
                                       font-family:'IBM Plex Mono',monospace;">
                            <option value="">— Belum Terhubung (N/A)</option>
                            @foreach($this->customerList as $customer)
                            <option value="{{ $customer->customer_code }}">[{{ $customer->customer_code }}] {{ $customer->customer_name }}</option>
                            @endforeach
                        </select>
                    </td>
                    {{-- Tipe Mesin --}}
                    <td style="padding:6px 8px; border-right:1px solid #E8E4DC;">
                        <input wire:model="editForm.tipe_mesin" type="text"
                               style="width:100%; padding:4px 6px; border:1px solid #F59E0B;
                                      border-radius:2px; font-size:11px;
                                      font-family:'IBM Plex Mono',monospace; box-sizing:border-box;">
                    </td>
                    {{-- Std Pkg --}}
                    <td style="padding:6px 8px; border-right:1px solid #E8E4DC;">
                        <input wire:model="editForm.standart_packaging_list" type="number"
                               style="width:100%; padding:4px 6px; border:1px solid #F59E0B;
                                      border-radius:2px; font-size:11px; text-align:right;
                                      font-family:'IBM Plex Mono',monospace; box-sizing:border-box;">
                    </td>
                    {{-- Setup Time --}}
                    <td style="padding:6px 8px; border-right:1px solid #E8E4DC;">
                        <input wire:model="editForm.setup_time_minute" type="text"
                               style="width:100%; padding:4px 6px; border:1px solid #F59E0B;
                                      border-radius:2px; font-size:11px; text-align:right;
                                      font-family:'IBM Plex Mono',monospace; box-sizing:border-box;">
                    </td>
                    {{-- Pair --}}
                    <td style="padding:6px 8px; border-right:1px solid #E8E4DC;">
                        <input wire:model="editForm.pair" type="text"
                               style="width:100%; padding:4px 6px; border:1px solid #F59E0B;
                                      border-radius:2px; font-size:11px; text-align:center;
                                      font-family:'IBM Plex Mono',monospace; box-sizing:border-box;">
                    </td>
                    {{-- Cavity --}}
                    <td style="padding:6px 8px; border-right:1px solid #E8E4DC;">
                        <input wire:model="editForm.cavity" type="number"
                               style="width:100%; padding:4px 6px; border:1px solid #F59E0B;
                                      border-radius:2px; font-size:11px; text-align:right;
                                      font-family:'IBM Plex Mono',monospace; box-sizing:border-box;">
                    </td>
                    {{-- Cycle Time --}}
                    <td style="padding:6px 8px; border-right:1px solid #E8E4DC;">
                        <input wire:model="editForm.cycle_time" type="number" step="0.01"
                               style="width:100%; padding:4px 6px; border:1px solid #F59E0B;
                                      border-radius:2px; font-size:11px; text-align:right;
                                      font-family:'IBM Plex Mono',monospace; box-sizing:border-box;">
                    </td>
                    {{-- Actions --}}
                    <td style="padding:6px 8px; text-align:center; white-space:nowrap;">
                        <button wire:click="saveEdit"
                                style="background:#1A1816; color:#fff; border:none; padding:4px 10px;
                                       border-radius:2px; font-size:10px; font-weight:700; cursor:pointer;
                                       font-family:'IBM Plex Sans',sans-serif; margin-right:4px;">
                            SAVE
                        </button>
                        <button wire:click="cancelEdit"
                                style="background:#F5F3EF; color:#7A756E; border:1px solid #D8D4CC;
                                       padding:4px 10px; border-radius:2px; font-size:10px;
                                       font-weight:700; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">
                            ✕
                        </button>
                    </td>
                </tr>

                @else
                {{-- ── Normal row ── --}}
                <tr style="border-bottom:1px solid #E8E4DC;
                           background:{{ $loop->even ? '#FAFAF8' : '#fff' }};">
                    <td style="padding:8px 12px; font-size:11px; font-weight:700; color:#1A1816;
                               border-right:1px solid #E8E4DC; white-space:nowrap;">
                        {{ $item->item_code }}
                    </td>
                    <td style="padding:8px 12px; font-size:11px; color:#3D3935;
                               border-right:1px solid #E8E4DC;">
                        {{ $item->item_name }}
                    </td>
                    <td style="padding:8px 12px; font-size:11px; color:#3D3935;
                               border-right:1px solid #E8E4DC;">
                        @if($item->customer)
                        <span style="background:#1A1816; color:#fff; font-size:9px; font-weight:700;
                                     padding:2px 6px; border-radius:2px;">
                            {{ $item->customer->customer_name }}
                        </span>
                        @else
                        <span style="background:#FEF3C7; color:#92400E; border:1px solid #FCD34D; font-size:9px; font-weight:700;
                                     padding:2px 6px; border-radius:2px; display:inline-flex; align-items:center; gap:3px;">
                            ⚠️ Belum Terhubung (N/A)
                        </span>
                        @endif
                    </td>
                    <td style="padding:8px 12px; font-size:11px; color:#3D3935;
                               border-right:1px solid #E8E4DC;">
                        {{ $item->tipe_mesin ?: '—' }}
                    </td>
                    <td style="padding:8px 12px; font-size:11px; color:#3D3935; text-align:right;
                               border-right:1px solid #E8E4DC;">
                        {{ $item->standart_packaging_list ? number_format($item->standart_packaging_list) : '—' }}
                    </td>
                    <td style="padding:8px 12px; font-size:11px; color:#3D3935; text-align:right;
                               border-right:1px solid #E8E4DC;">
                        {{ $item->setup_time_minute ?: '—' }}
                    </td>
                    <td style="padding:8px 12px; font-size:11px; color:#3D3935; text-align:center;
                               border-right:1px solid #E8E4DC;">
                        {{ $item->pair ?: '—' }}
                    </td>
                    <td style="padding:8px 12px; font-size:11px; color:#3D3935; text-align:right;
                               border-right:1px solid #E8E4DC;">
                        {{ $item->cavity ?: '—' }}
                    </td>
                    <td style="padding:8px 12px; font-size:11px; color:#3D3935; text-align:right;
                               border-right:1px solid #E8E4DC;">
                        @if($item->cycle_time)
                        <span style="font-weight:700; color:#1A1816;">{{ number_format($item->cycle_time, 1) }}</span>
                        <span style="color:#9A9590; font-size:10px;">s</span>
                        @else
                        <span style="color:#C8C4BC;">—</span>
                        @endif
                    </td>
                    <td style="padding:8px 12px; text-align:center;">
                        <button wire:click="startEdit({{ $item->id }})"
                                style="background:#F5F3EF; color:#1A1816; border:1px solid #D8D4CC;
                                       padding:4px 10px; border-radius:2px; font-size:10px;
                                       font-weight:700; cursor:pointer; font-family:'IBM Plex Sans',sans-serif;">
                            EDIT
                        </button>
                    </td>
                </tr>
                @endif

                @empty
                <tr>
                    <td colspan="10" style="padding:40px; text-align:center; color:#9A9590;
                                           font-size:13px; font-family:'IBM Plex Sans',sans-serif;">
                        No items found
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div style="margin-top:12px; display:flex; justify-content:space-between; align-items:center;">
        <span style="font-size:12px; color:#7A756E;">
            Showing {{ $items->firstItem() }}–{{ $items->lastItem() }} of {{ $items->total() }}
        </span>
        <div style="font-size:12px;">
            {{ $items->links() }}
        </div>
    </div>

</div>