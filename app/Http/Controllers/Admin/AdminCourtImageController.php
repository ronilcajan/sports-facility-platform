<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Models\CourtImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AdminCourtImageController extends Controller
{
    /**
     * Store newly uploaded court image(s).
     */
    public function store(Request $request, Court $court): RedirectResponse
    {
        $this->authorize('update', $court);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $path = $request->file('image')->store('courts', 'public');
        $isPrimary = $request->boolean('is_primary');

        if ($isPrimary) {
            $court->images()->update(['is_primary' => false]);
        }

        $maxSortOrder = $court->images()->max('sort_order') ?? 0;

        $court->images()->create([
            'path' => $path,
            'is_primary' => $isPrimary || $court->images()->count() === 0,
            'sort_order' => $maxSortOrder + 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Court image uploaded.')]);

        return back();
    }

    /**
     * Set image as primary hero image for the court.
     */
    public function setPrimary(Court $court, CourtImage $image): RedirectResponse
    {
        $this->authorize('update', $court);

        if ($image->court_id !== $court->id) {
            abort(404);
        }

        $court->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Primary hero image updated.')]);

        return back();
    }

    /**
     * Delete court image.
     */
    public function destroy(Court $court, CourtImage $image): RedirectResponse
    {
        $this->authorize('update', $court);

        if ($image->court_id !== $court->id) {
            abort(404);
        }

        $wasPrimary = $image->is_primary;

        if (Storage::disk('public')->exists($image->path)) {
            Storage::disk('public')->delete($image->path);
        }

        $image->delete();

        // If primary was deleted, set another image as primary if available
        if ($wasPrimary) {
            $firstRemaining = $court->images()->first();
            if ($firstRemaining) {
                $firstRemaining->update(['is_primary' => true]);
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Court image removed.')]);

        return back();
    }
}
