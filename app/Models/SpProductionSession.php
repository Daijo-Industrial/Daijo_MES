<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpProductionSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_id',
        'operator_id',
        'unit_line',
        'shift',
        'status',
        'started_at',
        'paused_at',
        'finished_at',
        'total_input',
        'total_good',
        'total_reject',
        'total_rework_in',
        'total_rework_recovered',
        'total_scrap',
        'remarks',
        'approved_by',
        'approved_at',
        'is_qc_bypassed',
        'qc_bypass_reason',
        'qc_bypassed_at',
        'qc_bypassed_by',
        'production_notes',
        'ng_remarks',
        'absent_employees',
        'next_production_schedule',
        'output_destination',
        'second_process_report_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'paused_at' => 'datetime',
        'finished_at' => 'datetime',
        'approved_at' => 'datetime',
        'is_qc_bypassed' => 'boolean',
        'qc_bypassed_at' => 'datetime',
        'next_production_schedule' => 'array',
    ];

    public function workOrder()
    {
        return $this->belongsTo(SpWorkOrder::class, 'work_order_id');
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function secondProcessReport()
    {
        return $this->belongsTo(SecondProcessReport::class, 'second_process_report_id');
    }

    public function qcBypassedBy()
    {
        return $this->belongsTo(User::class, 'qc_bypassed_by');
    }

    public function productionEntries()
    {
        return $this->hasMany(SpProductionEntry::class, 'session_id');
    }

    public function rejectEntries()
    {
        return $this->hasMany(SpRejectEntry::class, 'session_id');
    }

    public function reworkEntries()
    {
        return $this->hasMany(SpReworkEntry::class, 'session_id');
    }

    public function downtimeEntries()
    {
        return $this->hasMany(SpDowntimeEntry::class, 'session_id');
    }

    public function inputEntries()
    {
        return $this->hasMany(SpInputEntry::class, 'session_id');
    }

    public function manpowerEntries()
    {
        return $this->hasMany(SpSessionManpower::class, 'session_id');
    }

    public function materials()
    {
        return $this->hasMany(SpSessionMaterial::class, 'session_id');
    }

    public function defectEntries()
    {
        return $this->hasMany(SpRejectEntry::class, 'session_id');
    }

    public function manpowers()
    {
        return $this->hasMany(SpSessionManpower::class, 'session_id');
    }

    public function getYieldAttribute(): float
    {
        $totalOutput = $this->total_good + $this->total_reject;
        if ($totalOutput == 0) {
            return 0;
        }

        return round(($this->total_good / $totalOutput) * 100, 2);
    }

    public function getNgRateAttribute(): float
    {
        $totalOutput = $this->total_good + $this->total_reject;
        if ($totalOutput == 0) {
            return 0;
        }

        return round(($this->total_reject / $totalOutput) * 100, 2);
    }

    public function recalculateTotals(): void
    {
        $directGood = (int) $this->productionEntries()->sum('good_qty');
        $rawReject = (int) $this->rejectEntries()->sum('quantity');
        $reworkRecovered = (int) $this->reworkEntries()->sum('recovered_qty');
        $reworkScrapped = (int) $this->reworkEntries()->sum('scrapped_qty');

        $internalRecovered = (int) $this->reworkEntries()
            ->where(function ($q) {
                $q->where('remarks', 'not like', 'From Reworkable Input%')
                    ->orWhereNull('remarks');
            })
            ->sum('recovered_qty');
        $externalScrapped = (int) $this->reworkEntries()
            ->where('remarks', 'like', 'From Reworkable Input%')
            ->sum('scrapped_qty');

        $this->total_rework_in = (int) $this->reworkEntries()->sum('input_qty');
        $this->total_rework_recovered = $reworkRecovered;
        $this->total_scrap = $reworkScrapped;

        $this->total_good = $directGood + $reworkRecovered;
        $netLineReject = max(0, $rawReject - $internalRecovered);
        $this->total_reject = max($netLineReject + $externalScrapped, $reworkScrapped);
        $this->total_input = (int) $this->inputEntries()->sum('quantity');
        $this->save();
    }
}
