<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminVenueController extends Controller
{
    /**
     * Display a listing of venues.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Venue::class);

        $query = Venue::withCount('courts')
            ->with('courts.primaryImage')
            ->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $venues = $query->paginate(15)->through(fn (Venue $venue) => [
            'id' => $venue->id,
            'name' => $venue->name,
            'slug' => $venue->slug,
            'address' => $venue->address,
            'phone' => $venue->phone,
            'email' => $venue->email,
            'is_active' => $venue->is_active,
            'courts_count' => $venue->courts_count,
            'image_url' => $venue->image_url,
            'cover_image_url' => $venue->image_url,
            'created_at' => $venue->created_at?->toFormattedDateString(),
        ])->withQueryString();

        return Inertia::render('admin/venues/Index', [
            'venues' => $venues,
            'filters' => $request->only(['search']),
            'canDelete' => $request->user()?->isSuperAdmin() ?? false,
            'canManageVenueImages' => true,
        ]);
    }

    /**
     * Display a dedicated venue profile page with details, courts, and reservations.
     */
    public function show(Request $request, Venue $venue): Response
    {
        $this->authorize('view', $venue);

        $venue->load([
            'courts' => function ($q) {
                $q->with(['primaryImage'])->orderBy('name');
            },
        ]);

        $courts = $venue->courts->map(fn (Court $court) => [
            'id' => $court->id,
            'name' => $court->name,
            'slug' => $court->slug,
            'sport_type' => $court->sport_type?->label() ?? $court->sport_type,
            'description' => $court->description,
            'base_price' => (string) $court->base_price,
            'slot_duration_minutes' => $court->slot_duration_minutes,
            'is_active' => $court->is_active,
            'status' => $court->status?->value ?? $court->status,
            'primary_image_url' => $court->primaryImage ? (str_starts_with($court->primaryImage->path, 'http') ? $court->primaryImage->path : asset('storage/'.$court->primaryImage->path)) : null,
        ]);

        $query = Booking::query()
            ->whereHas('court', fn ($q) => $q->where('venue_id', $venue->id))
            ->with(['court', 'user'])
            ->latest();

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

        $bookings = $query->paginate(15)->through(fn (Booking $booking) => [
            'id' => $booking->id,
            'reference_code' => 'DY-RESRV-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT),
            'court_name' => $booking->court?->name ?? 'Deleted Court',
            'sport_type' => $booking->court?->sport_type?->label() ?? $booking->court?->sport_type,
            'name' => $booking->name,
            'email' => $booking->email,
            'phone' => $booking->phone,
            'date' => $booking->date->toDateString(),
            'time_slots' => $booking->time_slots,
            'total_price' => number_format((float) $booking->total_price, 2, '.', ''),
            'receipt_url' => $booking->receipt_url,
            'status' => $booking->status,
            'notes' => $booking->notes,
            'created_at' => $booking->created_at?->toFormattedDateString(),
        ])->withQueryString();

        return Inertia::render('admin/venues/Show', [
            'venue' => [
                'id' => $venue->id,
                'name' => $venue->name,
                'slug' => $venue->slug,
                'description' => $venue->description,
                'address' => $venue->address,
                'phone' => $venue->phone,
                'email' => $venue->email,
                'image_url' => $venue->image_url,
                'cover_image_url' => $venue->image_url,
                'gcash_number' => $venue->gcash_number,
                'gcash_qr_url' => $venue->paymentMethods()['gcash']['qr_url'] ?? null,
                'maya_number' => $venue->maya_number,
                'maya_qr_url' => $venue->paymentMethods()['maya']['qr_url'] ?? null,
                'is_active' => $venue->is_active,
                'courts_count' => $courts->count(),
                'created_at' => $venue->created_at?->toFormattedDateString(),
            ],
            'courts' => $courts,
            'bookings' => $bookings,
            'filters' => $request->only(['search', 'status']),
            'canDelete' => $request->user()?->isSuperAdmin() ?? false,
            'canManageVenue' => $request->user()?->isSuperAdmin() ?? false,
        ]);
    }

    /**
     * Show the form for creating a new venue.
     */
    public function create(): Response
    {
        $this->authorize('create', Venue::class);

        return Inertia::render('admin/venues/Create');
    }

    /**
     * Store a newly created venue.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Venue::class);

        $validated = $request->validate($this->rules());

        $validated['slug'] = Str::slug($validated['name']);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('venues', 'public');
        }

        if ($request->hasFile('gcash_qr')) {
            $validated['gcash_qr_path'] = $request->file('gcash_qr')->store('venue-qr', 'public');
        }

        if ($request->hasFile('maya_qr')) {
            $validated['maya_qr_path'] = $request->file('maya_qr')->store('venue-qr', 'public');
        }

        unset($validated['image'], $validated['gcash_qr'], $validated['maya_qr']);

        Venue::create($validated);

        return redirect()->route('admin.venues.index')
            ->with('success', 'Venue created successfully.');
    }

    /**
     * Show the form for editing the specified venue.
     */
    public function edit(Venue $venue): Response
    {
        $this->authorize('update', $venue);

        return Inertia::render('admin/venues/Edit', [
            'venue' => [
                'id' => $venue->id,
                'name' => $venue->name,
                'slug' => $venue->slug,
                'description' => $venue->description,
                'address' => $venue->address,
                'phone' => $venue->phone,
                'email' => $venue->email,
                'image_url' => $venue->image_url,
                'gcash_number' => $venue->gcash_number,
                'gcash_qr_url' => $venue->paymentMethods()['gcash']['qr_url'] ?? null,
                'maya_number' => $venue->maya_number,
                'maya_qr_url' => $venue->paymentMethods()['maya']['qr_url'] ?? null,
                'is_active' => $venue->is_active,
            ],
            'canManageVenueImages' => true,
        ]);
    }

    /**
     * Update the specified venue.
     */
    public function update(Request $request, Venue $venue): RedirectResponse
    {
        $this->authorize('update', $venue);

        $validated = $request->validate($this->rules());

        $validated['slug'] = Str::slug($validated['name']);
        $isDeleteImage = $request->boolean('delete_image') || in_array($request->input('delete_image'), [true, 'true', 1, '1', 'on'], true);

        if ($request->hasFile('image')) {
            if ($venue->image_path && Storage::disk('public')->exists($venue->image_path)) {
                Storage::disk('public')->delete($venue->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('venues', 'public');
        } elseif ($isDeleteImage) {
            if ($venue->image_path && Storage::disk('public')->exists($venue->image_path)) {
                Storage::disk('public')->delete($venue->image_path);
            }
            $validated['image_path'] = null;
            $venue->forceFill(['image_path' => null])->save();
        }

        if ($request->hasFile('gcash_qr')) {
            $validated['gcash_qr_path'] = $request->file('gcash_qr')->store('venue-qr', 'public');
        }

        if ($request->hasFile('maya_qr')) {
            $validated['maya_qr_path'] = $request->file('maya_qr')->store('venue-qr', 'public');
        }

        unset($validated['image'], $validated['delete_image'], $validated['gcash_qr'], $validated['maya_qr']);

        $venue->update($validated);

        return redirect()->route('admin.venues.index')
            ->with('success', 'Venue updated successfully.');
    }

    /**
     * Remove cover photo for the specified venue.
     */
    public function destroyImage(Venue $venue): RedirectResponse
    {
        $this->authorize('update', $venue);

        if ($venue->image_path && Storage::disk('public')->exists($venue->image_path)) {
            Storage::disk('public')->delete($venue->image_path);
        }

        $venue->forceFill(['image_path' => null])->save();

        return back()->with('success', 'Venue cover photo removed successfully.');
    }

    /**
     * Shared validation rules for storing/updating a venue.
     *
     * @return array<string, array<int, string>>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'delete_image' => ['nullable', 'boolean'],
            'gcash_number' => ['nullable', 'string', 'max:50'],
            'gcash_qr' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'maya_number' => ['nullable', 'string', 'max:50'],
            'maya_qr' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Remove the specified venue.
     */
    public function destroy(Venue $venue): RedirectResponse
    {
        $this->authorize('delete', $venue);

        $venue->delete();

        return redirect()->route('admin.venues.index')
            ->with('success', 'Venue deleted successfully.');
    }
}
