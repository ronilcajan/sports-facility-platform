<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreBookingRequest;
use App\Mail\BookingReceivedMail;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtUnavailability;
use App\Notifications\BookingStatusNotification;
use App\Support\ImageStorage;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

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
        $court = Court::findOrFail((int) $request->validated('court_id'));

        // Save receipt to public storage disk (optional — booking is valid without it)
        $receiptPath = $request->hasFile('receipt')
            ? ImageStorage::receipt($request->file('receipt'), 'receipts')
            : null;

        $requestedSlots = $request->validated('time');
        $date = $request->validated('date');

        $attributes = [
            'court_id' => $court->id,
            'user_id' => auth()->id(),
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'date' => $date,
            'time_slots' => $requestedSlots,
            'notes' => $request->validated('notes'),
            // Total price is driven by the court's dynamic per-slot pricing.
            'total_price' => collect($requestedSlots)->sum(fn (string $slot) => $court->getSlotPrice($slot)),
            'receipt_path' => $receiptPath,
            'transaction_code' => $request->validated('transaction_code'),
            'status' => 'pending',
        ];

        // The form request already rejected taken slots, but that check and this
        // insert are not atomic — two concurrent requests can both pass it. Hold
        // a per-court/per-day lock and re-verify before committing.
        $booking = Cache::lock("booking:{$court->id}:{$date}", 10)->block(5, function () use ($court, $date, $requestedSlots, $attributes): Booking {
            $this->assertSlotsAreFree($court->id, $date, $requestedSlots);

            return Booking::create($attributes);
        });

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
                'date' => $booking->date->toDateString(),
                'time_slots' => $booking->time_slots,
                'total_price' => number_format((float) $booking->total_price, 2, '.', ''),
                'receipt_url' => $booking->receipt_path ? asset('storage/'.$booking->receipt_path) : null,
                'qr_code' => $this->bookingQrDataUri($booking),
            ],
        ], 201);
    }

    /**
     * Abort with a validation error when any requested slot is already taken.
     *
     * @param  array<int, string>  $requestedSlots
     */
    private function assertSlotsAreFree(int $courtId, string $date, array $requestedSlots): void
    {
        $takenSlots = Booking::query()
            ->where('court_id', $courtId)
            ->where('date', $date)
            ->holdingSlots()
            ->pluck('time_slots')
            ->flatten()
            ->all();

        foreach ($requestedSlots as $slot) {
            if (in_array($slot, $takenSlots)) {
                throw ValidationException::withMessages([
                    'time' => "The slot '{$slot}' is already booked.",
                ]);
            }
        }
    }

    /**
     * Get real-time booked time slots and unavailabilities for courts on a specific date.
     */
    public function availability(Request $request): JsonResponse
    {
        $date = $request->query('date', date('Y-m-d'));
        $courtId = $request->query('court_id');
        $excludeBookingId = $request->query('exclude_booking_id');

        $query = Booking::query()
            ->where('date', $date)
            ->holdingSlots();

        if ($courtId) {
            $query->where('court_id', $courtId);
        }

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
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

        // Also fetch staff court unavailabilities. Slot-level blackouts map to a
        // single start_time; all-day blackouts have no slot to pin them to.
        $unavailabilities = CourtUnavailability::query()
            ->where('date', $date)
            ->when($courtId, fn ($unavailabilityQuery) => $unavailabilityQuery->where('court_id', $courtId))
            ->get(['court_id', 'start_time', 'all_day']);

        foreach ($unavailabilities as $unavailability) {
            $courtKey = (string) $unavailability->court_id;
            $bookedSlots[$courtKey] ??= [];

            if (! $unavailability->all_day && $unavailability->start_time) {
                $bookedSlots[$courtKey][] = $unavailability->start_time;
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
    public function update(Request $request, Booking $booking): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $booking);

        $validated = $request->validate([
            'court_id' => ['sometimes', 'required', 'exists:courts,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'date' => ['sometimes', 'required', 'date'],
            'time' => ['sometimes', 'required', 'array', 'min:1'],
            'time.*' => ['required', 'string'],
            'time_slots' => ['sometimes', 'required', 'array', 'min:1'],
            'time_slots.*' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $courtId = $validated['court_id'] ?? $booking->court_id;
        $date = $validated['date'] ?? $booking->date->toDateString();
        $requestedSlots = $validated['time'] ?? $validated['time_slots'] ?? $booking->time_slots;

        // Double-booking check if court, date, or time slots are being modified
        if (isset($validated['court_id']) || isset($validated['date']) || isset($validated['time']) || isset($validated['time_slots'])) {
            $conflictingBookings = Booking::query()
                ->where('court_id', $courtId)
                ->where('date', $date)
                ->holdingSlots()
                ->where('id', '!=', $booking->id)
                ->get();

            foreach ($conflictingBookings as $existing) {
                $bookedSlots = $existing->time_slots ?? [];
                foreach ($requestedSlots as $slot) {
                    if (in_array($slot, $bookedSlots)) {
                        if ($request->header('X-Inertia')) {
                            return back()->withErrors(['time' => "The time slot '{$slot}' is already booked on this court for the selected date."]);
                        }

                        return response()->json([
                            'message' => "The time slot '{$slot}' is already booked on this court for the selected date.",
                            'errors' => ['time' => ["The time slot '{$slot}' is already booked."]],
                        ], 422);
                    }
                }
            }
        }

        $court = Court::findOrFail($courtId);
        $totalPrice = collect($requestedSlots)->sum(fn (string $slot) => $court->getSlotPrice($slot));

        $updateData = [
            'court_id' => $courtId,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'date' => $date,
            'time_slots' => $requestedSlots,
            'notes' => $validated['notes'] ?? null,
            'total_price' => $totalPrice,
        ];

        $booking->update($updateData);

        if ($request->header('X-Inertia')) {
            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('Booking details updated successfully.'),
            ]);

            return back();
        }

        return response()->json([
            'success' => true,
            'message' => __('Booking details updated successfully.'),
            'booking' => $booking->fresh(['court']),
        ]);
    }

    /**
     * Cancel/delete the client's own booking.
     */
    public function destroy(Request $request, Booking $booking): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $booking);

        $booking->delete();

        if ($request->header('X-Inertia')) {
            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('Booking cancelled successfully.'),
            ]);

            return back();
        }

        return response()->json([
            'success' => true,
            'message' => __('Booking cancelled successfully.'),
        ]);
    }
}
