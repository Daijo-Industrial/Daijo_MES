<?php

namespace App\Application\Inventory\DTOs;

/**
 * Carries filter parameters from the HTTP layer into the Use Case.
 *
 * Using a DTO keeps the Use Case free of framework Request objects,
 * which makes it easy to test in isolation.
 */
readonly class StockHealthFilterDTO
{
    public function __construct(
        public readonly string $search = '',
        public readonly string $processOwner = '',
        public readonly string $family = '',
    ) {}

    /**
     * Convenience factory — builds the DTO from a plain array
     * (e.g. $request->only([...]) from the controller).
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            search: trim($data['search'] ?? ''),
            processOwner: trim($data['process_owner'] ?? ''),
            family: trim($data['family'] ?? ''),
        );
    }
}
