# Daijo MES: Second Process Domain

## Overview
Manages daily secondary processing across multiple operational categories: Painting, Buffing, Amplas, Treatment, Packing, Rework, Repair, and Assembly.

**Model / Controller**:
- Model: `App\Models\SecondProcessReport`
- Controller: `App\Http\Controllers\SecondProcessReportController`
- Analytics: `App\Http\Controllers\SecondProcessReportAnalyticsController`

---

## Form Tab Structure
- **Tab 1 (Setup & Manpower)**: Shift logistics, part number, customer, manpower allocations.
- **Tab 2 (Materials)**:
  - Item Paint (viscosity, mixing ratio, qty — active for `Painting` & `Repair`).
  - Item Parts / WIP Lots (WIP + Repairan reconciliation).
- **Tab 3 (Production Logs & NG)**: Target per hour, hourly production slots, NG breakdown by defects & remarks.
- **Tab 4 (Handover & Signs)**: Next schedule, downtime/troubles (`loss_time_minutes`), operator (Checker), Leader, optional PQC (placed after Leader), and Supervisor signoffs.

---

## Signature Workflow & Configurable Mapping (`config/roles.php`)
- **Sequential Workflow**:
  1. Checker (`submitted`)
  2. Leader (`leader_approved`)
  3. Optional PQC (`pqc_approved`)
  4. Supervisor (`acknowledged`)
- **Rejection**: Authorized dynamically for users who can sign as `leader`, `pqc`, or `acknowledged` (or admins) back to draft status.
- **Configuration & SSOT**:
  - Signature slot authorization is strictly centralized in `config('roles.signature_mapping.second_process')` and evaluated via `$user->canSign('second_process', $slot)`.
  - `config/roles.php` serves as the Single Source of Truth for IAM/security and signatures.
  - `config/mes.php` exclusively governs physical shop-floor operational parameters (shifts, lines, chemical processes, workstation station types `sp_manpower_roles`, and defect categories).
- **Granular Action & Workflow Guards (`SecondProcessReportController`)**:
  - `destroy`: Strictly restricted to `SUPER-ADMIN`, `ADMIN`, or `SUPERVISOR` (`acknowledged`).
  - `reject`: Authorized dynamically for users who can sign as `leader`, `pqc`, or `acknowledged` (or admins).
  - `edit`/`update`: Strictly restricted to report creator, authorized `checker`/`leader`, and admins when report is in `draft` status.
  - Universal bypass: `SUPER-ADMIN` and `ADMIN` are globally authorized to sign any approval slot.

---

## Customer Enforcement & Auto-Conversion
- The `customer` field is strictly enforced: must be an official customer name (`MasterCustomerDelivery::customer_name`) or `'N/A'`.
- Empty, `'0'`, `'-'`, or case-insensitive `'n/a'` are automatically normalized to `'N/A'`.
- Valid `customer_code` inputs (e.g. `CUST-001`) are automatically resolved and converted to `customer_name` on save.
- Part number search (`searchItems`) returns `$item->customer?->customer_name ?: 'N/A'` (never leaks raw codes or `'0'`).

---

## Material Input vs Production Output Reconciliation
- **Total Input** = `jml_input_wip + repairan` (accumulated in Tab 2).
- **Total Output** = `jumlah_ok + jumlah_ng + jml_ng_lebur` (accumulated from OK, NG, and Scrap in Tab 3).
- **Sisa Input** = `Total Input - Total Output` (persisted in `sisa_input` column).
- **Submission Rules**:
  - **Deficit (`Total Input < Total Output`)**: Submission blocked in client-side JS and backend controller (`ValidationException` on `sisa_input`). Draft saving allowed.
  - **Leftover (`Total Input > Total Output`)**: Allowed to submit, but creator must provide `sisa_input_remark`.
  - **Ideal (`Total Input == Total Output`)**: Submittable immediately without requiring remarks.

---

## Tab 3 UI Layout & Zone Separation
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
- **Form Input Compatibility**:
  - All form input names (`target_per_hour`, `jml_input_wip`, `repairan`, `jml_ng_lebur`, `jumlah_output`, `jumlah_ok`, `jumlah_ng`, `ng_prosentase`, `sisa_input`, `sisa_input_remark`) are strictly preserved.

