<?php

namespace App\Models\Inspection;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionQuantity extends Model
{
    use HasFactory;

    protected $table = 'inspection_quantities';

    protected $fillable = [
        'inspection_report_document_number',
        'output_quantity',
        'pass_quantity',
        'pass_rate',
        'reject_quantity',
        'reject_rate',
        'sampling_quantity',
        'ng_sample_quantity',
        'ng_sample_rate',
    ];

    protected $casts = [
        'output_quantity' => 'integer',
        'pass_quantity' => 'integer',
        'pass_rate' => 'double',
        'reject_quantity' => 'integer',
        'reject_rate' => 'double',
        'sampling_quantity' => 'integer',
        'ng_sample_quantity' => 'integer',
        'ng_sample_rate' => 'double',
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
