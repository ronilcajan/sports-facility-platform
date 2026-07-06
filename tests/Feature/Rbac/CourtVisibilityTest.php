<?php

use App\Enums\RoleName;
use App\Models\Court;

test('admin can view every court through the policy', function () {
    $admin = userWithRole(RoleName::Admin);
    $court = Court::factory()->create();

    expect($admin->can('view', $court))->toBeTrue();
});

test('staff can view only assigned courts', function () {
    $staff = userWithRole(RoleName::Staff);
    $assigned = Court::factory()->create();
    $unassigned = Court::factory()->create();

    $staff->assignedCourts()->attach($assigned);

    expect($staff->can('view', $assigned))->toBeTrue()
        ->and($staff->can('view', $unassigned))->toBeFalse();
});

test('visibleTo scope returns all courts for admins', function () {
    $admin = userWithRole(RoleName::Admin);
    Court::factory()->count(3)->create();

    expect(Court::visibleTo($admin)->count())->toBe(3);
});

test('visibleTo scope returns only assigned courts for staff', function () {
    $staff = userWithRole(RoleName::Staff);
    $assigned = Court::factory()->count(2)->create();
    Court::factory()->count(3)->create();

    $staff->assignedCourts()->attach($assigned->pluck('id'));

    $visible = Court::visibleTo($staff)->pluck('id');

    expect($visible->sort()->values()->all())
        ->toBe($assigned->pluck('id')->sort()->values()->all());
});

test('super admin bypasses policies via Gate before', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $court = Court::factory()->create();

    expect($superAdmin->can('view', $court))->toBeTrue()
        ->and($superAdmin->can('delete', $court))->toBeTrue()
        ->and($superAdmin->can('assignStaff', $court))->toBeTrue();
});

test('customer cannot view courts', function () {
    $customer = userWithRole(RoleName::Customer);
    $court = Court::factory()->create();

    expect($customer->can('view', $court))->toBeFalse();
});
