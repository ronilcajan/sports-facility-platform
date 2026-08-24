<?php

use App\Enums\RoleName;
use App\Models\Reward;
use App\Models\User;

test('super admin can view rewards management page', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    Reward::create([
        'name' => 'Free Mineral Water 500ml',
        'slug' => 'free-water-500ml',
        'category' => 'drink',
        'points_cost' => 100,
        'is_active' => true,
    ]);

    $response = $this->actingAs($superAdmin)
        ->get(route('admin.rewards.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/rewards/Index')
        ->has('rewards.data', 1)
        ->has('metrics')
        ->has('venues')
    );
});

test('super admin can create a new freebie reward', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    $response = $this->actingAs($superAdmin)
        ->post(route('admin.rewards.store'), [
            'name' => 'Cold Energy Drink 250ml',
            'description' => 'Rehydrate after a tough match.',
            'category' => 'drink',
            'points_cost' => 150,
            'stock' => 50,
            'badge_text' => 'POPULAR',
            'icon' => 'CupSoda',
            'terms' => 'Present voucher code at reception.',
            'is_active' => true,
        ]);

    $response->assertRedirect(route('admin.rewards.index'));

    $this->assertDatabaseHas('rewards', [
        'name' => 'Cold Energy Drink 250ml',
        'category' => 'drink',
        'points_cost' => 150,
        'stock' => 50,
        'badge_text' => 'POPULAR',
        'is_active' => 1,
    ]);
});

test('super admin can update an existing freebie reward', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    $reward = Reward::create([
        'name' => 'Sports Towel',
        'slug' => 'sports-towel',
        'category' => 'apparel',
        'points_cost' => 300,
        'stock' => 20,
        'is_active' => true,
    ]);

    $response = $this->actingAs($superAdmin)
        ->put(route('admin.rewards.update', $reward), [
            'name' => 'Premium Microfiber Sports Towel',
            'description' => 'Ultra absorbent microfiber towel.',
            'category' => 'apparel',
            'points_cost' => 350,
            'stock' => 15,
            'badge_text' => 'VIP',
            'icon' => 'Shirt',
            'terms' => 'One per customer per day.',
            'is_active' => true,
        ]);

    $response->assertRedirect(route('admin.rewards.index'));

    $reward->refresh();
    expect($reward->name)->toBe('Premium Microfiber Sports Towel')
        ->and($reward->points_cost)->toBe(350)
        ->and($reward->stock)->toBe(15);
});

test('super admin can toggle active status of a reward', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    $reward = Reward::create([
        'name' => 'Free Shuttlecock Pack',
        'slug' => 'shuttlecock-pack',
        'category' => 'gear',
        'points_cost' => 200,
        'is_active' => true,
    ]);

    $response = $this->actingAs($superAdmin)
        ->patch(route('admin.rewards.toggle-active', $reward));

    $response->assertRedirect();

    $reward->refresh();
    expect($reward->is_active)->toBeFalse();
});

test('super admin can delete a freebie reward', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    $reward = Reward::create([
        'name' => 'Discount Coupon 10%',
        'slug' => 'discount-coupon-10',
        'category' => 'discount',
        'points_cost' => 500,
        'is_active' => true,
    ]);

    $response = $this->actingAs($superAdmin)
        ->delete(route('admin.rewards.destroy', $reward));

    $response->assertRedirect(route('admin.rewards.index'));

    $this->assertDatabaseMissing('rewards', [
        'id' => $reward->id,
    ]);
});

test('regular customer cannot access admin rewards endpoints', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleName::Customer->value);

    $response = $this->actingAs($customer)
        ->get(route('admin.rewards.index'));

    $response->assertForbidden();
});
