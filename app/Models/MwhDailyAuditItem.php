<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MwhDailyAuditItem extends Model
{
    use HasFactory;

    protected $table = 'mwh_daily_audit_items';

    protected $fillable = [
        'daily_audit_id',
        'pallet_id',
        'position_id',
        'position_code',
        'rack_code',
        'system_item_code',
        'system_item_name',
        'system_lot_no',
        'system_qty',
        'uom',
        'actual_item_code',
        'actual_qty',
        'diff_qty',
        'discrepancy_reason',
        'status',
    ];

    protected $casts = [
        'system_qty' => 'decimal:2',
        'actual_qty' => 'decimal:2',
        'diff_qty'   => 'decimal:2',
    ];

    public function audit()
    {
        return $this->belongsTo(MwhDailyAudit::class, 'daily_audit_id');
    }

    public function position()
    {
        return $this->belongsTo(MwhPosition::class, 'position_id');
    }

    public function pallet()
    {
        return $this->belongsTo(MwhPallet::class, 'pallet_id', 'pallet_id');
    }
}
