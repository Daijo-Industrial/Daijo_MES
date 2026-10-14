<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterListMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'master_list_materials';

    protected $fillable = [
        'item_code',
        'item_description',
        'preferred_supplier',
        'purchasing_uom',
    ];

    public function getDescriptionAttribute(): ?string
    {
        return $this->item_description;
    }

    public function getItemNameAttribute(): ?string
    {
        return $this->item_description;
    }
}
