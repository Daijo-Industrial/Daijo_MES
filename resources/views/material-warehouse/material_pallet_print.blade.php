<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Print Label QR Pallet - {{ $pallet->pallet_id }}</title>
    <style>
        @page {
            size: portrait;
            margin: 0;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            width: 100%;
        }

        .page-wrapper {
            width: 96mm;
            max-width: 96mm;
            margin: 0 auto;
            padding: 2mm 0;
            background: #fff;
            box-sizing: border-box;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .label-card {
            border: 2.5px solid #000;
            border-radius: 8px;
            padding: 8px 10px;
            width: 100%;
            background: #fff;
            box-sizing: border-box;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 4px;
            margin-bottom: 5px;
        }
        .header h2 {
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header p {
            font-size: 7.5px;
            font-weight: bold;
            color: #333;
            margin-top: 1px;
        }

        /* Area Kosong Khusus Tempel Stiker FIFO */
        .fifo-sticker-zone {
            border: 1.5px dashed #475569;
            border-radius: 6px;
            height: 17mm;
            margin-bottom: 5px;
            background: #fff;
        }

        .pallet-banner {
            background: #f1f5f9;
            border: 1.5px solid #000;
            border-radius: 6px;
            text-align: center;
            padding: 4px;
            margin-bottom: 5px;
        }
        .pallet-banner .lbl {
            font-size: 7px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #475569;
        }
        .pallet-banner .val {
            font-size: 15px;
            font-weight: 900;
            font-family: 'Courier New', Courier, monospace;
            color: #0f172a;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        .details-table td {
            padding: 3px 2px;
            vertical-align: top;
            border-bottom: 1px dashed #cbd5e1;
        }
        .details-table tr:last-child td {
            border-bottom: none;
        }
        .details-table .lbl {
            font-size: 8px;
            font-weight: 900;
            text-transform: uppercase;
            color: #334155;
            width: 32%;
        }
        .details-table .val {
            font-size: 10px;
            font-weight: 900;
            color: #000;
        }
        .details-table .val-code {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
        }
        .details-table .val-slot {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            font-weight: 900;
            text-decoration: underline;
        }

        .qr-section {
            display: flex;
            justify-content: space-around;
            align-items: center;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 5px 0;
            margin-bottom: 4px;
            background: #fafafa;
        }
        .qr-box {
            text-align: center;
            width: 48%;
        }
        .qr-box img {
            width: 72px;
            height: 72px;
            display: block;
            margin: 2px auto;
        }
        .qr-title {
            font-size: 7px;
            font-weight: 900;
            text-transform: uppercase;
        }
        .qr-sub {
            font-size: 6.5px;
            font-weight: bold;
            font-family: monospace;
            word-break: break-all;
        }

        .footer {
            padding-top: 3px;
            font-size: 7.5px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            color: #334155;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                background: #fff !important;
                overflow: visible !important;
            }
            .page-wrapper {
                padding: 2mm 0 !important;
                width: 96mm !important;
                max-width: 96mm !important;
                margin: 0 auto !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .label-card {
                width: 100% !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
    </style>
</head>
<body>

    <div class="no-print" style="position: fixed; top: 12px; right: 12px; z-index: 9999;">
        <button onclick="window.print()" style="padding: 10px 18px; background: #059669; color: white; border: none; border-radius: 8px; font-weight: bold; font-size: 12px; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            🖨️ Cetak Label (Standar 100x150mm)
        </button>
    </div>

    <div class="page-wrapper">
        <div class="label-card">
            <!-- Area Kosong (Blank Space) Tempat Tempel Stiker FIFO di Paling Atas -->
            <div class="fifo-sticker-zone"></div>

            <div class="header">
                <h2>DAIJO MES — MATERIAL PALLET LABEL</h2>
                <p>PT. DAIJO INDUSTRIAL — MATERIAL WAREHOUSE</p>
            </div>

            <div class="pallet-banner">
                <div class="lbl">UNIT PALLET ID</div>
                <div class="val">{{ $pallet->pallet_id }}</div>
            </div>

            @php
                $writer = new \Endroid\QrCode\Writer\PngWriter();

                $qrText = new \Endroid\QrCode\QrCode(
                    data: $pallet->pallet_id,
                    errorCorrectionLevel: \Endroid\QrCode\ErrorCorrectionLevel::Low,
                    size: 100,
                    margin: 0
                );
                $qrTextBase64 = base64_encode($writer->write($qrText)->getString());

                $qrUrl = \Illuminate\Support\Facades\URL::signedRoute('mwh.public-pallet-lookup', ['palletId' => $pallet->pallet_id]);
                $qrUrlObj = new \Endroid\QrCode\QrCode(
                    data: $qrUrl,
                    errorCorrectionLevel: \Endroid\QrCode\ErrorCorrectionLevel::Low,
                    size: 100,
                    margin: 0
                );
                $qrUrlBase64 = base64_encode($writer->write($qrUrlObj)->getString());
            @endphp

            <table class="details-table">
                <tr>
                    <td class="lbl">BRANCH / GUDANG:</td>
                    <td class="val" style="font-weight: 900;">{{ $pallet->warehouse?->whse_name ?? ($pallet->position?->rack?->warehouse?->whse_name ?? 'GUDANG MATERIAL KBN') }}</td>
                </tr>
                <tr>
                    <td class="lbl">PART CODE:</td>
                    <td class="val val-code">{{ $pallet->item_code }}</td>
                </tr>
                <tr>
                    <td class="lbl">DESKRIPSI:</td>
                    <td class="val" style="font-size: 8.5px;">{{ $pallet->material ? $pallet->material->item_description : '-' }}</td>
                </tr>
                <tr>
                    <td class="lbl">CURRENT QTY:</td>
                    <td class="val">{{ number_format($pallet->current_qty, 2) }} KG</td>
                </tr>
                <tr>
                    <td class="lbl">SLOT RAK:</td>
                    <td class="val val-slot">{{ $pallet->position ? $pallet->position->position_code : ($pallet->initialPosition ? $pallet->initialPosition->position_code : ($pallet->outgoings?->first()?->position?->position_code ?: 'UNASSIGNED')) }}</td>
                </tr>
                <tr>
                    <td class="lbl">LOT / BATCH:</td>
                    <td class="val" style="font-size: 8.5px;">{{ $pallet->lot_no ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="lbl">SUPPLIER:</td>
                    <td class="val" style="font-size: 8px;">{{ $pallet->incomingHeader ? ($pallet->incomingHeader->supplier_name ?: '-') : '-' }}</td>
                </tr>
                <tr>
                    <td class="lbl">NO. PO:</td>
                    <td class="val" style="font-size: 8px;">{{ $pallet->incomingHeader ? ($pallet->incomingHeader->po_number ?: '-') : '-' }}</td>
                </tr>
            </table>

            <div class="qr-section">
                <div class="qr-box">
                    <div class="qr-title" style="color: #000;">OUTGOING / SCANNER</div>
                    <img src="data:image/png;base64,{{ $qrTextBase64 }}" alt="QR Outgoing">
                    <div class="qr-sub">{{ $pallet->pallet_id }}</div>
                </div>

                <div class="qr-box" style="border-left: 1.5px dashed #cbd5e1;">
                    <div class="qr-title" style="color: #059669;">LIVE CHECK (HP)</div>
                    <img src="data:image/png;base64,{{ $qrUrlBase64 }}" alt="QR Live">
                    <div class="qr-sub" style="color: #059669;">SCAN VIA HP</div>
                </div>
            </div>

            <div class="footer">
                <span>Tgl Kedatangan: {{ $pallet->incomingHeader && $pallet->incomingHeader->arrival_date ? $pallet->incomingHeader->arrival_date->format('d M Y') : ($pallet->created_at ? $pallet->created_at->timezone('Asia/Jakarta')->format('d M Y') : now()->timezone('Asia/Jakarta')->format('d M Y')) }}</span>
                <span>Daijo MES WMS</span>
            </div>
        </div>
    </div>

</body>
</html>
