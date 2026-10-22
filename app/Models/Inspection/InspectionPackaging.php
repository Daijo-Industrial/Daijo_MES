<?php

namespace App\Models\Inspection;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionPackaging extends Model
{
    use HasFactory;

    protected $table = 'inspection_packagings';

    protected $fillable = [
        'second_inspection_document_number',
        'snp',
        'box_label',
        'judgement',
        'remarks',
    ];

    protected $casts = [
        'snp' => 'integer',
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
