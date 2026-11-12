# Daijo MES: Master List Items & Customer Relations

## Overview
Manages part catalog, specifications, and customer associations across factory processes.

**Models**:
- `App\Models\MasterListItem`
- `App\Models\MasterListItemView`
- `App\Models\MasterCustomerDelivery`
- `App\Models\MasterBusinessPartner`

---

## Relationships & Resolution
- Relates to `MasterCustomerDelivery` via `belongsTo(MasterCustomerDelivery::class, 'customer_code', 'customer_code')`.
- Relates to `MasterBusinessPartner` via `belongsTo(MasterBusinessPartner::class, 'customer_code', 'bp_code')`.
- Hierarchical Resolution (`SecondProcessReportController::searchItems`):
  ```php
  $customerName = $item->customer?->customer_name ?: $item->businessPartner?->bp_name ?: 'N/A';
  ```
  Guarantees official registered customer names and prevents fallback `'N/A'` entries in Second Process production forms.

---

## Livewire Master List Manager (`/master-list-manager`)
- Includes connection filters: `Semua`, `✓ Terhubung`, `⚠️ Belum Terhubung`.
- Live count badges.
- Inline `<select>` customer dropdown mapping (`[Code] - [Name]`) to resolve unassigned parts without needing heavy bulk upload modals.

