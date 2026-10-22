<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PlantContextService
{
    public const ALL_PLANTS = 'ALL';

    /**
     * Check if the given (or authenticated) user can switch branches.
     */
    public function canSwitchBranch(?User $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('SUPER-ADMIN')
            || $user->hasRoleAccess('SUPER-ADMIN');
    }

    /**
     * Get the effective branch ID for current user session.
     * Returns null if "ALL" branches are selected or user has no branch.
     * Returns int branch ID if locked or selected.
     */
    public function getEffectiveBranchId(?User $user = null): ?int
    {
        $user = $user ?? Auth::user();

        if (! $user) {
            return null;
        }

        if ($this->canSwitchBranch($user)) {
            if (session()->has('active_branch_id')) {
                $sessionVal = session('active_branch_id');
                if ($sessionVal === self::ALL_PLANTS || empty($sessionVal)) {
                    return null;
                }

                return (int) $sessionVal;
            }

            // Default for global user if not explicitly switched: user's branch or null (All)
            return $user->branch_id;
        }

        return $user->branch_id;
    }

    /**
     * Determine if the view is currently scoped to all plants.
     */
    public function isAllPlants(?User $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (! $user || ! $this->canSwitchBranch($user)) {
            return false;
        }

        if (session()->has('active_branch_id')) {
            return session('active_branch_id') === self::ALL_PLANTS;
        }

        return $user->branch_id === null;
    }

    /**
     * Set active branch in session for users with switching capability.
     */
    public function setEffectiveBranch(string|int|null $branchId, ?User $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (! $user || ! $this->canSwitchBranch($user)) {
            return false;
        }

        if ($branchId === self::ALL_PLANTS || empty($branchId)) {
            session(['active_branch_id' => self::ALL_PLANTS]);

            return true;
        }

        $branch = Branch::where('id', $branchId)->where('is_active', true)->first();
        if ($branch) {
            session(['active_branch_id' => (int) $branch->id]);

            return true;
        }

        return false;
    }

    /**
     * Get the active Branch model, if scoped to a specific branch.
     */
    public function getEffectiveBranch(?User $user = null): ?Branch
    {
        $branchId = $this->getEffectiveBranchId($user);

        if (! $branchId) {
            return null;
        }

        return Branch::find($branchId);
    }

    /**
     * Get a human-readable display label for the active plant.
     */
    public function getActivePlantLabel(?User $user = null): string
    {
        $user = $user ?? Auth::user();

        if (! $user) {
            return 'Guest';
        }

        if ($this->isAllPlants($user)) {
            return 'All Plants';
        }

        $branch = $this->getEffectiveBranch($user);

        if ($branch) {
            return $branch->name;
        }

        return 'Unassigned Plant';
    }

    /**
     * Check if the active plant context is the Main Plant (Jakarta) or All Plants.
     */
    public function isMainPlant(?User $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (! $user) {
            return false;
        }

        if ($this->isAllPlants($user)) {
            return true;
        }

        $branch = $this->getEffectiveBranch($user);

        if ($branch) {
            return (bool) $branch->is_main;
        }

        return true;
    }

    /**
     * Check if the active plant context supports Second Process operations.
     * Evaluates dynamically against branch_department mapping (SP department).
     */
    public function supportsSecondProcess(?User $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (! $user) {
            return false;
        }

        if ($this->isAllPlants($user)) {
            return true;
        }

        $branch = $this->getEffectiveBranch($user);

        if ($branch) {
            // If branch_department mapping exists, check if 'SP' department is mapped and active
            if ($branch->departments()->exists()) {
                return $branch->departments()
                    ->where('code', 'SP')
                    ->where('departments.is_active', true)
                    ->exists();
            }

            // Fallback for unconfigured branch: check is_main
            return (bool) $branch->is_main;
        }

        // Legacy unassigned users default to true (Jakarta)
        return true;
    }
}
