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
 * @property string $total_price
 * @property string|null $receipt_path
 * @property string|null $transaction_code
 * @property string $status
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
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['receipt_url'];

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
}
