<?php

namespace App\Support;

use App\Enums\DeviceType;
use App\Enums\TrafficSource;
use App\Models\PageView;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Builds the website traffic payload for the admin dashboard from recorded
 * page views. Every figure is derived from the page_views table — nothing here
 * is estimated or extrapolated.
 */
class TrafficAnalytics
{
    public const WINDOW_DAYS = 30;

    private const TOP_PAGES_LIMIT = 6;

    /**
     * Friendly names for the public routes, keyed by route name.
     *
     * @var array<string, array{name: string, category: string}>
     */
    private const PAGE_LABELS = [
        'home' => ['name' => 'Home / Facilities Directory', 'category' => 'Main Directory'],
        'site.courts' => ['name' => 'Courts Listing', 'category' => 'Directory'],
        'site.courts.show' => ['name' => 'Court Profile & Hourly Rates', 'category' => 'Court Detail'],
        'site.venues.show' => ['name' => 'Venue Profile & Courts', 'category' => 'Venue Profile'],
        'site.about' => ['name' => 'About', 'category' => 'Informational'],
        'site.gallery' => ['name' => 'Gallery', 'category' => 'Informational'],
        'site.privacy' => ['name' => 'Privacy Policy', 'category' => 'Legal'],
        'site.terms' => ['name' => 'Terms of Service', 'category' => 'Legal'],
        'customer.bookings.index' => ['name' => 'My Bookings', 'category' => 'Customer Area'],
    ];

    /**
     * @return array<string, mixed>
     */
    public static function build(?CarbonImmutable $now = null): array
    {
        $now = $now ?? CarbonImmutable::now();
        $to = $now->endOfDay();
        $from = $now->subDays(self::WINDOW_DAYS - 1)->startOfDay();
        $previousTo = $from->subSecond();
        $previousFrom = $previousTo->subDays(self::WINDOW_DAYS - 1)->startOfDay();

        $views = PageView::query()->betweenDates($from, $to)->count();
        $visitors = PageView::query()->betweenDates($from, $to)->distinct()->count('visitor_id');

        $previousViews = PageView::query()->betweenDates($previousFrom, $previousTo)->count();
        $previousVisitors = PageView::query()->betweenDates($previousFrom, $previousTo)->distinct()->count('visitor_id');

        $sessions = self::sessionAggregates($from, $to);

        return [
            'summary' => [
                'totalPageViews' => $views,
                'uniqueVisitors' => $visitors,
                'avgSessionTime' => self::formatDuration(self::averageSessionSeconds($sessions)),
                'bounceRate' => self::formatPercentage(self::bounceRate($sessions)),
                'viewsGrowth' => self::formatGrowth($views, $previousViews),
                'visitorsGrowth' => self::formatGrowth($visitors, $previousVisitors),
            ],
            'trend' => self::trend($from, $to),
            'topPages' => self::topPages($from, $to, $sessions),
            'deviceBreakdown' => self::deviceBreakdown($from, $to, $views),
            'sourcesBreakdown' => self::sourcesBreakdown($from, $to, $views),
        ];
    }

    /**
     * One row per session: how many views it had, how long it lasted, and (for
     * single-view sessions) the only page it touched.
     *
     * @return Collection<int, array{session_id: string, views: int, seconds: int, entry_path: string}>
     */
    private static function sessionAggregates(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return PageView::query()
            ->betweenDates($from, $to)
            ->toBase()
            ->select('session_id')
            ->selectRaw('COUNT(*) as views')
            ->selectRaw('MIN(viewed_at) as first_view')
            ->selectRaw('MAX(viewed_at) as last_view')
            ->selectRaw('MIN(path) as entry_path')
            ->groupBy('session_id')
            ->get()
            ->map(fn (object $row): array => [
                'session_id' => (string) $row->session_id,
                'views' => (int) $row->views,
                'seconds' => (int) CarbonImmutable::parse((string) $row->last_view)
                    ->diffInSeconds(CarbonImmutable::parse((string) $row->first_view), true),
                'entry_path' => (string) $row->entry_path,
            ]);
    }

    /**
     * A bounce is a session that viewed exactly one page.
     *
     * @param  Collection<int, array{session_id: string, views: int, seconds: int, entry_path: string}>  $sessions
     */
    private static function bounceRate(Collection $sessions): float
    {
        if ($sessions->isEmpty()) {
            return 0.0;
        }

        return $sessions->where('views', 1)->count() / $sessions->count() * 100;
    }

    /**
     * Mean session length. Single-view sessions have no measurable duration
     * and are excluded so they don't drag the average to zero.
     *
     * @param  Collection<int, array{session_id: string, views: int, seconds: int, entry_path: string}>  $sessions
     */
    private static function averageSessionSeconds(Collection $sessions): int
    {
        $engaged = $sessions->where('views', '>', 1);

        if ($engaged->isEmpty()) {
            return 0;
        }

        return (int) round((float) $engaged->avg('seconds'));
    }

    /**
     * Daily views and unique visitors, with empty days filled in as zero so the
     * chart always plots a full window.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function trend(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = PageView::query()
            ->betweenDates($from, $to)
            ->toBase()
            ->selectRaw('DATE(viewed_at) as day')
            ->selectRaw('COUNT(*) as views')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $trend = [];

        for ($day = $from; $day->lessThanOrEqualTo($to); $day = $day->addDay()) {
            $key = $day->toDateString();
            $row = $rows->get($key);

            $trend[] = [
                'date' => $key,
                'label' => $day->format('M j'),
                'views' => (int) ($row->views ?? 0),
                'visitors' => (int) ($row->visitors ?? 0),
            ];
        }

        return $trend;
    }

    /**
     * Most-viewed pages, with a real per-page bounce rate.
     *
     * @param  Collection<int, array{session_id: string, views: int, seconds: int, entry_path: string}>  $sessions
     * @return array<int, array<string, mixed>>
     */
    private static function topPages(CarbonImmutable $from, CarbonImmutable $to, Collection $sessions): array
    {
        $rows = PageView::query()
            ->betweenDates($from, $to)
            ->toBase()
            ->select('path')
            ->selectRaw('MIN(route_name) as route_name')
            ->selectRaw('COUNT(*) as views')
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
            ->selectRaw('COUNT(DISTINCT session_id) as sessions')
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit(self::TOP_PAGES_LIMIT)
            ->get();

        $bouncesByPath = $sessions
            ->where('views', 1)
            ->countBy(fn (array $session): string => $session['entry_path']);

        return $rows->map(function (object $row) use ($bouncesByPath): array {
            $path = (string) $row->path;
            $label = self::PAGE_LABELS[(string) $row->route_name] ?? ['name' => $path, 'category' => 'Other'];
            $sessionCount = (int) $row->sessions;
            $bounces = (int) $bouncesByPath->get($path, 0);

            return [
                'name' => $label['name'],
                'url' => $path,
                'category' => $label['category'],
                'views' => (int) $row->views,
                'visitors' => (int) $row->visitors,
                'bounceRate' => self::formatPercentage($sessionCount > 0 ? $bounces / $sessionCount * 100 : 0.0),
            ];
        })->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function deviceBreakdown(CarbonImmutable $from, CarbonImmutable $to, int $totalViews): array
    {
        $counts = PageView::query()
            ->betweenDates($from, $to)
            ->toBase()
            ->select('device')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('device')
            ->pluck('total', 'device');

        return array_map(fn (DeviceType $device): array => [
            'device' => $device->label(),
            'percentage' => self::share((int) $counts->get($device->value, 0), $totalViews),
            'count' => (int) $counts->get($device->value, 0),
            'color' => $device->color(),
        ], DeviceType::cases());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function sourcesBreakdown(CarbonImmutable $from, CarbonImmutable $to, int $totalViews): array
    {
        $counts = PageView::query()
            ->betweenDates($from, $to)
            ->toBase()
            ->select('source')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('source')
            ->pluck('total', 'source');

        return array_map(fn (TrafficSource $source): array => [
            'source' => $source->label(),
            'percentage' => self::share((int) $counts->get($source->value, 0), $totalViews),
            'color' => $source->color(),
        ], TrafficSource::cases());
    }

    private static function share(int $count, int $total): int
    {
        return $total > 0 ? (int) round($count / $total * 100) : 0;
    }

    private static function formatPercentage(float $value): string
    {
        return number_format($value, 1).'%';
    }

    private static function formatDuration(int $seconds): string
    {
        return sprintf('%dm %02ds', intdiv($seconds, 60), $seconds % 60);
    }

    /**
     * Period-over-period change. With no prior traffic there is no baseline to
     * compare against, so the growth figure stays neutral.
     */
    private static function formatGrowth(int $current, int $previous): string
    {
        if ($previous === 0) {
            return $current > 0 ? '+100.0%' : '0.0%';
        }

        $change = ($current - $previous) / $previous * 100;

        return sprintf('%s%s%%', $change >= 0 ? '+' : '', number_format($change, 1));
    }
}
