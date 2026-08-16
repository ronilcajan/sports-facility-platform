<?php

namespace App\Http\Middleware;

use App\Enums\RoleName;
use App\Enums\SiteTheme;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                    'is_super_admin' => $user->hasRole(RoleName::SuperAdmin->value),
                    'is_admin' => $user->hasRole(RoleName::Admin->value),
                    'can_manage_all_courts' => $user->canManageAllCourts(),
                    'is_staff' => $user->isStaff(),
                    // Only staff navigation consumes this; skip the query otherwise.
                    'assigned_courts' => $user->isStaff()
                        ? $user->assignedCourts()->get(['courts.id', 'courts.name', 'courts.slug'])
                        : [],
                ] : null,
            ],
            'site' => $this->siteData(),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * Public-site identity, navigation, and contact details shared with every
     * page so the marketing header, footer, and metadata stay consistent.
     *
     * @return array<string, mixed>
     */
    protected function siteData(): array
    {
        $resolveLinks = fn (array $links): array => array_map(fn (array $link): array => [
            'label' => $link['label'],
            'href' => route($link['route']),
        ], $links);

        return [
            'name' => SiteSetting::get('site_name', config('site.name')),
            'tagline' => config('site.tagline'),
            'description' => config('site.description'),
            'contact' => [
                'email' => SiteSetting::get('contact_email', config('site.contact.email')),
                'phone' => SiteSetting::get('contact_phone', config('site.contact.phone')),
                'address_line' => SiteSetting::get('contact_address', config('site.contact.address_line')),
                'maps_query' => SiteSetting::get('contact_address', config('site.contact.maps_query')),
            ],
            'hours' => config('site.hours'),
            'social' => config('site.social'),
            'nav' => $resolveLinks(config('site.nav')),
            'legal' => $resolveLinks(config('site.legal')),
            'activeTheme' => SiteSetting::get('active_theme', SiteTheme::default()->value),
            'logo' => $this->logoUrl(),
        ];
    }

    /**
     * Resolve the current site logo URL (superadmin-uploaded, or the bundled default).
     */
    protected function logoUrl(): string
    {
        $path = SiteSetting::get('site_logo');

        return $path ? asset('storage/'.$path) : asset('logo.jpg');
    }
}
