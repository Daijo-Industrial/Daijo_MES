<!DOCTYPE html>
<html>
<head>
    <title>Zebra Label Kembar 40x15</title>
    <meta charset="UTF-8">
    <style>
        @page {
            size: 84mm 16mm;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f3f4f6;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Tombol print */
        .print-btn-container {
            position: fixed;
            top: 15px;
            right: 15px;
            z-index: 9999;
        }

        .print-btn {
            background: #2563eb;
            color: white;
            padding: 8px 18px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 13px;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .print-btn:hover {
            background: #1d4ed8;
        }

        .preview-wrapper {
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        /* 1 Baris Cetak = 2 Label Kembar (Ukuran Total: 82mm - 84mm x 15mm) */
        .label-row {
            width: 82mm;
            height: 15mm;
            display: flex;
            align-items: center;
            padding: 0;
            margin: 0 auto;
            page-break-after: always;
            break-after: page;
            background-color: transparent;
            box-sizing: border-box;
        }

        /* Masing-masing Label berukuran 40mm x 15mm */
        .label {
            width: 40mm;
            height: 15mm;
            border: 0.6pt solid #000;
            display: flex;
            align-items: center;
            padding: 0.8mm;
            margin: 0;
            box-sizing: border-box;
            background-color: #ffffff;
            overflow: hidden;
        }

        /* Jarak tengah (Gutter) antar 2 label standard 2-up: 40 + 2 + 40 = 82mm */
        .label + .label {
            margin-left: 2mm;
        }

        /* QR Code di sebelah kiri */
        .qr-code {
            width: 12.5mm;
            height: 12.5mm;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .qr-code img {
            width: 12.5mm;
            height: 12.5mm;
            image-rendering: pixelated;
        }

        /* Info teks di sebelah kanan */
        .info {
            flex: 1;
            margin-left: 1.2mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 12.5mm;
            overflow: hidden;
            line-height: 1.1;
        }

        .header-line {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 6pt;
            font-weight: 800;
            border-bottom: 0.4pt solid #000;
            padding-bottom: 0.3mm;
        }

        .header-title {
            letter-spacing: -0.2px;
        }

        .label-badge {
            background: #000;
            color: #fff;
            padding: 0 1.2mm;
            border-radius: 1mm;
            font-size: 6.5pt;
            font-weight: 900;
            font-family: monospace;
        }

        .part-no {
            font-size: 7pt;
            font-weight: 900;
            font-family: monospace;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 0.3mm;
            color: #000;
        }

        .part-name {
            font-size: 5pt;
            color: #222;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 25mm;
        }

        .footer-line {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 4.5pt;
            color: #444;
            margin-top: 0.2mm;
        }

        @media print {
            body {
                background: white;
            }

            .print-btn-container {
                display: none;
            }

            .preview-wrapper {
                display: block;
                padding: 0;
                margin: 0;
                gap: 0;
            }

            .label-row {
                page-break-after: always;
                break-after: page;
            }

            * {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

    <div class="print-btn-container">
        <button class="print-btn" onclick="window.print()">🖨️ Cetak Zebra 40x15 (Kembar)</button>
    </div>

    <div class="preview-wrapper">
        @foreach ($barcodes as $barcode)
            {{-- 1 Baris roll = 2 Label Kembar dengan data identik yang sama --}}
            <div class="label-row">
                
                {{-- Label KIRI (40x15) --}}
                <div class="label">
                    <div class="qr-code">
                        <img src="{{ $barcode['barcodeUrl'] }}" alt="QR">
                    </div>
                    <div class="info">
                        <div class="header-line">
                            <span class="header-title">DAIJO MES</span>
                            <span class="label-badge">#{{ $barcode['label'] }}</span>
                        </div>
                        <div class="part-no">{{ $barcode['partno'] }}</div>
                        <div class="part-name">{{ $barcode['partname'] ?: '-' }}</div>
                        <div class="footer-line">
                            <span>{{ date('d/m/y H:i', strtotime($generated_at)) }}</span>
                            <span>TRIAL</span>
                        </div>
                    </div>
                </div>

                {{-- Label KANAN (40x15) - DATA IDENTIK SAMA PERSIS --}}
                <div class="label">
                    <div class="qr-code">
                        <img src="{{ $barcode['barcodeUrl'] }}" alt="QR">
                    </div>
                    <div class="info">
                        <div class="header-line">
                            <span class="header-title">DAIJO MES</span>
                            <span class="label-badge">#{{ $barcode['label'] }}</span>
                        </div>
                        <div class="part-no">{{ $barcode['partno'] }}</div>
                        <div class="part-name">{{ $barcode['partname'] ?: '-' }}</div>
                        <div class="footer-line">
                            <span>{{ date('d/m/y H:i', strtotime($generated_at)) }}</span>
                            <span>TRIAL</span>
                        </div>
                    </div>
                </div>

            </div>
        @endforeach
    </div>

</body>
</html>
