<?php

namespace App\Traits;

use App\Models\Branch;
use App\Services\PlantContextService;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToBranch
{
    /**
     * Boot the BelongsToBranch trait.
     */
    public static function bootBelongsToBranch(): void
    {
        // Scope queries by current active branch context
        static::addGlobalScope('branch', function (Builder $builder) {
            $service = app(PlantContextService::class);
            $effectiveBranchId = $service->getEffectiveBranchId();

            // If effectiveBranchId is set (not "All Plants"), filter by branch_id or null legacy records
            if ($effectiveBranchId !== null) {
                $builder->where(function (Builder $q) use ($effectiveBranchId) {
                    $table = $q->getModel()->getTable();
                    $q->where($table . '.branch_id', $effectiveBranchId)
                        ->orWhereNull($table . '.branch_id');
                });
            }
        });

        // Automatically assign active branch_id when creating new records
        static::creating(function ($model) {
            if (empty($model->branch_id)) {
                $service = app(PlantContextService::class);
                $branchId = $service->getEffectiveBranchId();

                if (! $branchId && auth()->check() && auth()->user()->branch_id) {
                    $branchId = auth()->user()->branch_id;
                }

                if ($branchId) {
                    $model->branch_id = $branchId;
                }
            }
        });
    }

    /**
     * Relationship to Branch model.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Scope to bypass branch filtering explicitly.
     */
    public function scopeWithoutBranchScope(Builder $builder): Builder
    {
        return $builder->withoutGlobalScope('branch');
    }

    /**
     * Scope to explicitly filter by a specific branch.
     */
    public function scopeForBranch(Builder $builder, ?int $branchId): Builder
    {
        if ($branchId === null) {
            return $builder;
        }

        return $builder->where(function (Builder $q) use ($branchId) {
            $table = $q->getModel()->getTable();
            $q->where($table . '.branch_id', $branchId)
                ->orWhereNull($table . '.branch_id');
        });
    }
}
