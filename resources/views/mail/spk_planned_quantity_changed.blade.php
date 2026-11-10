<x-mail::message>
# 📋 Notifikasi Perubahan Planned Quantity SPK

@if ($isTest)
<x-mail::panel>
🧪 **Ini adalah email pengujian (TEST MODE)** yang dikirim melalui perintah `php artisan spk:test-planned-alert` untuk memverifikasi format notifikasi email ke penanggung jawab (**intan@daijo.co.id**, **sisca@daijo.co.id**).
</x-mail::panel>
@else
<x-mail::panel>
Sistem mendeteksi adanya perubahan / revisi **Planned Quantity** pada data SPK produksi dari hasil sinkronisasi SAP terbaru.
</x-mail::panel>
@endif

### 📊 Ringkasan Sesi Sinkronisasi
* **Waktu Sinkronisasi:** {{ now('Asia/Jakarta')->format('d M Y H:i:s') }} WIB
* **ID Sesi Sync:** `{{ $batchId }}`
* **Jumlah SPK yang Direvisi:** **{{ count($changes) }} SPK**

---

### 📍 Rincian SPK yang Mengalami Perubahan Planned Qty
<x-mail::table>
| No. SPK | Item Code & Deskripsi | Planned Lama | Planned Baru | Selisih (Qty) | Status SAP |
| :--- | :--- | :---: | :---: | :---: | :---: |
@foreach ($changes as $item)
| **{{ $item['spk_number'] }}** | `{{ $item['item_code'] }}`<br><small style="color: #64748b;">{{ $item['item_name'] ?: '—' }}</small> | {{ number_format($item['old_planned_qty']) }} | **{{ number_format($item['new_planned_qty']) }}** | **{{ ($item['planned_diff'] > 0 ? '+' : '') . number_format($item['planned_diff']) }}** | {{ $item['new_status'] ?? '-' }} |
@endforeach
</x-mail::table>

---

<x-mail::button :url="$auditUrl">
Buka Log Audit Perubahan SPK
</x-mail::button>

*Catatan: Notifikasi ini hanya dikirimkan apabila terdapat perubahan pada target Planned Quantity dari SAP. Perubahan Completed Quantity dari scan box operator tidak akan memicu email ini.*

Salam,<br>
**Daijo MES Automated System**
</x-mail::message>

