<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpSessionManpower extends Model
{
    use HasFactory;

    protected $table = 'sp_session_manpowers';

    protected $fillable = [
        'session_id',
        'user_id',
        'operator_name',
        'employee_no',
        'role',
    ];

    public function session()
    {
        return $this->belongsTo(SpProductionSession::class, 'session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
