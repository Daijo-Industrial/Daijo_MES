# Daijo MES: Access Management & RBAC Suite

## Overview
Enterprise role-based access control (RBAC), permission matrix, and plant access middleware (`/access-management`).

**Routes & Components**:
- Canonical route: `GET /access-management` (`admin.access-management`)
- Legacy alias: `GET /user-role-manager` (`admin.user-role-manager`)
- Manager Components: `UserRoleManager`, `RoleManager`, `PermissionManager`

---

## Four-Pillar Architecture
1. **Users (`tab=users`, `UserRoleManager`)**: User account credentials, plant/branch and department scope assignment, password reset, active/deleted status.
2. **Roles (`tab=roles`, `RoleManager`)**: System and custom roles directory, user associations, and role permission configuration via dedicated modals.
3. **Permissions (`tab=permissions`, `PermissionManager`)**:
   - **Immutable Identifier (`name`)**: `permissions.name` (e.g. `second-process-reports`) is strictly read-only (application gates depend on it).
   - **No Create / Delete via UI**: Permissions cannot be added or deleted by administrators via web interface.
   - **Editable Attributes**: Administrators can only edit human-readable `label`, `group` (module grouping), and `description`.
4. **Permission Matrix (`tab=matrix`, `RoleManager`)**:
   - **Strictly Read-Only Reference Plane**: The 2D permission matrix is purely an audit and inspection reference grid.
   - **Zero Direct Mutation**: Never place `wire:click` toggle buttons or batch actions directly inside the 2D matrix table cells to prevent accidental flips.
   - **Deliberate Role Configuration**: Permission changes must be executed from the `Roles` directory via the role permission configuration modal (`manage-permissions-modal`) requiring an explicit commit.

---

## Database Architecture & Evaluation
- `permissions`: `[id, name, label, group, description]`
- `roles`: `[id, name, permissions_configured]`
- `role_permissions`: pivot `[role_id, permission_id]`
- `roles.permissions_configured`: boolean flag distinguishing configured roles from legacy fixtures.
- Dynamic evaluation in `AuthServiceProvider` and `User::hasPermission()`. `SUPER-ADMIN` retains root access via `Gate::before`.

---

## Consolidated 1-Permission per Feature (Second Process Pilot)
- Consolidates view and manage into 1 permission per menu/feature:
  - `second-process-work-orders`: Work Orders (view, create, edit, release, revert).
  - `second-process-reports`: Daily Production Reports (view, create, edit, delete, reconcile).
  - `second-process-dashboard`: Floor Overview Dashboard & Live Machine Monitor.
  - `second-process-sessions`: Production Sessions / Line Gateway execution.
  - `second-process-approvals`: Production Approvals authorization.
  - `second-process-analytics`: Daily Report Analytics dashboard.
  - `second-process-first-piece`: First Piece Inspection quality gates.
  - `second-process-ipqc`: In-Process Quality Control inspection records.
- **Plant Access Middleware (`EnsureSecondProcessPlantAccess`)**: Unauthorized web requests redirect back to the previous page with error flash message, API requests return 403 JSON.

