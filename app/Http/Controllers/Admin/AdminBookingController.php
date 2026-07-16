<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Venue;
use App\Support\BookingCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $view = $request->input('view') === 'list' ? 'list' : 'calendar';

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
            ->get(['id', 'name', 'sport_type']);

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
