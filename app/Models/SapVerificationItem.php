<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SapVerificationItem extends Model
{
    use HasFactory;

    protected $table = 'sap_verification_items';

    protected $fillable = [
        'batch_id',
        'ref_code',
        'item_code',
        'description',
        'warehouse',
        'sap_qty',
        'mes_qty',
        'diff_qty',
        'status',
        'status_label',
        'sap_docs',
        'sap_dates',
        'mes_dates',
        'raw_data',
    ];

    protected $casts = [
        'sap_qty' => 'float',
        'mes_qty' => 'float',
        'diff_qty' => 'float',
        'sap_docs' => 'array',
        'sap_dates' => 'array',
        'mes_dates' => 'array',
        'raw_data' => 'array',
    ];

    public function batch()
    {
        return $this->belongsTo(SapVerificationBatch::class, 'batch_id');
    }
}
