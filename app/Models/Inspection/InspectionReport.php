<?php

namespace App\Models\Inspection;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InspectionReport extends Model
{
    use HasFactory;

    protected $table = 'inspection_reports';

    protected $fillable = [
        'document_number',
        'customer',
        'inspection_date',
        'part_number',
        'part_name',
        'weight',
        'weight_uom',
        'material',
        'color',
        'tool_number_or_cav_number',
        'machine_number',
        'shift',
        'operator',
        'inspector',
        'inspector_autograph',
        'leader_autograph',
        'head_autograph',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'weight' => 'double',
        'shift' => 'integer',
    ];

    /**
     * Detail inspection reports across periods (Period 1 to 4).
     */
    public function detailInspectionReports(): HasMany
    {
        return $this->hasMany(
            DetailInspectionReport::class,
            'inspection_report_document_number',
            'document_number'
        );
    }

    /**
     * Dimension measurements associated with this report.
     */
    public function dimensionData(): HasMany
    {
        return $this->hasMany(
            InspectionDimension::class,
            'inspection_report_document_number',
            'document_number'
        );
    }

    /**
     * Shift problems / downtime recorded for this report.
     */
    public function problemData(): HasMany
    {
        return $this->hasMany(
            InspectionProblem::class,
            'inspection_report_document_number',
            'document_number'
        );
    }

    /**
     * Production output and rejection quantity summary.
     */
    public function quantityData(): HasOne
    {
        return $this->hasOne(
            InspectionQuantity::class,
            'inspection_report_document_number',
            'document_number'
        );
    }
}
