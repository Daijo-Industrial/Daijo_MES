# Daijo MES: WMS Multi-Warehouse Rack Mapping & 2D Layouts

## Overview
Visual mapping and slot assignment for warehouse racks (`/wms/mapping`).

**Models & Components**:
- Models: `App\Models\WmsWarehouse`, `App\Models\WmsRack`, `App\Models\WmsPosition`
- Livewire: `App\Livewire\RackMapping`

---

## Multi-Warehouse Support
- Warehouse switching via reactive dropdown (`$whseId` bound to query string `?whseId=...`), auto-filtering rack grids and active statistics.
- Warehouse creation modal (`+ TAMBAH GUDANG`): creates `WmsWarehouse` with unique code and auto-switches active view to the new warehouse.
- Empty warehouse state provides direct `+ TAMBAH RAK PERTAMA` action.
- Warehouse deletion guard: prevents deletion if warehouse still contains active racks or is the sole remaining warehouse.
- Rack creation (`+ ADD RACK`) automatically sets `whse_id` to current active warehouse with uniqueness scoped per warehouse.

---

## Physical 2D Layouts & Shortest Path to Exit
Dual view modes in `/wms/mapping`: `🗺️ Denah 2D (GUB / GUA / J06)` (interactive real-time layout mirroring physical factory blueprint) and `📋 Grid Kartu` (traditional card view).

### Factory Blueprints & Exit Benchmark
- `wms_warehouses` table features `layout_image` and `exit_location` (`BOTTOM_LEFT`, `BOTTOM_RIGHT`, `BOTTOM_CENTER`, `TOP_LEFT`, `TOP_RIGHT`).
- Modal `📷 UPLOAD LAYOUT / EDIT GUDANG` stores factory blueprint images and defines exit reference points.

### Warehouses
1. **GUB (Gudang Utama B)**:
   - Horizontal rack orientation. Exit benchmark is bottom-right (`x=880, y=730`).
   - Priority Ranking: Rack `F25` is Rank #1 (closest to Exit, 450px), followed by `F24`, `F23`, `R01`, `R02`, `F32` down to `F31` (farthest, 1440px).
   - Sync: `php artisan wms:sync-gub-layout`.
2. **GUA (Gudang Utama A)**:
   - Vertical rack orientation (`orientation = 'VERTICAL'`).
   - Exit benchmark: 🔴 Pintu Keluar at bottom-left (`x=180, y=730`) near Forklift dock #2.
   - Lower Block: 5 vertical pairs (`F01/F02` to `F09/F10`). Upper Block: 6 vertical pairs (`F11/F12` to `F21/F22`).
   - Priority Ranking: Rack `F01` (230px) and `F02` (266px) Rank #1 & #2, down to `F21/F22` (farthest, 1026px).
   - Sync: `php artisan wms:sync-gua-layout`.
3. **Gudang 06 (J06 - Logistic & Store)**:
   - Dual-zone vertical layout: Green Zona Logistic (`F33`-`F40`), Blue Zona Store (`W09`-`W01`), plus horizontal top row `F41` & `W10`.
   - Exit benchmark: 🟢 Pintu Keluar at bottom-center (`x=500, y=730`) indicated by green arrow under `W08/W07` & `W06/W05`.
   - Priority Ranking: Rack `F40` (320px) and `W09` (336px) Rank #1 & #2, down to `F41` (farthest, 760px).
   - Sync: `php artisan wms:sync-j06-layout`.

### Distance Calculation & Pick Route
- Level selector pills (`Level 1 (Bawah)`, `Level 2`, `Level 3`, `Semua Level`) filter visual slots.
- `WmsService::getShortestPathPickLocations($partNo, $neededQty)` orders pick locations ascending by distance score so pickers start closest to the exit.

