<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MwhIncomingHeader extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mwh_incoming_headers';

    protected $fillable = [
        'whse_id',
        'document_no',
        'incoming_type',
        'supplier_name',
        'returned_from',
        'po_number',
        'original_outgoing_code',
        'arrival_date',
        'remarks',
        'created_at',
    ];

    protected $casts = [
        'arrival_date' => 'date',
    ];

    public function warehouse()
    {
        return $this->belongsTo(MwhWarehouse::class, 'whse_id');
    }

    public function pallets()
    {
        return $this->hasMany(MwhPallet::class, 'incoming_header_id');
    }
}
