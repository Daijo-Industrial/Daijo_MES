<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            font-family: Arial, sans-serif;
            font-size: 10px;
        }
        th, td {
            border: 1px solid #000000;
            padding: 4px 6px;
            text-align: center;
            vertical-align: middle;
        }
        .header-title {
            font-size: 14px;
            font-weight: bold;
            text-align: left;
            border: none;
            padding-bottom: 8px;
        }
        .header-bg {
            background-color: #00C4B4;
            color: #000000;
            font-weight: bold;
            text-transform: uppercase;
        }
        .shift-bg {
            background-color: #FF8A8A;
            color: #000000;
            font-weight: bold;
            text-transform: uppercase;
        }
        .total-shift-bg {
            background-color: #E66A6A;
            color: #000000;
            font-weight: bold;
        }
        .total-row {
            background-color: #E2E8F0;
            font-weight: bold;
        }
        .text-left {
            text-align: left;
        }
        .text-right {
            text-align: right;
        }
        .text-danger {
            color: #CC0000;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <table>
        <!-- Banner Title -->
        <tr>
            <td colspan="16" class="header-title" style="font-size: 14px; font-weight: bold; text-align: left;">
                LAPORAN PRODUKSI HARIAN (DAILY PRODUCTION REPORT) - PPIC INJECTION
            </td>
        </tr>
        <tr>
            <td colspan="16" style="text-align: left; font-weight: bold; font-size: 10px; border: none; padding-bottom: 10px;">
                TANGGAL PRODUKSI: {{ $date }}
            </td>
        </tr>

        <!-- Table Headers matching PPIC Manual Format -->
        <thead>
            <tr>
                <th rowspan="2" class="header-bg" style="width: 35px;">NO</th>
                <th rowspan="2" class="header-bg" style="width: 80px;">DATE</th>
                <th rowspan="2" class="header-bg" style="width: 100px;">CUST'M</th>
                <th rowspan="2" class="header-bg" style="width: 50px;">MC</th>
                <th rowspan="2" class="header-bg" style="width: 130px;">PART NO.</th>
                <th rowspan="2" class="header-bg" style="width: 220px;">PART NAME</th>
                <th colspan="4" class="header-bg">PRODUCTION</th>
                <th rowspan="2" class="header-bg" style="width: 55px;">MCT</th>
                <th rowspan="2" class="header-bg" style="width: 65px;">C/T (SEC)</th>
                <th rowspan="2" class="header-bg" style="width: 65px;">TARGET/H</th>
                <th rowspan="2" class="header-bg" style="width: 80px;">PLANNED TIME (HRS)</th>
                <th rowspan="2" class="header-bg" style="width: 80px;">ACTUAL TIME (HRS)</th>
                <th rowspan="2" class="header-bg" style="width: 80px;">DOWNTIME (HRS)</th>
            </tr>
            <tr>
                <th class="shift-bg" style="width: 75px;">ACTUAL QTY I</th>
                <th class="shift-bg" style="width: 75px;">ACTUAL QTY II</th>
                <th class="shift-bg" style="width: 75px;">ACTUAL QTY III</th>
                <th class="total-shift-bg" style="width: 90px;">TOTAL ACTUAL QTY</th>
            </tr>
        </thead>

        <tbody>
            @php
                $totShift1   = 0;
                $totShift2   = 0;
                $totShift3   = 0;
                $totAllShift = 0;
                $totPlan     = 0;
                $totActual   = 0;
                $totDowntime = 0;
            @endphp

            @forelse($reportData as $row)
                @php
                    $totShift1   += $row['shift_1'];
                    $totShift2   += $row['shift_2'];
                    $totShift3   += $row['shift_3'];
                    $totAllShift += $row['total_shift'];
                    $totPlan     += $row['plan'];
                    $totActual   += $row['actual'];
                    $totDowntime += $row['downtime'];
                @endphp
                <tr style="{{ !empty($row['is_pair_sub']) ? 'background-color: #F8FAFC; font-style: italic;' : '' }}">
                    <td>{{ $row['no'] }}</td>
                    <td>{{ $row['date'] }}</td>
                    <td class="text-left" style="font-weight: bold;">{{ $row['customer'] }}</td>
                    <td style="font-weight: bold;">{{ $row['mc'] }}</td>
                    <td class="text-left" style="font-family: monospace; font-weight: bold;">{{ $row['part_no'] }}</td>
                    <td class="text-left">{{ $row['part_name'] }}</td>
                    <td class="text-right">{{ number_format($row['shift_1']) }}</td>
                    <td class="text-right">{{ number_format($row['shift_2']) }}</td>
                    <td class="text-right">{{ number_format($row['shift_3']) }}</td>
                    <td class="text-right" style="font-weight: bold; background-color: #FFF0F0;">{{ number_format($row['total_shift']) }}</td>
                    <td style="font-weight: bold;">{{ $row['mct'] }}</td>
                    <td class="text-right">{{ $row['cycle_time'] }}</td>
                    <td class="text-right">{{ number_format($row['target_h']) }}</td>
                    <td class="text-right">{{ number_format($row['plan']) }}</td>
                    <td class="text-right">{{ number_format($row['actual']) }}</td>
                    <td class="text-right {{ $row['downtime'] > 0 ? 'text-danger' : '' }}">
                        {{ number_format($row['downtime'], 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="16" style="text-align: center; padding: 20px; font-style: italic; color: #718096;">
                        Tidak ada data produksi mesin untuk tanggal ini.
                    </td>
                </tr>
            @endforelse

            <!-- Total Summary Row -->
            @if(count($reportData) > 0)
                <tr class="total-row">
                    <td colspan="6" style="text-align: right; font-weight: bold;">TOTAL SUMMARY:</td>
                    <td class="text-right" style="font-weight: bold;">{{ number_format($totShift1) }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ number_format($totShift2) }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ number_format($totShift3) }}</td>
                    <td class="text-right" style="font-weight: bold; background-color: #FFD8D8;">{{ number_format($totAllShift) }}</td>
                    <td colspan="3"></td>
                    <td class="text-right" style="font-weight: bold;">{{ number_format($totPlan) }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ number_format($totActual) }}</td>
                    <td class="text-right text-danger" style="font-weight: bold;">{{ number_format($totDowntime, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
