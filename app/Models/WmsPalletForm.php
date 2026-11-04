<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WmsPalletForm extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'wms_pallet_forms';

    protected $primaryKey = 'pallet_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'pallet_id', 'plant', 'position_id', 'assigned_at', 'part_no', 'model_name',
        'prod_date', 'lot_no', 'delivery_name', 'delivery_shift',
        'box_qty', 'total_pallet_qty', 'status', 'remarks',
        'sap_sync_status', 'sap_error_msg', 'sap_sync_at', 'sap_sync_duration',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'sap_sync_at' => 'datetime',
    ];

    public function details()
    {
        return $this->hasMany(WmsPalletFormDetail::class, 'pallet_form_id', 'pallet_id');
    }

    public function position()
    {
        return $this->belongsTo(WmsPosition::class, 'position_id');
    }

    /**
     * Get plant code ('KBN' or 'KARAWANG')
     */
    public function getPlantAttribute(): string
    {
        if (! empty($this->attributes['plant'])) {
            return strtoupper($this->attributes['plant']);
        }

        if ($this->relationLoaded('details') && $this->details->isNotEmpty()) {
            $hasKr = $this->details->contains(fn ($d) => str_starts_with(strtoupper($d->warehouse ?? ''), 'KR'));
            return $hasKr ? 'KARAWANG' : 'KBN';
        }

        return 'KBN';
    }

    /**
     * Scope query per plant
     */
    public function scopeForPlant($query, ?string $plant)
    {
        if (empty($plant) || $plant === 'ALL') {
            return $query;
        }

        $plantUpper = strtoupper(trim($plant));
        if ($plantUpper === 'KARAWANG') {
            return $query->where(function ($q) {
                $q->where('plant', 'KARAWANG')
                  ->orWhereHas('details', function ($d) {
                      $d->where('warehouse', 'like', 'KR%');
                  });
            });
        } elseif ($plantUpper === 'KBN') {
            return $query->where(function ($q) {
                $q->where(function ($sq) {
                    $sq->where('plant', 'KBN')
                       ->orWhereNull('plant')
                       ->orWhere('plant', '');
                })->whereDoesntHave('details', function ($d) {
                    $d->where('warehouse', 'like', 'KR%');
                });
            });
        }

        return $query;
    }

    /**
     * Get age in days in warehouse
     */
    public function getAgeDaysAttribute(): int
    {
        $refDate = $this->assigned_at ?? $this->created_at;

        return $refDate ? (int) $refDate->diffInDays(now()) : 0;
    }

    /**
     * Check if stored in warehouse for >= 30 days
     */
    public function getIsOveragedAttribute(): bool
    {
        return $this->age_days >= 30;
    }

    /**
     * Get Putaway Lead Time formatted string (created_at -> assigned_at)
     */
    public function getPutawayLeadTimeAttribute(): string
    {
        if (! $this->assigned_at || ! $this->created_at) {
            return '-';
        }

        $diffMinutes = (int) $this->created_at->diffInMinutes($this->assigned_at);

        if ($diffMinutes < 1) {
            return '< 1 Min';
        }
        if ($diffMinutes < 60) {
            return "{$diffMinutes} Min";
        }

        $hours = floor($diffMinutes / 60);
        $mins = $diffMinutes % 60;

        if ($hours < 24) {
            return "{$hours} J " . ($mins > 0 ? "{$mins} M" : '');
        }

        $days = floor($hours / 24);
        $remHours = $hours % 24;

        return "{$days} Hr " . ($remHours > 0 ? "{$remHours} J" : '');
    }
}
