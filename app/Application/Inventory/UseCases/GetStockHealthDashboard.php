<?php

namespace App\Application\Inventory\UseCases;

use App\Application\Inventory\DTOs\StockHealthFilterDTO;
use App\Application\Inventory\DTOs\StockHealthItemDTO;
use App\Domain\Inventory\Contracts\FgInventoryRepositoryInterface;
use App\Domain\Inventory\Contracts\RejectRepositoryInterface;
use App\Domain\Inventory\ValueObjects\StockStatus;

/**
 * Orchestrates the Stock Health Dashboard data assembly.
 *
 * Responsibilities:
 *  1. Fetch FG inventory rows (filtered via the DTO).
 *  2. Fetch reject stock as a keyed map (one query, O(1) lookup).
 *  3. Map each row to a StockHealthItemDTO with a StockStatus.
 *  4. Compute summary counts.
 *  5. Return everything the view needs — no formatting, no HTML.
 *
 * This class has no Laravel dependencies so it can be unit-tested
 * by injecting mock repositories.
 */
class GetStockHealthDashboard
{
    public function __construct(
        private readonly FgInventoryRepositoryInterface $fgRepository,
        private readonly RejectRepositoryInterface $rejectRepository,
    ) {}

    /**
     * @return array{
     *   items:         StockHealthItemDTO[],
     *   summary:       array{total: int, healthy: int, at_risk: int, critical: int},
     *   processOwners: string[],
     *   families:      string[],
     * }
     */
    public function execute(StockHealthFilterDTO $filter): array
    {
        $rejectMap = $this->rejectRepository->getKeyedByItemNo();

        $fgRows = $this->fgRepository->getAll([
            'search' => $filter->search,
            'processOwner' => $filter->processOwner,
            'family' => $filter->family,
        ]);

        $items = $fgRows->map(function (object $row) use ($rejectMap): StockHealthItemDTO {
            $stock = (int) ($row->stock ?? 0);
            $safetyStock = (int) ($row->safety_stock ?? 0);
            $rejectStock = $rejectMap[$row->item_code] ?? 0;

            return new StockHealthItemDTO(
                itemCode: $row->item_code ?? '',
                itemName: $row->item_name ?? '',
                processOwner: $row->process_owner ?? '',
                family: $row->family ?? '',
                stock: $stock,
                safetyStock: $safetyStock,
                rejectStock: $rejectStock,
                status: StockStatus::fromStock($stock, $safetyStock),
            );
        })->values()->all();

        $summary = $this->buildSummary($items);

        return [
            'items' => $items,
            'summary' => $summary,
            'processOwners' => $this->fgRepository->getDistinctProcessOwners(),
            'families' => $this->fgRepository->getDistinctFamilies(),
        ];
    }

    /**
     * @param StockHealthItemDTO[] $items
     * @return array{total: int, healthy: int, at_risk: int, critical: int}
     */
    private function buildSummary(array $items): array
    {
        $summary = ['total' => count($items), 'healthy' => 0, 'at_risk' => 0, 'critical' => 0];

        foreach ($items as $item) {
            match ($item->status) {
                StockStatus::Healthy => $summary['healthy']++,
                StockStatus::AtRisk => $summary['at_risk']++,
                StockStatus::Critical => $summary['critical']++,
            };
        }

        return $summary;
    }
}
