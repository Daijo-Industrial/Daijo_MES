<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;

class PRD_ListAllMasterItem extends Model
{
    protected $table = 'prd_list_all_master_items';

    public function itemType()
    {
        return $this->belongsTo(PRD_MasterItemType);
    }
}
