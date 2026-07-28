<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CustomerBookingController extends Controller
{
    /**
     * Display the signed-in customer's bookings.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('customer/Bookings', [
            'bookings' => self::bookingsFor($request->user()),
            'courts' => Court::where('is_active', true)->get(['id', 'name', 'sport_type', 'base_price', 'slot_prices'])->map(function (Court $court) {
                return [
                    'id' => $court->id,
                    'name' => $court->name,
                    'sport_type' => $court->sport_type?->label() ?? $court->sport_type,
                    'base_price' => (string) $court->base_price,
                    'slot_prices' => $court->slot_prices,
                ];
            }),
        ]);
    }

    /**
     * Build the booking list for a customer: bookings linked to their account,
     * plus any guest bookings made with the same email (so registering later
     * with that email surfaces past bookings).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function bookingsFor(User $user): Collection
    {
        return Booking::with('court')
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->latest()
            ->get()
            ->map(function (Booking $booking) {
                return [
                    'id' => $booking->id,
                    'reference_code' => 'DY-RESRV-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT),
                    'court' => $booking->court ? [
                        'id' => $booking->court->id,
                        'name' => $booking->court->name,
                        'sport_type' => $booking->court->sport_type?->label() ?? $booking->court->sport_type,
                    ] : null,
                    'name' => $booking->name,
                    'email' => $booking->email,
                    'phone' => $booking->phone,
                    'date' => $booking->date,
                    'time_slots' => $booking->time_slots,
                    'total_price' => number_format((float) $booking->total_price, 2, '.', ''),
                    'receipt_url' => $booking->receipt_url,
                    'status' => $booking->status,
                    'notes' => $booking->notes,
                ];
            });
    }
}
