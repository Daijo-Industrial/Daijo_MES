<?php

namespace App\Models\Inspection;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionDimension extends Model
{
    use HasFactory;

    protected $table = 'inspection_dimensions';

    protected $fillable = [
        'inspection_report_document_number',
        'lower_limit',
        'upper_limit',
        'limit_uom',
        'actual_value',
        'start_datetime',
        'end_datetime',
        'judgement',
        'area',
        'remarks',
    ];

    protected $casts = [
        'lower_limit' => 'double',
        'upper_limit' => 'double',
        'actual_value' => 'decimal:3',
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
}
