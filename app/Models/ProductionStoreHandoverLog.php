<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionStoreHandoverLog extends Model
{
    use HasFactory;

    protected $table = 'production_store_handover_logs';

    protected $fillable = [
        'handover_id',
        'pallet_id',
        'received_boxes',
        'received_qty',
        'verified_by',
        'verified_by_name',
        'remarks',
    ];

    protected $casts = [
        'received_boxes' => 'integer',
        'received_qty' => 'integer',
    ];

    public function handover()
    {
        return $this->belongsTo(ProductionStoreHandover::class, 'handover_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function pallet()
    {
        return $this->belongsTo(WmsPalletForm::class, 'pallet_id', 'pallet_id');
    }
}
