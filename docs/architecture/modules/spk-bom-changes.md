# Daijo MES: SPK BOM Changes & Recipe Updates

## Overview
Manages integration between active SPK orders (`spk_masters`) and bill of materials (`/spk-bom-changes`).

**Services & Views**:
- Service: `App\Services\SpkBomService`
- View: `App\Livewire\SpkBomChangesView`
- Audit: `App\Models\SpkBomChangeLog`, `App\Models\ApiLog`

---

## Leaf (Non-WIP) Material Enforcement
- Strictly excludes WIP components (`is_wip = false` only).
- Father/sub-assembly components are recursively exploded down to raw materials.
- Dual-source resolution:
  1. Priority 1: Official production tables (`master_bom_fg_headers` & `master_bom_components` grouped by `component_item` with `SUM(total_qty_per_fg) as unit_qty`).
  2. Priority 2: Staging extract fallback (`MasterBom::getFlattenedSummary($itemCode, 1.0)['materials']`).
- Core Formula: `planned material quantity = unit_qty * planned_quantity`.

---

## SAP Production Order Update API (`POST /api/sap_production_order/update`)
- Handled via `SpkMasterService::updateProductionOrderLines` (extending `BaseSapService` with bearer auth token and retry).
- Supported payload actions:
  - **Update Qty**: Sends `{ item_code, plan_qty }` (and optional `base_qty`).
  - **Add New Material**: Sends `{ item_code, base_qty, plan_qty, warehouse }`.
  - **Delete Material**: Sends `{ item_code, delete: true }`.
  - **Replace Material**: Sends dual lines: old item with `{ delete: true }` and new item with full quantities & warehouse.

---

## Staged Batching & UI Flow
- Operator enters **Mode Edit Resep** (`startEditMode`).
- Changes (add, edit, delete, replace) are staged in `$stagedLines` without sending spam requests to SAP.
- Visual state: strikethrough red (`Ditandai Hapus`), yellow (`Diubah`), green (`Baru`).
- Single batch submit (`submitBatchChanges`) sends array `lines: [...]` to SAP in one call.
- Audit trail logged to `spk_bom_change_logs` and `api_logs`.

