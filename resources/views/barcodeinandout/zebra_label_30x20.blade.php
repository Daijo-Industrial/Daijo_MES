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
            padding: 0.5mm 0.6mm;
            box-sizing: border-box;
            background-color: #ffffff;
            overflow: hidden;
            gap: 0.5mm;
        }

        /* Kolom Kiri: QR Code (Ukuran 12.5mm x 12.5mm) & Item Code di Bawah */
        .qr-col {
            width: 13mm;
            height: 18mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            overflow: hidden;
            box-sizing: border-box;
            padding: 0.4mm 0;
            text-align: center;
        }

        .qr-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            overflow: hidden;
        }

        .qr-wrapper img {
            width: 12.5mm;
            height: 12.5mm;
            image-rendering: pixelated;
        }

        .qr-item-code {
            font-size: 3.5pt;
            font-weight: 700;
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            line-height: 1;
            white-space: nowrap;
            letter-spacing: -0.1px;
            text-align: center;
            width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Kolom Kanan: No Label Gede & Tanggal Jam WIB */
        .info-col {
            flex: 1;
            min-width: 0;
            height: 18mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            overflow: hidden;
            box-sizing: border-box;
            padding: 0.4mm 0.5mm;
            text-align: center;
        }

        .label-number-wrapper {
            flex: 1;
            min-height: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            overflow: hidden;
            box-sizing: border-box;
        }

        .label-number {
            font-weight: 900;
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            line-height: 1.05;
            letter-spacing: -0.2px;
            text-align: center;
            display: inline-block;
            white-space: nowrap;
            max-width: 100%;
        }

        .date-row {
            font-size: 3.5pt;
            font-weight: 700;
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            line-height: 1;
            white-space: nowrap;
            letter-spacing: -0.1px;
            text-align: center;
            width: 100%;
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
        <button class="print-btn" onclick="autoFitLabels(); window.print()">Cetak Zebra / Argox 30x20 (2x per Label)</button>
    </div>

    <div class="preview-wrapper">
        @foreach ($barcodes as $barcode)
            {{-- Tiap label dicetak 2 kali berurutan: #1, #1, #2, #2, dst --}}
            @for ($copy = 1; $copy <= 2; $copy++)
                <div class="label-row">
                    <div class="label">
                        {{-- Kolom Kiri: QR Code & Kode Item di Bawah --}}
                        <div class="qr-col">
                            <div class="qr-wrapper">
                                <img src="{{ $barcode['barcodeUrl'] }}" alt="QR">
                            </div>
                            <div class="qr-item-code" title="{{ $barcode['partno'] }}">
                                {{ $barcode['partno'] }}
                            </div>
                        </div>

                        {{-- Kolom Kanan: No Label Gede & Tanggal Jam WIB di Bawah --}}
                        <div class="info-col">
                            <div class="label-number-wrapper">
                                @php
                                    $labelStr = '#' . $barcode['label'];
                                    $labelLen = strlen($labelStr);
                                    if ($labelLen <= 2) {
                                        $fontSizePt = 18;
                                    } elseif ($labelLen == 3) {
                                        $fontSizePt = 15;
                                    } elseif ($labelLen == 4) {
                                        $fontSizePt = 12;
                                    } elseif ($labelLen == 5) {
                                        $fontSizePt = 9.8;
                                    } elseif ($labelLen == 6) {
                                        $fontSizePt = 8.2;
                                    } elseif ($labelLen == 7) {
                                        $fontSizePt = 7;
                                    } else {
                                        $fontSizePt = max(5.5, round(48 / $labelLen, 1));
                                    }
                                @endphp
                                <span class="label-number" style="font-size: {{ $fontSizePt }}pt;">{{ $labelStr }}</span>
                            </div>
                            <div class="date-row">
                                {{ \Carbon\Carbon::parse($generated_at ?? now())->timezone('Asia/Jakarta')->format('d/m/y H:i') }} WIB
                            </div>
                        </div>
                    </div>
                </div>
            @endfor
        @endforeach
    </div>

    <script>
        function autoFitLabels() {
            var wrappers = document.querySelectorAll('.label-number-wrapper');
            wrappers.forEach(function (wrapper) {
                var el = wrapper.querySelector('.label-number');
                if (!el) return;

                var maxW = wrapper.clientWidth - 2;
                var maxH = wrapper.clientHeight - 2;

                if (maxW <= 0 || maxH <= 0) return;

                var currentSize = parseFloat(window.getComputedStyle(el).fontSize);
                while ((el.scrollWidth > maxW || el.scrollHeight > maxH) && currentSize > 5) {
                    currentSize -= 0.3;
                    el.style.fontSize = currentSize + 'px';
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', autoFitLabels);
        } else {
            autoFitLabels();
        }
        window.addEventListener('load', autoFitLabels);
        window.addEventListener('beforeprint', autoFitLabels);
    </script>

</body>
</html>
