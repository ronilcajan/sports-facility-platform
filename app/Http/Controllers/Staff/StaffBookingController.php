<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Court;
use App\Support\BookingCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StaffBookingController extends Controller
{
    /**
     * Display a list of bookings for the court staff member's assigned courts.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $this->authorize('viewAny', Booking::class);

        $view = $request->input('view') === 'list' ? 'list' : 'calendar';

        $courts = Court::visibleTo($user)->orderBy('name')->get(['id', 'name', 'sport_type', 'base_price']);

        $query = Booking::visibleTo($user)->with(['court', 'user']);

        if ($request->filled('court_id')) {
            $query->where('court_id', $request->input('court_id'));
        }

        $shared = [
            'courts' => $courts,
            'assignedCourts' => $courts,
            'venues' => null,
            'basePath' => '/staff/bookings',
            'canDelete' => false,
            'showVenueFilter' => false,
        ];

        if ($view === 'calendar') {
            // The board shows every status so staff can see what was rejected or
            // cancelled; those bookings no longer hold their slot, so the hours
            // remain bookable regardless of what is displayed here.
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            $start = BookingCalendar::resolveStart($request->input('start'));

            return Inertia::render('staff/bookings/Index', [
                ...$shared,
                'view' => 'calendar',
                'days' => BookingCalendar::build($query, $start),
                'window' => BookingCalendar::window($start),
                'filters' => $request->only(['court_id', 'status']),
            ]);
        }

        // List view (paginated table).
        $query->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date')) {
            $query->where('date', $request->input('date'));
        }

        return Inertia::render('staff/bookings/Index', [
            ...$shared,
            'view' => 'list',
            'bookings' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'court_id', 'status', 'date']),
        ]);
    }

    /**
     * Create a new booking for an assigned court (CR Bookings for Staff).
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $this->authorize('create', Booking::class);

        $validated = $request->validate([
            'court_id' => ['required', 'exists:courts,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time_slots' => ['required', 'array', 'min:1'],
            'time_slots.*' => ['string'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $court = Court::visibleTo($user)->findOrFail($validated['court_id']);

        if (! isset($validated['total_price']) || empty($validated['total_price'])) {
            $validated['total_price'] = collect($validated['time_slots'])->sum(fn (string $slot) => $court->getSlotPrice($slot));
        }

        Booking::create([
            'court_id' => $court->id,
            'user_id' => null,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'date' => $validated['date'],
            'time_slots' => $validated['time_slots'],
            'notes' => $validated['notes'] ?? null,
            'total_price' => $validated['total_price'],
            'status' => 'approved',
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Booking created successfully.'),
        ]);

        return back();
    }

    /**
     * Display details of a booking for an assigned court.
     */
    public function show(Request $request, Booking $booking): Response
    {
        $this->authorize('view', $booking);

        $booking->load(['court', 'user']);

        return Inertia::render('staff/bookings/Show', [
            'booking' => $booking,
        ]);
    }

    /**
     * Approve, reject, or update booking request status for assigned court.
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
}
