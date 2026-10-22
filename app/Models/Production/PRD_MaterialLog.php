<?php

namespace App\Models\Production;

use App\Traits\BroadcastsDashboardModelUpdates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PRD_MaterialLog extends Model
{
    use BroadcastsDashboardModelUpdates, HasFactory;

    protected $table = 'prd_material_logs';

    protected $fillable = [
        'child_id',
        'process_name',
        'scan_in',
        'scan_start',
        'scan_out',
        'status',
        'pic',
        'remark',
    ];

    public function childData()
    {
        return $this->belongsTo(PRD_BillOfMaterialChild::class, 'child_id');
    }

    public function workers()
    {
        return $this->hasMany(PRD_MouldingUserLog::class, 'material_log_id');
    }
}
