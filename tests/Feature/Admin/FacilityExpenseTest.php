<?php

use App\Enums\RoleName;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Expense;
use App\Models\Venue;

test('super admin can view expenses management page', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);

    Expense::factory()->create([
        'title' => 'Air Conditioning Servicing',
        'amount' => 3500.00,
        'category' => 'maintenance',
        'date' => now()->format('Y-m-d'),
    ]);

    $response = $this->actingAs($superAdmin)
        ->get(route('admin.expenses.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/expenses/Index')
        ->has('expenses.data', 1)
        ->where('expenses.data.0.title', 'Air Conditioning Servicing')
        ->has('metrics')
        ->has('venues')
        ->has('categories')
        ->has('paymentMethods')
    );
});

test('unauthenticated users are redirected to login', function () {
    $this->get(route('admin.expenses.index'))
        ->assertRedirect(route('login'));
});

test('non super admin customers cannot view expenses management', function () {
    $customer = userWithRole(RoleName::Customer);

    $this->actingAs($customer)
        ->get(route('admin.expenses.index'))
        ->assertForbidden();
});

test('super admin can record a new facility expense', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $venue = Venue::factory()->create();

    $payload = [
        'title' => 'Shuttlecocks Bulk Replenishment',
        'amount' => 4500.50,
        'date' => '2026-10-15',
        'category' => 'equipment',
        'venue_id' => $venue->id,
        'payment_method' => 'gcash',
        'reference_number' => 'GCASH-TXN-8849',
        'notes' => '10 tubes of tournament-grade shuttlecocks',
    ];

    $response = $this->actingAs($superAdmin)
        ->post(route('admin.expenses.store'), $payload);

    $response->assertRedirect(route('admin.expenses.index'));
    $response->assertSessionHas('success');

    $expense = Expense::where('title', 'Shuttlecocks Bulk Replenishment')->first();
    expect($expense)->not->toBeNull();
    expect((float) $expense->amount)->toBe(4500.50);
    expect($expense->date->format('Y-m-d'))->toBe('2026-10-15');
    expect($expense->category)->toBe('equipment');
    expect($expense->venue_id)->toBe($venue->id);
    expect($expense->payment_method)->toBe('gcash');
    expect($expense->reference_number)->toBe('GCASH-TXN-8849');
    expect($expense->user_id)->toBe($superAdmin->id);
});

test('expense creation requires valid input fields', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);

    $response = $this->actingAs($superAdmin)
        ->post(route('admin.expenses.store'), [
            'title' => '',
            'amount' => -10,
            'date' => 'invalid-date',
            'category' => 'non-existent-category',
        ]);

    $response->assertSessionHasErrors(['title', 'amount', 'date', 'category']);
});

test('super admin can update an existing expense', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $expense = Expense::factory()->create([
        'title' => 'Original Title',
        'amount' => 1000.00,
        'category' => 'utilities',
    ]);

    $response = $this->actingAs($superAdmin)
        ->put(route('admin.expenses.update', $expense), [
            'title' => 'Updated Title',
            'amount' => 1250.75,
            'date' => '2026-10-16',
            'category' => 'maintenance',
            'payment_method' => 'bank_transfer',
            'reference_number' => 'REF-9921',
            'notes' => 'Adjusted amount after invoice reconciliation',
        ]);

    $response->assertRedirect(route('admin.expenses.index'));
    $response->assertSessionHas('success');

    $expense->refresh();
    expect($expense->title)->toBe('Updated Title');
    expect((float) $expense->amount)->toBe(1250.75);
    expect($expense->category)->toBe('maintenance');
});

test('super admin can delete an expense', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $expense = Expense::factory()->create();

    $response = $this->actingAs($superAdmin)
        ->delete(route('admin.expenses.destroy', $expense));

    $response->assertRedirect(route('admin.expenses.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('expenses', [
        'id' => $expense->id,
    ]);
});

test('super admin can filter expenses by category and venue', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $venueA = Venue::factory()->create();
    $venueB = Venue::factory()->create();

    Expense::factory()->create([
        'title' => 'Venue A Power Bill',
        'category' => 'utilities',
        'venue_id' => $venueA->id,
    ]);

    Expense::factory()->create([
        'title' => 'Venue B Maintenance',
        'category' => 'maintenance',
        'venue_id' => $venueB->id,
    ]);

    // Filter by utilities
    $response = $this->actingAs($superAdmin)
        ->get(route('admin.expenses.index', ['category' => 'utilities']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('expenses.data', 1)
        ->where('expenses.data.0.title', 'Venue A Power Bill')
    );

    // Filter by Venue B
    $responseB = $this->actingAs($superAdmin)
        ->get(route('admin.expenses.index', ['venue_id' => $venueB->id]));

    $responseB->assertOk();
    $responseB->assertInertia(fn ($page) => $page
        ->has('expenses.data', 1)
        ->where('expenses.data.0.title', 'Venue B Maintenance')
    );
});

test('reports page displays total expenses and net profit correctly', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $court = Court::factory()->create();

    $startDate = now()->startOfMonth()->toDateString();
    $endDate = now()->endOfMonth()->toDateString();

    // Create booking in current month with confirmed status for revenue
    Booking::factory()->create([
        'court_id' => $court->id,
        'date' => $startDate,
        'status' => 'confirmed',
        'total_price' => 5000.00,
    ]);

    // Create expenses in current month
    Expense::factory()->create([
        'date' => $startDate,
        'amount' => 1500.00,
        'category' => 'utilities',
    ]);

    Expense::factory()->create([
        'date' => $startDate,
        'amount' => 500.00,
        'category' => 'supplies',
    ]);

    $response = $this->actingAs($superAdmin)
        ->get(route('admin.reports.index', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/reports/Index')
        ->where('reports.totalRevenue', 5000)
        ->where('reports.totalExpenses', 2000)
        ->where('reports.netProfit', 3000)
        ->has('reports.expenseCategories', 2)
    );
});
