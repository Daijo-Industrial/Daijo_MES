<?php

namespace App\Domain\Inventory\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for accessing finished-goods inventory data.
 *
 * The Domain layer depends on this interface, not on any Eloquent model.
 * Swap the implementation at any time (e.g. API, cache) without touching
 * business logic.
 */
interface FgInventoryRepositoryInterface
{
    /**
     * Return all FG inventory rows, optionally filtered.
     *
     * Supported filter keys:
     *   - search       (string) — partial match on item_code OR item_name
     *   - processOwner (string) — exact match on process_owner
     *   - family       (string) — exact match on family
     *
     * @param array<string, mixed> $filters
     * @return Collection<int, object>
     */
    public function getAll(array $filters): Collection;

    /**
     * Return a unique, sorted list of process_owner values.
     *
     * @return string[]
     */
    public function getDistinctProcessOwners(): array;

    /**
     * Return a unique, sorted list of family values.
     *
     * @return string[]
     */
    public function getDistinctFamilies(): array;
}
