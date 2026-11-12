# PT Daijo Industrial MES: Architectural Overview

## 1. System Overview & Tech Stack
- **Application**: PT Daijo Industrial MES (Manufacturing Execution System)
- **Backend**: Laravel 10/12 (PHP 8.1/8.4), MySQL, Redis
- **Frontend**: Blade templates, Tailwind CSS, Alpine.js, Livewire 3
- **Development & Testing**: Docker via Laravel Sail (`./vendor/bin/sail test`)
- **ERP Integration**: SAP Business One API (Production Orders, Receipts, Transfers)

---

## 2. Domain Modules Index
The application domain logic is partitioned into dedicated modular specifications:

| Domain Module | Primary Models / Services | Specification File |
| :--- | :--- | :--- |
| **Second Process** | `SecondProcessReport`, `SecondProcessReportController` | [second-process.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/second-process.md) |
| **Master Items & Customers** | `MasterListItem`, `MasterCustomerDelivery`, `MasterBusinessPartner` | [master-list-customers.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/master-list-customers.md) |
| **Quality Inspections** | `FirstPieceInspection`, `IpqcInspection` | [quality-inspections.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/quality-inspections.md) |
| **Work Orders & Assembly** | `SpWorkOrder`, `AssemblyDailyProcess` | [work-orders-assembly.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/work-orders-assembly.md) |
| **SAP Gateway & Monitor** | `BaseSapService`, `SpkMasterService`, `ReceiptProductionLogs` | [sap-gateway.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/sap-gateway.md) |
| **Production Dashboard** | `ProductionDashboardService`, `DashboardController` | [production-dashboard.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/production-dashboard.md) |
| **Store Out & Delivery** | `SOController`, `SoTracking` | [store-out-delivery.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/store-out-delivery.md) |
| **WMS Pallet & Recap** | `PalletFormCreator`, `WmsPalletForm`, `DailyDeliveryRecap` | [wms-pallet-recap.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/wms-pallet-recap.md) |
| **WMS Rack Mapping & 2D** | `WmsWarehouse`, `WmsRack`, `WmsPosition`, `RackMapping` | [wms-rack-mapping.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/wms-rack-mapping.md) |
| **Master BOM & Sync** | `MasterBom`, `MasterBomFgHeader`, `MasterBomComponent` | [master-bom.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/master-bom.md) |
| **PPIC Scheduling** | `PpicListUp`, `PpicListUpItem`, `PpicListUpService` | [ppic-schedule.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/ppic-schedule.md) |
| **SPK BOM Changes** | `SpkBomService`, `SpkBomChangesView`, `SpkBomChangeLog` | [spk-bom-changes.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/spk-bom-changes.md) |
| **Access Management & RBAC** | `User`, `Role`, `Permission`, `AuthServiceProvider` | [access-management.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/access-management.md) |
| **Material Warehouse & FIFO** | `MwhDailyAudit`, `MaterialFifoService` | [material-warehouse.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/material-warehouse.md) |

