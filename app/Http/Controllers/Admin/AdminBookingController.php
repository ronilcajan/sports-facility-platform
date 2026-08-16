<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Venue;
use App\Support\BookingCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminBookingController extends Controller
{
    /**
     * Display a paginated list of all system bookings.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Booking::class);

        $user = $request->user();
        $rawView = $request->input('view');
        $view = in_array($rawView, ['calendar', 'list', 'table']) ? $rawView : 'table';

        $query = Booking::query()->with(['court', 'user']);

        // Venue admins only see bookings on their own venue's courts.
        if ($user->isVenueScopedAdmin()) {
            $query->visibleTo($user);
        }

        // Super-admins may narrow to a single venue.
        if ($user->isSuperAdmin() && $request->filled('venue_id')) {
            $venueId = $request->input('venue_id');
            $query->whereHas('court', fn ($courtQuery) => $courtQuery->where('venue_id', $venueId));
        }

        if ($request->filled('court_id')) {
            $query->where('court_id', $request->input('court_id'));
        }

        $courts = Court::query()
            ->visibleTo($user)
            ->when($request->filled('venue_id'), fn ($courtQuery) => $courtQuery->where('venue_id', $request->input('venue_id')))
            ->orderBy('name')
            ->get(['id', 'name', 'sport_type', 'base_price']);

        $venues = $user->isSuperAdmin()
            ? Venue::orderBy('name')->get(['id', 'name'])
            : null;

        $shared = [
            'courts' => $courts,
            'venues' => $venues,
            'basePath' => '/admin/bookings',
            'canDelete' => true,
            'showVenueFilter' => $user->isSuperAdmin(),
        ];

        if ($view === 'calendar') {
            // Default board hides rejected/cancelled unless a status is chosen.
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            } else {
                $query->whereNotIn('status', ['rejected', 'cancelled']);
            }

            $start = BookingCalendar::resolveStart($request->input('start'));

            return Inertia::render('admin/bookings/Index', [
                ...$shared,
                'view' => 'calendar',
                'days' => BookingCalendar::build($query, $start),
                'window' => BookingCalendar::window($start),
                'filters' => $request->only(['court_id', 'status', 'venue_id']),
            ]);
        }

        if ($view === 'table') {
            $startDate = $request->filled('date')
                ? Carbon::parse($request->input('date'))
                : Carbon::today();

            $endDate = $startDate->copy()->addDays(6);

            $tableDates = [];
            $curr = $startDate->copy();
            for ($i = 0; $i < 7; $i++) {
                $tableDates[] = [
                    'dateStr' => $curr->toDateString(),
                    'dayName' => $curr->format('D'),
                    'dayNum' => $curr->format('j'),
                    'monthName' => $curr->format('M'),
                    'formatted' => $curr->format('D, M j'),
                    'isToday' => $curr->isToday(),
                ];
                $curr->addDay();
            }

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            } else {
                $query->whereNotIn('status', ['cancelled']);
            }

            $query->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]);

            $tableBookings = $query->get()->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'reference_code' => 'DY-RESRV-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT),
                'name' => $booking->name,
                'email' => $booking->email,
                'phone' => $booking->phone,
                'date' => $booking->date->toDateString(),
                'time_slots' => $booking->time_slots,
                'total_price' => number_format((float) $booking->total_price, 2, '.', ''),
                'receipt_url' => $booking->receipt_url,
                'status' => $booking->status,
                'notes' => $booking->notes,
                'court' => $booking->court ? [
                    'id' => $booking->court->id,
                    'name' => $booking->court->name,
                    'sport_type' => $booking->court->sport_type?->label() ?? $booking->court->sport_type,
                ] : null,
            ]);

            return Inertia::render('admin/bookings/Index', [
                ...$shared,
                'view' => 'table',
                'tableDates' => $tableDates,
                'tableBookings' => $tableBookings,
                'filters' => $request->only(['search', 'court_id', 'status', 'date', 'venue_id']),
            ]);
        }

        // List view (paginated table).
        $query->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('id', $search);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date')) {
            $query->where('date', $request->input('date'));
        }

        return Inertia::render('admin/bookings/Index', [
            ...$shared,
            'view' => 'list',
            'bookings' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'court_id', 'status', 'date', 'venue_id']),
        ]);
    }

    /**
     * Create a booking manually (walk-in / phone reservation).
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Booking::class);

        $user = $request->user();

        $validated = $request->validate([
            'court_id' => ['required', 'exists:courts,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'date' => ['required', 'date'],
            'time_slots' => ['required', 'array', 'min:1'],
            'time_slots.*' => ['string'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Scope the court to what the user may manage (venue admins → own venue).
        $court = Court::visibleTo($user)->findOrFail($validated['court_id']);

        Booking::create([
            'court_id' => $court->id,
            'user_id' => null,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'date' => $validated['date'],
            'time_slots' => $validated['time_slots'],
            'notes' => $validated['notes'] ?? null,
            'total_price' => collect($validated['time_slots'])->sum(fn (string $slot) => $court->getSlotPrice($slot)),
            'status' => 'approved',
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Booking created successfully.'),
        ]);

        return back();
    }

    /**
     * Update an existing booking's date, time slots, court, customer info, or status.
     */
    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('update', $booking);

        $user = $request->user();

        $validated = $request->validate([
            'court_id' => ['sometimes', 'required', 'exists:courts,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'date' => ['sometimes', 'required', 'date'],
            'time_slots' => ['sometimes', 'required', 'array', 'min:1'],
            'time_slots.*' => ['required', 'string'],
            'status' => ['sometimes', 'required', 'string', Rule::in(['pending', 'approved', 'confirmed', 'rejected', 'cancelled', 'completed'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $courtId = $validated['court_id'] ?? $booking->court_id;
        $date = $validated['date'] ?? $booking->date->toDateString();
        $requestedSlots = $validated['time_slots'] ?? $booking->time_slots;

        // Scope check for venue admins
        $court = Court::visibleTo($user)->findOrFail($courtId);

        // Check for slot conflicts if court, date, or time_slots are being modified
        if (isset($validated['court_id']) || isset($validated['date']) || isset($validated['time_slots'])) {
            $conflictingBookings = Booking::query()
                ->where('court_id', $courtId)
                ->where('date', $date)
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->where('id', '!=', $booking->id)
                ->get();

            foreach ($conflictingBookings as $existing) {
                $bookedSlots = $existing->time_slots ?? [];
                foreach ($requestedSlots as $slot) {
                    if (in_array($slot, $bookedSlots)) {
                        return back()->withErrors([
                            'time_slots' => "The time slot '{$slot}' is already booked on this court for the selected date.",
                        ]);
                    }
                }
            }
        }

        $totalPrice = collect($requestedSlots)->sum(fn (string $slot) => $court->getSlotPrice($slot));

        $booking->update([
            'court_id' => $courtId,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'date' => $date,
            'time_slots' => $requestedSlots,
            'status' => $validated['status'] ?? $booking->status,
            'notes' => $validated['notes'] ?? null,
            'total_price' => $totalPrice,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Booking details updated successfully.'),
        ]);

        return back();
    }

    /**
     * Display details of a specific booking.
     */
    public function show(Booking $booking): Response
    {
        $this->authorize('view', $booking);

        $booking->load(['court', 'user']);

        return Inertia::render('admin/bookings/Show', [
            'booking' => $booking,
        ]);
    }

    /**
     * Update status of a booking (approve, reject, cancel, complete).
     */
    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('update', $booking);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(['pending', 'approved', 'confirmed', 'rejected', 'cancelled', 'completed'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $booking->update([
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? $booking->notes,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __("Booking status updated to {$validated['status']}."),
        ]);

        return back();
    }

    /**
     * Delete a booking entry.
     */
    public function destroy(Booking $booking): RedirectResponse
    {
        $this->authorize('delete', $booking);

        $booking->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Booking deleted.'),
        ]);

        return to_route('admin.bookings.index');
    }
}
