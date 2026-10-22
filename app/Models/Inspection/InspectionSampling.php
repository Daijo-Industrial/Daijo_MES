<?php

namespace App\Models\Inspection;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionSampling extends Model
{
    use HasFactory;

    protected $table = 'inspection_samplings';

    protected $fillable = [
        'second_inspection_document_number',
        'quantity',
        'box_label',
        'appearance',
        'ng_quantity',
        'remarks',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'ng_quantity' => 'integer',
    ];

    /**
     * Parent second inspection session.
     */
    public function secondInspection(): BelongsTo
    {
        return $this->belongsTo(
            SecondInspection::class,
            'second_inspection_document_number',
            'document_number'
        );
    }
}
