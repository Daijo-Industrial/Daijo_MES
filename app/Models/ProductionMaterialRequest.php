<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionMaterialRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'production_material_requests';

    protected $fillable = [
        'request_code',
        'item_code',
        'item_name',
        'qty_requested',
        'uom',
        'machine_no',
        'spk_code',
        'urgency',
        'needed_at',
        'notes',
        'status',
        'requested_by',
        'requested_by_name',
        'processed_by',
        'processed_by_name',
        'processed_at',
        'outgoing_id',
        'outgoing_code',
        'rejected_by',
        'rejected_by_name',
        'rejected_at',
        'reject_reason',
    ];

    protected $casts = [
        'qty_requested' => 'decimal:2',
        'needed_at' => 'datetime',
        'processed_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function material()
    {
        return $this->belongsTo(MasterListMaterial::class, 'item_code', 'item_code');
    }

    public function outgoing()
    {
        return $this->belongsTo(MwhOutgoing::class, 'outgoing_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}

