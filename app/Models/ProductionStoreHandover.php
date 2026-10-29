<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionStoreHandover extends Model
{
    use HasFactory;

    protected $table = 'production_store_handovers';

    protected $fillable = [
        'production_date',
        'shift',
        'machine_name',
        'plant',
        'item_code',
        'item_name',
        'customer_name',
        'operator_actual_qty',
        'scanned_qty',
        'scanned_boxes',
        'store_received_boxes',
        'store_received_qty',
        'status',
        'last_verified_by',
        'last_verified_by_name',
        'last_verified_at',
        'remarks',
    ];

    protected $casts = [
        'production_date' => 'date',
        'shift' => 'integer',
        'operator_actual_qty' => 'integer',
        'scanned_qty' => 'integer',
        'scanned_boxes' => 'integer',
        'store_received_boxes' => 'integer',
        'store_received_qty' => 'integer',
        'last_verified_at' => 'datetime',
    ];

    public function logs()
    {
        return $this->hasMany(ProductionStoreHandoverLog::class, 'handover_id')->orderBy('id', 'desc');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'last_verified_by');
    }

    public function masterItem()
    {
        return $this->belongsTo(MasterListItem::class, 'item_code', 'item_code');
    }

    public function getInTransitBoxesAttribute(): int
    {
        return max(0, $this->scanned_boxes - $this->store_received_boxes);
    }

    public function getInTransitQtyAttribute(): int
    {
        return max(0, $this->scanned_qty - $this->store_received_qty);
    }

    public function getProgressPercentAttribute(): int
    {
        if ($this->scanned_boxes <= 0) {
            return 0;
        }

        return min(100, (int) round(($this->store_received_boxes / $this->scanned_boxes) * 100));
    }

    public function getIsKarawangAttribute(): bool
    {
        return $this->plant === 'KARAWANG' || str_starts_with(strtoupper($this->machine_name), 'K');
    }
}
