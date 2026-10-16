<?php

namespace App\Models\Production;

use App\Traits\BroadcastsDashboardModelUpdates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PRD_BillOfMaterialParent extends Model
{
    use BroadcastsDashboardModelUpdates, HasFactory;

    protected $table = 'prd_bill_of_material_parents';

    protected $fillable = [
        'code',
        'description',
        'type',
        'customer',
    ];

    public function children()
    {
        return $this->hasMany(PRD_BillOfMaterialChild::class, 'parent_id');
    }
}
