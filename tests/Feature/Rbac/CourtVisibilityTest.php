<?php

use App\Enums\RoleName;
use App\Models\Court;
use App\Models\Venue;

test('venue admin can view only courts in their own venue', function () {
    $venue = Venue::factory()->create();
    $admin = userWithRole(RoleName::Admin);
    $admin->update(['venue_id' => $venue->id]);

    $ownCourt = Court::factory()->for($venue)->create();
    $otherCourt = Court::factory()->create();

    expect($admin->can('view', $ownCourt))->toBeTrue()
        ->and($admin->can('view', $otherCourt))->toBeFalse();
});

test('staff can view only assigned courts', function () {
    $staff = userWithRole(RoleName::Staff);
    $assigned = Court::factory()->create();
    $unassigned = Court::factory()->create();

    $staff->assignedCourts()->attach($assigned);

    expect($staff->can('view', $assigned))->toBeTrue()
        ->and($staff->can('view', $unassigned))->toBeFalse();
});

test('visibleTo scope returns only the venue admin\'s courts', function () {
    $venue = Venue::factory()->create();
    $admin = userWithRole(RoleName::Admin);
    $admin->update(['venue_id' => $venue->id]);

    Court::factory()->for($venue)->count(2)->create();
    Court::factory()->count(3)->create();

    expect(Court::visibleTo($admin)->count())->toBe(2);
});

test('visibleTo scope returns every court for super admins', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    Court::factory()->count(3)->create();

    expect(Court::visibleTo($superAdmin)->count())->toBe(3);
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
