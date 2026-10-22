<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpkBomChangeLog extends Model
{
    use HasFactory;

    protected $table = 'spk_bom_change_logs';

    protected $fillable = [
        'spk_number',
        'action_type',
        'item_code',
        'item_name',
        'replaced_item_code',
        'base_qty',
        'plan_qty',
        'old_plan_qty',
        'warehouse',
        'status',
        'message',
        'payload',
        'response',
        'created_by',
        'created_by_name',
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
        'base_qty' => 'float',
        'plan_qty' => 'float',
        'old_plan_qty' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function masterItem()
    {
        return $this->belongsTo(MasterListItem::class, 'item_code', 'item_code');
    }
}
