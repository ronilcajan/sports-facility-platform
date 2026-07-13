<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Court;
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

        $query = Booking::with(['court', 'user'])->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('id', $search);
            });
        }

        if ($request->filled('court_id')) {
            $query->where('court_id', $request->input('court_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date')) {
            $query->where('date', $request->input('date'));
        }

        $bookings = $query->paginate(15)->withQueryString();

        $courts = Court::select(['id', 'name', 'sport_type'])->get();

        return Inertia::render('admin/bookings/Index', [
            'bookings' => $bookings,
            'courts' => $courts,
            'filters' => $request->only(['search', 'court_id', 'status', 'date']),
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
