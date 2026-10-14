<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MwhDailyAudit extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mwh_daily_audits';

    protected $fillable = [
        'audit_code',
        'audit_date',
        'whse_id',
        'auditor_id',
        'auditor_name',
        'total_items',
        'matched_items',
        'discrepant_items',
        'status',
        'notes',
    ];

    protected $casts = [
        'audit_date' => 'date',
        'total_items' => 'integer',
        'matched_items' => 'integer',
        'discrepant_items' => 'integer',
    ];

    public function warehouse()
    {
        return $this->belongsTo(MwhWarehouse::class, 'whse_id');
    }

    public function auditor()
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    public function items()
    {
        return $this->hasMany(MwhDailyAuditItem::class, 'daily_audit_id');
    }
}
