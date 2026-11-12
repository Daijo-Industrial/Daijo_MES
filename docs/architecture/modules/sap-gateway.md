# Daijo MES: SAP Business One Integration Gateway

## Overview
Central integration gateway connecting Daijo MES with SAP Business One API.

**Core Service**: `App\Services\Sap\BaseSapService`
- Configuration: Centralized in `config('services.sap.*')` (`base_url`, `auth_url`, `company_db`, `username`, `password`).
- Architecture:
  - Lazy token loading.
  - Cache stampede atomic lock: `Cache::lock('sap_token_lock', 40)`.
  - 50-minute token TTL.
  - Auto-refresh on 401 Unauthorized with 120s timeout and retries.
  - Audits all traffic via `saveApiLog()` into `api_logs` table.

---

## Outbound Subservices (MES -> SAP)
1. **Receipt Production (`ReceiptProductionService`)**:
   - Pushes finished goods receipt to `/api/receipt_production/create`.
   - Dispatched every 10 minutes via `sap:dispatch-receipt`.
2. **QC Transfer (`QcTransferService`)**:
   - Pushes `/api/inventory_transfer/create` for QC Pass/Fail splits.
3. **WMS SAP Sync (`WmsSapSyncService`)**:
   - Pushes `/api/inventory_transfer/create` for Pallet movements between warehouses/bins.

---

## Inbound Master Synchronization (SAP -> MES)
**SPK Master Service (`SpkMasterService`)**:
- Synced every 10 minutes via `spk:sync` from `/api/sap_production_order/list` into `spk_masters` & `spk_change_logs`.
- **Single Source of Truth for Completed Qty**:
  - `completed_quantity` in `spk_masters` is strictly sourced from SAP sync.
  - Local operator shift submissions (`DashboardController::submitSPK`) must not mutate `completed_quantity` directly to avoid desync/anomalies in the audit trail.
- **SPK Change Tracking (`SpkChangeLogController`)**:
  - Distinguishes between Planned Target change (`🔴 PERUBAHAN QUANTITY PLANNED` - red alert) and Completed Qty change (`🟡 PERUBAHAN QTY COMPLETED` - amber/yellow).

---

## SAP Receipt Monitor (`ReceiptProductionLogs`, `production_summary`)
- Monitors SPK receipts pushed to SAP from production scanning.
- Bulk push & ignore actions feature real-time selection calculation (`selectedSummary`), displaying selected SPK count and total quantity (`pcs`) across top action bars, floating bottom action pills, and confirmation dialogs.

