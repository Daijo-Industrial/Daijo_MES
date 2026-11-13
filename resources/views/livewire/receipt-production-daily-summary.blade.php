<div style="font-family:'IBM Plex Sans',sans-serif; background:#F5F3EF; min-height:100vh; padding:24px;">

    {{-- Header --}}
    <div style="margin-bottom:20px; display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:12px;" class="no-print">
        <div>
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                <a href="{{ route('receipt-production-logs') }}" wire:navigate
                   style="display:inline-flex; align-items:center; gap:4px; font-size:12px; font-weight:700; color:#475569; text-decoration:none; padding:4px 8px; border-radius:4px; background:#E2E8F0; transition:all 0.15s ease;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Kembali ke SAP Receipt Monitor</span>
                </a>
                <span style="font-size:11px; font-weight:700; letter-spacing:.15em; text-transform:uppercase; color:#9A9590;">
                    Production Summary
                </span>
            </div>
            <div style="font-size:24px; font-weight:800; color:#1A1816;">
                Daily Production Summary ke SAP
            </div>
            <div style="font-size:13px; font-weight:500; color:#64748B; margin-top:2px;">
                Rekapitulasi kuantitas harian untuk verifikasi data penerimaan produksi di SAP Business One —
                <strong>{{ \Carbon\Carbon::parse($summaryData['target_date'])->translatedFormat('l, d F Y') }}</strong>
            </div>
        </div>

        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            {{-- Date Picker --}}
            <div style="display:flex; align-items:center; gap:6px; background:#fff; border:1px solid #CBD5E1; border-radius:6px; padding:6px 12px;">
                <label for="summary-date-input" style="font-size:11px; font-weight:700; color:#64748B; text-transform:uppercase;">Tanggal:</label>
                <input id="summary-date-input"
                       type="date"
                       wire:model.live="date"
                       style="border:none; padding:0; font-size:13px; font-weight:700; color:#1E293B; outline:none; cursor:pointer;">
            </div>

            {{-- Print Button --}}
            <button type="button"
                    onclick="window.print()"
                    style="display:inline-flex; align-items:center; gap:6px; background:#fff; color:#334155; font-size:12px; font-weight:700; padding:8px 14px; border-radius:6px; border:1px solid #CBD5E1; cursor:pointer; box-shadow:0 1px 2px rgba(0,0,0,0.05); transition:background 0.15s ease;">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>Print Summary</span>
            </button>
        </div>
    </div>

    {{-- Print Header (Only visible on paper / PDF) --}}
    <div class="print-only" style="display:none; margin-bottom:20px; border-bottom:2px solid #000; padding-bottom:10px;">
        <div style="font-size:20px; font-weight:800; color:#000;">PT DAIJO INDUSTRIAL - DAILY PRODUCTION SUMMARY</div>
        <div style="font-size:13px; font-weight:600; color:#333; margin-top:4px;">
            Tanggal Produksi: {{ \Carbon\Carbon::parse($summaryData['target_date'])->translatedFormat('l, d F Y') }} |
            Dicetak: {{ now()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB
        </div>
    </div>

    {{-- KPI Summary Stats --}}
    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:20px;">
        <div style="background:#fff; border:1px solid #E8E4DC; border-radius:4px; padding:16px;">
            <div style="font-size:10px; font-weight:700; color:#9A9590; letter-spacing:.1em; text-transform:uppercase; margin-bottom:6px;">Total SPK</div>
            <div style="font-size:24px; font-weight:800; color:#1A1816;">
                {{ number_format($summaryData['stats']['total_spk']) }}
                <span style="font-size:12px; font-weight:500; color:#64748B;">({{ number_format($summaryData['stats']['total_batches']) }} batch)</span>
            </div>
        </div>

        <div style="background:#fff; border:1px solid #E8E4DC; border-radius:4px; padding:16px;">
            <div style="font-size:10px; font-weight:700; color:#9A9590; letter-spacing:.1em; text-transform:uppercase; margin-bottom:6px;">Total Quantity</div>
            <div style="font-size:24px; font-weight:800; color:#1A1816;">
                {{ number_format($summaryData['stats']['total_qty']) }}
                <span style="font-size:12px; font-weight:500; color:#64748B;">pcs</span>
            </div>
        </div>

        <div style="background:#fff; border:1px solid #E8E4DC; border-radius:4px; padding:16px; border-left:4px solid #16A34A;">
            <div style="font-size:10px; font-weight:700; color:#15803D; letter-spacing:.1em; text-transform:uppercase; margin-bottom:6px;">Terkirim ke SAP</div>
            <div style="font-size:24px; font-weight:800; color:#15803D;">
                {{ number_format($summaryData['stats']['sent_qty']) }}
                <span style="font-size:12px; font-weight:500; color:#16A34A;">pcs</span>
            </div>
        </div>

        <div style="background:#fff; border:1px solid #E8E4DC; border-radius:4px; padding:16px; border-left:4px solid #EA580C;">
            <div style="font-size:10px; font-weight:700; color:#C2410C; letter-spacing:.1em; text-transform:uppercase; margin-bottom:6px;">Belum Terkirim</div>
            <div style="font-size:24px; font-weight:800; color:#C2410C;">
                {{ number_format($summaryData['stats']['pending_qty']) }}
                <span style="font-size:12px; font-weight:500; color:#EA580C;">pcs</span>
            </div>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <div style="background:#fff; border:1px solid #E8E4DC; border-radius:6px; padding:12px 16px; margin-bottom:16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;" class="no-print">
        {{-- View Mode Toggle --}}
        <div style="display:inline-flex; background:#F1F5F9; padding:3px; border-radius:6px; border:1px solid #CBD5E1;">
            <button type="button"
                    wire:click="$set('viewMode', 'spk')"
                    style="font-size:12px; font-weight:700; padding:6px 14px; border:none; border-radius:4px; cursor:pointer; transition:all 0.15s ease; {{ $viewMode === 'spk' ? 'background:#fff; color:#0F172A; box-shadow:0 1px 2px rgba(0,0,0,0.06);' : 'background:transparent; color:#64748B;' }}">
                Rekap per SPK
            </button>
            <button type="button"
                    wire:click="$set('viewMode', 'id')"
                    style="font-size:12px; font-weight:700; padding:6px 14px; border:none; border-radius:4px; cursor:pointer; transition:all 0.15s ease; {{ $viewMode === 'id' ? 'background:#fff; color:#0F172A; box-shadow:0 1px 2px rgba(0,0,0,0.06);' : 'background:transparent; color:#64748B;' }}">
                Semua Baris (Per ID)
            </button>
        </div>

        {{-- Filters --}}
        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
            {{-- Search Box --}}
            <div style="position:relative;">
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Cari SPK / Item / ID..."
                       style="font-size:12px; padding:6px 12px 6px 30px; border:1px solid #CBD5E1; border-radius:6px; width:220px; outline:none;">
                <svg style="width:14px; height:14px; color:#94A3B8; position:absolute; left:9px; top:8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            {{-- Warehouse Filter --}}
            <select wire:model.live="warehouse"
                    style="font-size:12px; font-weight:600; padding:6px 10px; border:1px solid #CBD5E1; border-radius:6px; background:#fff; color:#334155; outline:none; cursor:pointer;">
                <option value="">Semua Gudang</option>
                <option value="FFI">FFI (KBN)</option>
                <option value="KRFFI">KRFFI (Karawang)</option>
            </select>

            {{-- Status Filter --}}
            <select wire:model.live="status"
                    style="font-size:12px; font-weight:600; padding:6px 10px; border:1px solid #CBD5E1; border-radius:6px; background:#fff; color:#334155; outline:none; cursor:pointer;">
                <option value="">Semua Status</option>
                <option value="sent">Terkirim</option>
                <option value="pending">Belum / Gagal Terkirim</option>
            </select>
        </div>
    </div>

    {{-- Main Table --}}
    <div style="background:#fff; border:1px solid #E8E4DC; border-radius:6px; box-shadow:0 1px 3px rgba(0,0,0,0.04); overflow:hidden;">
        @if($viewMode === 'spk')
            {{-- Mode: Rekap per SPK --}}
            <table style="width:100%; border-collapse:collapse; font-size:12px; text-align:left;" class="table-fixed">
                <thead>
                    <tr style="background:#0F172A; color:#F8FAFC; text-transform:uppercase; font-size:10px; font-weight:700; letter-spacing:0.05em;">
                        <th style="padding:10px 12px; width:45px; text-align:center;">No</th>
                        <th style="padding:10px 12px; width:130px;">No. SPK</th>
                        <th style="padding:10px 12px; width:160px;">Item Code</th>
                        <th style="padding:10px 12px; width:80px; text-align:center;">Gudang</th>
                        <th style="padding:10px 12px; width:110px; text-align:right;">Total Qty</th>
                        <th style="padding:10px 12px;">ID Summary</th>
                        <th style="padding:10px 12px; width:150px; text-align:center;">Status SAP</th>
                        <th style="padding:10px 12px; width:140px;">Waktu Kirim</th>
                        <th style="padding:10px 12px; width:70px; text-align:center;" class="no-print">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summaryData['by_spk'] as $index => $row)
                        <tr style="border-bottom:1px solid #F1F5F9; {{ !empty($expandedSpks[$row['spk_code']]) ? 'background:#F8FAFC;' : '' }}"
                            class="hover:bg-slate-50 transition-colors">
                            <td style="padding:10px 12px; text-align:center; color:#94A3B8; font-weight:600;">{{ $index + 1 }}</td>
                            <td style="padding:10px 12px; font-family:monospace; font-weight:800; font-size:13px; color:#0F172A;">
                                {{ $row['spk_code'] }}
                            </td>
                            <td style="padding:10px 12px; font-weight:700; color:#334155;">{{ $row['item_code'] }}</td>
                            <td style="padding:10px 12px; text-align:center; font-weight:700; font-size:11px; color:#475569;">
                                {{ $row['warehouse'] }}
                            </td>
                            <td style="padding:10px 12px; text-align:right; font-weight:800; font-size:13px; color:#0F172A;">
                                {{ number_format($row['total_quantity']) }}
                                <span style="font-size:10px; font-weight:500; color:#64748B;">pcs</span>
                            </td>
                            <td style="padding:10px 12px; font-family:monospace; font-size:11px; color:#475569;">
                                {{ $row['id_list_str'] }}
                                @if($row['batch_count'] > 1)
                                    <span style="margin-left:6px; font-size:10px; font-weight:700; font-family:sans-serif; background:#E2E8F0; color:#334155; padding:1px 6px; border-radius:3px;">
                                        {{ $row['batch_count'] }} batch
                                    </span>
                                @endif
                            </td>
                            <td style="padding:10px 12px; text-align:center;">
                                <span class="{{ $row['status_class'] }}"
                                      style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:700; border-width:1px; border-style:solid;">
                                    {{ $row['status_label'] }}
                                </span>
                            </td>
                            <td style="padding:10px 12px; font-size:11px; font-weight:600; color:#475569;">
                                {{ $row['times_str'] }}
                            </td>
                            <td style="padding:10px 12px; text-align:center;" class="no-print">
                                @if($row['batch_count'] > 1)
                                    <button type="button"
                                            wire:click="toggleSpk('{{ $row['spk_code'] }}')"
                                            style="background:none; border:none; color:#2563EB; font-size:11px; font-weight:700; cursor:pointer; text-decoration:underline;">
                                        {{ !empty($expandedSpks[$row['spk_code']]) ? 'Tutup' : 'Rincian' }}
                                    </button>
                                @else
                                    <span style="color:#CBD5E1;">—</span>
                                @endif
                            </td>
                        </tr>

                        {{-- Expanded Sub-row for Multiple Batches --}}
                        @if($row['batch_count'] > 1 && !empty($expandedSpks[$row['spk_code']]))
                            <tr style="background:#F1F5F9; border-bottom:1px solid #CBD5E1;">
                                <td colspan="9" style="padding:10px 16px 12px 50px;">
                                    <div style="background:#fff; border:1px solid #CBD5E1; border-radius:4px; padding:8px 12px; box-shadow:0 1px 2px rgba(0,0,0,0.04);">
                                        <div style="font-size:10px; font-weight:700; color:#64748B; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:6px;">
                                            Rincian Batch Summary untuk SPK {{ $row['spk_code'] }}
                                        </div>
                                        <table style="width:100%; border-collapse:collapse; font-size:11px; text-align:left;">
                                            <thead>
                                                <tr style="color:#64748B; border-bottom:1px solid #E2E8F0; font-size:10px; font-weight:700;">
                                                    <th style="padding:4px 8px;">ID Summary</th>
                                                    <th style="padding:4px 8px;">Item Code</th>
                                                    <th style="padding:4px 8px; text-align:right;">Quantity</th>
                                                    <th style="padding:4px 8px; text-align:center;">Status SAP</th>
                                                    <th style="padding:4px 8px;">Waktu Kirim</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($row['batches'] as $batch)
                                                    <tr style="border-bottom:1px solid #F8FAFC;">
                                                        <td style="padding:4px 8px; font-family:monospace; font-weight:700; color:#0F172A;">#{{ $batch->id }}</td>
                                                        <td style="padding:4px 8px; font-weight:600; color:#334155;">{{ $batch->item_code }}</td>
                                                        <td style="padding:4px 8px; text-align:right; font-weight:700; color:#0F172A;">{{ number_format($batch->total_quantity) }} pcs</td>
                                                        <td style="padding:4px 8px; text-align:center;">
                                                            <span class="{{ $batch->status_class }}"
                                                                  style="display:inline-block; padding:1px 6px; border-radius:3px; font-size:10px; font-weight:700; border-width:1px; border-style:solid;">
                                                                {{ $batch->status_label }}
                                                            </span>
                                                        </td>
                                                        <td style="padding:4px 8px; font-size:11px; color:#475569;">{{ $batch->time_formatted }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="9" style="padding:40px; text-align:center; color:#64748B; font-weight:600;">
                                Tidak ada data summary untuk tanggal {{ \Carbon\Carbon::parse($summaryData['target_date'])->format('d/m/Y') }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @else
            {{-- Mode: Semua Baris (Per ID) --}}
            <table style="width:100%; border-collapse:collapse; font-size:12px; text-align:left;" class="table-fixed">
                <thead>
                    <tr style="background:#0F172A; color:#F8FAFC; text-transform:uppercase; font-size:10px; font-weight:700; letter-spacing:0.05em;">
                        <th style="padding:10px 12px; width:45px; text-align:center;">No</th>
                        <th style="padding:10px 12px; width:90px;">ID</th>
                        <th style="padding:10px 12px; width:130px;">No. SPK</th>
                        <th style="padding:10px 12px; width:180px;">Item Code</th>
                        <th style="padding:10px 12px; width:80px; text-align:center;">Gudang</th>
                        <th style="padding:10px 12px; width:110px; text-align:right;">Quantity</th>
                        <th style="padding:10px 12px; width:150px; text-align:center;">Status SAP</th>
                        <th style="padding:10px 12px;">Waktu Kirim</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summaryData['by_id'] as $index => $row)
                        <tr style="border-bottom:1px solid #F1F5F9;" class="hover:bg-slate-50 transition-colors">
                            <td style="padding:10px 12px; text-align:center; color:#94A3B8; font-weight:600;">{{ $index + 1 }}</td>
                            <td style="padding:10px 12px; font-family:monospace; font-weight:700; color:#0F172A;">#{{ $row->id }}</td>
                            <td style="padding:10px 12px; font-family:monospace; font-weight:800; font-size:13px; color:#0F172A;">{{ $row->spk_code }}</td>
                            <td style="padding:10px 12px; font-weight:700; color:#334155;">{{ $row->item_code }}</td>
                            <td style="padding:10px 12px; text-align:center; font-weight:700; font-size:11px; color:#475569;">{{ $row->warehouse }}</td>
                            <td style="padding:10px 12px; text-align:right; font-weight:800; font-size:13px; color:#0F172A;">
                                {{ number_format($row->total_quantity) }}
                                <span style="font-size:10px; font-weight:500; color:#64748B;">pcs</span>
                            </td>
                            <td style="padding:10px 12px; text-align:center;">
                                <span class="{{ $row->status_class }}"
                                      style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:700; border-width:1px; border-style:solid;">
                                    {{ $row->status_label }}
                                </span>
                            </td>
                            <td style="padding:10px 12px; font-size:11px; font-weight:600; color:#475569;">{{ $row->time_formatted }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding:40px; text-align:center; color:#64748B; font-weight:600;">
                                Tidak ada baris summary untuk tanggal {{ \Carbon\Carbon::parse($summaryData['target_date'])->format('d/m/Y') }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @endif

        {{-- Footer Stats Bar --}}
        <div style="background:#F8FAFC; border-top:1px solid #E8E4DC; padding:10px 16px; display:flex; justify-content:space-between; align-items:center; font-size:12px; color:#64748B;">
            <div>
                Menampilkan <strong>{{ count($viewMode === 'spk' ? $summaryData['by_spk'] : $summaryData['by_id']) }}</strong> data |
                Total: <strong>{{ number_format($summaryData['stats']['total_spk']) }} SPK</strong> /
                <strong>{{ number_format($summaryData['stats']['total_qty']) }} pcs</strong>
            </div>
            <div style="font-weight:600;">
                Terkirim: <span style="color:#15803D;">{{ number_format($summaryData['stats']['sent_qty']) }} pcs</span>
                @if($summaryData['stats']['pending_qty'] > 0)
                    | Belum Terkirim: <span style="color:#C2410C;">{{ number_format($summaryData['stats']['pending_qty']) }} pcs</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Print Styles --}}
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            .print-only {
                display: block !important;
            }
            body {
                background: #fff !important;
                padding: 0 !important;
            }
            div[style*="background:#F5F3EF"] {
                background: #fff !important;
                padding: 0 !important;
            }
            table {
                font-size: 10px !important;
            }
            th, td {
                padding: 4px 6px !important;
            }
        }
    </style>
</div>

