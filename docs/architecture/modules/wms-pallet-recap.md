# Daijo MES: WMS Pallet Form & Daily Delivery Recap

## Overview
Manages pallet generation for internal transfer and delivery (`/wms/pallet-form/create-delivery`).

**Components**:
- Pallet Creation: `App\Livewire\PalletFormCreator`, `App\Models\WmsPalletForm`
- Daily Recap: `App\Livewire\DailyDeliveryRecap`, `App\Services\WmsDeliveryRecapService`
- Putaway History: `App\Livewire\PalletFormIndex` (`/wms/pallet-form/history`)

---

## Concurrency & Idempotency Protection
- **Client-Side Lock**:
  - Button throttled with `wire:click.throttle.2000ms`.
  - Locked with Alpine.js (`x-data="{ isSubmitting: false }"`) at 0ms latency to eliminate browser double-click race conditions.
- **Server-Side Atomic Lock**:
  - `Cache::lock("wms_pallet_gen_lock_{$userKey}", 15)` blocks concurrent requests per user/session.
- **Payload Hash Idempotency**:
  - Generates deterministic MD5 hash of production date, lot, delivery, shift, and scanned box list.
  - Cached for 5 minutes (`wms_pallet_gen_hash_{$hash}`) returning existing pallet ID on repeat submissions.
- **Database Label Guard**:
  - Scans `WmsPalletFormDetail` within the last 10 minutes for matching box label; if matched, re-attaches to existing pallet instead of creating duplicates.

---

## Daily Delivery Recap (`/wms/pallet-form/daily-recap`)
- **07:30 Production Day Cutoff**:
  - Date D spans from `D 07:30:00` to `(D+1) 07:29:59` WIB (Shift 3 night runs until 07:29 morning of next day before rolling over to D+1 at 07:30:00).
- **Shift Schedule Determination (`determineShiftFromTime`)**:
  - Shift 1: `07:30:00` - `15:29:59`
  - Shift 2: `15:30:00` - `23:29:59`
  - Shift 3: `23:30:00` - `07:29:59` (next day)
- **Grouping by Item Code (Part No)**:
  - Summarizes delivery quantity (pcs), box count, pallet count, customer, shift breakdown (Shift 1, 2, 3), SPK list, and transporters.
  - Interactive drill-down / accordion displays each pallet carrying the item, rack slot, and box scan audit trail.
  - Dual views: Tab 1 (Rekap per Item Code), Tab 2 (Rekap per Pallet), Tab 3 (Log Scan Box Lengkap).
  - Multi-sheet Excel export (`DailyDeliveryRecapExport`).

---

## Store Putaway Quick Scan & Verification (`/wms/pallet-form/history`)
- High-throughput barcode scanner input for physical pallet verification during warehouse store putaway (`wire:keydown.enter="scanPallet"`).
- Immediate verification display: Pallet ID, status, Item Part No & Model, Customer, total Qty (pcs), box count, delivery name/shift, lot number, putaway lead time.
- Collapsible itemized box audit breakdown (`details` listing box labels, SPK numbers, and quantities).
- Integrated destination rack assignment:
  - Smart system recommendation via `WmsService::recommendPosition` (prioritizing consolidation of same item on PARTIAL slots, Level 1 for heavy/priority parts, or customer-specific EMPTY slots) with 1-click apply button.
  - Direct barcode scanner for rack positions (`wire:keydown.enter="scanRackPosition"`).
  - Dropdown selector filterable by warehouse (`whse_id`) with real-time status indicators (EMPTY / PARTIAL / FULL) and customer mapping.
- Zero-dependency Web Audio API synthesizer feedback (high success beep / low error double-beep) and automatic scanner focus recycling.

---

## Mixed Pallet Resolution & Split Assignment (`/wms/pallet-form/history`)
- **Mixed Pallet Detection**:
  - Automatically identifies multi-item pallets (`part_no === 'MIXED'` or multiple unique `part_no` values in `details`).
- **Modal Assign Slot with Warning**:
  - Prominently warns operators when attempting to assign a multi-item pallet directly to a rack.
  - **Option 1: Pisahkan Item (Solve Mixed - Recommended)**:
    - Scans warehouse for active racks holding matching `part_no` (`WmsService::findExistingRacksForItem`).
    - Suggests merging boxes into the existing pallet in that rack (`MERGE`).
    - If no rack holds the item, suggests creating a new pallet into an available slot (`NEW_PALLET`).
    - **Single Item / Granular Execution**:
      - Operators can process items individually via **`[Gabungkan Item Ini]`** (for existing rack merge) or **`[Simpan Item Ini]`** (for new pallet).
      - Allows resolving 1 item immediately while leaving unassigned items in the source mixed pallet without blocking.
    - **Batch Execution (`Eksekusi Semua yang Dipilih`)**:
      - Applies split to all items that have assigned rack slots; unassigned items safely remain in the source pallet.
    - **Worst Case Handling (All Items Consolidated)**:
      - Source pallet transitions to `status = 'RESOLVED'`, `total_pallet_qty = 0`, `box_qty = 0`, `position_id = null`.
      - Full audit trail preserved in `is_split = true`, `split_at`, `split_into` (JSON array of transferred parts, boxes, and target pallets/racks), and `wms_pallet_logs`.
  - **Option 2: Tetap Assign 1 Rak (Force Bypass)**:
    - Allows direct assignment of the entire mixed pallet to a single rack slot with explicit operator confirmation.

---

## SAP Sync Monitor Split Tracking (`/wms/sap-sync-monitor-delivery`)
- **Purpose**: Strictly read-only monitoring of pallet inventory transfer status to SAP.
- **Split Pallet Indicator**:
  - Pallets marked `is_split = true` or `status = 'RESOLVED'` render a `[SPLIT]` button adjacent to the Pallet ID.
  - Pallets created as split children show `(dari <parent_pallet_id>)`.
- **Read-Only Split History Modal**:
  - Clicking `[SPLIT]` opens a diagnostic modal displaying split timestamp, source pallet status, and breakdown table showing which items/boxes were transferred to which target pallets and rack positions.
- **SAP Sync Engine Protection**:
  - Pallets emptied during split (`status = 'RESOLVED'` and 0 details) automatically transition to `sap_sync_status = 4` (IGNORED/RESOLVED) with notes `"Pallet di-split ke pallet lain"` to eliminate false "FAILED: No items" sync errors.


