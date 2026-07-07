<?php

namespace App\Http\Middleware;

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
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
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
            'name' => config('site.name'),
            'tagline' => config('site.tagline'),
            'description' => config('site.description'),
            'contact' => config('site.contact'),
            'hours' => config('site.hours'),
            'social' => config('site.social'),
            'nav' => $resolveLinks(config('site.nav')),
            'legal' => $resolveLinks(config('site.legal')),
            'activeTheme' => SiteSetting::get('active_theme', SiteTheme::default()->value),
        ];
    }
}
