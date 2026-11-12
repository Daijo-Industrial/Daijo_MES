# Daijo MES: Material Warehouse (Store Material) & Daily Audit

## Overview
Manages raw material inventory, pallet tracking, rack slot positioning, and daily inventory audits (`/material-warehouse/*`).

**Models & Components**:
- Models: `App\Models\MaterialDailyAudit`, `App\Models\MwhDailyAudit`, `App\Models\MwhDailyAuditItem`
- Service: `App\Services\MaterialFifoService`
- Audit Route: `/material-warehouse/daily-audit` (`mwh.daily-audit`)

---

## Daily Audit Table Columns
1. **Rak / Posisi**: Position code (e.g. `A-01-01`), level, slot, rack block badge.
2. **Detail Data Sistem**: Pallet ID, Part No / Item Code, Material Description, Lot No.
3. **Qty Sistem**: Current active qty in system and UOM (`decimal(12,2)`).
4. **Actual Barang (Part No)**: Physical item code found at location (supports 1-click `=` fill shortcut).
5. **Actual Qty**: Physical counted quantity with real-time discrepancy badge (`✓ MATCH` / `⚠️ Selisih`).
6. **Alasan Jika Ada Perbedaan Data**: User input for discrepancy remark/reason.

**Fast Auditor Shortcuts**:
- `⚡ Isi Cepat Sesuai Sistem`: 1-click shortcut filling all rows to match system values.
- `🔄 Reset`: Clears all actual inputs.
- `📜 Riwayat Audit`: Interactive modal inspecting previous audit runs by date, auditor, and discrepancy counts.

---

## Temporary / Transit Area (`position_id = null`)
- Allows receiving incoming material in **Temporary** (transit) area without immediately choosing/filling a specific rack slot (for both KBN `whse_id = 1` and Karawang `whse_id = 2`).
- Operators can hold excess incoming goods without being restricted by physical rack slot capacity.
- **Incoming Form (`MaterialIncomingCreator`)**: Slot dropdown `TEMPORARY`, row shortcut `[⏳ Taruh di Area Temporary]`, batch header `[⏳ Set Semua ke Temporary]`.
- **Stock Pallet & Relocation (`MaterialPalletIndex`)**: Badge `⏳ TEMPORARY (Area Transit)`, filter `TEMPORARY`, and direct action `[📥 Masuk Slot]`.
- **2D Denah & Quick Assign (`RackMapping`)**: Alert banner for temporary material with `[📥 ASSIGN LANGSUNG KE SLOT RAK]`, drawer panel `[+ Masuk Slot]`.

---

## FIFO Violation Forensic Audit (`MaterialFifoService::detectFifoDeviations`)
- Reconstructs historical stock of older pallets at the exact moment of an outgoing transaction:
  ```
  historical_stock = initial_qty - sum(outgoings before timestamp)
  ```
- **Eliminates False Positives**: Previous logic falsely checked `updated_at > outgoingTimestamp`, misclassifying pallets that were already depleted before the transaction as FIFO violations.
- **Display**: Separates current remaining stock (`skipped_current_qty`) vs stock at event time (`skipped_stock_at_time`) with informative label `(Stok Saat Itu: X KG • Sekarang Habis)` if the older pallet was consumed later.

