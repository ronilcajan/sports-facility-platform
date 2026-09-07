<?php

namespace App\Models;

use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $court_id
 * @property int|null $user_id
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property Carbon $date
 * @property array $time_slots
 * @property string|null $notes
 * @property string|null $admin_notes
 * @property string $total_price
 * @property string|null $receipt_path
 * @property string|null $transaction_code
 * @property string $status
 * @property-read float $total_hours
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'court_id',
    'user_id',
    'name',
    'email',
    'phone',
    'date',
    'time_slots',
    'notes',
    'admin_notes',
    'total_price',
    'receipt_path',
    'transaction_code',
    'status',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    /**
     * Statuses that represent a booking which counts toward revenue.
     *
     * @var array<int, string>
     */
    public const REVENUE_STATUSES = ['approved', 'confirmed', 'completed'];

    /**
     * Statuses that release the court slot back to the public calendar.
     *
     * @var array<int, string>
     */
    public const RELEASED_STATUSES = ['cancelled', 'rejected'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['receipt_url', 'total_hours'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'court_id' => 'integer',
            'user_id' => 'integer',
            'date' => 'date:Y-m-d',
            'time_slots' => 'array',
            'total_price' => 'decimal:2',
        ];
    }

    /**
     * Calculate total duration in hours from time_slots.
     */
    public function calculateDurationHours(): float
    {
        if (empty($this->time_slots) || ! is_array($this->time_slots)) {
            return 0.0;
        }

        $totalMinutes = 0;
        foreach ($this->time_slots as $slot) {
            if (! is_string($slot) || trim($slot) === '') {
                continue;
            }

            // Check if slot represents a range, e.g. "9:00 AM – 11:00 AM" or "07:00 AM - 08:00 AM"
            $parts = preg_split('/\s*(?:–|-|\bto\b)\s*/u', trim($slot));
            if (is_array($parts) && count($parts) === 2) {
                try {
                    $start = Carbon::parse($parts[0]);
                    $end = Carbon::parse($parts[1]);
                    $diff = $start->diffInMinutes($end, false);
                    if ($diff < 0) {
                        $diff += 24 * 60;
                    }
                    if ($diff > 0) {
                        $totalMinutes += $diff;

                        continue;
                    }
                } catch (\Throwable) {
                    // Fallback to default single slot duration
                }
            }

            $totalMinutes += $this->court?->slot_duration_minutes ?? 60;
        }

        return round($totalMinutes / 60, 2);
    }

    /**
     * Accessor for total_hours.
     */
    public function getTotalHoursAttribute(): float
    {
        return $this->calculateDurationHours();
    }

    /**
     * Get full public URL for the payment receipt file.
     */
    public function getReceiptUrlAttribute(): ?string
    {
        if (! $this->receipt_path) {
            return null;
        }

        if (str_starts_with($this->receipt_path, 'http://') || str_starts_with($this->receipt_path, 'https://')) {
            return $this->receipt_path;
        }

        return asset('storage/'.$this->receipt_path);
    }

    /**
     * @return BelongsTo<Court, $this>
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope bookings to those on courts visible to the given user.
     *
     * @param  Builder<Booking>  $query
     * @return Builder<Booking>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        // Venue admins are constrained to their venue's courts; only unscoped
        // managers (super-admins, not-yet-assigned admins) see every booking.
        if (! $user->isVenueScopedAdmin() && $user->canManageAllCourts()) {
            return $query;
        }

        return $query->whereHas('court', function (Builder $courtQuery) use ($user): void {
            $courtQuery->visibleTo($user);
        });
    }

    /**
     * Scope bookings to those still occupying their court slot.
     *
     * @param  Builder<Booking>  $query
     * @return Builder<Booking>
     */
    public function scopeHoldingSlots(Builder $query): Builder
    {
        return $query->whereNotIn('status', self::RELEASED_STATUSES);
    }
}
