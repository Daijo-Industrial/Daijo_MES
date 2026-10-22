<?php

namespace App\Models\Inspection;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SecondInspection extends Model
{
    use HasFactory;

    protected $table = 'second_inspections';

    protected $fillable = [
        'detail_inspection_report_document_number',
        'document_number',
        'lot_size_quantity',
    ];

    protected $casts = [
        'lot_size_quantity' => 'integer',
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

    /**
     * Sampling records under this second inspection.
     */
    public function samplingData(): HasMany
    {
        return $this->hasMany(
            InspectionSampling::class,
            'second_inspection_document_number',
            'document_number'
        );
    }

    /**
     * Packaging check records under this second inspection.
     */
    public function packagingData(): HasMany
    {
        return $this->hasMany(
            InspectionPackaging::class,
            'second_inspection_document_number',
            'document_number'
        );
    }
}
