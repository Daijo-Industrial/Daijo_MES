<?php

namespace App\Models\Inspection;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionProblem extends Model
{
    use HasFactory;

    protected $table = 'inspection_problems';

    protected $fillable = [
        'inspection_report_document_number',
        'type',
        'time',
        'cycle_time',
        'remarks',
    ];

    protected $casts = [
        'cycle_time' => 'integer',
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
}
