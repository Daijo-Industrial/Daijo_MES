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

    protected static function booted()
    {
        static::creating(function ($model) {
            $schema = $model->getConnection()->getSchemaBuilder();
            $table = $model->getTable();

            if (! isset($model->attributes['actual_quantity']) && $schema->hasColumn($table, 'actual_quantity')) {
                $model->actual_quantity = 0;
            }
            if (empty($model->start_date) && $schema->hasColumn($table, 'start_date')) {
                $model->start_date = $model->schedule_date ?? now()->toDateString();
            }
            if (empty($model->end_date) && $schema->hasColumn($table, 'end_date')) {
                $model->end_date = $model->schedule_date ?? now()->toDateString();
            }
            if (empty($model->start_time) && $schema->hasColumn($table, 'start_time')) {
                $model->start_time = '07:30:00';
            }
            if (empty($model->end_time) && $schema->hasColumn($table, 'end_time')) {
                $model->end_time = '15:30:00';
            }
        });
    }

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

    public function getEffectiveCavityAttribute(): int
    {
        if (! is_null($this->temporal_cavity) && (int) $this->temporal_cavity > 0) {
            return (int) $this->temporal_cavity;
        }

        if (! empty($this->masterItem?->cavity) && (int) $this->masterItem->cavity > 1) {
            return (int) $this->masterItem->cavity;
        }

        if (! empty($this->masterItem?->pair) || ! empty($this->masterFg?->pair)) {
            return 2;
        }

        return 1;
    }
}
