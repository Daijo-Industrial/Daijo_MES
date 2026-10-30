<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionScannedData extends Model
{
    use HasFactory;

    protected $table = 'production_scanned_data';

    protected $fillable = [
        'spk_code',
        'dic_id',
        'item_code',
        'warehouse',
        'quantity',
        'label',
        'user',
        'processed',
        'summary_id',
        'store_check_status',
        'store_checked_by',
        'store_checked_by_name',
        'store_checked_at',
        'store_notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'store_checked_at' => 'datetime',
    ];

    public function ParentDailyItemCode()
    {
        return $this->hasOne(DailyItemCode::class, 'id', 'dic_id');
    }

    public function dailyItemCode()
    {
        return $this->belongsTo(DailyItemCode::class, 'dic_id', 'id');
    }

    public function summary()
    {
        return $this->belongsTo(ProductionSummary::class, 'summary_id', 'id');
    }

    public function masterItem()
    {
        return $this->belongsTo(MasterListItem::class, 'item_code', 'item_code');
    }

    public function storeChecker()
    {
        return $this->belongsTo(User::class, 'store_checked_by', 'id');
    }

    public function getPlantAttribute(): string
    {
        $machineName = strtoupper(trim($this->dailyItemCode?->user?->name ?? ''));
        if (str_starts_with($machineName, 'K')) {
            return 'KARAWANG';
        }

        if (str_starts_with(strtoupper($this->item_code ?? ''), 'K-')) {
            return 'KARAWANG';
        }

        $wh = strtoupper(trim($this->warehouse ?? ''));
        if (str_contains($wh, 'KRW') || str_contains($wh, 'KARAWANG')) {
            return 'KARAWANG';
        }

        return 'KBN';
    }

    public function getIsKarawangAttribute(): bool
    {
        return $this->plant === 'KARAWANG';
    }

    public function getIsFoundAttribute(): bool
    {
        return $this->store_check_status === 'FOUND';
    }

    public function getIsNotFoundAttribute(): bool
    {
        return $this->store_check_status === 'NOT_FOUND';
    }

    public function getIsPendingAttribute(): bool
    {
        return empty($this->store_check_status) || $this->store_check_status === 'PENDING';
    }
}
