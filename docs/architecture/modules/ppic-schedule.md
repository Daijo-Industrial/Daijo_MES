# Daijo MES: PPIC Machine List Up & Daily Schedule Generator

## Overview
Manages PPIC daily list-up per machine (`/ppic/listup-machine`), auto-resolving parameters and generating schedules directly to `daily_item_codes`.

**Models & Services**:
- Models: `App\Models\PpicListUp`, `App\Models\PpicListUpItem`, `App\Models\PpicListUpMachine`, `App\Models\DailyItemCode`
- Service: `App\Services\PpicListUpService`
- Component: `App\Livewire\PpicListUp`

---

## Single Daily Schedule & Lock State
- Schedules are strictly 1 per calendar day (`date`). No separate zones.
- Date navigation with previous/next day arrows, pills (`Hari Ini`, `Besok`), date picker, and history modal.
- When generated (`GENERATED` or `FINAL`), form is locked read-only (`$isLocked = true`) with an explicit `🔓 Buka Kunci` action to transition back to editable `DRAFT`.

---

## Auto-Resolution Rules
- `description`, `cavity`, `cycle_time`: resolved from `master_list_items`.
- `target_per_hour`: calculated as `round(3600 / cycle_time)`.
- `spk_no`: oldest active SPK from `spk_masters` (`production_status != 'C'`, ordered ascending by `post_date` and `id`).
- `material_type`: extracted from child components in `master_bom_components` (or staging `master_boms`) matched against `master_list_materials` (fallback: `item_type = 'RAW_MATERIAL'` or code starting with `40%`).

---

## Shift & Headcount Allocation
- Sub-columns `Operator (I, II, III)` represent active shifts and headcount for Shifts 1, 2, and 3.
- `qty_to_run`: allocated evenly across active shifts (`operator > 0`) using integer division (`intdiv($qty, $count)`) plus remainder distribution (`$base + ($idx < $rem ? 1 : 0)`).
- Generates `DailyItemCode` records per active shift with standard shift timings.

---

## Critical Machine Dashboard Gotchas
- `is_done`: Must strictly be saved as `null` (not `0`). `DashboardController` queries `->whereNull('is_done')` to display active jobs in machine login dashboard; setting `0` makes them completely invisible.
- `machine_id` vs `machine_name`: Machine accounts in `users` table have names like `0350F`. UI updates `machine_id` immediately on change.
- `MachineJob` Sync: When generating schedules, `PpicListUpService` automatically updates `MachineJob` with `dic_id` for Shift 1 so the job is pre-selected upon operator login.
- Number Input Spinner: Suppressed via CSS `[appearance:textfield]` with unique `wire:key="op{shift}-{index}-{id}"` and `debounce.250ms` to protect Livewire 3 DOM morphing.
- Cleanup: Old unstarted DICs (`ProductionOutputLog`, `production_scanned_data`, and `HourlyRemark` empty) are deleted when re-generating.

