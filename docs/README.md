# Daijo MES - Technical Documentation Hub

Pusat dokumentasi teknis sistem **PT Daijo Industrial MES (Manufacturing Execution System)**.

---

## 📚 Struktur Direktori Dokumentasi

```
docs/
├── README.md                      # Indeks utama dokumentasi (File ini)
├── SAP_MES_INTEGRATION_GUIDE.md   # Panduan lengkap integrasi dua arah MES <-> SAP & Webhook
├── SERVICES_REFERENCE.md          # Katalog dan referensi detail seluruh 28 Service di app/Services
├── WMS_DOCUMENTATION.md           # Dokumentasi modul Warehouse Management System (WMS)
└── bruno/                         # Koleksi API & Webhook siap pakai menggunakan Bruno
    ├── bruno.json                 # Manifest koleksi Bruno
    ├── environments/              # File Environment (Local & Production)
    │   ├── Local.bru
    │   └── Production.bru
    ├── Outbound - SAP API/        # 13 Endpoint panggilan keluar dari MES ke SAP
    │   ├── 01 - Auth Token.bru
    │   ├── 02 - Receipt Production Create.bru
    │   ├── 03 - Inventory Transfer Create (QC Transfer).bru
    │   ├── 04 - Inventory Transfer Create (WMS Pallet Transfer).bru
    │   ├── 05 - SPK Production Order List.bru
    │   ├── 06 - Delivery Sales Order List.bru
    │   ├── 07 - Delivery Schedule List.bru
    │   ├── 08 - Delivery Actual List.bru
    │   ├── 09 - BOM WIP List.bru
    │   ├── 10 - Inventory Material List.bru
    │   ├── 11 - Inventory Finished Goods List.bru
    │   ├── 12 - Line Production Routing List.bru
    │   └── 13 - Reject Inventory List.bru
    └── Inbound - Webhooks & Endpoints/ # 4 Endpoint panggilan masuk dari SAP / Customer
        ├── 01 - SAP DO Webhook (POST sap-do).bru
        ├── 02 - SAP SPK Webhook (POST sap-spk).bru
        ├── 03 - SAP Master List Item Webhook (POST sap-master-list-item).bru
        └── 04 - Customer Production Status Range (GET).bru
```

---

## 🚀 Panduan Cepat

### 1. Menjalankan Bruno Collection
1. Unduh dan buka aplikasi [Bruno](https://www.usebruno.com/).
2. Klik **Open Collection** dan arahkan ke path folder:
   `/home/andreas/projects/Daijo_MES/docs/bruno`
3. Pilih Environment di pojok kanan atas:
   - `Local` (`http://localhost:8000`)
   - `Production` (`http://116.254.114.93:8000`)
4. Jalankan request yang diinginkan.

### 2. Pelajari Integrasi SAP
Baca dokumen lengkap di [`SAP_MES_INTEGRATION_GUIDE.md`](file:///home/andreas/projects/Daijo_MES/docs/SAP_MES_INTEGRATION_GUIDE.md) untuk memahami diagram arsitektur, token caching dengan atomic lock, idempotensi transfer, middleware keamanan `ip.and.apikey`, serta cron scheduler.

### 3. Pelajari Katalog Service
Baca dokumen lengkap di [`SERVICES_REFERENCE.md`](file:///home/andreas/projects/Daijo_MES/docs/SERVICES_REFERENCE.md) untuk mempelajari seluruh 28 service yang ada di folder [`app/Services/`](file:///home/andreas/projects/Daijo_MES/app/Services/), dikelompokkan berdasarkan domain bisnis (SAP, Produksi, WMS, BOM, dan Utility).
