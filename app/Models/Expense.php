<?php

namespace App\Models;

use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property float $amount
 * @property Carbon $date
 * @property string $category
 * @property int|null $venue_id
 * @property int|null $user_id
 * @property string|null $payment_method
 * @property string|null $reference_number
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Venue|null $venue
 * @property-read User|null $user
 */
class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    /**
     * Recognized categories for facility expenses.
     */
    public const CATEGORIES = [
        'utilities' => 'Utilities & Power',
        'maintenance' => 'Repairs & Maintenance',
        'equipment' => 'Sports Equipment & Gear',
        'supplies' => 'Facility Supplies',
        'salaries' => 'Staff Wages & Payroll',
        'marketing' => 'Marketing & Promotion',
        'rent' => 'Venue Lease & Rent',
        'other' => 'Other / Miscellaneous',
    ];

    /**
     * Common payment methods.
     */
    public const PAYMENT_METHODS = [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'gcash' => 'GCash',
        'maya' => 'Maya',
        'credit_card' => 'Credit Card',
        'check' => 'Check',
        'other' => 'Other',
    ];

    protected $fillable = [
        'title',
        'amount',
        'date',
        'category',
        'venue_id',
        'user_id',
        'payment_method',
        'reference_number',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
        ];
    }

    /**
     * Venue this expense is incurred by (null if universal platform expense).
     *
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * User/Admin who recorded this expense.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get human-friendly label for a category key.
     */
    public static function categoryLabel(?string $category): string
    {
        if (! $category) {
            return 'Other';
        }

        return self::CATEGORIES[$category] ?? ucfirst($category);
    }

    /**
     * Scope query to a specific venue.
     *
     * @param  Builder<Expense>  $query
     * @return Builder<Expense>
     */
    public function scopeForVenue(Builder $query, ?int $venueId): Builder
    {
        if ($venueId === null) {
            return $query;
        }

        return $query->where('venue_id', $venueId);
    }

    /**
     * Scope query to between two dates (inclusive).
     *
     * @param  Builder<Expense>  $query
     * @return Builder<Expense>
     */
    public function scopeBetweenDates(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
}
