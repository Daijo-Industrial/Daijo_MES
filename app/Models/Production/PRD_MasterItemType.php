<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;

class PRD_MasterItemType extends Model
{
    protected $table = 'prd_master_item_types';

    public function item()
    {
        return $this->hasMany(PRD_ListAllMasterItem);
    }
}
