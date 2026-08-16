<?php

namespace App\Http\Middleware;

use App\Enums\DeviceType;
use App\Enums\TrafficSource;
use App\Models\PageView;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TrackPageView
{
    /**
     * Path prefixes that are operator surfaces or machine endpoints rather than
     * public marketing pages.
     *
     * @var array<int, string>
     */
    private const IGNORED_PREFIXES = [
        'admin', 'staff', 'api', 'build', 'storage', 'login', 'register',
        'password', 'forgot-password', 'reset-password', 'verify-email',
        'confirm-password', 'two-factor-challenge', 'user', 'settings',
        'up', 'telescope', 'horizon', '_debugbar', '_ignition',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Record the view after the response has been sent, so tracking never adds
     * latency to the page the visitor is waiting for.
     */
    public function terminate(Request $request, Response $response): void
    {
        if (! $this->shouldTrack($request, $response)) {
            return;
        }

        try {
            PageView::query()->create([
                'visitor_id' => $this->visitorId($request),
                'session_id' => $this->sessionId($request),
                'path' => '/'.ltrim($request->path(), '/'),
                'route_name' => $request->route()?->getName(),
                'referrer_host' => $this->referrerHost($request),
                'source' => TrafficSource::fromReferrer($request->headers->get('referer'), $request->getHost()),
                'device' => DeviceType::fromUserAgent($request->userAgent()),
                'viewed_at' => now(),
            ]);
        } catch (Throwable $e) {
            // Analytics must never break a page that already rendered fine.
            Log::warning('Failed to record page view.', ['exception' => $e->getMessage()]);
        }
    }

    /**
     * Only successful GET requests for public pages by real, non-operator
     * visitors are counted.
     */
    private function shouldTrack(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200) {
            return false;
        }

        if ($this->isIgnoredPath($request) || $this->isBot($request) || $this->isOperator($request)) {
            return false;
        }

        return $this->isPageResponse($response);
    }

    /**
     * A page is either a full HTML document or an Inertia navigation. Plain
     * JSON endpoints (availability lookups, for example) are data, not pages.
     */
    private function isPageResponse(Response $response): bool
    {
        if ($response->headers->has('X-Inertia')) {
            return true;
        }

        return str_contains((string) $response->headers->get('Content-Type'), 'text/html');
    }

    private function isIgnoredPath(Request $request): bool
    {
        $first = strtolower(explode('/', trim($request->path(), '/'))[0]);

        return in_array($first, self::IGNORED_PREFIXES, true);
    }

    private function isBot(Request $request): bool
    {
        $agent = strtolower((string) $request->userAgent());

        if ($agent === '') {
            return true;
        }

        return preg_match('/bot|crawl|spider|slurp|curl|wget|python-requests|headless|lighthouse|pingdom|uptime|monitor|preview|facebookexternalhit|semrush|ahrefs/i', $agent) === 1;
    }

    /**
     * Staff and admins browsing the public site are not website visitors.
     */
    private function isOperator(Request $request): bool
    {
        $user = $request->user();

        return $user instanceof User && ! $user->hasRole('customer');
    }

    /**
     * Daily-rotating, salted hash of IP + user-agent.
     *
     * Rotating on the date means the raw IP is never stored and the identifier
     * cannot be correlated across days, while still de-duplicating a visitor
     * within the day.
     */
    private function visitorId(Request $request): string
    {
        return hash('sha256', implode('|', [
            $request->ip(),
            $request->userAgent(),
            now()->toDateString(),
            config('app.key'),
        ]));
    }

    /**
     * Reuse the framework session where one exists so consecutive views stitch
     * into a single session; otherwise fall back to the daily visitor id.
     */
    private function sessionId(Request $request): string
    {
        if ($request->hasSession()) {
            return hash('sha256', $request->session()->getId());
        }

        return $this->visitorId($request);
    }

    private function referrerHost(Request $request): ?string
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);

        return is_string($host) && $host !== '' ? strtolower($host) : null;
    }
}
