<?php

use App\Enums\DeviceType;
use App\Enums\RoleName;
use App\Enums\TrafficSource;
use App\Models\PageView;

it('records a page view for an anonymous visitor on a public page', function () {
    $this->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)'])
        ->get('/')
        ->assertOk();

    expect(PageView::query()->count())->toBe(1);

    $view = PageView::query()->firstOrFail();

    expect($view->path)->toBe('/')
        ->and($view->route_name)->toBe('home')
        ->and($view->device)->toBe(DeviceType::Desktop)
        ->and($view->source)->toBe(TrafficSource::Direct);
});

it('never stores the raw ip address', function () {
    $this->withServerVariables([
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
        'REMOTE_ADDR' => '203.0.113.42',
    ])->get('/')->assertOk();

    $view = PageView::query()->firstOrFail();

    expect($view->visitor_id)->not->toContain('203.0.113.42')
        ->and(strlen($view->visitor_id))->toBe(64);
});

it('classifies device and traffic source from request headers', function () {
    $this->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Mobile/15E148'])
        ->get('/', ['referer' => 'https://www.google.com/search?q=pickleball'])
        ->assertOk();

    $view = PageView::query()->firstOrFail();

    expect($view->device)->toBe(DeviceType::Mobile)
        ->and($view->source)->toBe(TrafficSource::Search)
        ->and($view->referrer_host)->toBe('www.google.com');
});

it('treats a referrer from this site as direct traffic', function () {
    $this->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh)'])
        ->get('/', ['referer' => config('app.url').'/about'])
        ->assertOk();

    expect(PageView::query()->firstOrFail()->source)->toBe(TrafficSource::Direct);
});

it('ignores bots and empty user agents', function (string $agent) {
    $this->withServerVariables(['HTTP_USER_AGENT' => $agent])->get('/')->assertOk();

    expect(PageView::query()->count())->toBe(0);
})->with([
    'googlebot' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
    'curl' => 'curl/8.4.0',
    'headless' => 'HeadlessChrome/120.0.0.0',
    'empty' => '',
]);

it('does not track admin surfaces', function () {
    $admin = userWithRole(RoleName::SuperAdmin);

    $this->actingAs($admin)
        ->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh)'])
        ->get('/admin/dashboard')
        ->assertOk();

    expect(PageView::query()->count())->toBe(0);
});

it('does not count staff or admins browsing the public site', function (RoleName $role) {
    $operator = userWithRole($role);

    $this->actingAs($operator)
        ->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh)'])
        ->get('/')
        ->assertOk();

    expect(PageView::query()->count())->toBe(0);
})->with([
    'super admin' => RoleName::SuperAdmin,
    'admin' => RoleName::Admin,
    'staff' => RoleName::Staff,
]);

it('counts customers browsing the public site', function () {
    $customer = userWithRole(RoleName::Customer);

    $this->actingAs($customer)
        ->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh)'])
        ->get('/')
        ->assertOk();

    expect(PageView::query()->count())->toBe(1);
});

it('does not track non-page json endpoints', function () {
    $this->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh)'])
        ->getJson('/bookings/availability?court_id=1&date=2026-08-20');

    expect(PageView::query()->count())->toBe(0);
});
