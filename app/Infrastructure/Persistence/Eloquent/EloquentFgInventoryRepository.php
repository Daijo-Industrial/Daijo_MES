<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Inventory\Contracts\FgInventoryRepositoryInterface;
use App\Models\Delivery\sapInventoryFg;
use Illuminate\Support\Collection;

/**
 * Eloquent implementation of the FG Inventory repository contract.
 *
 * Swap this for an API-backed or cache-backed implementation
 * by rebinding in AppServiceProvider — no other code changes needed.
 */
class EloquentFgInventoryRepository implements FgInventoryRepositoryInterface
{
    public function getAll(array $filters): Collection
    {
        $query = sapInventoryFg::query();

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('item_code', 'like', "%{$term}%")
                    ->orWhere('item_name', 'like', "%{$term}%");
            });
        }

        if (! empty($filters['processOwner'])) {
            $query->where('process_owner', $filters['processOwner']);
        }

        if (! empty($filters['family'])) {
            $query->where('family', $filters['family']);
        }

        return $query
            ->select([
                'item_code',
                'item_name',
                'process_owner',
                'family',
                'stock',
                'safety_stock',
            ])
            ->orderBy('process_owner')
            ->orderBy('item_code')
            ->get();
    }

    public function getDistinctProcessOwners(): array
    {
        return sapInventoryFg::query()
            ->whereNotNull('process_owner')
            ->where('process_owner', '!=', '')
            ->distinct()
            ->orderBy('process_owner')
            ->pluck('process_owner')
            ->all();
    }

    public function getDistinctFamilies(): array
    {
        return sapInventoryFg::query()
            ->whereNotNull('family')
            ->where('family', '!=', '')
            ->distinct()
            ->orderBy('family')
            ->pluck('family')
            ->all();
    }
}
