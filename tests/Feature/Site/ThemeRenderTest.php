<?php

use App\Models\SiteSetting;

test('the homepage renders the default theme on the html element', function (): void {
    $this->get('/')->assertOk()->assertSee('data-theme="navy"', false);
});

test('the homepage reflects the active theme setting', function (): void {
    SiteSetting::set('active_theme', 'fairway');

    $this->get('/')->assertOk()->assertSee('data-theme="fairway"', false);
});
