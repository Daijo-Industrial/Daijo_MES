<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpWorkOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'wo_number',
        'planned_date',
        'unit_line',
        'process_prod',
        'part_number',
        'part_name',
        'model',
        'customer',
        'target_qty',
        'status',
        'created_by',
    ];

    public function sessions()
    {
        return $this->hasMany(SpProductionSession::class, 'work_order_id');
    }

    public function firstPieceInspections()
    {
        return $this->hasMany(FirstPieceInspection::class, 'work_order_id');
    }

    public function latestFirstPieceInspection()
    {
        return $this->hasOne(FirstPieceInspection::class, 'work_order_id')->latestOfMany();
    }

    /**
     * Derived QC status based on linked First Piece Inspection.
     */
    public function getQcStatusAttribute(): string
    {
        $inspection = $this->latestFirstPieceInspection
            ?? FirstPieceInspection::where('part_number', $this->part_number)->latest('id')->first();

        if (! $inspection) {
            return 'missing';
        }
        if ($inspection->isApproved()) {
            return 'approved';
        }

        return 'pending';
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateWoNumber(): string
    {
        $dateStr = now()->format('Ymd');
        $prefix = "WO-SP-{$dateStr}-";
        $lastWo = self::where('wo_number', 'LIKE', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastWo) {
            $seq = (int) substr($lastWo->wo_number, -4) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function getTotalGoodAttribute()
    {
        return $this->sessions->sum('total_good');
    }

    public function getTotalRejectAttribute()
    {
        return $this->sessions->sum('total_reject');
    }

    public function getProgressPercentageAttribute()
    {
        if ($this->target_qty == 0) {
            return 0;
        }
        $percentage = ($this->total_good / $this->target_qty) * 100;

        return min(100, round($percentage));
    }
}
