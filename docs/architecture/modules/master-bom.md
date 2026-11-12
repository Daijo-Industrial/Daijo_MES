# Daijo MES: Master Bill of Materials (BOM)

## Overview
Stores standardized parent-child BOM relationships directly extracted from SAP Business One without pre-processing.

**Models & Services**:
- Raw Staging: `App\Models\MasterBom` (`master_boms`)
- Production Tables: `App\Models\MasterBomFgHeader` (`master_bom_fg_headers`), `App\Models\MasterBomComponent` (`master_bom_components`)
- Service: `App\Services\MasterBomSyncService` (`php artisan bom:generate-valid`)
- View: `App\Livewire\MasterBomView`

---

## Production Dual-Table Architecture
Staging raw extract from SAP (`master_boms`) is cleansed and synchronized into 2 normalized production tables:
1. **Header Table (`master_bom_fg_headers`)**:
   - Stores real Finished Goods (`fg_item_code`, `fg_description`, `project_code`, `family`, `customer_name`, `total_wip_count`, `total_raw_count`, `max_depth_level`, `has_packaging`).
2. **Multilevel Detail Table (`master_bom_components`)**:
   - Stores exploded hierarchy (`fg_id`, `parent_item`, `component_item`, `component_description`, `depth_level`, `item_type`, `is_wip`, `unit_qty`, `total_qty_per_fg`, `uom`, `lineage_path`).

---

## SAP Dummy Project Headers & Config (`config/bom_projects.php`)
- In SAP Business One, users frequently misuse the Production BOM menu to create project groupings (e.g. `SHAD TOP CASE`, `KULKAS SHARP`, `YANFENG`) containing independent FGs (`D0B29100.`, `D0B46200KO`, etc.).
- `config/bom_projects.php` maintains explicit dummy project codes (`dummy_headers`), providing 100% deterministic exclusion and skipping without relying purely on heuristics.
- `MasterBomSyncService` reads `config('bom_projects.dummy_headers')` first; any newly detected project headers automatically update and sort `config/bom_projects.php`.
- **Excel Family Dual-Column Ingestion**:
  - Takes Column 1 if filled; if only Column 2 is filled -> Column 2; if both empty -> `null`.
  - Dummy headers are skipped from becoming FG headers; parent name assigned as `project_code` on child FGs.
  - Dummy project codes are guarded and never inserted into `master_bom_components`.
  - Child FGs are recognized as Top-Level FGs because they possess packaging items (`BOX`, `BAG`, `USER GUIDE`, `WARRANTY`).

---

## Engineering Features & Overrides
- **Dual-Tab UI**: **Master BOM Produksi (Sah)** vs **Staging SAP (Mentah)**.
- **Multilevel Components Modal**: Exploded hierarchy with depth levels (L1, L2, L3, ...), unit qty, total qty per FG, simulation multiplier.
- **Where-Used (Reverse BOM)**: Real-time search to instantly find all Finished Goods consuming a given component or raw material.
- **PE Audit Verification & Re-verification (`master_bom_verifications`, `master_bom_verification_logs`)**:
  - 1-click audit verification (`verifyBom` / `unverifyBom`) with timestamp, user ID, name, and history logs (`master_bom_verification_logs`).
  - Preserved across production synchronization cycles.
- **Material Type Override (`master_bom_material_overrides`)**:
  - PE / Engineering users can override material classification (`RAW_MATERIAL`, `RESIN`, `CHEMICAL`, `HARDWARE`, `PACKAGING`).
  - Restricted to leaf components (WIP remains fixed as `⚙️ WIP / Sub-Assembly`).
  - `MasterBom::determineCategory` prioritizes overrides before applying regex/prefix rules.

