<!DOCTYPE html>
<html>
<head>
    <title>Zebra Label 50x35</title>
    <style>
        @page {
            size: 102mm 35mm;
            margin: 0;
        }

        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            background-color: #f3f4f6;
        }

        .label-row {
            display: flex;
            justify-content: flex-start;
            gap: 2mm; /* Gutter between two labels */
            width: 102mm;
            height: 35mm;
            page-break-after: always;
            background-color: transparent;
        }

        .barcode-item {
            width: 50mm;
            height: 35mm;
            border: 1px solid #000;
            box-sizing: border-box;
            display: grid;
            grid-template-columns: 1.5fr 1fr; /* Two main columns */
            grid-template-rows: auto 1fr auto; /* Title/No row, Content row, Footer row */
            overflow: hidden;
            background-color: white;
        }

        /* Common Box Style from A4 design */
        .box {
            border: 0.5pt solid #000;
            display: flex;
            align-items: center;
            padding: 1mm;
            overflow: hidden;
        }

        .box-title {
            grid-column: span 1;
            font-weight: bold;
            font-size: 7pt;
            background-color: #f0f0f0;
            justify-content: center;
            text-align: center;
            line-height: 1.1;
            border-top: none;
            border-left: none;
        }

        .box-no {
            grid-column: span 1;
            font-weight: bold;
            font-size: 10pt;
            justify-content: center;
            background-color: #000;
            color: #fff;
            border-top: none;
            border-right: none;
        }

        .box-data {
            grid-column: span 1;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            padding: 1.5mm;
            border-left: none;
        }

        .part-no {
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 0.5mm;
            color: #000;
        }

        .part-name {
            font-size: 7pt;
            line-height: 1.1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            color: #333;
        }

        .box-qr {
            grid-column: span 1;
            grid-row: span 1; 
            justify-content: center;
            padding: 1mm;
            border-right: none;
        }

        .box-qr img {
            width: 18mm;
            height: 18mm;
        }

        .box-footer {
            grid-column: span 2;
            font-size: 5.5pt;
            justify-content: space-between;
            background-color: #f9f9f9;
            border-bottom: none;
            border-left: none;
            border-right: none;
        }

        /* Helper for screen view */
        @media screen {
            body {
                padding: 20px;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }
            .barcode-item {
                box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            }
            .no-print {
                margin-bottom: 10px;
            }
        }

        @media print {
            body {
                background-color: transparent;
                padding: 0;
            }
            .barcode-item {
                border: 1px solid #000; /* Ensure border prints */
            }
            .no-print {
                display: none;
            }
        }

        .print-btn {
            background: #2563eb;
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }
    </style>
</head>
<body>

    <button class="print-btn no-print" onclick="window.print()">Print Zebra Labels (50x35)</button>

    @foreach (array_chunk($barcodes, 2) as $pair)
        <div class="label-row">
            @foreach ($pair as $barcode)
                <div class="barcode-item">
                    <!-- Row 1: Title and NO -->
                    <div class="box box-title">QR PACKAGING</div>
                    <div class="box box-no">#{{ $barcode['label'] }}</div>
                    
                    <!-- Row 2: Part Data and QR Code -->
                    <div class="box box-data">
                        <div class="part-no">{{ $barcode['partno'] }}</div>
                        <div class="part-name">{{ $barcode['partname'] }}</div>
                    </div>
                    <div class="box box-qr">
                        <img src="{{ $barcode['barcodeUrl'] }}" alt="QR">
                    </div>

                    <!-- Row 3: Footer -->
                    <div class="box box-footer">
                        <span>{{ \Carbon\Carbon::parse($generated_at ?? now())->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</span>
                        <span style="font-weight: bold;">DAIJO MES</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach

</body>
</html>
