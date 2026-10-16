<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QcTransferLog extends Model
{
    protected $fillable = [
        'production_summary_id',
        'scanned_data_id',
        'item_code',
        'spk_code',
        'label',
        'from_warehouse',
        'original_qty',
        'ok_qty',
        'ng_qty',
        'ok_to_warehouse',
        'ok_sap_status',
        'ok_sap_error',
        'ok_sap_sent_at',
        'ng_to_warehouse',
        'ng_sap_status',
        'ng_sap_error',
        'ng_sap_sent_at',
        'inspected_by',
        'remarks',
    ];

    protected $casts = [
        'ok_sap_sent_at' => 'datetime',
        'ng_sap_sent_at' => 'datetime',
    ];

    // Warehouse mapping constants
    public const WAREHOUSE_MAP = [
        'FFI' => ['ok' => 'FG',   'ng' => 'RJCT'],
        'KRFFI' => ['ok' => 'KRFG', 'ng' => 'KRRJCT'],
    ];

    public function summary()
    {
        return $this->belongsTo(ProductionSummary::class, 'production_summary_id');
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    /**
     * Cek apakah kedua transfer (OK + NG) sudah selesai (success atau skip karena qty=0 / null target)
     */
    public function isFullyTransferred(): bool
    {
        $okDone = empty($this->ok_to_warehouse) || $this->ok_qty == 0 || $this->ok_sap_status == 1;
        $ngDone = empty($this->ng_to_warehouse) || $this->ng_qty == 0 || $this->ng_sap_status == 1;

        return $okDone && $ngDone;
    }
}
