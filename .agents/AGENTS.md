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

## Post-Test Cleanup & Git Staging Invariant
Setiap kali menjalankan pengujian dan mencapai status 100% checks pass:
1. **100% Verification**: Pastikan seluruh test suite terkait (Feature/Unit) lulus tanpa regresi sebelum melangkah ke penyelesaian tugas.
2. **Post-Test Workspace Cleanup**:
   - Hapus semua file sementara (temporary spreadsheets, scratch scripts, mock uploads) yang dibuat selama proses testing.
   - Kembalikan/revert file cache atau transient generated artifacts (misalnya `.phpunit.result.cache`) agar tidak mengotori repositori.
   - Hindari mengecek file spreadsheet dummy/sensitif ke dalam git kecuali secara eksplisit diminta.
3. **Selective Git Staging**:
   - JANGAN PERNAH menjalankan blind `git add .` atau `git commit -a`.
   - Periksa `git status` secara teliti.
   - Stage hanya file kode yang sengaja diubah dan diuji. Jaga agar konfigurasi lokal pengembang (seperti IP HMR dev server di `vite.config.js` atau catatan kerja) tetap terisolasi dan tidak ter-stage secara tidak sengaja.

---

## Testing Environment & Database Safety Invariant
1. **Strict Testing Isolation**:
   - Seluruh aktivitas pengujian (test suite, benchmarks, verifikasi controller) WAJIB menggunakan environment `testing` (`DB_DATABASE=testing`).
   - Selalu jalankan pengujian melalui `./vendor/bin/sail test` yang secara otomatis mengisolasi koneksi ke database `testing` sesuai `phpunit.xml`.
2. **Zero Local Data Loss & Destructive Command Ban**:
   - JANGAN PERNAH menjalankan perintah database destruktif (`migrate:fresh`, `db:wipe`, `migrate:reset`) terhadap database lokal/development (`DAIJO_MES`).
   - Jangan berasumsi opsi `--env=testing` otomatis aman tanpa memastikan file `.env.testing` ada dan koneksi aktif menunjuk ke database `testing`.
   - Jika perlu melakukan investigasi artisan, pastikan target database terverifikasi sebelum mengeksekusi (`config('database.connections.mysql.database') === 'testing'`).
3. **KPI Summary Cards UI Consistency**:
   - Kartu ringkasan metrik (KPI cards) harus difokuskan sebagai penyaji data ringkas (label, nominal, persentase) tanpa menumpuk tautan aksi/filter inline yang redundan dengan toolbar filter utama.

---

## Shop-Floor UI & Datatable Presentation Invariant
1. **Clean, Direct & No-Emoji**:
   - DILARANG menggunakan emoji dekoratif (seperti `✓`, `⏳`, `⛔`, `📋`, `📝`, `🔧`), simbol panah teks (`→`, `←`), atau subjudul/paragraf instruksi bertele-tele pada view formulir dan tabel MES.
   - Gunakan badge status berteks tegas dan langsung (`Approved`, `Reject`, `Menunggu`, `Ongoing`, `Draft`).
2. **Zero Redundant Icon/Text Glyphs**:
   - DILARANG menduplikasi simbol ikon ke dalam label teks tombol aksi.
   - Jika tombol sudah memiliki ikon SVG plus (`+`), label teks TIDAK BOLEH diawali tanda `+` (misal gunakan `<svg>...</svg> <span>New Report</span>`, BUKAN `<span>+ New Report</span>`).
   - Pilih salah satu: gunakan ikon SVG dengan label bersih atau gunakan teks bersimbol tanpa ikon SVG.
3. **Index Filter Bar Architecture (Progressive Disclosure & Batch Apply)**:
   - **Minimized Mode by Default**: Bilah filter pada halaman index MES harus tampil dalam mode ringkas (minimized) yang hanya menampilkan filter yang sedang aktif (misalnya preset default "This Month" atau parameter aktif lainnya) bersama tombol toggle pembuka `[Ubah Filter]` / `[Tutup Filter]`.
   - **Single Batch Submission**: Seluruh filter lanjutan/ekspansi (pencarian, dropdown sektor, customer, line, status) harus diterapkan melalui tombol aksi eksplisit `[Apply Filter]` dan `[Reset]`. Jangan melakukan auto-request pada setiap interaksi dropdown individu agar tidak memicu lonjakan request ke backend dan layout jumping saat operator memilih beberapa filter sekaligus.
   - **Stateless by Default**: Jangan menyimpan state buka/tutup filter atau draft filter di `localStorage` kecuali secara eksplisit diminta, untuk menjaga perilaku formulir tetap bersih, konsisten, dan mudah diprediksi.
4. **Filter Preset Pills & SSR/Alpine Class Integrity**:
   - Tombol pill preset (seperti `Today`, `Yesterday`, `This Month`, `Custom Range`, `All Dates`) WAJIB merender class status aktif/inaktif awal secara langsung di server Blade (`class="{{ $active ? $activeClass : $inactiveClass }}"`) untuk mencegah flash of unstyled content (FOUC) saat halaman pertama kali dimuat.
   - **No Conflicting Class Merging**: Hindari mencampur static utility class (`bg-slate-50`) dengan Alpine `:class` yang menambahkan kelas bertentangan (`bg-blue-600`), karena Alpine v3 tidak menghapus kelas yang sudah didefinisikan pada atribut static `class`.
   - Gunakan fungsi JavaScript terpusat (`setDatePreset(preset)`) dengan selektor `data-preset` untuk memanipulasi class DOM (`classList.add`/`classList.remove`) secara deterministik saat tombol preset diklik.
5. **Zero Horizontal Scroll on Desktop Datatables**:
   - Tabel desktop (layar `>= xl`) yang berada di dalam layout bersidebar (`lg:pl-64` / 256px) tidak boleh memiliki horizontal scrollbar pada resolusi standar (1280px / 1366px).
   - JANGAN menggunakan batas minimum paksa seperti `min-w-[1000px]`.
   - Gunakan `<table class="w-full table-fixed ...">` dengan persentase lebar kolom yang terukur pas (total 100%) dan padding sel kompak (`px-2 py-3` / `px-1.5`).
6. **Strict Visual Anti-Pattern Bans (Enterprise Control)**:
   - **Zero Decorative Emojis**: DILARANG keras menggunakan emoji dekoratif atau simbol panah teks pada header, status, atau tabel.
   - **Zero Purple/Violet Pastels**: Hindari gradasi atau aksen ungu/violet/indigo (`violet-*`, `purple-*`, `indigo-*`). Gunakan palet industrial berkontras tinggi (monochrome zinc/slate + status aksen fungsional emerald/rose).
   - **Zero Sparkly / Shiny Badges**: DILARANG menggunakan pill bulat pastel dengan titik indikator menyala/berkedip. Gunakan tag status berteks tegas dan rapi (`Active`, `Inactive`, `System`, `Custom`).
7. **Simple, Plain English & Direct Copy**:
   - Gunakan terminologi aplikasi yang standar, jelas, dan umum dikenal (`Access Management`, `Users`, `Roles`, `Permissions`, `Permission Matrix`, `Add User`, `Add Role`, `Active`, `Inactive`, `Save`, `Cancel`).
   - DILARANG menggunakan jargon sci-fi/aerospace berlebihan (misal: "POLICY ENFORCEMENT: ACTIVE", "GATE COMMIT", "AUDIT PLANE") maupun template marketing fluff.
8. **Single Source of Truth Navigation (No Redundant Switchers)**:
   - Jika halaman induk sudah memiliki segmented navigation bar (seperti `tab=users`, `tab=roles`, `tab=permissions`, `tab=matrix`), komponen anak DILARANG membuat toolbar switcher sekunder yang menduplikasi navigasi tersebut.

---

## Database Migrations & Livewire Reactivity Invariant
1. **Dedicated Alter Migrations**:
   - JANGAN PERNAH mengedit file migrasi pembuatan tabel yang sudah ada (`create_*_tables.php`) ketika menambah kolom atau mengubah atribut kolom.
   - Selalu buat migrasi alter baru (`add_*_to_*_table.php`) dengan pengecekan `Schema::hasColumn` pada `up()` dan rollback yang lengkap pada `down()`.
2. **Livewire Live Binding for Summary-Affecting Inputs**:
   - Jika suatu input formulir mempengaruhi teks ringkasan, indikator rentang (misal tanggal mulai - selesai), atau badge status di bagian lain view, input tersebut WAJIB menggunakan `wire:model.live`. Penggunaan binding biasa (deferred) akan menyebabkan UI tidak reaktif hingga event lain terpicu.
3. **No Unfinished Placeholder Logic**:
   - Dilarang meninggalkan string placeholder atau potongan logika setengah jadi (seperti string mentah `+hari`). Seluruh logika kalkulasi interval waktu, selisih hari, dan rentang tanggal wajib diimplementasikan tuntas dan teruji.
4. **Livewire Single-Root Element Invariant**:
   - Setiap view komponen Livewire WAJIB mempertahankan tepat 1 elemen root HTML pembungkus (tanpa ada tag `</div>` penutup prematur di dalam percabangan `@if`).

---

## Tech Stack & Architecture Map
- **Application**: PT Daijo Industrial MES (Manufacturing Execution System)
- **Backend**: Laravel 10/12 (PHP 8.1/8.4), MySQL, Redis
- **Frontend**: Blade templates, Tailwind CSS, Alpine.js, Livewire 3
- **Dev & Test**: Docker via Laravel Sail (`./vendor/bin/sail test`)
- **ERP Gateway**: SAP Business One API (`App\Services\Sap\BaseSapService`)

### Domain Modules Reference
Detailed architectural specifications, schemas, workflows, and business rules are maintained in dedicated module docs:
- **Architecture Overview**: [overview.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/overview.md)
- **Second Process**: [second-process.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/second-process.md) — 4-tab workflow, checker->leader->pqc->spv signatures, input/output reconciliation.
- **Master List & Customers**: [master-list-customers.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/master-list-customers.md) — Customer resolution hierarchy, manager livewire view.
- **Quality Inspections**: [quality-inspections.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/quality-inspections.md) — First Piece Inspection gate & IPQC sample logs.
- **Work Orders & Assembly**: [work-orders-assembly.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/work-orders-assembly.md) — Dispatching, barcode box printing, packaging tracking.
- **SAP ERP Gateway**: [sap-gateway.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/sap-gateway.md) — Atomic lock token cache, receipt pushes, spk:sync, receipt monitor.
- **Production Dashboard**: [production-dashboard.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/production-dashboard.md) — Shift half-day toggles, Karawang vs KBN adjuster restrictions, NG breakdown.
- **Store Out & Delivery**: [store-out-delivery.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/store-out-delivery.md) — Composite indexes, DO scan tracking, CSV export.
- **WMS Pallet & Recap**: [wms-pallet-recap.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/wms-pallet-recap.md) — 07:30 cutoff, atomic pallet locks, daily delivery recap, putaway scan.
- **WMS Multi-Warehouse Rack Mapping**: [wms-rack-mapping.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/wms-rack-mapping.md) — 2D factory layouts (GUB, GUA, J06), shortest path pick algorithm.
- **Master BOM**: [master-bom.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/master-bom.md) — Staging vs production dual tables, dummy SAP headers, PE verification, material type overrides.
- **PPIC Scheduling**: [ppic-schedule.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/ppic-schedule.md) — Single daily schedule, DIC generator, integer shift distribution, machine job sync.
- **SPK BOM Changes**: [spk-bom-changes.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/spk-bom-changes.md) — Leaf material enforcement, staging draft batch updates to SAP.
- **Access Management (RBAC)**: [access-management.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/access-management.md) — 4 pillars (Users, Roles, Permissions, Matrix), consolidated feature permissions, plant access middleware.
- **Material Warehouse & FIFO**: [material-warehouse.md](file:///home/monlay/projects/Daijo_MES/docs/architecture/modules/material-warehouse.md) — Daily audit, temporary transit area, reconstructed historical FIFO forensic audit.

---

## Essential Development Guidelines
- Always verify changes with automated tests via Sail: `./vendor/bin/sail test --filter=<TestClass>`.
- Maintain field validation integrity, error feedback banners, and tab switching handlers across legacy Blade views.
- Keep calculations consistent between client-side Alpine/JS real-time updates and server-side controller persistence.

---

## Continuous Knowledge Maintenance
- **Domain Updates**: When adding or updating domain features, schema changes, or business rules, update the corresponding document under `docs/architecture/modules/` rather than bloating this root rule file.
- **Maintain High-Signal Rules**: Keep `.agents/AGENTS.md` strictly focused on cross-cutting invariants, decision ladders, and essential development guidelines to prevent prompt truncation and maintain 100% rule compliance.
