<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Print Label Output #{{ $log->id }}</title>

    <style>
        @page {
            size: 30mm 20mm;
            margin: 0;
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            width: 30mm;
            height: 20mm;
            margin: 0;
            padding: 0;
            background: #fff;
            font-family: Arial, Helvetica, sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .label {
            width: 30mm;
            height: 20mm;
            padding: 1.5mm 1mm 1mm 1.5mm;
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            overflow: hidden;
            page-break-after: always;
            break-after: page;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .label:last-child {
            page-break-after: auto;
            break-after: auto;
        }

        .info {
            flex: 1;
            min-width: 0;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding-right: 1mm;
            overflow: hidden;
        }

        .item-code {
            font-size: 5.5pt;
            font-weight: 800;
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #000;
        }

        .item-name {
            font-size: 4pt;
            line-height: 1.1;
            max-height: 5.5mm;
            overflow: hidden;
            word-break: break-word;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            color: #111;
        }

        .operator {
            font-size: 4pt;
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #222;
        }

        .datetime {
            font-size: 3.8pt;
            line-height: 1.1;
            white-space: nowrap;
            color: #333;
        }

        .qr-container {
            width: 13.5mm;
            height: 13.5mm;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-shrink: 0;
        }

        .qr-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
            image-rendering: pixelated;
            image-rendering: crisp-edges;
        }
    </style>
</head>

<body>

@foreach($barcodes as $barcode)

<div class="label">

    <div class="info">

        <div class="item-code">
            {{ $barcode['item_code'] }}
        </div>

        <div class="item-name">
            {{ $log->dailyItemCode->masterItem->item_name ?? '-' }}
        </div>

        <div class="operator">
            {{ $log->operator_name }}
        </div>

        <div class="datetime">
            {{ $log->logged_at->format('d/m/y H:i') }}
        </div>

    </div>

    <div class="qr-container">
        <img
            src="data:image/png;base64,{{ $barcode['qrCodeBase64'] }}"
            alt="QR Code">
    </div>

</div>

@endforeach

<script>
window.onload = function () {
    setTimeout(function () {
        window.print();
    }, 500);
};
</script>

</body>
</html>