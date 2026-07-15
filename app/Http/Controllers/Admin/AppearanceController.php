<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SiteTheme;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAppearanceRequest;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppearanceController extends Controller
{
    /**
     * Show the theme picker.
     */
    public function index(): Response
    {
        return Inertia::render('admin/appearance/Index', [
            'themes' => array_map(fn (SiteTheme $theme): array => [
                'value' => $theme->value,
                'label' => $theme->label(),
                'description' => $theme->description(),
            ], SiteTheme::cases()),
            'activeTheme' => SiteSetting::get('active_theme', SiteTheme::default()->value),
            'siteName' => SiteSetting::get('site_name', config('site.name')),
            'logoUrl' => $this->logoUrl(),
            'contact' => [
                'email' => SiteSetting::get('contact_email', config('site.contact.email')),
                'phone' => SiteSetting::get('contact_phone', config('site.contact.phone')),
                'address' => SiteSetting::get('contact_address', config('site.contact.address_line')),
            ],
        ]);
    }

    /**
     * Persist the operator-selected active theme.
     */
    public function update(UpdateAppearanceRequest $request): RedirectResponse
    {
        SiteSetting::set('active_theme', $request->validated('theme'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Theme updated.')]);

        return back();
    }

    /**
     * Update site branding — display name and/or logo.
     */
    public function updateBranding(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_address' => ['nullable', 'string', 'max:255'],
        ]);

        SiteSetting::set('site_name', $validated['name']);
        SiteSetting::set('contact_email', $validated['contact_email'] ?? '');
        SiteSetting::set('contact_phone', $validated['contact_phone'] ?? '');
        SiteSetting::set('contact_address', $validated['contact_address'] ?? '');

        if ($request->hasFile('logo')) {
            SiteSetting::set('site_logo', $request->file('logo')->store('branding', 'public'));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Branding updated.')]);

        return back();
    }

    /**
     * Resolve the current site logo URL (uploaded, or the bundled default).
     */
    private function logoUrl(): string
    {
        $path = SiteSetting::get('site_logo');

        return $path ? asset('storage/'.$path) : asset('logo.jpg');
    }
}
