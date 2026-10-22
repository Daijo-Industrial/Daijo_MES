<?php

namespace App\Traits;

use App\Events\ParentDataUpdated; // Adjust the event name if needed
use App\Models\Production\PRD_BillOfMaterialChild;
use App\Models\Production\PRD_BillOfMaterialParent;
use App\Models\Production\PRD_MaterialLog;
use App\Models\Production\PRD_MouldingUserLog;

trait BroadcastsDashboardModelUpdates
{
    protected static function bootBroadcastsDashboardModelUpdates()
    {
        static::updated(function () {
            // Fetch updated data
            // $parents = PRD_BillOfMaterialParent::all();
            // $childs = PRD_BillOfMaterialChild::all();
            // $materialLogs = PRD_MaterialLog::all();
            // $mouldingUserLogs= PRD_MouldingUserLog::all();

            // event(new ParentDataUpdated($parents, $childs, $materialLogs, $mouldingUserLogs));
        });

        static::created(function () {
            // $parents = PRD_BillOfMaterialParent::all();
            // $childs = PRD_BillOfMaterialChild::all();
            // $materialLogs = PRD_MaterialLog::all();
            // $mouldingUserLogs = PRD_MouldingUserLog::all();

            // event(new ParentDataUpdated($parents, $childs, $materialLogs, $mouldingUserLogs));
        });

        static::deleted(function () {
            // $parents = PRD_BillOfMaterialParent::all();
            // $childs = PRD_BillOfMaterialChild::all();
            // $materialLogs = PRD_MaterialLog::all();
            // $mouldingUserLogs = PRD_MouldingUserLog::all();

            // event(new ParentDataUpdated($parents, $childs, $materialLogs, $mouldingUserLogs));
        });
    }
}
