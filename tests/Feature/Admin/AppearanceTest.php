<?php

use App\Enums\RoleName;

test('a super admin can view the appearance page', function (): void {
    $this->actingAs(userWithRole(RoleName::SuperAdmin))
        ->get(route('admin.appearance.index'))
        ->assertOk();
});

test('admin, staff, and customers cannot manage appearance', function (RoleName $role): void {
    $this->actingAs(userWithRole($role))
        ->get(route('admin.appearance.index'))
        ->assertForbidden();
})->with([RoleName::Admin, RoleName::Staff, RoleName::Customer]);

test('guests are redirected to login', function (): void {
    $this->get(route('admin.appearance.index'))->assertRedirect(route('login'));
});

test('the appearance page renders the branding settings', function (): void {
    $this->actingAs(userWithRole(RoleName::SuperAdmin))
        ->get(route('admin.appearance.index'))
        ->assertInertia(fn ($page) => $page
            ->component('admin/appearance/Index')
            ->has('siteName')
            ->has('contact'));
});
