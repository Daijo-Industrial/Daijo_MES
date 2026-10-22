<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceCheckHeader extends Model
{
    use HasFactory;

    protected $table = 'maintenance_check_headers';

    protected $fillable = [
        'machine_id',
        'date',
        'check_time',
        'prepared_by',
        'approved_by',
        'status',
    ];

    public function machine()
    {
        return $this->belongsTo(User::class, 'machine_id');
    }

    public function details()
    {
        return $this->hasMany(MaintenanceCheckDetail::class, 'header_id');
    }
}
