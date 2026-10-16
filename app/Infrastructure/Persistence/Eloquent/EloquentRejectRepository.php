<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Inventory\Contracts\RejectRepositoryInterface;
use App\Models\Delivery\SapReject;

/**
 * Eloquent implementation of the Reject repository contract.
 *
 * Returns all reject records as a flat [item_no => in_stock] map so
 * the Use Case can do O(1) lookups without additional queries.
 */
class EloquentRejectRepository implements RejectRepositoryInterface
{
    public function getKeyedByItemNo(): array
    {
        return SapReject::query()
            ->select(['item_no', 'in_stock'])
            ->get()
            ->keyBy('item_no')
            ->map(fn ($row) => $row->in_stock)
            ->all();
    }
}
