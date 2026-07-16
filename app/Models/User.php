<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RoleName;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property int|null $venue_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'venue_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Courts this user (staff) is assigned to.
     *
     * @return BelongsToMany<Court, $this>
     */
    public function assignedCourts(): BelongsToMany
    {
        return $this->belongsToMany(Court::class)->withTimestamps();
    }

    /**
     * The venue this user belongs to.
     *
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Bookings this user (customer) has made through their account.
     *
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Whether this user may manage (and see) every court, regardless of
     * staff assignment. True for admins and super-admins.
     */
    public function canManageAllCourts(): bool
    {
        return $this->hasAnyRole([RoleName::SuperAdmin->value, RoleName::Admin->value]);
    }

    /**
     * Whether this user is assigned to the given court.
     */
    public function isAssignedToCourt(Court $court): bool
    {
        return $this->assignedCourts()
            ->whereKey($court->getKey())
            ->exists();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(RoleName::SuperAdmin->value);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(RoleName::Admin->value) || $this->isSuperAdmin();
    }

    public function isStaff(): bool
    {
        return $this->hasRole(RoleName::Staff->value);
    }

    /**
     * Whether this user is an admin confined to a single venue: an admin (not a
     * super-admin) who has been assigned to a venue. Super-admins manage every
     * venue globally; an admin with no venue assignment is not yet scoped.
     */
    public function isVenueScopedAdmin(): bool
    {
        return $this->hasRole(RoleName::Admin->value)
            && ! $this->isSuperAdmin()
            && $this->venue_id !== null;
    }

    /**
     * Whether this user may manage the given court. Super-admins (and
     * not-yet-scoped admins) manage every court; venue admins manage only
     * courts in their assigned venue; staff manage only courts they are
     * assigned to. This mirrors {@see Court::scopeVisibleTo()} and is the
     * authorization counterpart used by the court and booking policies.
     */
    public function canManageCourt(Court $court): bool
    {
        if ($this->isVenueScopedAdmin()) {
            return $court->venue_id === $this->venue_id;
        }

        if ($this->canManageAllCourts()) {
            return true;
        }

        return $this->isAssignedToCourt($court);
    }

    /**
     * Scope query to users with the Staff role.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeStaff(Builder $query): Builder
    {
        return $query->role(RoleName::Staff->value);
    }
}
