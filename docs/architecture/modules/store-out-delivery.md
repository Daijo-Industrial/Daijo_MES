# Daijo MES: Store Out & Delivery Scanning

## Overview
High-throughput scanning for sales orders / delivery orders (`/so/process/{docNum}`).

**Controllers & Views**:
- Controller: `App\Http\Controllers\SOController`
- Tracking: `App\Livewire\SoTracking` (`/so/tracking`, route `so.tracking`)

---

## High-Performance Database Indexes
Sub-second scan processing via composite database indexes on:
- `scanned_data` (`[doc_num, item_code]`, `[doc_num, spk_code, label]`)
- `so_datas` (`[doc_num, item_code]`)
- `wms_pallet_form_details` (`[part_no, label]`)

---

## Scanning Mechanics & Tracking DO (`/so/tracking`)
- Frontend scanning features immediate Enter key handling, composite tab-delimited QR barcode parsing (`part\tspk\tqty\tlabel`), and rapid debouncing (150ms).
- **Tracking SPK & Label DO (`so.tracking`)**:
  - Instant menu to track which Sales Order / Delivery Order document an SPK number, box label, or part number was scanned into (`scanned_data` joined to `so_datas`).
  - **Quick Barcode Scanner Strip**: Physical barcode scanner support with tab-delimited composite QR auto-parsing.
  - **Universal & Advanced Filtering**: Filter by SPK, Label, No DO, Part No, Customer, scan date range, and DO status (`SELESAI (DONE)` vs `IN PROGRESS`).
  - **Real-Time KPI Stats**: Displays total boxes scanned, total qty (pcs), unique SPK count, and associated DO document count.
  - **DO Breakdown Modal**: Progress scan breakdown per item with 1-click link to scan processing page (`/so/process/{docNum}`).
  - **Instant CSV Export**: Streaming UTF-8 BOM CSV download for delivery audits.

