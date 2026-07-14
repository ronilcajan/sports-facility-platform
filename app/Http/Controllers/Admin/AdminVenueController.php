<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'created_at' => $venue->created_at?->toFormattedDateString(),
        ])->withQueryString();

        return Inertia::render('admin/venues/Index', [
            'venues' => $venues,
            'filters' => $request->only(['search']),
            'canDelete' => $request->user()?->isSuperAdmin() ?? false,
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

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);

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
                'is_active' => $venue->is_active,
            ],
        ]);
    }

    /**
     * Update the specified venue.
     */
    public function update(Request $request, Venue $venue): RedirectResponse
    {
        $this->authorize('update', $venue);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $venue->update($validated);

        return redirect()->route('admin.venues.index')
            ->with('success', 'Venue updated successfully.');
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
