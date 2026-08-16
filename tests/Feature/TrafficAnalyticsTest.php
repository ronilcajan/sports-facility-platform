<?php

use App\Enums\DeviceType;
use App\Enums\TrafficSource;
use App\Models\PageView;
use App\Support\TrafficAnalytics;
use Carbon\CarbonImmutable;

it('reports zeroes when nothing has been recorded', function () {
    $analytics = TrafficAnalytics::build();

    expect($analytics['summary']['totalPageViews'])->toBe(0)
        ->and($analytics['summary']['uniqueVisitors'])->toBe(0)
        ->and($analytics['summary']['bounceRate'])->toBe('0.0%')
        ->and($analytics['summary']['avgSessionTime'])->toBe('0m 00s')
        ->and($analytics['trend'])->toHaveCount(TrafficAnalytics::WINDOW_DAYS)
        ->and($analytics['topPages'])->toBe([]);
});

it('counts total views and de-duplicates unique visitors', function () {
    PageView::factory()->count(3)->create(['visitor_id' => 'visitor-a']);
    PageView::factory()->count(2)->create(['visitor_id' => 'visitor-b']);

    $summary = TrafficAnalytics::build()['summary'];

    expect($summary['totalPageViews'])->toBe(5)
        ->and($summary['uniqueVisitors'])->toBe(2);
});

it('excludes views outside the reporting window', function () {
    PageView::factory()->create(['viewed_at' => now()]);
    PageView::factory()->create(['viewed_at' => now()->subDays(TrafficAnalytics::WINDOW_DAYS + 5)]);

    expect(TrafficAnalytics::build()['summary']['totalPageViews'])->toBe(1);
});

it('builds a full window trend with empty days filled in', function () {
    $now = CarbonImmutable::parse('2026-08-16 12:00:00');

    PageView::factory()->count(2)->create(['viewed_at' => $now]);
    PageView::factory()->create(['viewed_at' => $now->subDays(3)]);

    $trend = TrafficAnalytics::build($now)['trend'];

    expect($trend)->toHaveCount(30)
        ->and($trend[29]['date'])->toBe('2026-08-16')
        ->and($trend[29]['views'])->toBe(2)
        ->and($trend[26]['date'])->toBe('2026-08-13')
        ->and($trend[26]['views'])->toBe(1)
        ->and($trend[0]['views'])->toBe(0);
});

it('computes bounce rate from single-view sessions', function () {
    // One engaged session (2 views) and three bounces => 75%.
    PageView::factory()->count(2)->forSession('engaged')->create();
    PageView::factory()->forSession('bounce-1')->create();
    PageView::factory()->forSession('bounce-2')->create();
    PageView::factory()->forSession('bounce-3')->create();

    expect(TrafficAnalytics::build()['summary']['bounceRate'])->toBe('75.0%');
});

it('computes average session time across engaged sessions', function () {
    $now = CarbonImmutable::parse('2026-08-16 12:00:00');

    PageView::factory()->forSession('s1')->create(['viewed_at' => $now->subMinutes(4)]);
    PageView::factory()->forSession('s1')->create(['viewed_at' => $now]);

    PageView::factory()->forSession('s2')->create(['viewed_at' => $now->subMinutes(2)]);
    PageView::factory()->forSession('s2')->create(['viewed_at' => $now->subSeconds(90)]);

    // (240s + 30s) / 2 = 135s
    expect(TrafficAnalytics::build($now)['summary']['avgSessionTime'])->toBe('2m 15s');
});

it('ranks top pages by views and labels known routes', function () {
    PageView::factory()->count(5)->create(['path' => '/', 'route_name' => 'home']);
    PageView::factory()->count(2)->create(['path' => '/about', 'route_name' => 'site.about']);

    $topPages = TrafficAnalytics::build()['topPages'];

    expect($topPages[0]['url'])->toBe('/')
        ->and($topPages[0]['name'])->toBe('Home / Facilities Directory')
        ->and($topPages[0]['views'])->toBe(5)
        ->and($topPages[1]['url'])->toBe('/about')
        ->and($topPages[1]['views'])->toBe(2);
});

it('computes a per-page bounce rate', function () {
    // /about: one bounced session, one engaged session that started there.
    PageView::factory()->forSession('bounced')->create(['path' => '/about', 'route_name' => 'site.about']);
    PageView::factory()->forSession('engaged')->create(['path' => '/about', 'route_name' => 'site.about']);
    PageView::factory()->forSession('engaged')->create(['path' => '/gallery', 'route_name' => 'site.gallery']);

    $about = collect(TrafficAnalytics::build()['topPages'])->firstWhere('url', '/about');

    expect($about['bounceRate'])->toBe('50.0%');
});

it('breaks views down by device as whole percentages', function () {
    PageView::factory()->count(6)->create(['device' => DeviceType::Desktop]);
    PageView::factory()->count(4)->create(['device' => DeviceType::Mobile]);

    $devices = collect(TrafficAnalytics::build()['deviceBreakdown'])->keyBy('device');

    expect($devices['Desktop / PC']['percentage'])->toBe(60)
        ->and($devices['Desktop / PC']['count'])->toBe(6)
        ->and($devices['Mobile Phones']['percentage'])->toBe(40)
        ->and($devices['Tablets & Other']['percentage'])->toBe(0);
});

it('breaks views down by traffic source', function () {
    PageView::factory()->count(7)->create(['source' => TrafficSource::Direct]);
    PageView::factory()->count(3)->create(['source' => TrafficSource::Search]);

    $sources = collect(TrafficAnalytics::build()['sourcesBreakdown'])->keyBy('source');

    expect($sources['Direct Traffic']['percentage'])->toBe(70)
        ->and($sources['Google / Search']['percentage'])->toBe(30)
        ->and($sources['Social Media']['percentage'])->toBe(0);
});

it('reports growth against the preceding window', function () {
    $now = CarbonImmutable::parse('2026-08-16 12:00:00');

    PageView::factory()->count(6)->create(['viewed_at' => $now->subDays(2)]);
    PageView::factory()->count(4)->create(['viewed_at' => $now->subDays(35)]);

    expect(TrafficAnalytics::build($now)['summary']['viewsGrowth'])->toBe('+50.0%');
});

it('reports a neutral growth figure with no prior baseline', function () {
    expect(TrafficAnalytics::build()['summary']['viewsGrowth'])->toBe('0.0%');
});
