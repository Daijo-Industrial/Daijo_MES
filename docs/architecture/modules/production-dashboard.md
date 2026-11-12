# Daijo MES: Production Dashboard & Performance Tracking

## Overview
High-performance production tracking, NG rates, adjust/mould setup times, and adjuster NG trend.

**Controllers & Services**:
- `App\Http\Controllers\DashboardController`
- `App\Services\ProductionDashboardService`

---

## Shift Schedules & Half-Day Logic
- **Normal Schedule**:
  - Shift 1: 07:30 - 15:30
  - Shift 2: 15:30 - 23:30
  - Shift 3: 23:30 - 07:30 (next day)
- **Half-Day Schedule**:
  - Shift 1: 07:30 - 12:30
  - Shift 2: 12:30 - 17:30
  - Shift 3: 17:30 - 22:30 (rollover to 07:30 next day)
- **Daily View**: Manual toggle "Setengah Hari" (defaults to unchecked/OFF) so Saturday/Sunday can run full-day or half-day as needed.
- **Weekly View**: Dedicated manual checkboxes "Sabtu" and "Minggu" under "Setengah Hari" (default unchecked/OFF). Weekdays (Mon–Fri) always use normal schedule, while Saturday and/or Sunday use half-day only when checked by user.
- **NG & Output Balance**: Multi-adjuster shift distribution uses integer division (`intdiv`) plus remainder allocation (`$baseNg + ($idx < $rem ? 1 : 0)`) so the sum of individual adjuster NGs strictly matches the shift total.
- **Machine Efficiency & Prod Time**: Calculated based on total accumulated production seconds from cycle times capped against available operating time (`maxDetik`), avoiding premature individual hourly clamp loss.

---

## Adjuster Machine Plant Restriction (Karawang vs KBN)
- **Karawang Adjusters**: Strictly restricted to 4 people:
  1. `Haerul Anwar`
  2. `Rudi Siswanto`
  3. `Rodi Khayrudin`
  4. `Agung Setyawan`
- **Machine Name Routing**:
  - Machine accounts starting with `'K'` (Karawang machines) only display and permit these 4 Karawang adjusters in `DashboardController::index` and `startAdjustMachine`.
  - Machine accounts not starting with `'K'` (KBN machines) display all other adjusters with `position = 'Adjuster'`, strictly excluding the 4 Karawang adjusters (`whereNotIn`).
  - `startAdjustMachine` validates and rejects cross-plant adjuster logging with status 422.

---

## NG Breakdown Models & Remarks Inspection
- `processNgBreakdown` groups NG data per defect type down to individual part numbers/models (`models_count`, `models` list containing `item_code`, `item_name`, and total NG).
- Each model itemizes individual defect log entries with Date, Shift, Hour, Machine, NG Qty, `hourly_remark` (Remark Produksi), and `ng_remarks` (Remark NG).
- Dashboard UI provides interactive accordion per defect type, filter model dropdown (`<select x-model="selectedModel">`), collapsible model rows (closed by default, expanded via dropdown/click), and a log table with dedicated **Remark Produksi** (🏭) and **Remark NG** (⚠️) columns.

