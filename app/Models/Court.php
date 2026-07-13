<?php

namespace App\Models;

use App\Enums\CourtStatus;
use App\Enums\SportType;
use Database\Factories\CourtFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property SportType $sport_type
 * @property string|null $description
 * @property CourtStatus $status
 * @property string $base_price
 * @property int $slot_duration_minutes
 * @property int $buffer_minutes
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'name',
    'slug',
    'sport_type',
    'description',
    'status',
    'base_price',
    'slot_duration_minutes',
    'buffer_minutes',
    'is_active',
])]
class Court extends Model
{
    /** @use HasFactory<CourtFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sport_type' => SportType::class,
            'status' => CourtStatus::class,
            'base_price' => 'decimal:2',
            'slot_duration_minutes' => 'integer',
            'buffer_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<CourtImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(CourtImage::class);
    }

    /**
     * @return HasOne<CourtImage, $this>
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(CourtImage::class)->where('is_primary', true);
    }

    /**
     * Staff members assigned to this court.
     *
     * @return BelongsToMany<User, $this>
     */
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * Bookings associated with this court.
     *
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Unavailable blackout dates/slots for this court.
     *
     * @return HasMany<CourtUnavailability, $this>
     */
    public function unavailabilities(): HasMany
    {
        return $this->hasMany(CourtUnavailability::class);
    }

    /**
     * Limit the query to courts the given user is allowed to see.
     *
     * Admins and super-admins see every court; staff see only courts they are
     * assigned to. This is the single source of truth for court visibility and
     * must be reused by every downstream (booking) query.
     *
     * @param  Builder<Court>  $query
     * @return Builder<Court>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->canManageAllCourts()) {
            return $query;
        }

        return $query->whereHas('staff', function (Builder $staffQuery) use ($user): void {
            $staffQuery->whereKey($user->getKey());
        });
    }
}
