<?php

namespace App\Domain\Inventory\ValueObjects;

/**
 * Represents the health classification of a finished-goods inventory item.
 *
 * Thresholds:
 *   Critical  – stock is below the safety stock level.
 *   AtRisk    – stock is at or above safety stock but below 1.5× safety stock.
 *   Healthy   – stock is at or above 1.5× safety stock.
 *
 * To change the "buffer" ratio, update AT_RISK_MULTIPLIER here — one place, done.
 */
enum StockStatus: string
{
    case Healthy = 'healthy';
    case AtRisk = 'at_risk';
    case Critical = 'critical';

    /**
     * The multiplier applied to safetyStock to determine the At-Risk upper boundary.
     * stock >= safetyStock * AT_RISK_MULTIPLIER → Healthy
     */
    private const AT_RISK_MULTIPLIER = 1.5;

    /**
     * Classify an item given its current stock and configured safety stock.
     *
     * @param int|null $stock Current on-hand quantity.
     * @param int|null $safetyStock Minimum desired quantity.
     */
    public static function fromStock(?int $stock, ?int $safetyStock): self
    {
        $stock = $stock ?? 0;
        $safetyStock = $safetyStock ?? 0;

        // When safety stock is not configured, treat any stock as healthy.
        if ($safetyStock <= 0) {
            return self::Healthy;
        }

        if ($stock < $safetyStock) {
            return self::Critical;
        }

        if ($stock < $safetyStock * self::AT_RISK_MULTIPLIER) {
            return self::AtRisk;
        }

        return self::Healthy;
    }

    /** Human-readable label for display in views. */
    public function label(): string
    {
        return match ($this) {
            self::Healthy => 'Healthy',
            self::AtRisk => 'At Risk',
            self::Critical => 'Critical',
        };
    }

    /** Tailwind CSS classes for the badge in the Blade view. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Healthy => 'bg-green-100 text-green-800',
            self::AtRisk => 'bg-yellow-100 text-yellow-800',
            self::Critical => 'bg-red-100 text-red-800',
        };
    }
}
