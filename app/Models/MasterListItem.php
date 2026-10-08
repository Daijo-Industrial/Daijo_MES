<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterListItem extends Model
{
    use HasFactory;

    public $timestamps = false;

     protected $fillable = [
        'item_code',
        'item_name',
        'tipe_mesin',
        'standart_packaging_list',
        'setup_time_minute',
        'pair',
        'cavity',
        'customer_code',
        'cycle_time',
        'project_code',
        'family',
        'description_in_foreign_lang',
        'color',
        'half_code_1',
        'half_code_2',
        'position',
    ];
    

    public function files()
    {
        return $this->hasMany(File::class, 'item_code', 'item_code');
    }

    public function photo()
    {
        return $this->hasOne(MasterItemPhoto::class, 'item_code', 'item_code');
    }

    public function customer()
    {
        return $this->belongsTo(
            MasterCustomerDelivery::class,
            'customer_code',   // foreign key di master_list_items
            'customer_code'    // owner key di master_customer_delivery
        );
    }

    public function businessPartner()
    {
        return $this->belongsTo(
            MasterBusinessPartner::class,
            'customer_code',
            'bp_code'
        );
    }
}
