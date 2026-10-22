<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaitingPurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'mold_name',
        'process',
        'price',
        'quotation_no',
        'remark',
        'doc_num',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($order) {
            // Format the date into yyyymmdd
            $dateCreated = $order->created_at->format('Ymd');

            // Format the doc_num as WPO/id/dateCreated
            $docNum = 'WPO/' . $order->id . '/' . $dateCreated;

            // Update the model with the generated doc_num
            $order->update(['doc_num' => $docNum]);
        });
    }

    public function files()
    {
        return $this->hasMany(File::class, 'item_code', 'doc_num');
    }
}
