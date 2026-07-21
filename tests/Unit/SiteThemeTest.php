<?php

use App\Enums\SiteTheme;

test('site theme exposes its string values', function (): void {
    expect(SiteTheme::values())->toBe(['navy', 'fairway', 'electric']);
});

test('site theme has a human label and description for each case', function (): void {
    expect(SiteTheme::Navy->label())->toBe('Gold & Onyx')
        ->and(SiteTheme::Fairway->label())->toBe('Warm Amber')
        ->and(SiteTheme::Electric->label())->toBe('Obsidian Platinum')
        ->and(SiteTheme::Navy->description())->not->toBe('');
});

test('site theme default is navy', function (): void {
    expect(SiteTheme::default())->toBe(SiteTheme::Navy);
});
