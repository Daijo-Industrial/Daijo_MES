<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WmsPalletFormDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pallet_form_id',
        'part_no',         // item per box (multi-item support)
        'model_name',      // nama item per box
        'spk_no',
        'qty',
        'warehouse',
        'label',
        'is_no_label',     // true jika box tidak punya label/SPK
        'no_label_reason', // alasan tidak ada label (opsional)
        'sap_sync_status',
        'sap_error_msg',
        'sap_sync_at',
    ];

    protected $casts = [
        'is_no_label' => 'boolean',
        'qty' => 'float',
        'sap_sync_at' => 'datetime',
    ];

    public function header()
    {
        return $this->belongsTo(WmsPalletForm::class, 'pallet_form_id', 'pallet_id');
    }

    public function item()
    {
        return $this->belongsTo(MasterListItem::class, 'part_no', 'item_code');
    }
}
