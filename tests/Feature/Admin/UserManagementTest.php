<?php

use App\Enums\RoleName;
use App\Models\User;

test('super admin can view user accounts list', function () {
    $this->actingAs(userWithRole(RoleName::SuperAdmin));

    $this->get(route('admin.users.index'))->assertOk();
});

test('admin can view user accounts list', function () {
    $this->actingAs(userWithRole(RoleName::Admin));

    $this->get(route('admin.users.index'))->assertOk();
});

test('staff and customer cannot view user accounts list', function (RoleName $role) {
    $this->actingAs(userWithRole($role));

    $this->get(route('admin.users.index'))->assertForbidden();
})->with([RoleName::Staff, RoleName::Customer]);

test('super admin can create a user with any role', function () {
    $this->actingAs(userWithRole(RoleName::SuperAdmin));

    $response = $this->post(route('admin.users.store'), [
        'name' => 'New Regional Admin',
        'email' => 'regionaladmin@example.com',
        'password' => 'password123',
        'role' => RoleName::Admin->value,
    ]);

    $response->assertRedirect(route('admin.users.index'));
    $newUser = User::where('email', 'regionaladmin@example.com')->first();
    expect($newUser)->not->toBeNull()
        ->and($newUser->hasRole(RoleName::Admin->value))->toBeTrue();
});

test('admin can create staff and customer users', function () {
    $this->actingAs(userWithRole(RoleName::Admin));

    $response = $this->post(route('admin.users.store'), [
        'name' => 'New Court Staff',
        'email' => 'newstaff@example.com',
        'password' => 'password123',
        'role' => RoleName::Staff->value,
    ]);

    $response->assertRedirect(route('admin.users.index'));
    $newUser = User::where('email', 'newstaff@example.com')->first();
    expect($newUser)->not->toBeNull()
        ->and($newUser->hasRole(RoleName::Staff->value))->toBeTrue();
});

test('admin cannot create admin or super admin users', function () {
    $this->actingAs(userWithRole(RoleName::Admin));

    $this->post(route('admin.users.store'), [
        'name' => 'Hacker Admin',
        'email' => 'hackeradmin@example.com',
        'password' => 'password123',
        'role' => RoleName::Admin->value,
    ])->assertForbidden();
});

test('super admin can update user information and role', function () {
    $this->actingAs(userWithRole(RoleName::SuperAdmin));
    $user = User::factory()->create();
    $user->assignRole(RoleName::Customer->value);

    $response = $this->put(route('admin.users.update', $user->id), [
        'name' => 'Promoted User',
        'email' => $user->email,
        'role' => RoleName::Staff->value,
    ]);

    $response->assertRedirect(route('admin.users.index'));
    expect($user->fresh())
        ->name->toBe('Promoted User')
        ->and($user->hasRole(RoleName::Staff->value))->toBeTrue();
});

test('admin cannot update existing admin or super admin users', function () {
    $this->actingAs(userWithRole(RoleName::Admin));
    $targetAdmin = userWithRole(RoleName::Admin);

    $this->put(route('admin.users.update', $targetAdmin->id), [
        'name' => 'Hijacked Admin',
        'email' => $targetAdmin->email,
        'role' => RoleName::Customer->value,
    ])->assertForbidden();
});

test('super admin can delete a user', function () {
    $this->actingAs(userWithRole(RoleName::SuperAdmin));
    $user = User::factory()->create();

    $this->delete(route('admin.users.destroy', $user->id))
        ->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

test('admin can delete a staff user', function () {
    $this->actingAs(userWithRole(RoleName::Admin));
    $staffUser = userWithRole(RoleName::Staff);

    $this->delete(route('admin.users.destroy', $staffUser->id))
        ->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseMissing('users', ['id' => $staffUser->id]);
});

test('admin cannot delete another admin or super admin', function () {
    $this->actingAs(userWithRole(RoleName::Admin));
    $otherAdmin = userWithRole(RoleName::Admin);

    $this->delete(route('admin.users.destroy', $otherAdmin->id))
        ->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $otherAdmin->id]);
});
