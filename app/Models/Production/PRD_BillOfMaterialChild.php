<?php

namespace App\Models\Production;

use App\Traits\BroadcastsDashboardModelUpdates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PRD_BillOfMaterialChild extends Model
{
    use BroadcastsDashboardModelUpdates, HasFactory;

    protected $table = 'prd_bill_of_material_childs';

    protected $fillable = [
        'parent_id',
        'item_code',
        'item_description',
        'quantity',
        'measure',
        'status',
        'action_type',
    ];

    public function materialProcess()
    {
        return $this->hasMany(PRD_MaterialLog::class, 'child_id');
    }

    public function parent()
    {
        return $this->belongsTo(PRD_BillOfMaterialParent::class, 'parent_id');
    }

    public function brokenChild()
    {
        return $this->hasMany(PRD_BrokenChild::class, 'child_id');
    }
}
