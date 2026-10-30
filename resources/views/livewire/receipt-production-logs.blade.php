<div style="font-family:'IBM Plex Sans',sans-serif; background:#F5F3EF; min-height:100vh; padding:24px;"
     @if(DB::table('production_summary')->where('sap_sent', 2)->exists()) wire:poll.5s @endif>

    {{-- Header --}}
    <div style="margin-bottom:20px; display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:12px;">
        <div>
            <div style="font-size:11px; font-weight:700; letter-spacing:.15em; text-transform:uppercase;
                        color:#9A9590; margin-bottom:4px;">Production Summary</div>
            <div style="font-size:22px; font-weight:800; color:#1A1816;">SAP Receipt Monitor</div>
        </div>
        <div>
            <a href="{{ route('sap.verification') }}" wire:navigate
               style="display:inline-flex; align-items:center; gap:6px; background:#2563EB; color:#fff; font-size:12px; font-weight:700; padding:8px 14px; border-radius:6px; text-decoration:none; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                <span>🔍 Verifikasi File Excel SAP</span>
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div style="display:grid; grid-template-columns:repeat(6,1fr); gap:12px; margin-bottom:20px;">
        <div style="background:#fff; border:1px solid #E8E4DC; border-radius:4px; padding:16px;">
            <div style="font-size:10px; font-weight:700; color:#9A9590; letter-spacing:.1em;
                        text-transform:uppercase; margin-bottom:6px;">Total SPK</div>
            <div style="font-size:24px; font-weight:800; color:#1A1816;">{{ number_format($this->stats['total']) }}</div>
        </div>
        <div style="background:#fff; border:1px solid #E8E4DC; border-radius:4px; padding:16px;
                    border-left:3px solid #22C55E;">
            <div style="font-size:10px; font-weight:700; color:#9A9590; letter-spacing:.1em;
                        text-transform:uppercase; margin-bottom:6px;">Terkirim ke SAP</div>
            <div style="font-size:24px; font-weight:800; color:#15803D;">{{ number_format($this->stats['sent']) }}</div>
        </div>
        <div style="background:#fff; border:1px solid #E8E4DC; border-radius:4px; padding:16px;
                    border-left:3px solid #F97316;">
            <div style="font-size:10px; font-weight:700; color:#9A9590; letter-spacing:.1em;
                        text-transform:uppercase; margin-bottom:6px;">Belum Terkirim</div>
            <div style="font-size:24px; font-weight:800; color:#C2410C;">{{ number_format($this->stats['pending']) }}</div>
        </div>
        @if(($this->stats['processing'] ?? 0) > 0)
        <div style="background:#FFFBEB; border:1px solid #FDE68A; border-radius:4px; padding:16px;
                    border-left:3px solid #F59E0B;">
            <div style="font-size:10px; font-weight:700; color:#92400E; letter-spacing:.1em;
                        text-transform:uppercase; margin-bottom:6px;">⚠ Processing/Stuck</div>
            <div style="font-size:24px; font-weight:800; color:#B45309;">{{ number_format($this->stats['processing']) }}</div>
        </div>
        @endif
        <div style="background:#fff; border:1px solid #E8E4DC; border-radius:4px; padding:16px;
                    border-left:3px solid #7C3AED;">
            <div style="font-size:10px; font-weight:700; color:#9A9590; letter-spacing:.1em;
                        text-transform:uppercase; margin-bottom:6px;">Diabaikan</div>
            <div style="font-size:24px; font-weight:800; color:#7C3AED;">{{ number_format($this->stats['ignored']) }}</div>
        </div>
        <div style="background:#fff; border:1px solid #E8E4DC; border-radius:4px; padding:16px;">
            <div style="font-size:10px; font-weight:700; color:#9A9590; letter-spacing:.1em;
                        text-transform:uppercase; margin-bottom:6px;">Total Qty</div>
            <div style="font-size:24px; font-weight:800; color:#1A1816;">{{ number_format($this->stats['total_qty']) }}</div>
        </div>
    </div>

    {{-- Filters --}}
    <div style="background:#fff; border:1px solid #E8E4DC; border-radius:4px; padding:14px 16px;
                margin-bottom:16px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
        <div>
            <label style="font-size:10px; font-weight:700; color:#9A9590; display:block;
                          text-transform:uppercase; letter-spacing:.08em; margin-bottom:4px;">Mode</label>
            <div style="display:flex; border:1px solid #D8D4CC; border-radius:2px; overflow:hidden;">
                <button wire:click="$set('filterMode', 'daily')"
                        style="padding:6px 12px; font-size:11px; font-weight:600; border:none; cursor:pointer;
                               font-family:'IBM Plex Sans',sans-serif; transition:all .15s;
                               {{ $filterMode === 'daily' ? 'background:#1A1816; color:#fff;' : 'background:#fff; color:#9A9590;' }}">
                    Harian
                </button>
                <button wire:click="$set('filterMode', 'monthly')"
                        style="padding:6px 12px; font-size:11px; font-weight:600; border:none; cursor:pointer;
                               font-family:'IBM Plex Sans',sans-serif; border-left:1px solid #D8D4CC; transition:all .15s;
                               {{ $filterMode === 'monthly' ? 'background:#1A1816; color:#fff;' : 'background:#fff; color:#9A9590;' }}">
                    Bulanan
                </button>
            </div>
        </div>
        <div>
            <label style="font-size:10px; font-weight:700; color:#9A9590; display:block;
                          text-transform:uppercase; letter-spacing:.08em; margin-bottom:4px;">
                {{ $filterMode === 'monthly' ? 'Bulan' : 'Tanggal' }}
            </label>
            @if($filterMode === 'monthly')
                <input type="month" wire:model.live="filterMonth"
                       style="border:1px solid #D8D4CC; border-radius:2px; padding:6px 10px;
                              font-size:12px; font-family:'IBM Plex Sans',sans-serif; color:#1A1816;">
            @else
                <input type="date" wire:model.live="filterDate"
                       style="border:1px solid #D8D4CC; border-radius:2px; padding:6px 10px;
                              font-size:12px; font-family:'IBM Plex Sans',sans-serif; color:#1A1816;">
            @endif
        </div>
        <div>
            <label style="font-size:10px; font-weight:700; color:#9A9590; display:block;
                          text-transform:uppercase; letter-spacing:.08em; margin-bottom:4px;">Cari SPK</label>
            <input type="text" wire:model.live.debounce.400ms="filterSpk"
                   placeholder="No. SPK..."
                   style="border:1px solid #D8D4CC; border-radius:2px; padding:6px 10px;
                          font-size:12px; font-family:'IBM Plex Sans',sans-serif; color:#1A1816; width:160px;">
        </div>
        <div>
            <label style="font-size:10px; font-weight:700; color:#9A9590; display:block;
                          text-transform:uppercase; letter-spacing:.08em; margin-bottom:4px;">Item Code</label>
            <input type="text" wire:model.live.debounce.400ms="filterItemCode"
                   placeholder="Item code..."
                   style="border:1px solid #D8D4CC; border-radius:2px; padding:6px 10px;
                          font-size:12px; font-family:'IBM Plex Sans',sans-serif; color:#1A1816; width:160px;">
        </div>
        <div>
            <label style="font-size:10px; font-weight:700; color:#9A9590; display:block;
                          text-transform:uppercase; letter-spacing:.08em; margin-bottom:4px;">Gudang</label>
            <select wire:model.live="filterWarehouse"
                    style="border:1px solid #D8D4CC; border-radius:2px; padding:6px 10px;
                           font-size:12px; font-family:'IBM Plex Sans',sans-serif; color:#1A1816;">
                <option value="">Semua (FFI & KRFFI)</option>
                <option value="FFI">FFI</option>
                <option value="KRFFI">KRFFI</option>
            </select>
        </div>
        <div>
            <label style="font-size:10px; font-weight:700; color:#9A9590; display:block;
                          text-transform:uppercase; letter-spacing:.08em; margin-bottom:4px;">Status SAP</label>
            <select wire:model.live="filterStatus"
                    style="border:1px solid #D8D4CC; border-radius:2px; padding:6px 10px;
                           font-size:12px; font-family:'IBM Plex Sans',sans-serif; color:#1A1816;">
                <option value="">Semua</option>
                <option value="sent">✓ Terkirim</option>
                <option value="pending">✕ Belum</option>
                <option value="ignored">⊘ Diabaikan</option>
            </select>
        </div>

        {{-- Batch Push, Ignore & Selection Action Buttons --}}
        <div style="margin-left:auto; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            @if(count($selectedLogs) > 0)
            <div style="background:#FEF3C7; border:1px solid #F59E0B; border-radius:3px; padding:6px 12px; display:flex; align-items:center; gap:8px; font-size:11px; color:#92400E;">
                <span>
                    <strong style="font-size:12px;">{{ count($selectedLogs) }}</strong> SPK terpilih
                </span>
                <span style="color:#D97706;">|</span>
                <span>
                    Total Qty: <strong style="font-family:'IBM Plex Mono',monospace; font-size:13px; color:#B45309;">{{ number_format($this->selectedSummary['total_qty']) }}</strong> pcs
                </span>
            </div>

            <button wire:click="pushSelectedToSap()"
                    wire:confirm="Yakin ingin mengirim {{ count($selectedLogs) }} SPK terpilih (Total: {{ number_format($this->selectedSummary['total_qty']) }} pcs) ke SAP?"
                    style="background:#D97706; color:#fff; border:none; border-radius:2px; 
                           padding:8px 14px; font-size:11px; font-weight:700; cursor:pointer; 
                           font-family:'IBM Plex Sans',sans-serif; text-transform:uppercase; 
                           letter-spacing:.08em; transition:all 0.2s; hover:background:#B45309;">
                🚀 Kirim Terpilih ({{ number_format($this->selectedSummary['total_qty']) }} pcs)
            </button>
            <button wire:click="ignoreSelected()"
                    wire:confirm="Yakin ingin mengabaikan {{ count($selectedLogs) }} SPK terpilih (Total: {{ number_format($this->selectedSummary['total_qty']) }} pcs)?"
                    style="background:#DC2626; color:#fff; border:none; border-radius:2px; 
                           padding:8px 14px; font-size:11px; font-weight:700; cursor:pointer; 
                           font-family:'IBM Plex Sans',sans-serif; text-transform:uppercase; 
                           letter-spacing:.08em; transition:all 0.2s; hover:background:#B91C1C;">
                ⊘ Abaikan Terpilih
            </button>
            @endif

            @if($this->stats['pending'] > 0)
            <button wire:click="ignoreAllFiltered()"
                    wire:confirm="Yakin ingin mengabaikan semua SPK pending yang terfilter?"
                    style="background:#7C3AED; color:#fff; border:none; border-radius:2px; 
                           padding:8px 14px; font-size:11px; font-weight:700; cursor:pointer; 
                           font-family:'IBM Plex Sans',sans-serif; text-transform:uppercase; 
                           letter-spacing:.08em; transition:all 0.2s; hover:background:#6D28D9;">
                ⊘ Abaikan Semua Terfilter
            </button>

            <button wire:click="pushPendingBatchToSap()"
                    @if(collect($pushingRows)->count() > 0) disabled @endif
                    wire:confirm="Yakin ingin mengirim semua {{ $this->stats['pending'] }} SPK pending ke SAP?"
                    style="background:#22C55E; color:#fff; border:none; border-radius:2px; 
                           padding:8px 14px; font-size:11px; font-weight:700; cursor:pointer; 
                           font-family:'IBM Plex Sans',sans-serif; text-transform:uppercase; 
                           letter-spacing:.08em; transition:all 0.2s;
                           @if(collect($pushingRows)->count() > 0) opacity:0.5; cursor:not-allowed; @else hover:background:#16A34A; @endif">
                🚀 Push Semua Pending ({{ $this->stats['pending'] }})
            </button>
            @endif
        </div>
    </div>

    {{-- Table --}}
    <div style="background:#fff; border:1px solid #E8E4DC; border-radius:4px; overflow:hidden;">
        <table style="width:100%; border-collapse:collapse; font-size:12px;">
            <thead>
                <tr style="background:#1A1816; color:#fff;">
                    <th style="padding:10px 14px; text-align:center; font-size:9px; font-weight:700;
                               letter-spacing:.12em; text-transform:uppercase; width:40px;">
                        <input type="checkbox" wire:model.live="selectAll"
                               style="width:14px; height:14px; cursor:pointer; vertical-align:middle;">
                    </th>
                    <th style="padding:10px 14px; text-align:left; font-size:9px; font-weight:700;
                               letter-spacing:.12em; text-transform:uppercase;">Tanggal</th>
                    <th style="padding:10px 14px; text-align:left; font-size:9px; font-weight:700;
                               letter-spacing:.12em; text-transform:uppercase;">ID</th>
                    <th style="padding:10px 14px; text-align:left; font-size:9px; font-weight:700;
                               letter-spacing:.12em; text-transform:uppercase;">No. SPK</th>
                    <th style="padding:10px 14px; text-align:left; font-size:9px; font-weight:700;
                               letter-spacing:.12em; text-transform:uppercase;">Item Code</th>
                    <th style="padding:10px 14px; text-align:center; font-size:9px; font-weight:700;
                               letter-spacing:.12em; text-transform:uppercase;">Gudang</th>
                    <th style="padding:10px 14px; text-align:right; font-size:9px; font-weight:700;
                               letter-spacing:.12em; text-transform:uppercase;">Qty</th>
                    <th style="padding:10px 14px; text-align:center; font-size:9px; font-weight:700;
                               letter-spacing:.12em; text-transform:uppercase;">Label</th>
                    <th style="padding:10px 14px; text-align:center; font-size:9px; font-weight:700;
                               letter-spacing:.12em; text-transform:uppercase;">Status SAP</th>
                    <th style="padding:10px 14px; text-align:left; font-size:9px; font-weight:700;
                               letter-spacing:.12em; text-transform:uppercase;">Waktu Kirim</th>
                    <th style="padding:10px 14px; text-align:center; font-size:9px; font-weight:700;
                               letter-spacing:.12em; text-transform:uppercase;">Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse($this->logs as $i => $row)
                @php
                    $sent        = $row->sap_sent == 1;
                    $processing  = $row->sap_sent == 2;
                    $ignored     = $row->sap_sent == 99;
                    $failed      = $row->sap_sent == 3;
                    $bg          = $i % 2 === 0 ? '#fff' : '#FAFAF8';
                    $bl          = $sent ? '3px solid #22C55E' : ($ignored ? '3px solid #7C3AED' : ($processing ? '3px solid #F59E0B' : ($failed ? '3px solid #EF4444' : '3px solid #F97316')));
                    $createdAt   = Carbon\Carbon::parse($row->created_at)->timezone('Asia/Jakarta');
                    $createdDate = Carbon\Carbon::parse($row->created_date);
                    $isPushing   = isset($pushingRows[$row->id]);
                    $pushResult  = $pushResults[$row->id] ?? null;
                    $errorType   = $pushResult['type'] ?? 'unknown';
                @endphp
                <tr wire:key="row-{{ $row->id }}"
                    style="border-bottom:1px solid #F0EDE8; background:{{ $bg }}; border-left:{{ $bl }};">

                    {{-- Checkbox --}}
                    <td style="padding:10px 14px; text-align:center; width:40px;">
                        @if(in_array($row->sap_sent, [0, 2, 3]))
                            <input type="checkbox" wire:model.live="selectedLogs" value="{{ $row->id }}"
                                   style="width:14px; height:14px; cursor:pointer; vertical-align:middle;">
                        @else
                            <input type="checkbox" disabled style="width:14px; height:14px; cursor:not-allowed; opacity:0.3; vertical-align:middle;">
                        @endif
                    </td>

                    {{-- Tanggal --}}
                    <td style="padding:10px 14px; color:#5A554E; font-size:11px;">
                        <div style="font-weight:600; color:#1A1816; font-size:12px;">
                            {{ $createdDate->format('d M Y') }}
                        </div>
                        <div style="font-size:10px; color:#9A9590; margin-top:1px;">
                            {{ $createdDate->translatedFormat('l') }}
                        </div>
                        <div style="font-size:10px; color:#C8C4BC; margin-top:1px;">
                            {{ $createdAt->format('H:i') }} WIB
                        </div>
                    </td>

                    {{-- ID --}}
                    <td style="padding:10px 14px; font-weight:700; color:#1A1816;
                            font-family:'IBM Plex Mono',monospace;">
                        {{ $row->id }}
                    </td>

                    {{-- No SPK --}}
                    <td style="padding:10px 14px; font-weight:700; color:#1A1816;
                            font-family:'IBM Plex Mono',monospace;">
                        {{ $row->spk_code }}
                    </td>

                    {{-- Item Code --}}
                    <td style="padding:10px 14px; font-size:11px; color:#3D3935;
                            font-family:'IBM Plex Mono',monospace;">
                        {{ $row->item_code ?? '—' }}
                    </td>

                    {{-- Gudang --}}
                    <td style="padding:10px 14px; text-align:center;">
                        <span style="background:#F5F3EF; color:#5A554E; font-size:10px;
                                    font-weight:700; padding:2px 8px; border-radius:2px;">
                            {{ $row->warehouse }}
                        </span>
                    </td>

                    {{-- Qty --}}
                    <td style="padding:10px 14px; text-align:right; font-weight:700;
                            color:#1A1816; font-family:'IBM Plex Mono',monospace;">
                        {{ number_format($row->total_quantity) }}
                    </td>

                    {{-- Label --}}
                    <td style="padding:10px 14px; text-align:center; color:#5A554E;">
                        {{ $row->label }}
                    </td>

                    {{-- Status SAP --}}
                    <td style="padding:10px 14px; text-align:center;">
                        @if($ignored)
                        <span style="background:#F3E8FF; color:#7C3AED; font-size:10px;
                                    font-weight:700; padding:3px 10px; border-radius:20px;">
                            ⊘ Diabaikan
                        </span>
                        @elseif($sent)
                        <span style="background:#DCFCE7; color:#15803D; font-size:10px;
                                    font-weight:700; padding:3px 10px; border-radius:20px;">
                            ✓ Terkirim
                        </span>
                        @elseif($processing)
                        <span style="background:#FEF3C7; color:#B45309; font-size:10px;
                                    font-weight:700; padding:3px 10px; border-radius:20px;">
                            ⏳ Processing
                        </span>
                        @elseif($failed)
                        <span style="background:#FEE2E2; color:#EF4444; font-size:10px;
                                    font-weight:700; padding:3px 10px; border-radius:20px;">
                            ✕ Gagal / Timeout
                        </span>
                        @if(!empty($row->error_summary))
                        <div style="margin-top:5px;">
                            <button type="button"
                                    wire:click="showErrorDetail('{{ $row->spk_code }}', {{ $row->id }})"
                                    title="Klik untuk melihat diagnosa lengkap & solusi error ini"
                                    style="background:{{ $row->error_summary['badge_bg'] }};
                                           color:{{ $row->error_summary['badge_color'] }};
                                           border:1px solid {{ $row->error_summary['badge_border'] }};
                                           border-radius:4px; padding:3px 7px; font-size:9.5px; font-weight:700;
                                           cursor:pointer; display:inline-flex; align-items:center; gap:4px;
                                           max-width:140px; text-align:left; box-shadow:0 1px 2px rgba(0,0,0,0.05);
                                           line-height:1.2; font-family:'IBM Plex Sans',sans-serif;">
                                <span>{{ $row->error_summary['icon'] }}</span>
                                <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $row->error_summary['badge_label'] }}</span>
                                <span style="font-size:8px; opacity:0.75;">ℹ️</span>
                            </button>
                        </div>
                        @endif
                        @else
                        <span style="background:#F0EDE8; color:#5A554E; font-size:10px;
                                    font-weight:700; padding:3px 10px; border-radius:20px;">
                            ✕ Belum
                        </span>
                        @endif
                    </td>

                    {{-- Waktu Kirim --}}
                    <td style="padding:10px 14px; font-size:11px; color:#5A554E;">
                        @if($ignored)
                        <div style="display:flex; flex-direction:column; gap:2px;">
                            <span style="background:#F3E8FF; color:#7C3AED; font-size:10px;
                                        font-weight:700; padding:2px 8px; border-radius:2px;
                                        display:inline-block; width:fit-content;">
                                ⊘ Diabaikan
                            </span>
                            <span style="font-size:10px; color:#C8C4BC;">Tidak dikirim ke SAP</span>
                        </div>
                        @elseif($processing)
                        <div style="display:flex; flex-direction:column; gap:2px;">
                            <span style="background:#FEF3C7; color:#B45309; font-size:10px;
                                        font-weight:700; padding:2px 8px; border-radius:2px;
                                        display:inline-block; width:fit-content;">
                                ⏳ Sedang diproses...
                            </span>
                            <span style="font-size:10px; color:#C8C4BC;">Worker sedang mengirim ke SAP</span>
                        </div>
                        @elseif($row->sap_sent_at)
                        @php $sentAt = Carbon\Carbon::parse($row->sap_sent_at)->timezone('Asia/Jakarta'); @endphp
                        <div style="font-weight:700; color:#15803D; font-size:13px;">
                            {{ $sentAt->format('H:i') }}
                            <span style="font-size:10px; font-weight:400;">WIB</span>
                        </div>
                        <div style="font-size:10px; color:#9A9590; margin-top:2px;">
                            {{ $sentAt->format('d M Y') }}
                        </div>
                        @elseif($failed)
                        <div style="display:flex; flex-direction:column; gap:3px;">
                            <span style="background:#FEE2E2; color:#EF4444; font-size:10px;
                                        font-weight:700; padding:2px 8px; border-radius:2px;
                                        display:inline-block; width:fit-content;">
                                ✕ Gagal Kirim
                            </span>
                            @if(!empty($row->error_summary))
                            <div wire:click="showErrorDetail('{{ $row->spk_code }}', {{ $row->id }})"
                                 title="Klik untuk diagnosa & solusi lengkap"
                                 style="font-size:10.5px; color:#B91C1C; font-weight:600; line-height:1.25; cursor:pointer;">
                                {{ Str::limit($row->error_summary['user_message'], 75) }}
                                <span style="font-size:9px; color:#4B5563; text-decoration:underline; display:block; margin-top:2px;">
                                    🔍 Rincian Solusi &raquo;
                                </span>
                            </div>
                            @else
                            <span style="font-size:10px; color:#9A9590;">Butuh pengecekan manual</span>
                            @endif
                        </div>
                        @else
                        <div style="display:flex; flex-direction:column; gap:2px;">
                            <span style="background:#FEF3C7; color:#B45309; font-size:10px;
                                        font-weight:700; padding:2px 8px; border-radius:2px;
                                        display:inline-block; width:fit-content;">
                                ⏳ Menunggu
                            </span>
                            <span style="font-size:10px; color:#C8C4BC;">Belum dikirim ke SAP</span>
                        </div>
                        @endif
                    </td>

                    {{-- Aksi --}}
                    <td style="padding:10px 14px;">
                        <div style="display:flex; flex-direction:column; gap:4px;">
                            {{-- Detail Button --}}
                            <button wire:click="toggleDetail({{ $row->id }})"
                                    style="background:#EFF6FF; border:1px solid #BFDBFE; color:#1D4ED8;
                                        padding:5px 10px; border-radius:2px; font-size:10px; font-weight:700;
                                        cursor:pointer; font-family:'IBM Plex Sans',sans-serif;
                                        white-space:nowrap; width:100%; transition:all 0.2s;
                                        hover:background:#DBEAFE;">
                                {{ isset($expandedRows[$row->id]) ? '▲ Tutup' : '▼ Detail' }}
                            </button>

                            {{-- Push or Manage Buttons --}}
                            @if($row->sap_sent == 0 || $row->sap_sent == 3)
                                {{-- Pending or Failed: Show Push Button --}}
                                @if($isPushing)
                                <button disabled style="background:#E0E7FF; border:1px solid #C7D2FE; color:#818CF8;
                                            padding:5px 10px; border-radius:2px; font-size:10px; font-weight:700;
                                            cursor:not-allowed; font-family:'IBM Plex Sans',sans-serif; 
                                            white-space:nowrap; width:100%;">
                                    ⏳ Mengirim...
                                </button>
                                @else
                                <button wire:click="pushToSapManual({{ $row->id }})"
                                        wire:confirm="Kirim SPK {{ $row->spk_code }} ke SAP sekarang?"
                                        style="background:#FEF3C7; border:1px solid #FDE68A; color:#B45309;
                                            padding:5px 10px; border-radius:2px; font-size:10px; font-weight:700;
                                            cursor:pointer; font-family:'IBM Plex Sans',sans-serif; 
                                            white-space:nowrap; width:100%; transition:all 0.2s;
                                            hover:background:#FCD34D;">
                                    🚀 Kirim SAP
                                </button>
                                @endif

                                {{-- Show Result if any --}}
                                @if($pushResult)
                                @php
                                    // Determine styling based on error type
                                    $bgColor = '#DCFCE7';
                                    $textColor = '#15803D';
                                    $borderColor = '#86EFAC';
                                    $icon = '✓';
                                    
                                    if ($pushResult['status'] === 'error') {
                                        $icon = '✕';
                                        switch ($errorType) {
                                            case 'business_logic':
                                                // Data issue - light red
                                                $bgColor = '#FEE2E2';
                                                $textColor = '#B91C1C';
                                                $borderColor = '#FECACA';
                                                break;
                                            case 'connection':
                                                // Connection issue - orange
                                                $bgColor = '#FEF3C7';
                                                $textColor = '#B45309';
                                                $borderColor = '#FDE68A';
                                                break;
                                            case 'system_config':
                                                // System config issue - red
                                                $bgColor = '#FECACA';
                                                $textColor = '#7F1D1D';
                                                $borderColor = '#F87171';
                                                break;
                                            default:
                                                // Unknown error - purple
                                                $bgColor = '#F3E8FF';
                                                $textColor = '#6B21A8';
                                                $borderColor = '#DDD6FE';
                                                break;
                                        }
                                    }
                                @endphp
                                <div style="padding:6px 8px; border-radius:2px; font-size:9px; 
                                           background:{{ $bgColor }}; color:{{ $textColor }}; 
                                           border:1px solid {{ $borderColor }}; 
                                           line-height:1.3; word-break:break-word;">
                                    <div style="font-weight:700; margin-bottom:2px;">
                                        {{ $icon }} {{ $pushResult['status'] === 'success' ? 'Berhasil' : 'Error' }}
                                    </div>
                                    <div style="font-size:8px;">
                                        {{ $pushResult['message'] }}
                                    </div>
                                    @if(isset($pushResult['raw_message']) && $pushResult['status'] === 'error')
                                    <div style="margin-top:3px; padding-top:3px; border-top:1px solid {{ $borderColor }}; 
                                               font-size:8px; font-family:'IBM Plex Mono',monospace;">
                                        📋 Detail: {{ substr($pushResult['raw_message'], 0, 80) }}{{ strlen($pushResult['raw_message']) > 80 ? '...' : '' }}
                                    </div>
                                    @endif
                                </div>

                                {{-- Clear button (optional) --}}
                                <button wire:click="$set('pushResults.{{ $row->id }}', null)"
                                        style="background:#F5F3EF; border:1px solid #D8D4CC; color:#5A554E;
                                            padding:3px 8px; border-radius:2px; font-size:9px; font-weight:600;
                                            cursor:pointer; font-family:'IBM Plex Sans',sans-serif; 
                                            white-space:nowrap; width:100%; transition:all 0.2s;
                                            hover:background:#E8E4DC;">
                                    ✕ Tutup
                                </button>
                                @endif

                                {{-- Mark as Success Button (only for Failed status) --}}
                                @if($row->sap_sent == 3)
                                <button wire:click="markAsSuccess({{ $row->id }})"
                                        wire:confirm="Yakin ingin menandai SPK {{ $row->spk_code }} ini sebagai sukses di SAP (tanpa mengirim ulang)?"
                                        style="background:#DCFCE7; border:1px solid #86EFAC; color:#15803D;
                                            padding:5px 10px; border-radius:2px; font-size:10px; font-weight:700;
                                            cursor:pointer; font-family:'IBM Plex Sans',sans-serif; 
                                            white-space:nowrap; width:100%; transition:all 0.2s;
                                            hover:background:#BBF7D0; margin-top: 4px; border-style:dashed;">
                                    ✓ Tandai Sukses (Sudah di SAP)
                                </button>
                                @endif

                                {{-- Ignore Button --}}
                                <button wire:click="markAsIgnored({{ $row->id }})"
                                        wire:confirm="Yakin ingin mengabaikan SPK {{ $row->spk_code }}?"
                                        style="background:#F3E8FF; border:1px solid #DDD6FE; color:#7C3AED;
                                            padding:5px 10px; border-radius:2px; font-size:10px; font-weight:700;
                                            cursor:pointer; font-family:'IBM Plex Sans',sans-serif; 
                                            white-space:nowrap; width:100%; transition:all 0.2s;
                                            hover:background:#F3E8FF;">
                                    ⊘ Abaikan
                                </button>
                            @elseif($processing)
                                {{-- Processing/Stuck: Show Reset Button --}}
                                <div style="padding:4px 6px; background:#FFFBEB; border:1px solid #FDE68A;
                                            border-radius:2px; font-size:9px; color:#92400E; margin-bottom:4px;
                                            line-height:1.3;">
                                    ⚠ Sedang diproses worker.<br>Jika lebih dari 15 menit, klik reset.
                                </div>
                                <button wire:click="markAsPending({{ $row->id }})"
                                        wire:confirm="Reset SPK {{ $row->spk_code }} ke pending? Lakukan ini jika worker stuck lebih dari 15 menit."
                                        style="background:#FEF3C7; border:1px solid #FDE68A; color:#B45309;
                                            padding:5px 10px; border-radius:2px; font-size:10px; font-weight:700;
                                            cursor:pointer; font-family:'IBM Plex Sans',sans-serif; 
                                            white-space:nowrap; width:100%; transition:all 0.2s;
                                            hover:background:#FCD34D;">
                                    ↩ Reset ke Pending
                                </button>
                            @elseif($row->sap_sent == 99)
                                {{-- Ignored: Show Reset Button --}}
                                <button wire:click="markAsPending({{ $row->id }})"
                                        wire:confirm="Reset SPK {{ $row->spk_code }} ke pending?"
                                        style="background:#FEF3C7; border:1px solid #FDE68A; color:#B45309;
                                            padding:5px 10px; border-radius:2px; font-size:10px; font-weight:700;
                                            cursor:pointer; font-family:'IBM Plex Sans',sans-serif; 
                                            white-space:nowrap; width:100%; transition:all 0.2s;
                                            hover:background:#FCD34D;">
                                    ↩ Reset
                                </button>
                            @else
                                {{-- Sent: Show Status Only --}}
                                <span style="color:#C8C4BC; font-size:10px; text-align:center; display:block; padding:5px;">
                                    Sudah terkirim
                                </span>
                            @endif
                        </div>
                    </td>

                </tr>

                {{-- Expanded detail row — di LUAR <tr> utama, sejajar --}}
                @if(isset($expandedRows[$row->id]))
                <tr wire:key="detail-{{ $row->id }}" style="background:#F0F7FF; border-left:3px solid #1D4ED8;">
                    <td colspan="11" style="padding:0;">
                        <div style="padding:14px 20px;">
                            {{-- Analisis Error SAP --}}
                            @if(!empty($row->error_summary))
                            <div style="background:#FFF5F5; border:1px solid #FECACA; border-left:4px solid #DC2626; border-radius:4px; padding:12px 16px; margin-bottom:14px; font-family:'IBM Plex Sans',sans-serif;">
                                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:8px;">
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <span style="font-size:20px;">{{ $row->error_summary['icon'] }}</span>
                                        <div>
                                            <div style="font-size:12px; font-weight:800; color:#991B1B;">
                                                {{ $row->error_summary['title'] }}
                                            </div>
                                            <div style="font-size:10px; color:#7F1D1D; margin-top:1px;">
                                                Kategori: <strong>{{ $row->error_summary['badge_label'] }}</strong>
                                                @if(!empty($row->error_summary['bom_line']))
                                                    • <span style="background:#FEE2E2; color:#991B1B; padding:1px 5px; border-radius:3px; font-weight:700;">Line BOM Komponen: #{{ $row->error_summary['bom_line'] }}</span>
                                                @endif
                                                @if(!empty($row->error_summary['logged_at']))
                                                    • Tercatat pada {{ $row->error_summary['logged_at'] }} WIB
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button"
                                            wire:click="showErrorDetail('{{ $row->spk_code }}', {{ $row->id }})"
                                            style="background:#DC2626; color:#fff; border:none; border-radius:3px; padding:5px 12px; font-size:10px; font-weight:700; cursor:pointer; white-space:nowrap; display:inline-flex; align-items:center; gap:4px;">
                                        <span>🔍</span>
                                        <span>Panduan Solusi & Aksi</span>
                                    </button>
                                </div>

                                <div style="background:#fff; border:1px solid #FED7D7; border-radius:3px; padding:8px 12px; margin-bottom:8px; font-size:11px; color:#1F2937; line-height:1.45;">
                                    <strong style="color:#B91C1C;">Penyebab:</strong> {{ $row->error_summary['user_message'] }}
                                </div>

                                <div style="background:#FEF2F2; border-left:3px solid #F87171; padding:7px 12px; font-size:11px; color:#991B1B; line-height:1.45;">
                                    <strong>💡 Rekomendasi Solusi:</strong> {{ $row->error_summary['action_recommendation'] }}
                                </div>
                            </div>
                            @endif

                            <div style="font-size:10px; font-weight:700; color:#1D4ED8; letter-spacing:.1em;
                                        text-transform:uppercase; margin-bottom:10px;">
                                📋 Detail Scanned Data — SPK {{ $row->spk_code }}
                                <span style="background:#1D4ED8; color:#fff; font-size:9px; padding:1px 6px;
                                            border-radius:2px; margin-left:6px;">
                                    {{ count($rowDetails[$row->id] ?? []) }} records
                                </span>
                            </div>

                            @if(!empty($rowDetails[$row->id]))
                            <table style="width:100%; border-collapse:collapse; font-size:11px;
                                        font-family:'IBM Plex Mono',monospace; margin-bottom:10px;">
                                <thead>
                                    <tr style="background:#DBEAFE;">
                                        <th style="padding:5px 10px; text-align:left; font-size:9px; font-weight:700;
                                                color:#1E40AF; letter-spacing:.08em; text-transform:uppercase;
                                                border-bottom:1px solid #BFDBFE;">ID</th>
                                        <th style="padding:5px 10px; text-align:left; font-size:9px; font-weight:700;
                                                color:#1E40AF; letter-spacing:.08em; text-transform:uppercase;
                                                border-bottom:1px solid #BFDBFE;">Item Code</th>
                                        <th style="padding:5px 10px; text-align:right; font-size:9px; font-weight:700;
                                                color:#1E40AF; letter-spacing:.08em; text-transform:uppercase;
                                                border-bottom:1px solid #BFDBFE;">Qty</th>
                                        <th style="padding:5px 10px; text-align:center; font-size:9px; font-weight:700;
                                                color:#1E40AF; letter-spacing:.08em; text-transform:uppercase;
                                                border-bottom:1px solid #BFDBFE;">Label</th>
                                        <th style="padding:5px 10px; text-align:left; font-size:9px; font-weight:700;
                                                color:#1E40AF; letter-spacing:.08em; text-transform:uppercase;
                                                border-bottom:1px solid #BFDBFE;">User</th>
                                        <th style="padding:10px 10px; text-align:left; font-size:9px; font-weight:700;
                                                color:#1E40AF; letter-spacing:.08em; text-transform:uppercase;
                                                border-bottom:1px solid #BFDBFE;">Waktu Scan</th>
                                        <th style="padding:5px 10px; text-align:center; font-size:9px; font-weight:700;
                                                color:#1E40AF; letter-spacing:.08em; text-transform:uppercase;
                                                border-bottom:1px solid #BFDBFE;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rowDetails[$row->id] as $j => $detail)
                                    <tr style="border-bottom:1px solid #EFF6FF;
                                            background:{{ $j % 2 === 0 ? '#F0F7FF' : '#fff' }};">
                                        <td style="padding:5px 10px; color:#5A554E;">{{ $detail['id'] }}</td>
                                        <td style="padding:5px 10px; font-weight:700; color:#1A1816;">{{ $detail['item_code'] }}</td>
                                        <td style="padding:5px 10px; text-align:right; font-weight:700; color:#1A1816;">
                                            {{ number_format($detail['quantity']) }}
                                        </td>
                                        <td style="padding:5px 10px; text-align:center; color:#5A554E;">{{ $detail['label'] }}</td>
                                        <td style="padding:5px 10px; color:#5A554E;">{{ $detail['user'] ?? '—' }}</td>
                                        <td style="padding:5px 10px; color:#5A554E;">{{ $detail['created_at'] }}</td>
                                        <td style="padding:5px 10px; text-align:center;">
                                            <button wire:click="pushDetailToSap({{ $detail['id'] }}, {{ $row->id }})"
                                                    style="background:#DBEAFE; border:1px solid #93C5FD; color:#1D4ED8;
                                                        padding:3px 8px; border-radius:2px; font-size:9px; font-weight:700;
                                                        cursor:pointer; font-family:'IBM Plex Sans',sans-serif;
                                                        white-space:nowrap; transition:all 0.2s;
                                                        hover:background:#BFDBFE;">
                                                → SAP
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr style="background:#DBEAFE; border-top:2px solid #BFDBFE;">
                                        <td colspan="2" style="padding:5px 10px; font-size:10px; font-weight:700; color:#1E40AF;">
                                            TOTAL
                                        </td>
                                        <td style="padding:5px 10px; text-align:right; font-weight:800; color:#1E40AF;">
                                            {{ number_format(collect($rowDetails[$row->id])->sum('quantity')) }}
                                        </td>
                                        <td colspan="4"></td>
                                    </tr>
                                </tfoot>
                            </table>
                            @else
                            <div style="text-align:center; padding:20px; color:#93C5FD; font-size:12px;">
                                Tidak ada detail scanned data
                            </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @endif
                @empty
                <tr wire:key="row-empty">
                    <td colspan="11" style="padding:60px; text-align:center; color:#C8C4BC;">
                        <div style="font-size:28px; margin-bottom:8px;">◈</div>
                        Tidak ada data
                    </td>
                </tr>
                @endforelse
            </tbody>

            {{-- tfoot di LUAR tbody, di dalam table --}}
            @if($this->logs->count() > 0)
            <tfoot>
                <tr wire:key="tfoot-total" style="background:#1A1816;">
                    <td colspan="5" style="padding:12px 14px; font-size:10px; font-weight:700;
                                           color:#9A9590; letter-spacing:.08em; text-transform:uppercase;">
                        {{ $this->logs->total() }} SPK ditemukan
                        @if(count($selectedLogs) > 0)
                            <span style="color:#FBBF24; margin-left:10px; font-weight:600;">
                                • {{ count($selectedLogs) }} SPK terpilih (Total Qty: {{ number_format($this->selectedSummary['total_qty']) }} pcs)
                            </span>
                        @endif
                    </td>
                    <td style="padding:12px 14px; text-align:right; font-family:'IBM Plex Mono',monospace;">
                        <div style="font-size:10px; color:#9A9590; font-weight:700;
                                    letter-spacing:.08em; text-transform:uppercase; margin-bottom:2px;">
                            Total Qty
                        </div>
                        <div style="font-size:16px; font-weight:800; color:#C8A97E;">
                            {{ number_format($this->filteredTotalQty) }}
                        </div>
                    </td>
                    <td colspan="5"></td>
                </tr>
            </tfoot>
            @endif

        </table>

        {{-- Pagination --}}
        <div style="padding:12px 16px; border-top:1px solid #E8E4DC;">
            {{ $this->logs->links() }}
        </div>
    </div>

    {{-- Floating Bottom Selection Bar --}}
    @if(count($selectedLogs) > 0)
    <div style="position:fixed; bottom:24px; left:50%; transform:translateX(-50%); z-index:50;
                background:#1A1816; color:#fff; border-radius:6px; padding:10px 20px;
                box-shadow:0 10px 25px -5px rgba(0,0,0,0.4), 0 8px 10px -6px rgba(0,0,0,0.3);
                display:flex; align-items:center; gap:16px; font-family:'IBM Plex Sans',sans-serif;
                border:1px solid #383531;">
        <div style="display:flex; align-items:center; gap:8px;">
            <span style="background:#D97706; color:#fff; font-size:11px; font-weight:800; padding:3px 8px; border-radius:3px;">
                {{ count($selectedLogs) }} SPK Terpilih
            </span>
            <span style="font-size:13px; color:#E8E4DC;">
                Total Akan Dikirim:
                <strong style="color:#FBBF24; font-size:16px; font-family:'IBM Plex Mono',monospace; margin-left:4px;">
                    {{ number_format($this->selectedSummary['total_qty']) }}
                </strong>
                <span style="font-size:11px; color:#A8A29E; margin-left:2px;">pcs</span>
            </span>
        </div>

        <div style="height:22px; width:1px; background:#383531;"></div>

        <div style="display:flex; align-items:center; gap:8px;">
            <button wire:click="pushSelectedToSap()"
                    wire:confirm="Yakin ingin mengirim {{ count($selectedLogs) }} SPK terpilih (Total: {{ number_format($this->selectedSummary['total_qty']) }} pcs) ke SAP?"
                    style="background:#22C55E; color:#fff; border:none; border-radius:3px; 
                           padding:7px 16px; font-size:12px; font-weight:700; cursor:pointer; 
                           letter-spacing:.05em; transition:all 0.2s; hover:background:#16A34A; display:flex; align-items:center; gap:6px;">
                <span>🚀 Kirim ke SAP</span>
                <span style="background:rgba(0,0,0,0.2); padding:1px 6px; border-radius:3px; font-size:11px;">{{ number_format($this->selectedSummary['total_qty']) }} pcs</span>
            </button>

            <button wire:click="ignoreSelected()"
                    wire:confirm="Yakin ingin mengabaikan {{ count($selectedLogs) }} SPK terpilih (Total: {{ number_format($this->selectedSummary['total_qty']) }} pcs)?"
                    style="background:#DC2626; color:#fff; border:none; border-radius:3px; 
                           padding:7px 12px; font-size:12px; font-weight:600; cursor:pointer; 
                           letter-spacing:.05em; transition:all 0.2s; hover:background:#B91C1C;">
                ⊘ Abaikan
            </button>

            <button wire:click="$set('selectedLogs', [])"
                    title="Batal pilih semua"
                    style="background:#2A2724; color:#D6D3D1; border:1px solid #444; border-radius:3px; 
                           padding:7px 10px; font-size:11px; font-weight:600; cursor:pointer; 
                           transition:all 0.2s; hover:background:#383531;">
                ✕ Batal
            </button>
        </div>
    </div>
    @endif

    {{-- Modal Diagnosa Error SAP --}}
    @if($activeErrorModal)
    <div style="position:fixed; inset:0; background:rgba(15, 23, 42, 0.7); backdrop-filter:blur(2px); z-index:100;
                display:flex; align-items:center; justify-content:center; padding:16px;"
         wire:keydown.escape="closeErrorModal">
        {{-- Click outside backdrop --}}
        <div style="position:absolute; inset:0;" wire:click="closeErrorModal"></div>

        <div style="position:relative; background:#fff; border-radius:8px; width:100%; max-width:680px; max-height:90vh;
                    display:flex; flex-direction:column; box-shadow:0 25px 50px -12px rgba(0,0,0,0.35);
                    overflow:hidden; border:1px solid #CBD5E1; font-family:'IBM Plex Sans',sans-serif; z-index:101;">
            
            {{-- Modal Header --}}
            <div style="background:#1E293B; color:#fff; padding:16px 20px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #334155;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <span style="font-size:26px; line-height:1;">{{ $activeErrorModal['error']['icon'] ?? '⚠️' }}</span>
                    <div>
                        <div style="font-size:15px; font-weight:800; letter-spacing:.02em; color:#F8FAFC;">
                            Diagnosa Error SAP — SPK {{ $activeErrorModal['spk_code'] }}
                        </div>
                        <div style="font-size:11px; color:#94A3B8; margin-top:3px; display:flex; align-items:center; gap:6px;">
                            <span>Kategori:</span>
                            <span style="background:{{ $activeErrorModal['error']['badge_bg'] ?? '#FEF2F2' }}; color:{{ $activeErrorModal['error']['badge_color'] ?? '#991B1B' }}; border:1px solid {{ $activeErrorModal['error']['badge_border'] ?? '#FECACA' }}; padding:1px 6px; border-radius:3px; font-weight:700;">
                                {{ $activeErrorModal['error']['badge_label'] ?? 'Error SAP' }}
                            </span>
                            @if(!empty($activeErrorModal['logged_at']))
                                <span style="color:#64748B;">•</span>
                                <span>Tercatat: {{ $activeErrorModal['logged_at'] }} WIB</span>
                            @endif
                        </div>
                    </div>
                </div>
                <button type="button"
                        wire:click="closeErrorModal"
                        style="background:transparent; border:none; color:#94A3B8; font-size:20px; cursor:pointer; line-height:1; padding:4px 8px; border-radius:4px; transition:all 0.2s;"
                        title="Tutup (Esc)">
                    ✕
                </button>
            </div>

            {{-- Modal Body --}}
            <div style="padding:20px; overflow-y:auto; display:flex; flex-direction:column; gap:16px;">
                {{-- Info SPK & Produksi --}}
                <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:6px; padding:12px 16px; display:grid; grid-template-columns:repeat(4, 1fr); gap:12px; font-size:12px;">
                    <div>
                        <div style="font-size:10px; color:#64748B; font-weight:700; text-transform:uppercase; letter-spacing:.05em;">Nomor SPK</div>
                        <div style="font-weight:700; color:#0F172A; font-family:'IBM Plex Mono',monospace; margin-top:2px;">
                            {{ $activeErrorModal['spk_code'] }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:10px; color:#64748B; font-weight:700; text-transform:uppercase; letter-spacing:.05em;">Part Number</div>
                        <div style="font-weight:700; color:#0F172A; font-family:'IBM Plex Mono',monospace; margin-top:2px;">
                            {{ $activeErrorModal['item_code'] ?? '—' }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:10px; color:#64748B; font-weight:700; text-transform:uppercase; letter-spacing:.05em;">Total Output</div>
                        <div style="font-weight:800; color:#1D4ED8; font-family:'IBM Plex Mono',monospace; margin-top:2px;">
                            {{ number_format($activeErrorModal['total_quantity'] ?? 0) }} pcs
                        </div>
                    </div>
                    <div>
                        <div style="font-size:10px; color:#64748B; font-weight:700; text-transform:uppercase; letter-spacing:.05em;">Gudang / Tanggal</div>
                        <div style="font-weight:600; color:#334155; margin-top:2px;">
                            {{ $activeErrorModal['warehouse'] ?? '—' }} ({{ $activeErrorModal['created_date'] ?? '—' }})
                        </div>
                    </div>
                </div>

                {{-- Penjelasan Masalah --}}
                <div>
                    <div style="font-size:11px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px; display:flex; align-items:center; gap:6px;">
                        <span>🚨</span>
                        <span>Penyebab Masalah (Penjelasan Mudah Dipahami)</span>
                    </div>
                    <div style="background:#FFF5F5; border:1px solid #FECACA; border-left:4px solid #DC2626; border-radius:4px; padding:14px 16px;">
                        <div style="font-size:13px; font-weight:800; color:#991B1B; margin-bottom:4px;">
                            {{ $activeErrorModal['error']['title'] ?? 'Gagal Terhubung / Error SAP' }}
                        </div>
                        <div style="font-size:12.5px; color:#7F1D1D; line-height:1.55;">
                            {{ $activeErrorModal['error']['user_message'] ?? 'Pesan error tidak teridentifikasi.' }}
                        </div>
                        @if(!empty($activeErrorModal['error']['bom_line']))
                            <div style="margin-top:10px; font-size:11.5px; background:#FEE2E2; border:1px solid #FECACA; padding:4px 10px; border-radius:4px; display:inline-flex; align-items:center; gap:6px; font-weight:700; color:#991B1B;">
                                <span>🏷️</span>
                                <span>Baris Komponen BOM di SAP: Line #{{ $activeErrorModal['error']['bom_line'] }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Rekomendasi Solusi --}}
                <div>
                    <div style="font-size:11px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px; display:flex; align-items:center; gap:6px;">
                        <span>💡</span>
                        <span>Rekomendasi Langkah Penyelesaian</span>
                    </div>
                    <div style="background:#F0FDF4; border:1px solid #BBF7D0; border-left:4px solid #16A34A; border-radius:4px; padding:14px 16px; font-size:12.5px; color:#166534; line-height:1.55;">
                        {{ $activeErrorModal['error']['action_recommendation'] ?? 'Lakukan pengecekan manual pada SAP atau hubungi tim IT.' }}
                    </div>
                </div>

                {{-- Detail Teknis / Raw Log (Collapsible via Alpine) --}}
                <div x-data="{ showRaw: false }" style="border:1px solid #E2E8F0; border-radius:6px; overflow:hidden;">
                    <button type="button"
                            @click="showRaw = !showRaw"
                            style="width:100%; background:#F8FAFC; border:none; padding:10px 14px; font-size:11px; font-weight:700; color:#64748B; text-align:left; cursor:pointer; display:flex; align-items:center; justify-content:space-between;">
                        <span style="display:flex; align-items:center; gap:6px;">
                            <span>🔧</span>
                            <span>Detail Teknis / Raw Log API (Untuk IT & Developer)</span>
                        </span>
                        <span x-text="showRaw ? '▲ Sembunyikan' : '▼ Tampilkan'"></span>
                    </button>
                    <div x-show="showRaw" style="padding:12px 14px; background:#0F172A; color:#E2E8F0; font-family:'IBM Plex Mono',monospace; font-size:11px; line-height:1.45; max-height:180px; overflow-y:auto; word-break:break-all; border-top:1px solid #334155;">
                        <div style="color:#94A3B8; margin-bottom:6px; font-size:10px;">
                            // API Log ID: #{{ $activeErrorModal['log_id'] ?? 'N/A' }} | Status Code: {{ $activeErrorModal['error']['status_code'] ?? '-' }}
                        </div>
                        <div>{{ $activeErrorModal['error']['raw_message'] ?? 'Tidak ada data log mentah' }}</div>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div style="background:#F8FAFC; border-top:1px solid #E2E8F0; padding:14px 20px; display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                <div style="font-size:11px; color:#64748B;">
                    Tindakan untuk SPK ini:
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    @if(!empty($activeErrorModal['summary_id']))
                        <button type="button"
                                wire:click="pushToSapManual({{ $activeErrorModal['summary_id'] }})"
                                wire:confirm="Kirim ulang SPK {{ $activeErrorModal['spk_code'] }} ke SAP sekarang?"
                                style="background:#2563EB; color:#fff; border:none; border-radius:4px; padding:7px 14px; font-size:11px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:5px; transition:all 0.2s; hover:background:#1D4ED8;">
                            <span>🚀</span>
                            <span>Kirim Ulang Sekarang</span>
                        </button>

                        <button type="button"
                                wire:click="markAsSuccess({{ $activeErrorModal['summary_id'] }})"
                                wire:confirm="Yakin SPK {{ $activeErrorModal['spk_code'] }} sudah sukses diposting manual di SAP? Status di MES akan ditandai sukses tanpa kirim ulang."
                                style="background:#16A34A; color:#fff; border:none; border-radius:4px; padding:7px 12px; font-size:11px; font-weight:700; cursor:pointer; transition:all 0.2s; hover:background:#15803D;">
                            ✓ Tandai Sukses
                        </button>

                        <button type="button"
                                wire:click="markAsIgnored({{ $activeErrorModal['summary_id'] }})"
                                wire:confirm="Abaikan SPK {{ $activeErrorModal['spk_code'] }} dari pengiriman SAP?"
                                style="background:#9333EA; color:#fff; border:none; border-radius:4px; padding:7px 12px; font-size:11px; font-weight:700; cursor:pointer; transition:all 0.2s; hover:background:#7E22CE;">
                            ⊘ Abaikan
                        </button>
                    @endif

                    <button type="button"
                            wire:click="closeErrorModal"
                            style="background:#E2E8F0; color:#334155; border:none; border-radius:4px; padding:7px 14px; font-size:11px; font-weight:700; cursor:pointer; transition:all 0.2s; hover:background:#CBD5E1;">
                        Tutup
                    </button>
                </div>
            </div>

        </div>
    </div>
    @endif
</div>