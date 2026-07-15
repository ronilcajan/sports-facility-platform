<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreBookingRequest;
use App\Mail\BookingReceivedMail;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtUnavailability;
use App\Notifications\BookingStatusNotification;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingController extends Controller
{
    /**
     * Build a scannable QR code (SVG data URI) that opens the staff booking view for verification.
     */
    private function bookingQrDataUri(Booking $booking): string
    {
        $payload = url('/staff/bookings/'.$booking->id);

        $renderer = new ImageRenderer(
            new RendererStyle(240, 1),
            new SvgImageBackEnd,
        );

        $svg = (new Writer($renderer))->writeString($payload);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * Store a newly created booking in the database.
     */
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $court = Court::findOrFail($request->validated('court_id'));

        // Save receipt to public storage disk (optional — booking is valid without it)
        $receiptPath = $request->hasFile('receipt')
            ? $request->file('receipt')->store('receipts', 'public')
            : null;

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
            'transaction_code' => $request->validated('transaction_code'),
            'status' => 'pending',
        ]);

        // Send notification to staff assigned to this court
        foreach ($court->staff as $staffMember) {
            $staffMember->notify(new BookingStatusNotification($booking, 'created'));
        }

        // Email the customer a booking-received confirmation (never let a mail failure break the booking)
        try {
            Mail::to($booking->email)->send(new BookingReceivedMail($booking));
        } catch (\Throwable $e) {
            Log::warning('Booking confirmation email failed to send.', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
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
                'receipt_url' => $booking->receipt_path ? asset('storage/'.$booking->receipt_path) : null,
                'qr_code' => $this->bookingQrDataUri($booking),
            ],
        ], 201);
    }

    /**
     * Get real-time booked time slots and unavailabilities for courts on a specific date.
     */
    public function availability(Request $request): JsonResponse
    {
        $date = $request->query('date', date('Y-m-d'));
        $courtId = $request->query('court_id');

        $query = Booking::query()
            ->where('date', $date)
            ->where('status', '!=', 'cancelled');

        if ($courtId) {
            $query->where('court_id', $courtId);
        }

        $bookings = $query->get(['court_id', 'time_slots']);

        $bookedSlots = [];
        foreach ($bookings as $b) {
            $cId = (string) $b->court_id;
            if (! isset($bookedSlots[$cId])) {
                $bookedSlots[$cId] = [];
            }
            $bookedSlots[$cId] = array_merge($bookedSlots[$cId], $b->time_slots ?? []);
        }

        // Also fetch staff court unavailabilities
        $unavailabilities = CourtUnavailability::query()
            ->where('date', $date)
            ->get(['court_id', 'slot_time']);

        foreach ($unavailabilities as $u) {
            $cId = (string) $u->court_id;
            if (! isset($bookedSlots[$cId])) {
                $bookedSlots[$cId] = [];
            }
            if ($u->slot_time) {
                $bookedSlots[$cId][] = $u->slot_time;
            }
        }

        // Unique array for each court
        foreach ($bookedSlots as $cId => $slots) {
            $bookedSlots[$cId] = array_values(array_unique($slots));
        }

        return response()->json([
            'date' => $date,
            'booked_slots' => $bookedSlots,
        ]);
    }

    /**
     * Update the client's own booking details.
     */
    public function update(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('update', $booking);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $booking->update($validated);

        return response()->json([
            'success' => true,
            'message' => __('Booking details updated successfully.'),
            'booking' => $booking,
        ]);
    }

    /**
     * Cancel/delete the client's own booking.
     */
    public function destroy(Booking $booking): JsonResponse
    {
        $this->authorize('delete', $booking);

        $booking->delete();

        return response()->json([
            'success' => true,
            'message' => __('Booking cancelled successfully.'),
        ]);
    }
}
