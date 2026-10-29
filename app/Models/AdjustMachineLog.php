<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdjustMachineLog extends Model
{
    use HasFactory;

    protected $table = 'adjust_machine_logs';

    protected $fillable = [
        'user_id',
        'item_code',
        'end_time',
        'remark',
        'pic',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function masterListItem()
    {
        return $this->belongsTo(MasterListItem::class, 'item_code', 'item_code'); // Adjust if the foreign key or field name is different
    }

    public function ngDetails()
    {
        return $this->hasMany(AdjustMachineNgDetail::class, 'adjust_machine_log_id');
    }

    public function getTotalNgAttribute(): int
    {
        return (int) $this->ngDetails->sum('ng_quantity');
    }
}
