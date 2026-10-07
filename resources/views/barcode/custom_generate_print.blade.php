<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Custom Barcode Labels</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: #f3f4f6;
            color: #000;
            padding: 10px 0;
        }

        /* Floating action buttons */
        .no-print-bar {
            background-color: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            width: 100%;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            border-radius: 8px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary {
            background-color: #4f46e5;
            color: #ffffff;
            border: 1px solid #4338ca;
        }

        .btn-primary:hover {
            background-color: #4338ca;
        }

        .btn-secondary {
            background-color: #ffffff;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .btn-secondary:hover {
            background-color: #f9fafb;
        }

        .a4-sheet-container {
            width: 200mm;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .a4-sheet {
            width: 200mm;
            height: 277mm;
            display: grid;
            grid-template-columns: 97mm 97mm;
            grid-template-rows: repeat(6, 43mm);
            gap: 2mm 4mm;
            padding: 1mm 1mm;
            page-break-after: always;
            box-sizing: border-box;
            overflow: hidden;
            background-color: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
            border: 1px solid #e5e7eb;
        }

        .a4-sheet:last-child {
            page-break-after: auto;
            margin-bottom: 0;
        }

        /* Single Sticker Label styling */
        .sticker-label {
            width: 97mm;
            height: 43mm;
            border: 1.5px solid #000000;
            border-radius: 2px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-sizing: border-box;
            background-color: #ffffff;
        }

        /* Top Section: Header + Item Info + Top-Right Sequence & Code Box */
        .label-top-container {
            height: 17mm;
            display: flex;
            border-bottom: 1.5px solid #000000;
            box-sizing: border-box;
        }

        .top-left-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-sizing: border-box;
        }

        .company-header {
            height: 6.5mm;
            display: flex;
            align-items: center;
            padding-left: 2mm;
            border-bottom: 1px solid #000000;
            box-sizing: border-box;
        }

        .company-logo {
            height: 4.8mm;
            width: auto;
            max-width: 9mm;
            object-fit: contain;
            vertical-align: middle;
            margin-right: 1.5mm;
            display: inline-block;
        }

        .item-info-header {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            line-height: 1.15;
            text-align: center;
            box-sizing: border-box;
            padding: 0.5mm 0;
        }

        .item-code {
            font-size: 11pt;
            font-weight: 900;
            letter-spacing: 0.5px;
        }

        .item-name {
            font-size: 6.8pt;
            font-weight: 900;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 77mm;
        }

        .top-right-box {
            width: 18mm;
            height: 100%;
            border-left: 1.5px solid #000000;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
            background-color: #fafafa;
        }

        .default-seq-no {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11pt;
            font-weight: 900;
        }

        .sharp-seq-no {
            height: 6mm;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8pt;
            font-weight: 900;
            border-bottom: 1.5px solid #000000;
        }

        .sharp-code-box {
            height: 11mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 11pt;
            font-weight: 900;
            line-height: 1.05;
            letter-spacing: 0.5px;
        }

        /* Bottom Section: Form Details + QR Code */
        .label-body {
            flex: 1;
            display: flex;
            height: calc(43mm - 17mm);
            box-sizing: border-box;
        }

        .body-details {
            flex: 1;
            border-right: 1.5px solid #000000;
            padding: 1.2mm 2.2mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            font-size: 6.8pt;
            font-weight: 900;
            line-height: 1.2;
            box-sizing: border-box;
        }

        .detail-row {
            display: flex;
            white-space: nowrap;
        }

        .detail-label {
            width: 19mm;
            flex-shrink: 0;
        }

        .detail-divider {
            width: 2.5mm;
            flex-shrink: 0;
        }

        .detail-value {
            flex-grow: 1;
            font-weight: 900;
        }

        .shift-active {
            border: 1px solid #000000;
            border-radius: 50%;
            padding: 0px 3px;
            font-weight: 900;
            display: inline-block;
            line-height: 1;
        }

        .body-qr-container {
            width: 25mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1mm 0;
            flex-shrink: 0;
            box-sizing: border-box;
        }

        .qr-image {
            width: 15mm;
            height: 15mm;
            display: block;
        }

        .rohs-box {
            width: 21mm;
            border: 1.2px solid #000000;
            text-align: center;
            font-size: 5pt;
            font-weight: 900;
            padding: 1px 0;
            margin-top: 1mm;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        /* ITSP Specific Layout (8 labels per sheet: 2 cols x 4 rows, printer-safe A4) */
        .a4-sheet.itsp-sheet {
            width: 200mm;
            height: 278mm;
            grid-template-columns: 98mm 98mm;
            grid-template-rows: repeat(4, 66.5mm);
            gap: 2mm 2mm;
            padding: 2mm 1mm;
            box-sizing: border-box;
        }

        .sticker-label.sticker-label-itsp {
            width: 98mm;
            height: 66.5mm;
            box-sizing: border-box;
        }

        .itsp-container {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
            font-size: 7pt;
            font-weight: 900;
        }

        .itsp-top-header {
            border-bottom: 1.5px solid #000000;
            padding: 0.8mm 1.5mm 0.3mm 1.5mm;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
            background-color: #ffffff;
            height: 13.5mm;
            justify-content: space-between;
        }

        .itsp-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            line-height: 1;
        }

        .itsp-main-code {
            text-align: center;
            letter-spacing: 0.5px;
            line-height: 1.1;
            display: flex;
            justify-content: center;
            align-items: baseline;
        }

        .itsp-body {
            flex: 1;
            display: flex;
            box-sizing: border-box;
            overflow: hidden;
            height: calc(66.5mm - 13.5mm);
        }

        .itsp-left-table {
            flex: 1;
            display: flex;
            flex-direction: column;
            border-right: 1.5px solid #000000;
            box-sizing: border-box;
            overflow: hidden;
        }

        .itsp-row {
            display: flex;
            border-bottom: 1.2px solid #000000;
            box-sizing: border-box;
            height: 5.4mm;
            align-items: stretch;
            line-height: 1.1;
        }

        .itsp-cell-label {
            width: 16mm;
            border-right: 1.2px solid #000000;
            padding: 0.3mm 0.8mm;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            background-color: #fafafa;
            font-size: 6.2pt;
            font-weight: 900;
        }

        .itsp-cell-val {
            flex: 1;
            padding: 0.3mm 0.8mm;
            display: flex;
            align-items: center;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            font-size: 7pt;
            font-weight: 900;
        }

        .itsp-bottom-grid {
            flex: 1;
            display: flex;
            box-sizing: border-box;
            height: calc(66.5mm - 13.5mm - (5.4mm * 5));
        }

        .itsp-color-pos-box {
            width: 24mm;
            border-right: 1.2px solid #000000;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
            flex-shrink: 0;
        }

        .itsp-color-cell {
            flex: 1;
            border-bottom: 1.2px solid #000000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10pt;
            font-weight: 900;
            text-align: center;
            text-transform: uppercase;
        }

        .itsp-pos-cell {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13pt;
            font-weight: 900;
            text-align: center;
        }

        .itsp-stamp-box {
            flex: 1;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
            overflow: hidden;
        }

        .itsp-stamp-cols {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            box-sizing: border-box;
        }

        .itsp-stamp-col {
            border-right: 1.2px solid #000000;
            display: flex;
            flex-direction: column;
            text-align: center;
            box-sizing: border-box;
        }

        .itsp-stamp-col:last-child {
            border-right: none;
        }

        .itsp-stamp-head {
            border-bottom: 1.2px solid #000000;
            padding: 0.3mm 0;
            font-size: 6pt;
            background-color: #fafafa;
            font-weight: 900;
        }

        .itsp-stamp-body {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 6.8pt;
            font-weight: 900;
        }

        .itsp-stamp-foot {
            border-top: 1.2px solid #000000;
            padding: 0.3mm 0.8mm;
            font-size: 5pt;
            display: flex;
            justify-content: space-between;
            font-weight: 900;
        }

        .itsp-right-qr {
            width: 24mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            padding: 0.8mm 0.5mm;
            box-sizing: border-box;
            flex-shrink: 0;
        }

        /* Print media styles */
        @media print {
            @page {
                size: A4 portrait;
                margin: 0;
            }

            body {
                background-color: #ffffff;
                padding: 0;
                margin: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print-bar {
                display: none;
            }

            .a4-sheet-container {
                width: 210mm;
                margin: 0;
                padding: 0;
            }

            .a4-sheet {
                box-shadow: none;
                margin-bottom: 0;
                border: none;
                padding: 5mm 6mm 15mm 6mm;
                width: 210mm;
                height: 297mm;
                box-sizing: border-box;
                overflow: hidden;
                page-break-after: always;
                page-break-inside: avoid;
            }

            .a4-sheet:last-child {
                page-break-after: auto;
            }

            .a4-sheet.itsp-sheet {
                padding: 6mm 5mm 6mm 5mm;
                grid-template-columns: 98mm 98mm;
                grid-template-rows: repeat(4, 66.5mm);
                gap: 2mm 2mm;
                width: 210mm;
                height: 297mm;
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body>

    <!-- Print Navigation Bar -->
    <div class="no-print-bar">
        <a href="{{ route('barcode.custom.form') }}" class="btn btn-secondary">
            ⬅️ Kembali ke Form
        </a>
        <div style="font-size: 13px; font-weight: bold; color: #4338ca;">
            @if(($barcodeType ?? 'default') === 'sharp')
                Format: Customer SHARP (QR Code & Kode Bulan-Tahun)
            @elseif(($barcodeType ?? 'default') === 'yanfeng')
                Format: Customer YANFENG (QR Code & QAD / Foreign Name)
            @elseif(($barcodeType ?? 'default') === 'itsp')
                Format: Customer PT. ITSP (Part Tag: 8 label/sheet, Model, Color, Position, SP)
            @else
                Format: Standard Default (QR Code)
            @endif
        </div>
        <button onclick="window.print()" class="btn btn-primary">
            🖨️ Print Now
        </button>
    </div>

    <!-- Sticker Labels Grid container -->
    <div class="a4-sheet-container">
        @php
            $chunkSize = ($barcodeType === 'itsp') ? 8 : 12;
            $chunks = array_chunk($labels, $chunkSize);
        @endphp

        @foreach($chunks as $chunk)
            <div class="a4-sheet {{ ($barcodeType === 'itsp') ? 'itsp-sheet' : '' }}">
                @foreach($chunk as $label)
                    @if(($label['barcode_type'] ?? 'default') === 'itsp')
                        <div class="sticker-label sticker-label-itsp">
                            <div class="itsp-container">
                                <!-- Top Header -->
                                <div class="itsp-top-header">
                                    <div class="itsp-header-row">
                                        <div style="display: flex; align-items: center;">
                                            @if(!empty($logoBase64))
                                                <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Logo" class="company-logo">
                                            @else
                                                <img src="{{ asset('picture/logo-dj.png') }}" alt="Logo" class="company-logo" onerror="this.style.display='none'">
                                            @endif
                                            <span style="font-size: 8pt; font-weight: 900; letter-spacing: 0.2px; text-transform: uppercase;">PT DAIJO INDUSTRIAL</span>
                                        </div>
                                        <span style="font-size: 6.8pt; font-weight: 900; letter-spacing: 0.3px;">PART TAG</span>
                                    </div>
                                    <div class="itsp-main-code">
                                        @if(!empty($label['half_code_1']) || !empty($label['half_code_2']))
                                            @php
                                                $posClean = strtoupper(trim((string)($label['position'] ?? '')));
                                                $isLeft = ($label['enlarged_half_code'] ?? null) === 1 
                                                    || in_array($posClean, ['LH', 'LEFT', 'L'])
                                                    || str_contains($posClean, 'LEFT')
                                                    || str_contains($posClean, 'LH');
                                            @endphp
                                            @if($isLeft)
                                                <span style="font-size: 16pt; font-weight: 900;">{{ $label['half_code_1'] }}</span><span style="font-size: 11pt; font-weight: 900;">{{ $label['half_code_2'] }}</span>
                                            @else
                                                <span style="font-size: 11pt; font-weight: 900;">{{ $label['half_code_1'] }}</span><span style="font-size: 16pt; font-weight: 900;">{{ $label['half_code_2'] }}</span>
                                            @endif
                                        @else
                                            <span style="font-size: 13.5pt; font-weight: 900;">{{ $label['itsp_code'] }}</span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Body -->
                                <div class="itsp-body">
                                    <!-- Left Table -->
                                    <div class="itsp-left-table">
                                        <!-- Part Name -->
                                        <div class="itsp-row">
                                            <div class="itsp-cell-label">PART NAME</div>
                                            <div class="itsp-cell-val" style="font-weight: 900; font-size: 7.2pt; text-transform: uppercase;">
                                                {{ $label['item_name'] }}
                                            </div>
                                        </div>

                                        <!-- Model & Qty -->
                                        <div class="itsp-row">
                                            <div class="itsp-cell-label">MODEL</div>
                                            <div class="itsp-cell-val" style="width: 20mm; flex-shrink: 0; border-right: 1.2px solid #000000; font-size: 7.2pt;">
                                                {{ $label['model'] ?: '-' }}
                                            </div>
                                            <div class="itsp-cell-val" style="font-size: 8pt; font-weight: 900;">
                                                QTY : {{ $label['quantity'] }}
                                            </div>
                                        </div>

                                        <!-- Part Code & SPK -->
                                        <div class="itsp-row">
                                            <div class="itsp-cell-label">PART CODE</div>
                                            <div class="itsp-cell-val" style="width: 20mm; flex-shrink: 0; border-right: 1.2px solid #000000; font-size: 7.5pt;">
                                                {{ $label['qad'] ?: '-' }}
                                            </div>
                                            <div class="itsp-cell-val" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                                <span style="font-size: 7.2pt; font-weight: 900; white-space: nowrap;">NO SPK: {{ $label['spk_number'] }}</span>
                                                @if(!empty($label['is_sp']))
                                                    <span style="background-color: #000000; color: #ffffff; padding: 0.5px 3px; font-size: 5.8pt; font-weight: 900; border-radius: 1px; line-height: 1.2; flex-shrink: 0; margin-left: 2px;">SP</span>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Customer -->
                                        <div class="itsp-row">
                                            <div class="itsp-cell-label">CUSTOMER</div>
                                            <div class="itsp-cell-val" style="width: 20mm; flex-shrink: 0; border-right: 1.2px solid #000000; font-size: 7.2pt;">
                                                {{ $label['customer'] ?: 'PT. ITSP' }}
                                            </div>
                                            <div class="itsp-cell-val" style="font-size: 6.8pt; font-weight: 900;">
                                                FROM: {{ $label['warehouse'] }}
                                            </div>
                                        </div>

                                        <!-- Prod Date -->
                                        <div class="itsp-row">
                                            <div class="itsp-cell-label">PROD. DATE</div>
                                            <div class="itsp-cell-val" style="font-size: 6.8pt; font-weight: 900;">
                                                {{ $label['prod_date'] }}
                                            </div>
                                        </div>

                                        <!-- Bottom Grid: Color/Pos + Stamp -->
                                        <div class="itsp-bottom-grid">
                                            <div class="itsp-color-pos-box">
                                                <div class="itsp-color-cell">{{ $label['color'] ?: '-' }}</div>
                                                <div class="itsp-pos-cell">{{ $label['position'] ?: '-' }}</div>
                                            </div>
                                            <div class="itsp-stamp-box">
                                                <div class="itsp-stamp-cols">
                                                    <div class="itsp-stamp-col">
                                                        <div class="itsp-stamp-head">PROD</div>
                                                        <div class="itsp-stamp-body">&nbsp;</div>
                                                    </div>
                                                    <div class="itsp-stamp-col">
                                                        <div class="itsp-stamp-head">QUALITY</div>
                                                        <div class="itsp-stamp-body">&nbsp;</div>
                                                    </div>
                                                    <div class="itsp-stamp-col">
                                                        <div class="itsp-stamp-head">WH</div>
                                                        <div class="itsp-stamp-body">&nbsp;</div>
                                                    </div>
                                                </div>
                                                <div class="itsp-stamp-foot">
                                                    <span>Rev.01</span>
                                                    <span>Effective Date :</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right QR Code -->
                                    <div class="itsp-right-qr">
                                        <div style="font-size: 8.5pt; font-weight: 900;">#{{ $label['label_no'] }}</div>
                                        <img src="data:image/png;base64,{{ $label['qr_code_base64'] }}" alt="QR" class="qr-image" style="width: 17mm; height: 17mm;">
                                        <div class="rohs-box" style="width: 20mm; font-size: 6pt; padding: 1.5px 0; margin-top: 0;">RoHS FREE</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="sticker-label">
                            
                            <!-- Top Section: Header + Item Info + Top-Right Sequence & Code Box -->
                            <div class="label-top-container">
                                <div class="top-left-content">
                                    <div class="company-header">
                                        @if(!empty($logoBase64))
                                            <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Logo" class="company-logo">
                                        @else
                                            <img src="{{ asset('picture/logo-dj.png') }}" alt="Logo" class="company-logo" onerror="this.style.display='none'">
                                        @endif
                                        <span style="font-size: 8pt; font-weight: 900; letter-spacing: 0.2px; text-transform: uppercase;">PT DAIJO INDUSTRIAL</span>
                                    </div>
                                    <div class="item-info-header">
                                        @if(($label['barcode_type'] ?? 'default') === 'yanfeng' && !empty($label['qad']))
                                            <div style="display: flex; justify-content: space-between; align-items: baseline; width: 100%; padding: 0 2mm;">
                                                <div class="item-code" style="text-align: left;">{{ $label['item_code'] }}</div>
                                                <div style="font-size: 8.5pt; font-weight: 900; letter-spacing: 0.3px; text-align: right;">{{ $label['qad'] }}</div>
                                            </div>
                                            <div class="item-name" style="text-align: left; padding-left: 2mm; max-width: 77mm;">{{ $label['item_name'] }}</div>
                                        @else
                                            <div class="item-code">{{ $label['item_code'] }}</div>
                                            <div class="item-name">{{ $label['item_name'] }}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="top-right-box">
                                    @if(($label['barcode_type'] ?? 'default') === 'sharp')
                                        <div class="sharp-seq-no">{{ $label['label_no'] }}</div>
                                        <div class="sharp-code-box">
                                            <div>{{ $label['year_code'] }}</div>
                                            <div>{{ $label['month_code'] }}</div>
                                        </div>
                                    @else
                                        <div class="default-seq-no">{{ $label['label_no'] }}</div>
                                    @endif
                                </div>
                            </div>

                            <!-- Bottom Section: Logistics details & QR -->
                            <div class="label-body">
                                
                                <!-- Left: Form details -->
                                <div class="body-details">
                                    <div class="detail-row">
                                        <div class="detail-label">SPK NO</div>
                                        <div class="detail-divider">:</div>
                                        <div class="detail-value" style="display: flex; justify-content: space-between; width: 100%;">
                                            <span style="display: inline-flex; align-items: center; gap: 4px; vertical-align: middle;">
                                                <span>{{ $label['spk_number'] }}</span>
                                                @if(!empty($label['is_trial']))
                                                    <span style="color: #dc2626; border: 1px solid #dc2626; font-size: 5.5pt; font-weight: 900; padding: 0 3px; border-radius: 2px; line-height: 1.2;">TRIAL</span>
                                                @endif
                                            </span>
                                            <span style="font-weight: 900; margin-right: 1mm;">FROM : {{ $label['warehouse'] }}</span>
                                        </div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">PROD DATE</div>
                                        <div class="detail-divider">:</div>
                                        <div class="detail-value">
                                            @if(($label['barcode_type'] ?? 'default') === 'sharp')
                                                <div style="display: flex; justify-content: flex-end; width: 100%; padding-right: 2mm;">
                                                    <span>{{ $label['prod_date_formatted'] }}</span>
                                                </div>
                                            @else
                                                {{ $label['prod_date'] }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">OPERATOR</div>
                                        <div class="detail-divider">:</div>
                                        <div class="detail-value">{{ $label['operator'] }}</div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">QTY</div>
                                        <div class="detail-divider">:</div>
                                        <div class="detail-value" style="display: flex; justify-content: space-between; width: 100%;">
                                            <span style="font-size: 8pt; font-weight: 900;">{{ $label['quantity'] }}</span>
                                            <span style="font-weight: 900; margin-right: 1mm;">
                                                SHIFT : 
                                                @if ($label['shift'] === 'I')
                                                    <span class="shift-active">I</span> / II / III
                                                @elseif ($label['shift'] === 'II')
                                                    I / <span class="shift-active">II</span> / III
                                                @else
                                                    I / II / <span class="shift-active">III</span>
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">CUSTOMER</div>
                                        <div class="detail-divider">:</div>
                                        <div class="detail-value">{{ $label['customer'] }}</div>
                                    </div>
                                </div>

                                <!-- Right: QR Code & RoHS FREE -->
                                <div class="body-qr-container">
                                    <img src="data:image/png;base64,{{ $label['qr_code_base64'] }}" alt="QR" class="qr-image">
                                    <div class="rohs-box">RoHS FREE</div>
                                </div>
                            </div>

                        </div>
                    @endif
                @endforeach

                <!-- If current page chunk has less than $chunkSize items, fill with invisible/empty slots to preserve grid placement -->
                @if(count($chunk) < $chunkSize)
                    @for($i = 0; $i < ($chunkSize - count($chunk)); $i++)
                        <div class="sticker-label {{ ($barcodeType === 'itsp') ? 'sticker-label-itsp' : '' }}" style="visibility: hidden;"></div>
                    @endfor
                @endif
            </div>
        @endforeach
    </div>

</body>
</html>
