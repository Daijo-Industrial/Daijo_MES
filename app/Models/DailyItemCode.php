<?php

namespace App\Models;

use App\Models\Delivery\DelschedFinal;
use App\Models\Delivery\sapInventoryFg;
use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyItemCode extends Model
{
    use BelongsToBranch;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'branch_id',
        'item_code',
        'quantity',
        'final_quantity',
        'loss_package_quantity',
        'actual_quantity',
        'shift',
        'start_time',
        'end_time',
        'is_done',
        'schedule_date',
        'start_date',
        'end_date',
        'remark',
        'material_lot',
        'temporal_cycle_time',
        'temporal_cycletime',
        'temporal_cavity',
        'temporal_cavity',
        'resin_usage',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function spkRelation()
    {
        return $this->hasMany(SpkMaster::class);
    }

    public function masterItem()
    {
        return $this->hasOne(MasterListItem::class, 'item_code', 'item_code');
    }

    public function accessoryLots()
    {
        return $this->hasMany(DicAccessoryLot::class, 'dic_id', 'id');
    }

    public function machinerelation()
    {
        return $this->hasOne(MachineJob::class, 'user_id', 'user_id');
    }

    public function scannedData()
    {
        return $this->hasMany(ProductionScannedData::class, 'dic_id', 'id');
    }

    public function hourlyRemarks()
    {
        return $this->hasMany(HourlyRemark::class, 'dic_id', 'id');
    }

    public function masterFg()
    {
        return $this->hasOne(sapInventoryFg::class, 'item_code', 'item_code');
    }

    public function delsched()
    {
        return $this->hasMany(
            DelschedFinal::class,
            'item_code',
            'item_code'
        );
    }

    public function outputLogs()
    {
        return $this->hasMany(ProductionOutputLog::class, 'dic_id', 'id');
    }
}
