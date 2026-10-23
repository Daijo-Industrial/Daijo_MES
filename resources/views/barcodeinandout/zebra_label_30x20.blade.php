<!DOCTYPE html>
<html>
<head>
    <title>Zebra / Argox Label 30x20 (2x Tiap Label)</title>
    <meta charset="UTF-8">
    <style>
        @page {
            size: 30mm 20mm;
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
            background: #d97706;
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
            background: #b45309;
        }

        .preview-wrapper {
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        /* 1 Baris Cetak = 1 Label (Ukuran: 30mm x 20mm) */
        .label-row {
            width: 30mm;
            height: 20mm;
            page-break-after: always;
            break-after: page;
            margin: 0 auto;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: transparent;
        }

        /* Box Label Tunggal 30mm x 20mm (Compact 2-Column: QR Kiri, Detail Kanan) */
        .label {
            width: 30mm;
            height: 20mm;
            border: 0.5pt solid #000;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            padding: 0.8mm;
            box-sizing: border-box;
            background-color: #ffffff;
            overflow: hidden;
            gap: 0.8mm;
        }

        /* Kolom Kiri: QR Code (Besar & Mudah di-scan) */
        .qr-col {
            width: 14mm;
            height: 14mm;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .qr-col img {
            width: 14mm;
            height: 14mm;
            image-rendering: pixelated;
        }

        /* Kolom Kanan: Label #, Part Code, Part Name, Tanggal */
        .info-col {
            flex: 1;
            height: 15mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            line-height: 1.1;
        }

        .top-row {
            display: flex;
            align-items: center;
            justify-content: flex-start;
        }

        .label-badge {
            background: #000;
            color: #fff;
            padding: 0 1.2mm;
            border-radius: 0.8mm;
            font-size: 6.5pt;
            font-weight: 900;
            font-family: monospace;
            line-height: 1.15;
            display: inline-block;
        }

        .part-no {
            font-size: 6.5pt;
            font-weight: 900;
            font-family: monospace;
            color: #000;
            word-break: break-all;
            line-height: 1.1;
            margin-top: 0.3mm;
        }

        .part-name {
            font-size: 4.8pt;
            color: #222;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.1;
            word-break: break-word;
        }

        .date-row {
            font-size: 4.2pt;
            color: #444;
            line-height: 1;
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
        <button class="print-btn" onclick="window.print()">🖨️ Cetak Zebra / Argox 30x20 (2x per Label)</button>
    </div>

    <div class="preview-wrapper">
        @foreach ($barcodes as $barcode)
            {{-- Tiap label dicetak 2 kali berurutan: #1, #1, #2, #2, dst --}}
            @for ($copy = 1; $copy <= 2; $copy++)
                <div class="label-row">
                    <div class="label">
                        {{-- Kolom Kiri: QR Code --}}
                        <div class="qr-col">
                            <img src="{{ $barcode['barcodeUrl'] }}" alt="QR">
                        </div>

                        {{-- Kolom Kanan: Compact Info (Label #, Part Code, Part Name, Tanggal) --}}
                        <div class="info-col">
                            <div class="top-row">
                                <span class="label-badge">#{{ $barcode['label'] }}</span>
                            </div>
                            <div class="part-no" title="{{ $barcode['partno'] }}">
                                {{ $barcode['partno'] }}
                            </div>
                            <div class="part-name">
                                {{ $barcode['partname'] ?: '-' }}
                            </div>
                            <div class="date-row">
                                {{ date('d/m/y H:i', strtotime($generated_at)) }}
                            </div>
                        </div>
                    </div>
                </div>
            @endfor
        @endforeach
    </div>

</body>
</html>
