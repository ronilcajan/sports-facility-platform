<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\User;

class UserPolicy
{
    /**
     * A venue-scoped admin may only manage staff that belong to their own
     * venue. Customers are global (they book across venues) and remain
     * manageable; super-admins/other admins are already blocked upstream.
     */
    private function deniesCrossVenueStaff(User $user, User $model): bool
    {
        if (! $user->isVenueScopedAdmin()) {
            return false;
        }

        if ($model->hasRole(RoleName::Staff->value)) {
            return $model->venue_id !== $user->venue_id;
        }

        return false;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Admin cannot view/manage super-admins.
        if ($user->isAdmin() && ! $user->isSuperAdmin() && $model->isSuperAdmin()) {
            return false;
        }

        if ($this->deniesCrossVenueStaff($user, $model)) {
            return false;
        }

        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Admin cannot manage super-admins or other admins.
        if ($user->isAdmin() && ! $user->isSuperAdmin() && ($model->isSuperAdmin() || $model->isAdmin())) {
            return false;
        }

        if ($this->deniesCrossVenueStaff($user, $model)) {
            return false;
        }

        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Admin cannot manage super-admins or other admins.
        if ($user->isAdmin() && ! $user->isSuperAdmin() && ($model->isSuperAdmin() || $model->isAdmin())) {
            return false;
        }

        if ($this->deniesCrossVenueStaff($user, $model)) {
            return false;
        }

        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->isSuperAdmin();
    }
}
