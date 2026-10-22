<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpkChangeLog extends Model
{
    use HasFactory;

    protected $table = 'spk_change_logs';

    protected $fillable = [
        'sync_batch_id',
        'spk_number',
        'item_code',
        'change_type',
        'old_planned_qty',
        'new_planned_qty',
        'old_completed_qty',
        'new_completed_qty',
        'old_status',
        'new_status',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
        'old_planned_qty' => 'integer',
        'new_planned_qty' => 'integer',
        'old_completed_qty' => 'integer',
        'new_completed_qty' => 'integer',
    ];

    public function masterItem()
    {
        return $this->belongsTo(MasterListItem::class, 'item_code', 'item_code');
    }
}
