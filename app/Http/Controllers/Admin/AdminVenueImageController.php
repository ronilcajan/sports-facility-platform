<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Models\VenueImage;
use App\Support\ImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AdminVenueImageController extends Controller
{
    /**
     * Store a newly uploaded venue gallery photo.
     */
    public function store(Request $request, Venue $venue): RedirectResponse
    {
        $this->authorize('update', $venue);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $path = ImageStorage::photo($request->file('image'), 'venues');
        $isPrimary = $request->boolean('is_primary');

        if ($isPrimary) {
            $venue->images()->update(['is_primary' => false]);
        }

        $maxSortOrder = $venue->images()->max('sort_order') ?? 0;

        $venue->images()->create([
            'path' => $path,
            'is_primary' => $isPrimary || $venue->images()->count() === 0,
            'sort_order' => $maxSortOrder + 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Venue photo uploaded.')]);

        return back();
    }

    /**
     * Set an image as the venue's primary hero photo.
     */
    public function setPrimary(Venue $venue, VenueImage $image): RedirectResponse
    {
        $this->authorize('update', $venue);

        if ($image->venue_id !== $venue->id) {
            abort(404);
        }

        $venue->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Primary venue photo updated.')]);

        return back();
    }

    /**
     * Delete a venue gallery photo.
     */
    public function destroy(Venue $venue, VenueImage $image): RedirectResponse
    {
        $this->authorize('update', $venue);

        if ($image->venue_id !== $venue->id) {
            abort(404);
        }

        $wasPrimary = $image->is_primary;

        if (Storage::disk('public')->exists($image->path)) {
            Storage::disk('public')->delete($image->path);
        }

        $image->delete();

        // Promote another photo so the venue keeps a hero image where possible.
        if ($wasPrimary) {
            $firstRemaining = $venue->images()->first();
            if ($firstRemaining) {
                $firstRemaining->update(['is_primary' => true]);
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Venue photo removed.')]);

        return back();
    }
}
