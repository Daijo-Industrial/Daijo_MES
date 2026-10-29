<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SapVerificationBatch extends Model
{
    use HasFactory;

    protected $table = 'sap_verification_batches';

    protected $fillable = [
        'type',
        'file_name',
        'file_path',
        'uploaded_by',
        'start_date',
        'end_date',
        'total_items',
        'total_sap_qty',
        'total_mes_qty',
        'match_count',
        'diff_count',
        'missing_count',
        'only_sap_count',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_sap_qty' => 'float',
        'total_mes_qty' => 'float',
        'match_count' => 'integer',
        'diff_count' => 'integer',
        'missing_count' => 'integer',
        'only_sap_count' => 'integer',
    ];

    public function items()
    {
        return $this->hasMany(SapVerificationItem::class, 'batch_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
