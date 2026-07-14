<?php

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('all roles are seeded', function () {
    expect(Role::pluck('name')->sort()->values()->all())
        ->toBe(collect(RoleName::values())->sort()->values()->all());
});

test('court permissions are seeded', function () {
    expect(Permission::whereIn('name', [
        'courts.viewAny',
        'courts.view',
        'courts.create',
        'courts.update',
        'courts.delete',
        'courts.assignStaff',
    ])->count())->toBe(6);
});

test('seeder is idempotent', function () {
    $rolesBefore = Role::count();
    $permissionsBefore = Permission::count();

    $this->seed(RolePermissionSeeder::class);
    $this->seed(RolePermissionSeeder::class);

    expect(Role::count())->toBe($rolesBefore)
        ->and(Permission::count())->toBe($permissionsBefore);
});

test('admin has all court management permissions', function () {
    $admin = userWithRole(RoleName::Admin);

    expect($admin->can('courts.create'))->toBeTrue()
        ->and($admin->can('courts.delete'))->toBeTrue()
        ->and($admin->can('courts.assignStaff'))->toBeTrue();
});

test('staff has only view and create permissions', function () {
    $staff = userWithRole(RoleName::Staff);

    expect($staff->can('courts.viewAny'))->toBeTrue()
        ->and($staff->can('courts.view'))->toBeTrue()
        ->and($staff->can('courts.create'))->toBeTrue()
        ->and($staff->can('courts.delete'))->toBeFalse();
});

test('customer has no court management permissions', function () {
    $customer = userWithRole(RoleName::Customer);

    expect($customer->can('courts.viewAny'))->toBeFalse()
        ->and($customer->can('courts.create'))->toBeFalse();
});

test('new registrations receive the customer role', function () {
    $this->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'jane@example.com')->firstOrFail();

    expect($user->hasRole(RoleName::Customer->value))->toBeTrue();
});
