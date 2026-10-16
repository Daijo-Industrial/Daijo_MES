<?php

namespace App\Application\Inventory\DTOs;

use App\Domain\Inventory\ValueObjects\StockStatus;

/**
 * Represents a single FG item enriched with reject data and health status.
 *
 * Readonly: once constructed by the Use Case, views should never mutate it.
 */
readonly class StockHealthItemDTO
{
    public function __construct(
        public readonly string $itemCode,
        public readonly string $itemName,
        public readonly string $processOwner,
        public readonly string $family,
        public readonly int $stock,
        public readonly int $safetyStock,
        public readonly int|float $rejectStock,
        public readonly StockStatus $status,
    ) {}
}
