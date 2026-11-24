<?php

use App\Models\WaitingPurchaseOrder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $orders = WaitingPurchaseOrder::whereNull('doc_num')->get();

        foreach ($orders as $order) {
            // Format the date into yyyymmdd
            $dateCreated = $order->created_at->format('Ymd');

            // Format the doc_num as WPO/id/dateCreated
            $docNum = 'WPO/' . $order->id . '/' . $dateCreated;

            // Update the model with the generated doc_num
            $order->update(['doc_num' => $docNum]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // One way migration, no need to rollback
    }
};
