<?php

namespace App\Support;

use App\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Builds the 5-day calendar-board payload for the bookings views. Shared by the
 * admin and staff booking controllers so both produce an identical structure;
 * the caller passes an already scoped + filtered booking query.
 */
class BookingCalendar
{
    public const WINDOW_DAYS = 5;

    /**
     * Build the day columns for the window starting at $start.
     *
     * @param  Builder<Booking>  $bookings  Already scoped/filtered booking query.
     * @return array<int, array<string, mixed>>
     */
    public static function build(Builder $bookings, CarbonImmutable $start): array
    {
        $startDate = $start->startOfDay();
        $end = $startDate->addDays(self::WINDOW_DAYS - 1);
        $today = CarbonImmutable::now()->toDateString();

        $rows = (clone $bookings)
            ->with('court:id,name')
            ->whereBetween('date', [$startDate->toDateString(), $end->toDateString()])
            ->get()
            ->map(fn (Booking $booking): array => [
                'id' => $booking->id,
                'name' => $booking->name,
                'email' => $booking->email,
                'phone' => $booking->phone,
                'date' => $booking->date,
                'time_slots' => $booking->time_slots,
                'total_price' => number_format((float) $booking->total_price, 2, '.', ''),
                'status' => $booking->status,
                'receipt_url' => $booking->receipt_url,
                'court' => $booking->court ? ['id' => $booking->court->id, 'name' => $booking->court->name] : null,
            ]);

        $days = [];

        for ($offset = 0; $offset < self::WINDOW_DAYS; $offset++) {
            $day = $startDate->addDays($offset);
            $dateString = $day->toDateString();

            $days[] = [
                'date' => $dateString,
                'weekday' => $day->format('D'),
                'dayNum' => $day->format('j'),
                'month' => $day->format('M'),
                'isToday' => $dateString === $today,
                'bookings' => $rows->where('date', $dateString)->values()->all(),
            ];
        }

        return $days;
    }

    /**
     * Navigation anchors for the current window.
     *
     * @return array<string, string|bool>
     */
    public static function window(CarbonImmutable $start): array
    {
        $startDate = $start->startOfDay();
        $today = CarbonImmutable::now()->startOfDay();

        return [
            'start' => $startDate->toDateString(),
            'prev' => $startDate->subDays(self::WINDOW_DAYS)->toDateString(),
            'next' => $startDate->addDays(self::WINDOW_DAYS)->toDateString(),
            'today' => $today->toDateString(),
            'isToday' => $startDate->equalTo($today),
        ];
    }

    /**
     * Resolve the window start date from a request value, defaulting to today.
     */
    public static function resolveStart(?string $start): CarbonImmutable
    {
        if ($start !== null && $start !== '') {
            try {
                return CarbonImmutable::parse($start)->startOfDay();
            } catch (\Exception) {
                // Fall through to today on an unparseable value.
            }
        }

        return CarbonImmutable::now()->startOfDay();
    }
}
