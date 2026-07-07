<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SiteTheme;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAppearanceRequest;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
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
}
