<?php

namespace App\Models\Production;

use App\Traits\BroadcastsDashboardModelUpdates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PRD_MouldingUserLog extends Model
{
    use BroadcastsDashboardModelUpdates, HasFactory;

    protected $table = 'prd_moulding_user_logs';

    protected $fillable = [
        'material_log_id',
        'username',
        'shift',
        'jobs',
        'remark',
    ];

    public function userlog()
    {
        return $this->belongsTo(PRD_MaterialLog::class, 'material_log_id');
    }
}
