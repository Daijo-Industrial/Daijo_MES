<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Harian Crosscheck Scan Produksi ke Store - {{ $selectedDate }} ({{ $plant }})</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            margin: 0;
            padding: 10px;
            background: #fff;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .company-title {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
        }
        .doc-title {
            font-size: 13px;
            font-weight: bold;
            color: #2563eb;
            text-transform: uppercase;
        }
        .meta-info {
            font-size: 9px;
            color: #64748b;
            line-height: 1.4;
        }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 8px;
            margin-bottom: 14px;
        }
        .metric-card {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px;
            background: #f8fafc;
        }
        .metric-card.found {
            background: #f0fdf4;
            border-color: #86efac;
        }
        .metric-card.missing {
            background: #fef2f2;
            border-color: #fca5a5;
        }
        .metric-card.pending {
            background: #fffbeb;
            border-color: #fde68a;
        }
        .metric-label {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
        }
        .metric-value {
            font-size: 16px;
            font-weight: bold;
            margin-top: 2px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            font-size: 9px;
        }
        th {
            background: #0f172a;
            color: #fff;
            font-weight: bold;
            text-align: left;
            text-transform: uppercase;
            font-size: 8px;
        }
        tr:nth-child(even) {
            background: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-found {
            background: #dcfce7;
            color: #166534;
        }
        .badge-missing {
            background: #fee2e2;
            color: #991b1b;
        }
        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }
        .signatures {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .sig-box {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px;
            text-align: center;
        }
        .sig-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #475569;
            margin-bottom: 45px;
        }
        .sig-line {
            border-top: 1px solid #94a3b8;
            padding-top: 4px;
            font-size: 9px;
            font-weight: bold;
        }
        .no-print {
            margin-bottom: 12px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="no-print" style="background: #f1f5f9; padding: 10px; border-radius: 6px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <strong>Pratinjau Cetak Laporan Crosscheck Store</strong> - Silakan periksa data sebelum mencetak.
        </div>
        <div>
            <button onclick="window.print()" style="padding: 6px 14px; background: #059669; color: #fff; border: 0; border-radius: 6px; font-weight: bold; cursor: pointer;">
                🖨️ Cetak Dokumen (Print)
            </button>
            <button onclick="window.close()" style="padding: 6px 14px; background: #64748b; color: #fff; border: 0; border-radius: 6px; font-weight: bold; cursor: pointer; margin-left: 6px;">
                Tutup
            </button>
        </div>
    </div>

    <!-- Header Dokumen -->
    <div class="header">
        <div>
            <div class="company-title">PT DAIJO INDUSTRIAL</div>
            <div class="doc-title">LAPORAN CROSSCHECK SCAN PRODUKSI KE STORE (WAREHOUSE)</div>
            <div class="meta-info">
                Plant: <strong>{{ $plant === 'ALL' ? 'KBN & KARAWANG' : $plant }}</strong> |
                Tanggal Produksi: <strong>{{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d F Y') }}</strong> |
                Window Cutoff: {{ $startTime->format('d/m/Y H:i') }} - {{ $endTime->format('d/m/Y H:i') }} WIB
            </div>
        </div>
        <div style="text-align: right;" class="meta-info">
            Dicetak Tanggal: <strong>{{ now()->format('d/m/Y H:i:s') }} WIB</strong><br>
            Oleh: <strong>{{ auth()->user()?->name ?: 'Store System' }}</strong><br>
            Status Laporan: <strong>{{ $snapshot->status ?? 'OPEN' }}</strong>
        </div>
    </div>

    <!-- Ringkasan Statistik -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-label">Total Box Discan Produksi</div>
            <div class="metric-value">{{ number_format($metrics['total_boxes']) }} <span style="font-size: 10px; font-weight: normal;">Box</span></div>
            <div style="font-size: 8px; color: #64748b; margin-top: 2px;">{{ number_format($metrics['total_qty']) }} Pcs</div>
        </div>
        <div class="metric-card found">
            <div class="metric-label" style="color: #166534;">✓ Ada di Store (Ditemukan)</div>
            <div class="metric-value" style="color: #166534;">{{ number_format($metrics['found_boxes']) }} <span style="font-size: 10px; font-weight: normal;">Box</span></div>
            <div style="font-size: 8px; color: #166534; margin-top: 2px;">{{ number_format($metrics['found_qty']) }} Pcs ({{ $metrics['accuracy_rate'] }}%)</div>
        </div>
        <div class="metric-card pending">
            <div class="metric-label" style="color: #92400e;">⏳ Belum Dicek (Pending)</div>
            <div class="metric-value" style="color: #92400e;">{{ number_format($metrics['pending_boxes']) }} <span style="font-size: 10px; font-weight: normal;">Box</span></div>
            <div style="font-size: 8px; color: #92400e; margin-top: 2px;">{{ number_format($metrics['pending_qty']) }} Pcs</div>
        </div>
        <div class="metric-card missing">
            <div class="metric-label" style="color: #991b1b;">✕ Tidak Ditemukan / Hilang</div>
            <div class="metric-value" style="color: #991b1b;">{{ number_format($metrics['missing_boxes']) }} <span style="font-size: 10px; font-weight: normal;">Box</span></div>
            <div style="font-size: 8px; color: #991b1b; margin-top: 2px;">{{ number_format($metrics['missing_qty']) }} Pcs</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Akurasi Verifikasi Store</div>
            <div class="metric-value" style="color: #2563eb;">{{ $metrics['accuracy_rate'] }}%</div>
            <div style="font-size: 8px; color: #64748b; margin-top: 2px;">Progress: {{ $metrics['progress_rate'] }}% Selesai</div>
        </div>
    </div>

    @if ($snapshot && $snapshot->notes)
        <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; margin-bottom: 12px; font-size: 9px;">
            <strong>Catatan Khusus Store:</strong> {{ $snapshot->notes }}
        </div>
    @endif

    <!-- Tabel Rincian Box -->
    <table>
        <thead>
            <tr>
                <th style="width: 25px; text-align: center;">No</th>
                <th style="width: 45px;">Plant</th>
                <th style="width: 80px;">No Box / Label</th>
                <th>Part Number & Nama Barang</th>
                <th style="width: 70px;">Customer</th>
                <th style="width: 45px; text-align: right;">Qty</th>
                <th style="width: 55px;">Shift/Mesin</th>
                <th style="width: 70px;">Operator Scan</th>
                <th style="width: 80px;">Status Store</th>
                <th style="width: 75px;">Verifikator Store</th>
                <th>Catatan Store</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @forelse ($items as $item)
                @php
                    $isFound = ($item->store_check_status === 'FOUND');
                    $isNotFound = ($item->store_check_status === 'NOT_FOUND');
                    $shiftVal = $item->dailyItemCode?->shift ?: \App\Services\WmsDeliveryRecapService::determineShiftFromTime($item->created_at);
                    $machineName = $item->dailyItemCode?->user?->name ?: '-';
                    $itemName = $item->masterItem?->item_name ?: ($item->masterItem?->description ?: '-');
                    $customerName = $item->masterItem?->customer?->customer_name ?: ($item->masterItem?->customer_code ?: '-');
                @endphp
                <tr>
                    <td style="text-align: center;">{{ $no++ }}</td>
                    <td><strong>{{ $item->plant }}</strong></td>
                    <td style="font-family: monospace; font-weight: bold;">{{ $item->label }}</td>
                    <td>
                        <strong>{{ $item->item_code }}</strong><br>
                        <span style="color: #64748b;">{{ $itemName }}</span>
                    </td>
                    <td>{{ $customerName }}</td>
                    <td style="text-align: right; font-weight: bold;">{{ number_format($item->quantity) }}</td>
                    <td>S{{ $shiftVal }} / {{ $machineName }}</td>
                    <td>
                        {{ $item->user ?: '-' }}<br>
                        <span style="color: #94a3b8; font-size: 8px;">{{ $item->created_at ? $item->created_at->format('H:i') : '' }}</span>
                    </td>
                    <td>
                        @if ($isFound)
                            <span class="badge badge-found">✓ ADA</span>
                        @elseif ($isNotFound)
                            <span class="badge badge-missing">✕ TIDAK ADA</span>
                        @else
                            <span class="badge badge-pending">⏳ BELUM</span>
                        @endif
                    </td>
                    <td>
                        {{ $item->store_checked_by_name ?: '-' }}<br>
                        <span style="color: #94a3b8; font-size: 8px;">{{ $item->store_checked_at ? $item->store_checked_at->format('H:i') : '' }}</span>
                    </td>
                    <td>{{ $item->store_notes ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align: center; padding: 20px; color: #64748b;">
                        Tidak ada data scan produksi untuk tanggal dan kriteria ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Tanda Tangan & Persetujuan -->
    <div class="signatures">
        <div class="sig-box">
            <div class="sig-title">Diverifikasi Oleh (Checker Store)</div>
            <div class="sig-line">{{ $snapshot->verified_by_name ?? '( ................................ )' }}</div>
            <div style="font-size: 8px; color: #64748b;">Operator / Checker Store</div>
        </div>
        <div class="sig-box">
            <div class="sig-title">Diserahkan Oleh (Operator Produksi)</div>
            <div class="sig-line">( ................................ )</div>
            <div style="font-size: 8px; color: #64748b;">PIC / Leader Produksi</div>
        </div>
        <div class="sig-box">
            <div class="sig-title">Diperiksa Oleh (Leader Store)</div>
            <div class="sig-line">( ................................ )</div>
            <div style="font-size: 8px; color: #64748b;">Leader Warehouse / Store</div>
        </div>
        <div class="sig-box">
            <div class="sig-title">Disetujui Oleh (Supervisor Store)</div>
            <div class="sig-line">( ................................ )</div>
            <div style="font-size: 8px; color: #64748b;">Supervisor Store / Head</div>
        </div>
    </div>

</body>
</html>
