<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkspaceSubscription;
use Illuminate\Auth\Access\Response;

class WorkspaceSubscriptionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, WorkspaceSubscription $workspaceSubscription): Response
    {
        return $workspaceSubscription->workspace->isAccessibleBy($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    public function requestChange(User $user, WorkspaceSubscription $workspaceSubscription): Response
    {
        return $workspaceSubscription->workspace->isOwnedBy($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function requestCancellation(User $user, WorkspaceSubscription $workspaceSubscription): Response
    {
        return $this->requestChange($user, $workspaceSubscription);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, WorkspaceSubscription $workspaceSubscription): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WorkspaceSubscription $workspaceSubscription): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, WorkspaceSubscription $workspaceSubscription): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, WorkspaceSubscription $workspaceSubscription): bool
    {
        return false;
    }
}
