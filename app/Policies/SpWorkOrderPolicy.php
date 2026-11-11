<?php

namespace App\Policies;

use App\Models\SpWorkOrder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SpWorkOrderPolicy
{
    /**
     * Determine whether the user can view any work orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('second-process-work-orders');
    }

    /**
     * Determine whether the user can view the work order.
     */
    public function view(User $user, SpWorkOrder $workOrder): bool
    {
        return $user->can('second-process-work-orders');
    }

    /**
     * Determine whether the user can create work orders.
     */
    public function create(User $user): bool
    {
        return $user->can('second-process-work-orders');
    }

    /**
     * Determine whether the user can update the work order.
     */
    public function update(User $user, SpWorkOrder $workOrder): Response
    {
        if (! $user->can('second-process-work-orders')) {
            return Response::deny('You do not have permission to edit Second Process work orders.');
        }

        if ($workOrder->status !== 'draft') {
            return Response::deny('Only draft Work Orders can be edited.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can release the work order to production.
     */
    public function release(User $user, SpWorkOrder $workOrder): Response
    {
        if (! $user->can('second-process-work-orders')) {
            return Response::deny('You do not have permission to release Second Process work orders.');
        }

        if ($workOrder->status !== 'draft') {
            return Response::deny('Only draft Work Orders can be released.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can revert the work order to draft.
     */
    public function revertToDraft(User $user, SpWorkOrder $workOrder): Response
    {
        if (! $user->can('second-process-work-orders')) {
            return Response::deny('You do not have permission to revert Second Process work orders.');
        }

        if ($workOrder->status !== 'planned') {
            return Response::deny('Only planned Work Orders can be reverted to draft.');
        }

        if ($workOrder->sessions()->count() > 0) {
            return Response::deny('Cannot revert Work Order to draft because production sessions have already been logged.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can delete the work order.
     */
    public function delete(User $user, SpWorkOrder $workOrder): Response
    {
        if (! $user->can('second-process-work-orders')) {
            return Response::deny('You do not have permission to delete Second Process work orders.');
        }

        if (! in_array($workOrder->status, ['draft', 'planned'], true)) {
            return Response::deny('Cannot delete Work Orders that are already in progress or completed.');
        }

        return Response::allow();
    }
}

