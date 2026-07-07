<?php

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

test('get returns the default when the key is absent', function (): void {
    expect(SiteSetting::get('active_theme', 'navy'))->toBe('navy');
});

test('set persists a value that get returns', function (): void {
    SiteSetting::set('active_theme', 'fairway');

    expect(SiteSetting::get('active_theme', 'navy'))->toBe('fairway');
    $this->assertDatabaseHas('site_settings', ['key' => 'active_theme', 'value' => 'fairway']);
});

test('set overwrites an existing value and busts the cache', function (): void {
    SiteSetting::set('active_theme', 'fairway');
    SiteSetting::get('active_theme'); // warm the cache

    SiteSetting::set('active_theme', 'electric');

    expect(SiteSetting::get('active_theme'))->toBe('electric');
    expect(Cache::get('site_setting:active_theme'))->toBe('electric');
});
