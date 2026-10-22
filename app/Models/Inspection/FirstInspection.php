<?php

namespace App\Models\Inspection;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FirstInspection extends Model
{
    use HasFactory;

    protected $table = 'first_inspections';

    protected $fillable = [
        'detail_inspection_report_document_number',
        'appearance',
        'remarks',
        'weight',
        'weight_uom',
        'fitting_test',
    ];

    protected $casts = [
        'weight' => 'double',
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
