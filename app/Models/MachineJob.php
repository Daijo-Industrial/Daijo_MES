<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineJob extends Model
{
    use BelongsToBranch;
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'branch_id',
        'item_code',
        'shift',
        'employee_name',
        'dic_id',
    ];

    public function activeDailyItemCode()
    {
        return $this->belongsTo(DailyItemCode::class, 'dic_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // Relation to DailyItemCode (assuming they are linked by user_id)
    public function dailyItemCode()
    {
        return $this->hasMany(DailyItemCode::class, 'user_id', 'user_id');
    }

    public function mouldChangeLogs()
    {
        return $this->hasMany(MouldChangeLog::class, 'user_id', 'user_id');
    }

    public function adjustMachineLogs()
    {
        return $this->hasMany(AdjustMachineLog::class, 'user_id', 'user_id');
    }

    public function repairMachineLogs()
    {
        return $this->hasMany(RepairMachineLog::class, 'user_id', 'user_id');
    }
}
