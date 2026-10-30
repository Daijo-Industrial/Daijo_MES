<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreCrosscheckDailyReport extends Model
{
    use HasFactory;

    protected $table = 'store_crosscheck_daily_reports';

    protected $fillable = [
        'report_date',
        'plant',
        'shift',
        'total_boxes',
        'total_qty',
        'found_boxes',
        'found_qty',
        'missing_boxes',
        'missing_qty',
        'pending_boxes',
        'pending_qty',
        'accuracy_rate',
        'status',
        'verified_by',
        'verified_by_name',
        'verified_at',
        'notes',
    ];

    protected $casts = [
        'report_date' => 'date',
        'verified_at' => 'datetime',
        'total_boxes' => 'integer',
        'total_qty' => 'integer',
        'found_boxes' => 'integer',
        'found_qty' => 'integer',
        'missing_boxes' => 'integer',
        'missing_qty' => 'integer',
        'pending_boxes' => 'integer',
        'pending_qty' => 'integer',
        'accuracy_rate' => 'float',
    ];

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by', 'id');
    }
}
