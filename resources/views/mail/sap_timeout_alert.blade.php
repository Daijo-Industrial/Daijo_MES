<x-mail::message>
# ⚠️ Peringatan Integrasi SAP: Operation Timed Out

@if ($isTest)
<x-mail::panel>
🧪 **Ini adalah email pengujian (TEST MODE)** yang dikirim melalui perintah `php artisan sap:alert-timeout --test` untuk memverifikasi konfigurasi pengiriman email ke **andreas@daijo.co.id** dan **timo@daijo.co.id**.
</x-mail::panel>
@else
<x-mail::panel>
Sistem mendeteksi adanya kegagalan koneksi **Operation Timed Out (cURL error 28)** saat memproses pengiriman data **Receipt Production** ke server SAP Business One Gateway.
</x-mail::panel>
@endif

### 📊 Ringkasan Kejadian
* **Total Timeout Terdeteksi:** **{{ $summary['total_timeouts'] }}x**
* **Kejadian Pertama:** {{ $summary['first_occurred'] ?? '-' }} WIB
* **Kejadian Terakhir:** {{ $summary['last_occurred'] ?? '-' }} WIB
* **Target Gateway:** `{{ config('services.sap.base_url', 'http://192.168.6.149:9001') }}`

---

### 📍 Rincian per Endpoint SAP
<x-mail::table>
| API Name | Endpoint | Jumlah Timeout |
| :--- | :--- | :---: |
@foreach ($summary['endpoints_breakdown'] as $ep)
| **{{ $ep['api_name'] }}** | `{{ $ep['endpoint'] }}` | **{{ $ep['count'] }}x** |
@endforeach
</x-mail::table>

---

### 📝 Sampel Error Log Terbaru
<x-mail::table>
| ID Log | Waktu (WIB) | API Name | Cuplikan Error |
| :---: | :---: | :--- | :--- |
@foreach ($recentLogs as $log)
| #{{ $log->id }} | {{ \Carbon\Carbon::parse($log->created_at)->timezone('Asia/Jakarta')->format('H:i:s') }} | {{ $log->api_name }} | {{ \Illuminate\Support\Str::limit($log->message, 120) }} |
@endforeach
</x-mail::table>

---

### 🛠️ Langkah Penanganan yang Disarankan
1. **Periksa Server SAP:** Pastikan host SAP (`192.168.6.149:9001`) dapat dijangkau dan service API wrapper aktif.
2. **Periksa Resource Server SAP:** Cek beban CPU, RAM, atau antrean transaksi DI Server SAP Business One yang mungkin mengalami *deadlock* atau antrean panjang.
3. **Cek Koneksi Jaringan:** Pastikan routing jaringan internal antara server MES dan server SAP stabil.

<x-mail::button :url="$dashboardUrl">
Buka API Logs Dashboard
</x-mail::button>

Email ini dibuat secara otomatis oleh sistem pemantauan background **Daijo MES**.

Salam,<br>
**{{ config('app.name', 'Daijo MES') }} Notification System**
</x-mail::message>
