<?php

namespace App\Http\Middleware;

use App\Enums\SiteTheme;
use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleSiteTheme
{
    /**
     * Share the operator-selected marketing theme with the root view so it can
     * be applied to <html data-theme> before first paint.
     */
    public function handle(Request $request, Closure $next): Response
    {
        View::share('siteTheme', SiteSetting::get('active_theme', SiteTheme::default()->value));

        return $next($request);
    }
}
