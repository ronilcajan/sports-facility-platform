<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Court;
use App\Notifications\BookingStatusNotification;
use Illuminate\Http\JsonResponse;

class BookingController extends Controller
{
    /**
     * Store a newly created booking in the database.
     */
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $court = Court::findOrFail($request->validated('court_id'));

        // Save receipt to public storage disk
        $receiptPath = $request->file('receipt')->store('receipts', 'public');

        // Calculate total price based on duration slots
        $totalPrice = $court->base_price * count($request->validated('time'));

        // Persist booking record
        $booking = Booking::create([
            'court_id' => $court->id,
            'user_id' => auth()->id(),
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'date' => $request->validated('date'),
            'time_slots' => $request->validated('time'),
            'notes' => $request->validated('notes'),
            'total_price' => $totalPrice,
            'receipt_path' => $receiptPath,
            'status' => 'pending',
        ]);

        // Send notification to staff assigned to this court
        foreach ($court->staff as $staffMember) {
            $staffMember->notify(new BookingStatusNotification($booking, 'created'));
        }

        return response()->json([
            'success' => true,
            'booking' => [
                'id' => $booking->id,
                'reference_code' => 'DY-RESRV-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT),
                'name' => $booking->name,
                'date' => $booking->date,
                'time_slots' => $booking->time_slots,
                'total_price' => number_format((float) $booking->total_price, 2, '.', ''),
                'receipt_url' => asset('storage/'.$booking->receipt_path),
            ],
        ], 201);
    }
}
