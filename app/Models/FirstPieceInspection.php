<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FirstPieceInspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_id',
        'date',
        'model',
        'part_name',
        'part_number',
        'paint_code',
        'thinner_code',
        'ink_code',
        'viscosity',
        'cycle_time',
        'check_results',
        'overall_judgement',
        'remark',
        'prepared_by',
        'prepared_at',
        'checked_by',
        'checked_at',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'check_results' => 'array',
        'prepared_at' => 'datetime',
        'checked_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function workOrder()
    {
        return $this->belongsTo(SpWorkOrder::class, 'work_order_id');
    }

    public function attachments()
    {
        return $this->morphMany(QcAttachment::class, 'attachable');
    }

    public function isApproved(): bool
    {
        return $this->overall_judgement === 'OK' && $this->checked_at !== null;
    }

    /**
     * Scope query to find inspection for a part number on a specific date (defaults to today).
     */
    public function scopeApprovedForPartToday($query, string $partNumber, ?string $date = null)
    {
        $dateStr = $date ?: now()->format('Y-m-d');

        return $query->where('part_number', $partNumber)
            ->whereDate('date', $dateStr)
            ->orderBy('id', 'desc');
    }

    /**
     * Scope query to find the latest inspection for a part number regardless of date.
     */
    public function scopeLatestForPart($query, string $partNumber)
    {
        return $query->where('part_number', $partNumber)->orderBy('id', 'desc');
    }
}
