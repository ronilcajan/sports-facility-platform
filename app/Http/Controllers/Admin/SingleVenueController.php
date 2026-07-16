<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SingleVenueController extends Controller
{
    /**
     * Show the settings form for the admin's own assigned venue.
     */
    public function edit(Request $request): Response
    {
        $venue = $this->resolveVenue($request);

        $this->authorize('update', $venue);

        return Inertia::render('admin/settings/Index', [
            'venue' => [
                'id' => $venue->id,
                'name' => $venue->name,
                'slug' => $venue->slug,
                'description' => $venue->description,
                'address' => $venue->address,
                'phone' => $venue->phone,
                'email' => $venue->email,
                'gcash_number' => $venue->gcash_number,
                'gcash_qr_url' => $venue->paymentMethods()['gcash']['qr_url'] ?? null,
                'maya_number' => $venue->maya_number,
                'maya_qr_url' => $venue->paymentMethods()['maya']['qr_url'] ?? null,
                'is_active' => $venue->is_active,
            ],
        ]);
    }

    /**
     * Update the admin's own assigned venue.
     */
    public function update(Request $request): RedirectResponse
    {
        $venue = $this->resolveVenue($request);

        $this->authorize('update', $venue);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'gcash_number' => ['nullable', 'string', 'max:50'],
            'gcash_qr' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'maya_number' => ['nullable', 'string', 'max:50'],
            'maya_qr' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'is_active' => ['boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        if ($request->hasFile('gcash_qr')) {
            $validated['gcash_qr_path'] = $request->file('gcash_qr')->store('venue-qr', 'public');
        }

        if ($request->hasFile('maya_qr')) {
            $validated['maya_qr_path'] = $request->file('maya_qr')->store('venue-qr', 'public');
        }

        unset($validated['gcash_qr'], $validated['maya_qr']);

        $venue->update($validated);

        return redirect()->route('admin.settings.edit')
            ->with('success', 'Venue settings updated successfully.');
    }

    /**
     * Resolve the venue the current admin is assigned to, 404-ing when the
     * user has no venue (e.g. a super-admin or an unassigned admin).
     */
    private function resolveVenue(Request $request): Venue
    {
        /** @var User $user */
        $user = $request->user();

        $venue = $user->venue;

        abort_if($venue === null, 404, 'No venue is assigned to your account.');

        return $venue;
    }
}
