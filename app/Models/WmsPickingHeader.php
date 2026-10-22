<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WmsPickingHeader extends Model
{
    use HasFactory;

    protected $table = 'wms_picking_headers';

    protected $fillable = [
        'picking_no',
        'doc_num',
        'status',
        'created_by',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(WmsPickingDetail::class, 'picking_header_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
