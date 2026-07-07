<?php

use App\Enums\RoleName;
use App\Models\SiteSetting;

test('an admin can view the appearance page', function (): void {
    $this->actingAs(userWithRole(RoleName::Admin))
        ->get(route('admin.appearance.index'))
        ->assertOk();
});

test('an admin can update the active theme', function (): void {
    $this->actingAs(userWithRole(RoleName::Admin))
        ->put(route('admin.appearance.update'), ['theme' => 'fairway'])
        ->assertRedirect();

    expect(SiteSetting::get('active_theme'))->toBe('fairway');
    $this->assertDatabaseHas('site_settings', ['key' => 'active_theme', 'value' => 'fairway']);
});

test('an invalid theme is rejected', function (): void {
    $this->actingAs(userWithRole(RoleName::Admin))
        ->put(route('admin.appearance.update'), ['theme' => 'chartreuse'])
        ->assertSessionHasErrors('theme');
});

test('staff and customers cannot manage appearance', function (RoleName $role): void {
    $this->actingAs(userWithRole($role))
        ->get(route('admin.appearance.index'))
        ->assertForbidden();
})->with([RoleName::Staff, RoleName::Customer]);

test('guests are redirected to login', function (): void {
    $this->get(route('admin.appearance.index'))->assertRedirect(route('login'));
});

test('the appearance page receives themes and the active theme', function (): void {
    $this->actingAs(userWithRole(RoleName::Admin))
        ->get(route('admin.appearance.index'))
        ->assertInertia(fn ($page) => $page
            ->component('admin/appearance/Index')
            ->has('themes', 3)
            ->where('activeTheme', 'navy'));
});
