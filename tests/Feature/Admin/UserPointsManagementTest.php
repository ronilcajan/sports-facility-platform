<?php

use App\Enums\RoleName;
use App\Models\Reward;
use App\Models\User;

test('super admin can add points to customer account with audit record', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    $customer = User::factory()->create(['points' => 100]);
    $customer->assignRole(RoleName::Customer->value);

    $response = $this->actingAs($superAdmin)
        ->post(route('admin.users.adjust-points', $customer), [
            'action' => 'add',
            'amount' => 150,
            'reason' => 'Weekend tournament promotion bonus',
        ]);

    $response->assertRedirect();

    $customer->refresh();
    expect($customer->points)->toBe(250);

    $this->assertDatabaseHas('point_transactions', [
        'user_id' => $customer->id,
        'points' => 150,
        'type' => 'admin_adjustment',
        'created_by_id' => $superAdmin->id,
    ]);
});

test('super admin can deduct points from customer account with audit record', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    $customer = User::factory()->create(['points' => 200]);
    $customer->assignRole(RoleName::Customer->value);

    $response = $this->actingAs($superAdmin)
        ->post(route('admin.users.adjust-points', $customer), [
            'action' => 'deduct',
            'amount' => 50,
            'reason' => 'Adjustment for cancelled reservation refund',
        ]);

    $response->assertRedirect();

    $customer->refresh();
    expect($customer->points)->toBe(150);

    $this->assertDatabaseHas('point_transactions', [
        'user_id' => $customer->id,
        'points' => -50,
        'type' => 'admin_adjustment',
        'created_by_id' => $superAdmin->id,
    ]);
});

test('customer can view rewards center and claim freebie voucher', function () {
    $customer = User::factory()->create(['points' => 300]);
    $customer->assignRole(RoleName::Customer->value);

    $reward = Reward::create([
        'name' => 'Free Gatorade 500ml',
        'slug' => 'free-gatorade-500ml',
        'category' => 'drink',
        'points_cost' => 100,
        'stock' => 10,
        'is_active' => true,
    ]);

    $response = $this->actingAs($customer)
        ->get(route('customer.rewards.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('customer/Rewards')
        ->has('rewards', 1)
        ->has('loyaltySummary')
    );

    $claimResponse = $this->actingAs($customer)
        ->post(route('customer.rewards.claim', $reward));

    $claimResponse->assertRedirect();

    $customer->refresh();
    $reward->refresh();

    expect($customer->points)->toBe(200)
        ->and($reward->stock)->toBe(9);

    $this->assertDatabaseHas('reward_claims', [
        'user_id' => $customer->id,
        'reward_id' => $reward->id,
        'points_spent' => 100,
        'status' => 'active',
    ]);

    $this->assertDatabaseHas('point_transactions', [
        'user_id' => $customer->id,
        'points' => -100,
        'type' => 'reward_claim',
    ]);
});

test('customer cannot claim reward with insufficient points', function () {
    $customer = User::factory()->create(['points' => 50]);
    $customer->assignRole(RoleName::Customer->value);

    $reward = Reward::create([
        'name' => 'VIP Badminton Shirt',
        'slug' => 'vip-shirt',
        'category' => 'apparel',
        'points_cost' => 500,
        'stock' => 5,
        'is_active' => true,
    ]);

    $response = $this->actingAs($customer)
        ->post(route('customer.rewards.claim', $reward));

    $response->assertRedirect();

    $customer->refresh();
    expect($customer->points)->toBe(50);

    $this->assertDatabaseMissing('reward_claims', [
        'user_id' => $customer->id,
        'reward_id' => $reward->id,
    ]);
});
