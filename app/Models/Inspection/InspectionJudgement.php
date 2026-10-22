<?php

namespace App\Models\Inspection;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionJudgement extends Model
{
    use HasFactory;

    protected $table = 'inspection_judgements';

    protected $fillable = [
        'detail_inspection_report_document_number',
        'pass_quantity',
        'reject_quantity',
    ];

    protected $casts = [
        'pass_quantity' => 'integer',
        'reject_quantity' => 'integer',
    ];

    /**
     * Parent detail inspection report for the period.
     */
    public function detailInspectionReport(): BelongsTo
    {
        return $this->belongsTo(
            DetailInspectionReport::class,
            'detail_inspection_report_document_number',
            'document_number'
        );
    }
}
