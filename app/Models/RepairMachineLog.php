<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairMachineLog extends Model
{
    use HasFactory;

    protected $table = 'repair_machine_logs';

    protected $fillable = [
        'user_id',
        'item_code',
        'problem',
        'finish_repair',
        'pic',
        'remark',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function masterListItem()
    {
        return $this->belongsTo(MasterListItem::class, 'item_code', 'item_code');
    }


}
