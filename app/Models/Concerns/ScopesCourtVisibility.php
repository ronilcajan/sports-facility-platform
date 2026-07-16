<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Court visibility scope. Kept in its own file as the single source of truth
 * for which courts a user may see, reused by every downstream (booking) query.
 */
trait ScopesCourtVisibility
{
    /**
     * Limit the query to courts the given user is allowed to see.
     *
     * Super-admins (and not-yet-scoped admins) see every court; venue admins
     * see only courts in their assigned venue; staff see only courts they are
     * assigned to.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isVenueScopedAdmin()) {
            return $query->where('venue_id', $user->venue_id);
        }

        if ($user->canManageAllCourts()) {
            return $query;
        }

        return $query->whereHas('staff', function (Builder $staffQuery) use ($user): void {
            $staffQuery->whereKey($user->getKey());
        });
    }
}
