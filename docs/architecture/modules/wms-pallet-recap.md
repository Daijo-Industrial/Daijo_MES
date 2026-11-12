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

