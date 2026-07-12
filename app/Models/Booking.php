<?php

namespace App\Models;

use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
 * @property Carbon|string $date
 * @property array $time_slots
 * @property string|null $notes
 * @property string $total_price
 * @property string|null $receipt_path
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
    'status',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

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
            'time_slots' => 'array',
            'total_price' => 'decimal:2',
        ];
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
}
