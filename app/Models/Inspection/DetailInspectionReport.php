<?php

namespace App\Models\Inspection;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetailInspectionReport extends Model
{
    use HasFactory;

    protected $table = 'detail_inspection_reports';

    protected $fillable = [
        'inspection_report_document_number',
        'document_number',
        'period',
        'start_datetime',
        'end_datetime',
    ];

    protected $casts = [
        'period' => 'integer',
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
    ];

    /**
     * Parent inspection report header.
     */
    public function inspectionReport(): BelongsTo
    {
        return $this->belongsTo(
            InspectionReport::class,
            'inspection_report_document_number',
            'document_number'
        );
    }

    /**
     * First inspection checks for this period.
     */
    public function firstInspections(): HasMany
    {
        return $this->hasMany(
            FirstInspection::class,
            'detail_inspection_report_document_number',
            'document_number'
        );
    }

    /**
     * Second inspection sessions for this period.
     */
    public function secondInspections(): HasMany
    {
        return $this->hasMany(
            SecondInspection::class,
            'detail_inspection_report_document_number',
            'document_number'
        );
    }

    /**
     * Judgement records (pass/reject qty) for this period.
     */
    public function judgementData(): HasMany
    {
        return $this->hasMany(
            InspectionJudgement::class,
            'detail_inspection_report_document_number',
            'document_number'
        );
    }
}
