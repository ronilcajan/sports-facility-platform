<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Models\CourtImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StaffCourtImageController extends Controller
{
    /**
     * Store newly uploaded court image for assigned court.
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
     * Set image as primary hero image for assigned court.
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
     * Deleting court images is forbidden for Staff.
     */
    public function destroy(Court $court, CourtImage $image): RedirectResponse
    {
        abort(403, 'Staff members are not permitted to delete court images.');
    }
}
