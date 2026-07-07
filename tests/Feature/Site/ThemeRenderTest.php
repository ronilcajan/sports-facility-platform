<?php

use App\Models\SiteSetting;

test('the homepage renders the default theme on the html element', function (): void {
    $this->get('/')->assertOk()->assertSee('data-theme="navy"', false);
});

test('the homepage reflects each active theme', function (string $theme): void {
    SiteSetting::set('active_theme', $theme);

    $this->get('/')->assertOk()->assertSee('data-theme="'.$theme.'"', false);
})->with(['navy', 'fairway', 'electric']);
