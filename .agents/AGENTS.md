# Ponytail: Lazy Senior Developer Rule
Sebelum menulis kode baru, Anda wajib mengikuti tangga keputusan (decision ladder) berikut dan berhenti di anak tangga pertama yang menyelesaikan masalah:
1. **YAGNI**: Apakah fitur/tugas ini benar-benar harus ada? Jika tidak, lewati (jangan dibuat).
2. **Reuse**: Apakah sudah ada helper, utility, atau fungsi di codebase ini yang serupa? Jika ya, gunakan kembali.
3. **Standard Library**: Apakah library bawaan PHP/JS bisa menyelesaikannya? Jika ya, gunakan.
4. **Native Platform**: Apakah fitur bawaan browser/database/HTML bisa menangani ini? (misal input type="date", foreign key constraint).
5. **Existing Dependency**: Apakah dependency yang sudah terinstal bisa menyelesaikannya?
6. **One-Liner**: Apakah bisa diselesaikan dalam 1 baris kode saja?
7. **Minimal Code**: Jika semua di atas tidak bisa, tulis kode seminimal mungkin yang bekerja dengan baik.

*Catatan: Aspek keamanan (security), perlindungan data (data-loss protection), validasi input, penanganan error, dan aksesibilitas TIDAK BOLEH dikurangi atau diabaikan.*

---

# Daijo MES: App Context & Architecture

## 1. Overview & Tech Stack
- **Application**: PT Daijo Industrial MES (Manufacturing Execution System)
- **Backend**: Laravel 12 (PHP 8.4), MySQL, Redis
- **Frontend**: Blade templates, Tailwind CSS, Alpine.js, Livewire
- **Dev & Test Environment**: Docker via Laravel Sail (`./vendor/bin/sail test`)

## 2. Core Functional Modules & Domains
1. **Second Process (`SecondProcessReport`)**:
   - Manages daily secondary processing (Painting, Buffing, Amplas, Treatment, Packing, Rework, Repair, Assy).
   - Form Tabs:
     - **Tab 1 (Setup & Manpower)**: Shift logistics, part number, customer, manpowers.
     - **Tab 2 (Materials)**: Item Paint (viscosity, mixing ratio, qty — active for `Painting` & `Repair`) and Item Parts / WIP Lots (WIP + Repairan reconciliation).
     - **Tab 3 (Production Logs & NG)**: Target per hour, hourly production slots, NG breakdown by defects & remarks.
     - **Tab 4 (Handover & Signs)**: Next schedule, downtime/troubles (`loss_time_minutes`), operator (Checker), Leader, optional PQC (placed after Leader), and Supervisor signoffs.
   - **Signature Workflow & Configurable Mapping (`config/roles.php`)**:
     - Sequential workflow: Checker (`submitted`) -> Leader (`leader_approved`) -> Optional PQC (`pqc_approved`) -> Supervisor (`acknowledged`).
     - Rejection can be executed by Leader, PQC, or Supervisor back to draft status.
     - Signature slot authorization is strictly centralized in `config('roles.signature_mapping.second_process')` and evaluated via `$user->canSign('second_process', $slot)`.
     - `config/roles.php` serves as the Single Source of Truth for IAM/security and signatures, while `config/mes.php` exclusively governs physical shop-floor operational parameters (shifts, lines, chemical processes, workstation station types `sp_manpower_roles`, and defect categories).
     - **Granular Action & Workflow Guards (`SecondProcessReportController`)**:
       - `destroy`: Strictly restricted to `SUPER-ADMIN`, `ADMIN`, or `SUPERVISOR` (`acknowledged`).
       - `reject`: Authorized dynamically for users who can sign as `leader`, `pqc`, or `acknowledged` (or admins).
       - `edit`/`update`: Strictly restricted to report creator, authorized `checker`/`leader`, and admins when report is in `draft` status.
     - Universal bypass: `SUPER-ADMIN` and `ADMIN` are globally authorized to sign any approval slot.
   - **Customer Enforcement & Auto-Conversion**:
     - The `customer` field is strictly enforced: must be an official customer name (`MasterCustomerDelivery::customer_name`) or `'N/A'`.
     - Empty, `'0'`, `'-'`, or case-insensitive `'n/a'` are automatically normalized to `'N/A'`.
     - Valid `customer_code` inputs (e.g. `CUST-001`) are automatically resolved and converted to `customer_name` on save.
     - Part number search (`searchItems`) returns `$item->customer?->customer_name ?: 'N/A'` (never leaks raw codes or `'0'`).
   - **Material Input vs Production Output Reconciliation**:
     - `Total Input = jml_input_wip + repairan` (accumulated in Tab 2).
     - `Total Output = jumlah_ok + jumlah_ng + jml_ng_lebur` (accumulated from OK, NG, and Scrap in Tab 3).
     - `sisa_input = Total Input - Total Output` (persisted in `sisa_input` column).
     - **Deficit (`Total Input < Total Output`)**: Submission blocked in client-side JS and backend controller (`ValidationException` on `sisa_input`). Draft saving allowed.
     - **Leftover (`Total Input > Total Output`)**: Allowed to submit, but creator must provide `sisa_input_remark`.
     - **Ideal (`Total Input == Total Output`)**: Submittable immediately without requiring remarks.
   - **Tab 3 UI Layout & Zone Separation (Responsive & Direct)**:
     - **Zone 1 (Read-Only Information Monitoring `#reconcile-summary-card`)**: Responsive 3-column / 1-column grid (`sm:grid-cols-3`), larger shop-floor typography (`text-2xl sm:text-3xl` figures), and direct labels without verbose paragraphs:
       - Card 1: Input Material (`#reconcile-total-input` + WIP & Repairan breakdown + Tab 2 shortcut).
       - Card 2: Total Output (`#reconcile-total-output` + NG % badge + OK, NG, Scrap statistic chips).
       - Card 3: Sisa Material (`#reconcile-sisa-qty` + dynamic status badge `⛔ Defisit` / `⚠️ Ada Sisa` / `✓ Ideal` + direct inline note).
       - Deficit Banner (`#reconcile-state-deficit`): Responsive flex alert showing exact deficit pcs and Tab 2 link.
     - **Zone 2 (User Input Parameters Strip)**: Integrated touch-friendly 2-column input strip (`grid-cols-1 sm:grid-cols-2`):
       - Form input for `target_per_hour` (Target Produksi / Jam with `pcs/jam` suffix, 42px touch height, `text-sm sm:text-base`).
       - Form input for `jml_ng_lebur` (Scrap/Lebur with `pcs` suffix and `+ ke Total Output` badge).
       - Conditional `sisa_input_remark` textarea when `sisa_input > 0`.
     - **Zone 3 (Hourly Production & Defect Grid `#unified-production-table`)**:
       - Distinct column indicators: `OK Qty (INPUT)` vs `Accum OK (AUTO)` and `Total NG (AUTO)` with clear typography.
     - Full backward compatibility: all form input names (`target_per_hour`, `jml_input_wip`, `repairan`, `jml_ng_lebur`, `jumlah_output`, `jumlah_ok`, `jumlah_ng`, `ng_prosentase`, `sisa_input`, `sisa_input_remark`) strictly preserved.
   - Analytics & Reports: `SecondProcessReportAnalyticsController`.

2. **Master List Items & Customer Relations (`MasterListItem`, `MasterListItemView`)**:
   - Relates to `MasterCustomerDelivery` via `belongsTo(MasterCustomerDelivery::class, 'customer_code', 'customer_code')`.
   - `/master-list-item` Livewire management view includes connection filters (`Total`, `✓ Terhubung`, `⚠️ Belum Terhubung`), unassigned badges, and inline editing to resolve disconnected part-customer relationships.

3. **First Piece Inspection (`FirstPieceInspection`)**:
   - Quality gate conducted at the start of a production run.
   - Acts as a required approval gate before Second Process PQC signoff.

4. **IPQC Inspection (`IpqcInspection`)**:
   - In-Process Quality Control recording defect rates, sample inspection rounds, and judgements.

5. **Work Orders & Assembly (`SpWorkOrder`, `AssemblyDailyProcess`)**:
   - Work order dispatching, batch tracking, barcode generation, and packaging master/detail records.

6. **SAP ERP Gateway (`BaseSapService`)**:
   - Central integration gateway with SAP Business One API.
   - Implements lazy token loading, cache stampede atomic lock (`Cache::lock('sap_token_lock', 40)`), 50-minute TTL, and auto-refresh on 401 Unauthorized with 120s timeout and retries.
   - Audits all traffic via `saveApiLog()` into `api_logs` table.
   - **Key Subservices**:
     - *Outbound Pushes*: `ReceiptProductionService` (`/api/receipt_production/create` every 10m via `sap:dispatch-receipt`), `QcTransferService` (`/api/inventory_transfer/create` for QC Pass/Fail splits), `WmsSapSyncService` (`/api/inventory_transfer/create` for Pallet moves).
     - *Inbound Master*: `SpkMasterService` (`/api/sap_production_order/list` synced to `spk_masters` & `spk_change_logs` every 10m via `spk:sync`).
       - *Single Source of Truth for Completed Qty*: `completed_quantity` in `spk_masters` is strictly sourced from SAP sync. Local operator shift submissions (`DashboardController::submitSPK`) must not mutate `completed_quantity` to avoid desync/anomalies in the audit trail.
       - *SPK Change Tracking (`SpkChangeLogController`)*: Distinguishes between Planned Target change (`🔴 PERUBAHAN QUANTITY PLANNED` - red alert) and Completed Qty change (`🟡 PERUBAHAN QTY COMPLETED` - amber/yellow).
   - *Configuration*: All SAP connection parameters (`base_url`, `auth_url`, `company_db`, `username`, `password`) are centralized under `config('services.sap.*')`.

5. **Production Dashboard (`ProductionDashboard`, `ProductionDashboardService`)**:
   - High-performance production tracking, NG rates, adjust/mould setup times, and adjuster NG trend.
   - **Shift Schedules & Half-Day Logic**:
     - *Normal Schedule*: Shift 1 (07:30 - 15:30), Shift 2 (15:30 - 23:30), Shift 3 (23:30 - 07:30 next day).
     - *Half-Day Schedule*: Shift 1 (07:30 - 12:30), Shift 2 (12:30 - 17:30), Shift 3 (17:30 - 22:30, rollover to 07:30 next day).
     - *Daily View*: Manual toggle "Setengah Hari" (defaults to unchecked/OFF) so Saturday/Sunday can run full-day or half-day as needed.
     - *Weekly View*: Dedicated manual checkboxes "Sabtu" and "Minggu" under "Setengah Hari" (default unchecked/OFF). Weekdays (Mon–Fri) always use normal schedule, while Saturday and/or Sunday use half-day only when checked by user.
     - *NG & Output Balance*: Multi-adjuster shift distribution uses integer division (`intdiv`) plus remainder allocation (`$baseNg + ($idx < $rem ? 1 : 0)`) so the sum of individual adjuster NGs strictly matches the shift total.
     - *Machine Efficiency & Prod Time*: Calculated based on total accumulated production seconds from cycle times capped against available operating time (`maxDetik`), avoiding premature individual hourly clamp loss.
     - *Adjuster Machine Plant Restriction (Karawang vs KBN)*:
       - Karawang adjusters are strictly restricted to 4 people: `Haerul Anwar`, `Rudi Siswanto`, `Rodi Khayrudin`, and `Agung Setyawan`.
       - Machine accounts starting with `'K'` (Karawang machines) only display and permit these 4 Karawang adjusters in `DashboardController::index` and `startAdjustMachine`.
       - Machine accounts not starting with `'K'` (KBN machines) display all other adjusters with `position = 'Adjuster'`, strictly excluding the 4 Karawang adjusters (`whereNotIn`).
       - `startAdjustMachine` validates and rejects cross-plant adjuster logging with status 422.
     - *NG Breakdown Models & Remarks Inspection*:
       - `processNgBreakdown` groups NG data per defect type down to individual part numbers/models (`models_count`, `models` list containing `item_code`, `item_name`, and total NG).
       - Each model itemizes individual defect log entries with Date, Shift, Hour, Machine, NG Qty, `hourly_remark` (Remark Produksi), and `ng_remarks` (Remark NG).
       - Dashboard UI provides interactive accordion per defect type, filter model dropdown (`<select x-model="selectedModel">`), collapsible model rows (closed by default, expanded via dropdown/click), and a log table with dedicated **Remark Produksi** (🏭) and **Remark NG** (⚠️) columns.

6. **SAP Receipt Monitor (`ReceiptProductionLogs`, `production_summary`)**:
   - Monitors SPK receipts pushed to SAP from production scanning.
   - Bulk push & ignore actions feature real-time selection calculation (`selectedSummary`), displaying selected SPK count and total quantity (`pcs`) across top action bars, floating bottom action pills, and confirmation dialogs.

7. **Store Out & Delivery Scanning (`SOController`, `soresults`)**:
   - High-throughput scanning for sales orders / delivery orders (`/so/process/{docNum}`).
   - Fast sub-second scan processing via composite database indexes on `scanned_data` (`[doc_num, item_code]`, `[doc_num, spk_code, label]`), `so_datas` (`[doc_num, item_code]`), and `wms_pallet_form_details` (`[part_no, label]`).
   - Frontend scanning features immediate Enter key handling, composite tab-delimited QR barcode parsing, and rapid debouncing (150ms).

8. **WMS Pallet Form Creation (`PalletFormCreator`, `WmsPalletForm`)**:
   - Manages pallet generation for internal transfer and delivery (`/wms/pallet-form/create-delivery`).
   - **Concurrency & Idempotency Protection**:
     - *Client-Side*: Button is throttled (`wire:click.throttle.2000ms`) and locked with Alpine.js (`x-data="{ isSubmitting: false }"`) at 0ms latency to eliminate browser double-click race conditions.
     - *Atomic Lock*: Server acquires `Cache::lock("wms_pallet_gen_lock_{$userKey}", 15)` to block concurrent requests per user/session.
     - *Payload Hash Idempotency*: Generates deterministic MD5 hash of production date, lot, delivery, shift, and scanned box list cached for 5 minutes (`wms_pallet_gen_hash_{$hash}`) returning existing pallet ID on repeat submissions.
     - *Database Label Guard*: Scans `WmsPalletFormDetail` within the last 10 minutes for any matching box label; if matched, re-attaches to the existing pallet instead of creating duplicate records.

9. **WMS Multi-Warehouse Rack Mapping (`RackMapping`, `WmsWarehouse`, `WmsRack`, `WmsPosition`)**:
   - Visual mapping and slot assignment for warehouse racks (`/wms/mapping`).
   - **Multi-Warehouse Support**:
     - Warehouse switching via reactive dropdown (`$whseId` bound to query string `?whseId=...`), auto-filtering rack grids and active statistics.
     - Warehouse creation modal (`+ TAMBAH GUDANG`): creates `WmsWarehouse` with unique code and auto-switches active view to the new warehouse.
     - Empty warehouse state provides direct `+ TAMBAH RAK PERTAMA` action.
     - Warehouse deletion guard: prevents deletion if warehouse still contains active racks or is the sole remaining warehouse.
     - Rack creation (`+ ADD RACK`) automatically sets `whse_id` to current active warehouse with uniqueness scoped per warehouse.
   - **Physical 2D Layouts & Shortest Path to Exit (`gub-layout.blade.php`, `gua-layout.blade.php`, `j06-layout.blade.php`, `SyncWmsGubLayout`, `SyncWmsGuaLayout`, `SyncWmsJ06Layout`)**:
     - Dual View Modes in `/wms/mapping`: `🗺️ Denah 2D (GUB / GUA / J06)` (interactive real-time layout mirroring physical factory blueprint) and `📋 Grid Kartu` (traditional card view).
     - **Blueprint Upload & Dynamic Exit Pinning**:
       - `wms_warehouses` table features `layout_image` and `exit_location` (`BOTTOM_LEFT`, `BOTTOM_RIGHT`, `BOTTOM_CENTER`, `TOP_LEFT`, `TOP_RIGHT`).
       - Modal `📷 UPLOAD LAYOUT / EDIT GUDANG` allows users to store real factory blueprint images and define the exit reference point.
     - **GUB (Gudang Utama B)**:
       - Horizontal rack orientation. Exit Gate benchmark is at bottom-right (`x=880, y=730`).
       - Priority Ranking: Rack `F25` is Rank #1 (closest to Exit, 450px), followed by `F24`, `F23`, `R01`, `R02`, `F32` down to `F31` (farthest, 1440px).
       - Artisan sync command: `php artisan wms:sync-gub-layout`.
     - **GUA (Gudang Utama A)**:
       - Vertical rack orientation (`orientation = 'VERTICAL'`).
       - Exit Gate benchmark: 🔴 Pintu Keluar is located at **bottom-left** (`x=180, y=730`) near Forklift dock #2.
       - Lower Block: 5 vertical pairs (`F01/F02` to `F09/F10`). Upper Block: 6 vertical pairs (`F11/F12` to `F21/F22`).
       - Priority Ranking: Rack `F01` (230px) and `F02` (266px) are Rank #1 & #2 (closest to Exit), followed by `F03/F04`, `F05/F06`, `F11/F12` down to `F21/F22` (farthest, 1026px).
       - Artisan sync command: `php artisan wms:sync-gua-layout`.
     - **Gudang 06 (J06 - Logistic & Store)**:
       - Dual-zone vertical layout: Green Zona Logistic (`F33`-`F40`), Blue Zona Store (`W09`-`W01`), plus horizontal top row `F41` & `W10`.
       - Exit Gate benchmark: 🟢 Pintu Keluar is located at **bottom-center** (`x=500, y=730`) indicated by the green arrow under `W08/W07` & `W06/W05`.
       - Priority Ranking: Rack `F40` (320px) and `W09` (336px) are Rank #1 & #2 (closest to Green Arrow Exit), followed by `F39`, `W08`, `F38`, `W07`, down to `F41` (farthest, 760px).
       - Artisan sync command: `php artisan wms:sync-j06-layout`.
     - Level selector pills (`Level 1 (Bawah)`, `Level 2`, `Level 3`, `Semua Level`) filter visual slots in the 2D layout with full status colors and real-time search pulse glow.
     - `WmsService::getShortestPathPickLocations($partNo, $neededQty)`: Orders pick locations ascending by distance score so pickers begin at the slot closest to the Exit Gate.

10. **Master Bill of Materials (`MasterBom`, `master_boms`)**:
    - Stores standardized parent-child BOM relationships directly extracted from SAP Business One without pre-processing.
    - **Schema & Precision**:
      - `sap_line_id`: Line/ID from SAP extract.
      - `parent_item`, `parent_description`: Father code (FG or WIP).
      - `component_item`, `component_description`: Child material or WIP component.
      - `quantity`: `decimal(16, 6)` storing unit consumption per 1 Qty Father (`base_qty = 1` implicit).
      - `uom`: Stock unit of measure (`PCS`, `KG`, `LT`).
      - Indexing: Individual indexes on `parent_item`, `component_item`, and composite index `[parent_item, component_item]`.
    - **Multi-Level Recursion & WIP Detection**:
      - `subComponents()`: Eloquent `hasMany(MasterBom::class, 'parent_item', 'component_item')`.
      - `is_wip`: True if `component_item` exists as `parent_item` in other rows.
      - `MasterBom::explodeTree($parentItem, $multiplier)`: Recursive method to explode complete material tree with total quantities down to base raw materials.
      - `MasterBom::getFlattenedSummary($parentItem, $multiplier)`: Aggregates net raw materials & chemicals needed for warehouse requisition, plus required WIP sub-assemblies for SPK creation.
    - **Production Dual-Table Architecture (`master_bom_fg_headers` & `master_bom_components`)**:
      - Staging raw extract from SAP (`master_boms`) is cleansed and synchronized into 2 normalized production tables via `MasterBomSyncService` (`php artisan bom:generate-valid` or UI button `⚡ VALIDASI KE TABEL PRODUKSI`):
      - **Header Table (`master_bom_fg_headers`)**: Stores real Finished Goods (`fg_item_code`, `fg_description`, `project_code`, `family`, `customer_name`, `total_wip_count`, `total_raw_count`, `max_depth_level`, `has_packaging`).
      - **Multilevel Detail Table (`master_bom_components`)**: Stores entire exploded hierarchy (`fg_id`, `parent_item`, `component_item`, `component_description`, `depth_level`, `item_type`, `is_wip`, `unit_qty`, `total_qty_per_fg`, `uom`, `lineage_path`).
      - **SAP Dummy Project Header Detection & 100% Deterministic Config (`config/bom_projects.php`)**:
        - In SAP Business One, users frequently misuse the Production BOM menu to create project groupings (e.g. `SHAD TOP CASE`, `KULKAS SHARP`, `YANFENG`) containing independent FGs (`D0B29100.`, `D0B46200KO`, etc.).
        - `config/bom_projects.php` maintains the explicit list of dummy project codes (`dummy_headers`), providing 100% deterministic exclusion and skipping without relying purely on heuristics.
        - `MasterBomSyncService` reads `config('bom_projects.dummy_headers')` first; if any new project headers are detected during sync, it automatically updates and sorts `config/bom_projects.php`.
        - **Excel Family Dual-Column Ingestion & Priority Resolution**:
          - Excel extract contains 2 `Family` columns (e.g. Col H and Col I).
          - All unique non-empty values from both columns are distincted and automatically merged into `config/bom_projects.php` under `dummy_headers`.
          - `family` value stored on `master_bom_fg_headers` and `master_boms` follows strict priority:
            1. If Column 1 is filled (including when both are filled) -> take Column 1.
            2. If only Column 2 is filled -> take Column 2.
            3. If both are empty -> `null`.
        - Dummy headers are skipped from becoming FG headers; instead, their parent name is assigned as `project_code` on each child FG header (e.g. `D0B29100.` gets `project_code = 'SHAD TOP CASE'`).
        - Dummy project codes are strictly guarded and never inserted into `master_bom_components` (neither as parent nor component).
        - Child FGs are recognized as Top-Level FGs because they possess packaging items (`BOX`, `BAG`, `USER GUIDE`, `WARRANTY`).
    - **UI & Presentation (`MasterBomView`, `bom-tree-node`)**:
      - *Dual-Tab Architecture*: Top-level tabs switch between **⚡ Master BOM Produksi (Sah)** (default: displays `master_bom_fg_headers` with interactive drill-down into `master_bom_components`) and **📥 Staging SAP (Mentah)** (displays raw `master_boms` extract with Excel upload & validate trigger).
      - *Production FG Header Table*: Shows Finished Goods with project code badge (e.g. `SHAD TOP CASE`), family badge (e.g. `YANFENG KS_PE`), description, customer, WIP sub-assembly count, raw material count, hierarchy depth level, packaging flag, and actions. Filterable by Project Code, Family, Packaging, and Depth Level.
      - *Table 2 Multilevel Components Modal*: Clicking `📋 Multilevel Komponen` opens the exploded recipe directly from `master_bom_components` with depth levels (L1, L2, L3, ...), parent assembly, component item, description, type (WIP, RAW, PACKAGING), unit qty, total qty per FG, simulation multiplier (calculating required net material for N units of FG), and lineage path.
      - *Where-Used (Reverse BOM)*: Real-time search to instantly find all Finished Goods that consume a given component or raw material.
      - *Staging Grouped View*: Shows 1 row per Parent Item with standard 8 columns (`#`, `Parent Item`, `Deskripsi Parent`, `Component Item`, `Deskripsi Komponen`, `Quantity`, `UoM`, `Aksi/Pohon`). If a parent has multiple components, a dropdown button (`▼ +X Komponen Lainnya`) opens a floating popover to switch active component in the row or toggle inline full table expansion (`📋 Expand`).
      - *PE Audit Verification & Re-verification (`master_bom_verifications`, `master_bom_verification_logs`)*:
        - 1-click audit verification & re-verification for PE (`verifyBom` / `unverifyBom`).
        - Automatically captures timestamp (`verified_at`), verifier user ID (`verified_by`), and name (`verified_by_name`).
        - Dedicated history log table (`master_bom_verification_logs`) preserves all historical verification events across re-verifications (`Verifikasi Awal`, `Verifikasi Ulang #1`, `#2`, dst).
        - Renders status in Table 1 (`✓ [Date] [Time]` + quick `🔄` re-verify button), and in Tree Explorer with `🔄 Verifikasi Ulang` and an interactive `📜 Riwayat (X Log)` popover displaying full audit trail.
        - Verification state is preserved across production synchronization cycles via `MasterBomSyncService`.
      - *Material Type Override for Engineering (`master_bom_material_overrides`)*:
        - Allows PE / Engineering users to override material classification per item code (`updateMaterialType`).
        - Specifically restricted to materials/leaf components (WIP remains fixed as `⚙️ WIP / Sub-Assembly`).
        - Supported categories: `RAW_MATERIAL` (Material / Part), `RESIN` (Biji Plastik / Resin), `CHEMICAL` (Cat / Chemical), `HARDWARE` (Hardware / Fastener), `PACKAGING` (Packaging / Kemasan).
        - Accessible directly via interactive dropdown badge in 3 places: Visual Tree Node, Flattened Summary Table, and Table 2 Multilevel Components Modal.
        - `MasterBom::determineCategory` prioritizes overrides before applying automatic regex/prefix rules, ensuring manual corrections persist across recalculations and syncs.
      - *Project Code & Family Grouping Integration*:
        - In SAP Business One extracts, family/grouping names from Excel (`Family` columns) are distincted and merged into `config/bom_projects.php` under `dummy_headers`.
        - `MasterBomSyncService` and `MasterBomView` automatically detect `family` into `project_code` grouping if `project_code` is empty (and vice versa).
        - Dropdown filter `📁 Semua Project (X FG)` in Table 1 groups and aggregates both `project_code` and `family` via `COALESCE(NULLIF(project_code, ""), family)`, ensuring all Finished Goods belonging to a family are immediately discoverable and filterable in the Project dropdown.
11. **PPIC Machine List Up & Daily Schedule Generator (`PpicListUp`, `PpicListUpItem`, `PpicListUpMachine`, `PpicListUpService`)**:
    - Manages PPIC daily list-up per machine (`/ppic/listup-machine`), auto-resolving parameters and generating schedules directly to `daily_item_codes`.
    - **Single Daily Schedule & History Navigation (No Zones)**:
      - Zones are removed: schedules are strictly 1 per calendar day (`date`).
      - Date navigation includes previous/next day arrows, quick pills (`Hari Ini`, `Besok`), direct date picker, and a `📅 Riwayat List Up` modal to inspect and switch to past/future schedules.
    - **Finalization & Lock State**:
      - When a schedule has been generated (`status = 'GENERATED'` or `'FINAL'`), the form is locked into read-only mode (`$isLocked = true`) with disabled inputs and disabled add/delete buttons to protect generated data.
      - A status banner provides an explicit `🔓 Buka Kunci (Edit Kembali)` action to transition the list-up back to editable `DRAFT`.
    - **Auto-Resolution Business Rules**:
      - `description`, `cavity`, `cycle_time`: resolved from `master_list_items`.
      - `target_per_hour`: calculated as `round(3600 / cycle_time)`.
      - `spk_no`: automatically picks the oldest active SPK from `spk_masters` (`production_status != 'C'`, ordered ascending by `post_date` and `id`).
      - `material_type`: extracted from child components in `master_bom_components` (or staging `master_boms`) matched against `master_list_materials` (fallback: `item_type = 'RAW_MATERIAL'` or code starting with `40%`).
    - **SPK Selection & Hybrid Input**:
      - `available_spks`: resolves all matching SPKs from `spk_masters` for the chosen part.
      - **Dropdown Mode**: Shows dropdown listing all matching SPKs with remaining quantity (`Sisa Qty`), pre-selecting the oldest open SPK.
      - **Cari SPK Master Modal (🔍)**: Searchable modal across all `spk_masters` displaying SPK number, Part No, Description, Planned Qty, Sisa Qty, Post Date, and Status. Selecting an SPK auto-fills Part No & resolves part details if currently blank.
      - **Manual Mode Toggle (✏️ / 📋)**: Allows switching freely between dropdown and manual text input.
      - **Missing SPK Warning**: When a part is selected but has no SPKs in `spk_masters` (or `spk_no` is blank), an inline alert badge `⚠️ Tidak ada SPK` is rendered directly under the input.
    - **Operator & Shift Allocation Logic**:
      - Sub-columns `Operator (I, II, III)` represent active shifts and headcount for Shift 1, 2, and 3.
      - `qty_to_run`: total planned quantity.
      - Allocated evenly across active shifts (`operator > 0`) using integer division (`intdiv($qty, $count)`) plus remainder distribution (`$base + ($idx < $rem ? 1 : 0)`). Example: Part with Operator I=1, II=1, III=1 and Qty=300 runs 100 pcs per shift.
      - Generates `DailyItemCode` records per active shift with standard shift timings (Shift 1: 07:30–15:30, Shift 2: 15:30–23:30, Shift 3: 23:30–07:30 next day).
      - Supports "+ Change To" button to append sub-runs on the same machine.
    - **DailyItemCode Compatibility & Machine Login Gotchas**:
      - `is_done`: Must strictly be saved as `null` (not `0`). `DashboardController` queries `->whereNull('is_done')` to display active jobs in the machine login dashboard; setting `0` makes them completely invisible.
      - `machine_id` vs `machine_name`: Machine accounts in `users` table have names like `0350F` (with leading zeroes). When machine is changed in the UI, `onMachineChange()` immediately updates `machine_id` so rows never map to mismatched machine users.
      - `MachineJob` Sync: When generating schedules, `PpicListUpService` automatically updates `MachineJob` with `dic_id` for Shift 1 so the job is pre-selected and immediately active when the operator logs into the machine dashboard.
      - **Number Input Spinner & Livewire 3 Morph Guard**:
        - Small grid number inputs (`Operator I, II, III`) suppress browser spinners via `[appearance:textfield]` and `-webkit-inner-spin-button: none` to prevent native browser up/down arrows from covering or masking the typed digits.
        - Inputs explicitly define `value="..."`, `placeholder="0"`, and unique `wire:key="op{shift}-{index}-{id}"` with `debounce.250ms` to protect the DOM element from being cleared or desynced during Livewire morphing.
      - **Schedule Re-generation & Obsolete DIC Cleanup**:
        - When a list-up is revised and re-generated, `PpicListUpService` updates or creates the new DICs, tracks their IDs, and automatically deletes old unstarted DICs (`ProductionOutputLog`, `production_scanned_data`, and `HourlyRemark` empty) for the involved machines.

12. **SPK BOM Changes & Leaf Material Requirement (`SpkBomService`, `SpkBomChangesView`, `SpkBomChangeLog`)**:
    - Manages integration between active SPK orders (`spk_masters`) and bill of materials (`/spk-bom-changes`).
    - **Leaf (Non-WIP) Material Enforcement**:
      - Strictly excludes WIP components (`is_wip = false` only).
      - Components that serve as fathers or have sub-assemblies are recursively exploded down to raw materials.
      - Dual-source resolution:
        - Priority 1: Official production tables (`master_bom_fg_headers` & `master_bom_components` grouped by `component_item` with `SUM(total_qty_per_fg) as unit_qty`).
        - Priority 2: Staging extract fallback (`MasterBom::getFlattenedSummary($itemCode, 1.0)['materials']`).
      - Core Formula: `planned material quantity = unit_qty * planned_quantity`.
    - **SAP Production Order Update API (`POST /api/sap_production_order/update`)**:
      - Handled via `SpkMasterService::updateProductionOrderLines` (extending `BaseSapService` with bearer auth token and retry).
      - Supported payload actions:
        - **Update Qty**: Sends `{ item_code, plan_qty }` (and optional `base_qty`).
        - **Add New Material**: Sends `{ item_code, base_qty, plan_qty, warehouse }`.
        - **Delete Material**: Sends `{ item_code, delete: true }`.
        - **Replace Material**: Sends dual lines: old item with `{ delete: true }` and new item with full quantities & warehouse.
    - **Audit Trail & Tracing (`spk_bom_change_logs` & `api_logs`)**:
      - Every material update, addition, replacement, and deletion is recorded in `spk_bom_change_logs` with `spk_number`, `action_type`, `item_code`, `plan_qty`, `old_plan_qty`, `base_qty`, `warehouse`, user (`created_by`, `created_by_name`), timestamp, and SAP status/response.
      - Dual-logged into `api_logs` for complete SAP integration audit trail.
    - **UI & Interaction**:
      - SPK table listing with status pills, planned qty, completed qty, due date, BOM availability, and change count badges (`📜 X Perubahan`).
      - Instant accordion toggle (`toggleSpk`) with component-level cache (`$spkBomCache`).
      - Interactive material action buttons: ✏️ Edit Qty, 🔄 Ganti Material, 🗑️ Hapus Material, and `+ Tambah Material` (hanya dimunculkan saat berada di dalam **Mode Edit Resep**).
      - **Material Dropdown Autocomplete & Auto-Calculated Plan Qty**:
        - Saat mengetik kode/nama material di modal Tambah atau Ganti material, dropdown suggestion mengambil data dari **Master List Material** (`MasterListMaterial` / `master_list_materials` dengan fallback ke `MasterListItem`).
        - User hanya perlu mengisi `base_qty` (kebutuhan per 1 unit FG). Input menggunakan `inputmode="decimal"` dengan reaktivitas lokal Alpine.js (`x-data` & `wire:model.blur`) sehingga operator bebas mengetik angka desimal (seperti `0.`, `0.15`, atau `0,15`) tanpa terganggu race condition debouncing jaringan Livewire yang dapat menghapus karakter titik (`.`).
        - `plan_qty` dihitung secara instan (0ms) di browser: `plan_qty = base_qty * SPK Planned Qty` dan otomatis tersinkronisasi saat submit draft.
        - Mencegah spam request ke API SAP pada setiap aksi individual. Operator masuk ke **Mode Edit Resep** (`startEditMode`).
        - Seluruh aksi edit quantity, tambah material baru, tandai hapus material, dan ganti material dicatat ke draft sementara (`$stagedLines`) tanpa memicu HTTP request ke SAP.
        - Tampilan tabel interaktif menampilkan status draft secara visual: baris terhapus (strikethrough merah `🗑️ Ditandai Hapus (Draft)`), baris diubah (kuning `✏️ Diubah (Draft)` dengan nilai lama vs baru), dan baris baru (hijau `➕ Baru (Draft)`).
        - Setiap baris memiliki tombol Undo / Batalkan (`unstageItem`) untuk mengembalikan ke nilai semula tanpa dampak ke SAP.
        - Tombol `🚀 Selesai & Kirim ke SAP (X)` (`submitBatchChanges`) mengumpulkan seluruh `$stagedLines` menjadi satu single payload array `lines: [...]` dan mengirimkannya sekaligus ke `POST /api/sap_production_order/update`.
        - Tombol `✕ Batal Edit` (`cancelEditMode`) membatalkan seluruh perubahan draft dan keluar dari mode edit.
      - Chronological Timeline Modal (`openHistoryModal`) showing full history of changes (timestamp, operator/user, action badge, old vs new qty, and SAP response status).
      - Batch actions: `expandAll` and `collapseAll` on active page.
      - Quick filters: search (SPK no, item code, description), status (R, P, C), and BOM status (`with_bom`, `without_bom`).

8. **User & Role Permissions Management (`RoleManager`, `Permission`, `Role`, `role_permissions`)**:
   - Authorized users with `manage-users-roles` or `SUPER-ADMIN` can CRUD roles and assign/unassign granular permissions via `/user-role-manager?tab=roles`.
   - **Database Architecture**:
     - `permissions`: `[id, name, label, group, description]`.
     - `role_permissions`: pivot `[role_id, permission_id]`.
     - `roles.permissions_configured`: boolean flag distinguishing configured roles from legacy/unconfigured test fixtures, preserving 100% backward compatibility.
   - **Gate Evaluation**: Dynamic evaluation in `AuthServiceProvider` and `User::hasPermission()`. `SUPER-ADMIN` retains root access via `Gate::before`.
   - **Fine-Grained Pilot (Second Process Domain - Consolidated 1-Permission per Feature)**:
     - Consolidates view and manage into 1 permission per menu/feature so Super Admin only toggles 1 permission to grant both sidebar navigation visibility and full feature capabilities:
       - `second-process-work-orders`: Work Orders (view, create, edit, release, revert).
       - `second-process-reports`: Daily Production Reports (view, create, edit, delete, reconcile).
       - `second-process-dashboard`: Floor Overview Dashboard & Live Machine Monitor.
       - `second-process-sessions`: Production Sessions / Line Gateway execution.
       - `second-process-approvals`: Production Approvals authorization.
       - `second-process-analytics`: Daily Report Analytics dashboard.
       - `second-process-first-piece`: First Piece Inspection quality gates.
       - `second-process-ipqc`: In-Process Quality Control inspection records.
     - **Backward Compatibility & Alias Mapping**: `AuthServiceProvider` gates and `Role::defaultPermissionFallback` automatically map legacy split checks (`view-*`, `manage-*`) to the canonical feature gate.
     - **Consolidated Navigation**: All Second Process sub-features are consolidated into a single "Second Process" parent dropdown in `sidebar.blade.php`, dynamically rendering only child links permitted for the authenticated user.
     - **Plant Access Middleware (`EnsureSecondProcessPlantAccess`)**: Evaluates canonical route gates. Instead of leaving users stuck on an HTTP 403 error page, unauthorized web requests redirect back to the previous page (with a safe dashboard fallback preventing loops) with an error flash message, while API/JSON requests return a 403 JSON response.

## 3. Essential Development Guidelines
- Always verify changes with automated tests via Sail: `./vendor/bin/sail test --filter=<TestClass>`.
- Maintain field validation integrity, error feedback banners, and tab switching handlers across legacy Blade views.
- Keep calculations consistent between client-side Alpine/JS real-time updates and server-side controller persistence.

## 4. Continuous Knowledge Maintenance (Mandatory for Agent)
- **Automatic Updates**: Upon finishing an implementation plan, completing a major change, or before the conversation session wraps up / gets compacted, the agent **MUST automatically update `.agents/AGENTS.md`** with any new context, newly added features, schema changes, or discovered business rules.
- **Autonomous & Zero-Prompt**: Do NOT wait for the user to ask or remind you to update this file. Proactively review and update it as part of finishing tasks.
- **Maintain High-Signal Content**: Keep entries concise, structured, and focused on business rules, architecture, and gotchas so the file remains punchy and within optimal context size.
