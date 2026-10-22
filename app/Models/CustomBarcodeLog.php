<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomBarcodeLog extends Model
{
    use HasFactory;

    protected $table = 'custom_barcode_logs';

    protected $fillable = [
        'user_id',
        'user_name',
        'item_code',
        'item_name',
        'spk_number',
        'quantity',
        'warehouse',
        'shift',
        'start_label',
        'end_label',
        'total_labels',
        'prod_date',
        'operator',
        'customer',
        'barcode_type',
        'is_trial',
        'remark',
    ];

    protected $casts = [
        'is_trial' => 'boolean',
        'prod_date' => 'date',
        'quantity' => 'integer',
        'start_label' => 'integer',
        'end_label' => 'integer',
        'total_labels' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function masterItem()
    {
        return $this->belongsTo(MasterListItem::class, 'item_code', 'item_code');
    }
}
