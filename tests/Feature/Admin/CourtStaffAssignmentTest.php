<?php

use App\Enums\RoleName;
use App\Models\Court;

test('admins can assign a staff member to a court', function () {
    $this->actingAs(userWithRole(RoleName::Admin));
    $court = Court::factory()->create();
    $staff = userWithRole(RoleName::Staff);

    $this->post(route('admin.courts.staff.store', $court), ['user_id' => $staff->id])
        ->assertRedirect();

    expect($staff->fresh()->isAssignedToCourt($court))->toBeTrue();
});

test('assigning the same staff twice does not duplicate the pivot', function () {
    $this->actingAs(userWithRole(RoleName::Admin));
    $court = Court::factory()->create();
    $staff = userWithRole(RoleName::Staff);

    $this->post(route('admin.courts.staff.store', $court), ['user_id' => $staff->id]);
    $this->post(route('admin.courts.staff.store', $court), ['user_id' => $staff->id]);

    expect($court->staff()->count())->toBe(1);
});

test('only staff-role users can be assigned', function () {
    $this->actingAs(userWithRole(RoleName::Admin));
    $court = Court::factory()->create();
    $customer = userWithRole(RoleName::Customer);

    $this->post(route('admin.courts.staff.store', $court), ['user_id' => $customer->id])
        ->assertSessionHasErrors('user_id');
});

test('admins can unassign a staff member', function () {
    $this->actingAs(userWithRole(RoleName::Admin));
    $court = Court::factory()->create();
    $staff = userWithRole(RoleName::Staff);
    $court->staff()->attach($staff);

    $this->delete(route('admin.courts.staff.destroy', [$court, $staff]))
        ->assertRedirect();

    expect($staff->fresh()->isAssignedToCourt($court))->toBeFalse();
});

test('customers cannot assign staff', function () {
    $this->actingAs(userWithRole(RoleName::Customer));
    $court = Court::factory()->create();
    $staff = userWithRole(RoleName::Staff);

    $this->post(route('admin.courts.staff.store', $court), ['user_id' => $staff->id])
        ->assertForbidden();
});
