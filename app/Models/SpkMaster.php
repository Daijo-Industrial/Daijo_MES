<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SpkMaster extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'spk_number',
                'production_status',
                'item_code',
                'planned_quantity',
                'completed_quantity',
                'warehouse',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('spk');
    }

    public $timestamps = false;

    protected $fillable = [
        'spk_number',
        'post_date',
        'due_date',
        'production_status',
        'item_code',
        'planned_quantity',
        'completed_quantity',
        'warehouse',
    ];

    public function masterItem()
    {
        return $this->belongsTo(MasterListItem::class, 'item_code', 'item_code');
    }

    public function fgHeader()
    {
        return $this->belongsTo(MasterBomFgHeader::class, 'item_code', 'fg_item_code');
    }

    public function changeLogs()
    {
        return $this->hasMany(SpkChangeLog::class, 'spk_number', 'spk_number');
    }

    public function bomChangeLogs()
    {
        return $this->hasMany(SpkBomChangeLog::class, 'spk_number', 'spk_number');
    }
}
