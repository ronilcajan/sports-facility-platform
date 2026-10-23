<?php

use App\Enums\RoleName;
use App\Mail\BookingApprovedMail;
use App\Mail\BookingReceivedMail;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Venue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Mail::fake();
    Storage::fake('public');
});

test('customer receives confirmation email on submission and approval email on super admin approval', function (): void {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $venue = Venue::factory()->create(['name' => 'Metro Sports Complex']);
    $court = Court::factory()->for($venue)->create(['name' => 'Center Court']);

    // Step 1: Customer submits booking
    $response = $this->postJson(route('site.bookings.store'), [
        'court_id' => $court->id,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '09171234567',
        'date' => Carbon::now()->addDays(2)->toDateString(),
        'time' => ['09:00 AM'],
        'notes' => 'First time playing.',
    ]);

    $response->assertStatus(201);
    $bookingId = $response->json('booking.id');
    $booking = Booking::findOrFail($bookingId);

    // Assert exactly 1 email sent so far: BookingReceivedMail (Pending Approval)
    Mail::assertSent(BookingReceivedMail::class, 1);
    Mail::assertSent(BookingReceivedMail::class, function (BookingReceivedMail $mail) {
        return $mail->hasTo('jane@example.com')
            && str_contains($mail->envelope()->subject, 'Pending Approval');
    });
    Mail::assertNotSent(BookingApprovedMail::class);

    // Verify confirmation_email_sent_at is tracked
    expect($booking->fresh()->confirmation_email_sent_at)->not->toBeNull();
    expect($booking->fresh()->approved_email_sent_at)->toBeNull();

    // Step 2: Super Admin approves booking
    $this->actingAs($superAdmin)
        ->patch(route('admin.bookings.update-status', $booking), [
            'status' => 'approved',
        ])
        ->assertRedirect();

    expect($booking->fresh()->status)->toBe('approved');
    expect($booking->fresh()->approved_email_sent_at)->not->toBeNull();

    // Assert second email sent: BookingApprovedMail
    Mail::assertSent(BookingApprovedMail::class, 1);
    Mail::assertSent(BookingApprovedMail::class, function (BookingApprovedMail $mail) {
        return $mail->hasTo('jane@example.com');
    });

    // Exactly 2 emails sent in total
    Mail::assertSentCount(2);
});

test('duplicate emails are not sent when booking status is updated repeatedly or refreshed', function (): void {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $venue = Venue::factory()->create();
    $court = Court::factory()->for($venue)->create();

    // Customer submits booking
    $this->postJson(route('site.bookings.store'), [
        'court_id' => $court->id,
        'name' => 'John Smith',
        'email' => 'john@example.com',
        'phone' => '09179876543',
        'date' => Carbon::now()->addDays(3)->toDateString(),
        'time' => ['10:00 AM'],
    ])->assertStatus(201);

    $booking = Booking::where('email', 'john@example.com')->first();
    Mail::assertSent(BookingReceivedMail::class, 1);

    // Simulating calling sendReceivedEmail again (e.g. duplicate trigger or refresh)
    $booking->sendReceivedEmail();
    Mail::assertSent(BookingReceivedMail::class, 1);

    // Super Admin approves
    $this->actingAs($superAdmin)
        ->patch(route('admin.bookings.update-status', $booking), [
            'status' => 'approved',
        ])
        ->assertRedirect();

    Mail::assertSent(BookingApprovedMail::class, 1);

    // Super Admin updates notes on the already approved booking (simulating re-save/refresh)
    $this->actingAs($superAdmin)
        ->patch(route('admin.bookings.update-status', $booking), [
            'status' => 'approved',
            'notes' => 'Updated notes on approved booking',
        ])
        ->assertRedirect();

    // Still only 1 approval email sent!
    Mail::assertSent(BookingApprovedMail::class, 1);

    // Repeated call to sendApprovalEmail directly does not re-send
    $booking->fresh()->sendApprovalEmail();
    Mail::assertSent(BookingApprovedMail::class, 1);

    // Exactly two emails total across the entire lifecycle
    Mail::assertSentCount(2);
});

test('booking received email content informs customer that booking is pending approval', function (): void {
    $venue = Venue::factory()->create(['name' => 'Sunset Court Club']);
    $court = Court::factory()->for($venue)->create(['name' => 'Court 1']);
    $booking = Booking::factory()->for($court)->create([
        'name' => 'Carlos Reyes',
        'email' => 'carlos@example.com',
        'status' => 'pending',
        'date' => Carbon::now()->addDays(1)->toDateString(),
        'time_slots' => ['02:00 PM'],
    ]);

    $mail = new BookingReceivedMail($booking);
    $html = $mail->render();

    expect($html)
        ->toContain('pending approval from the Super Admin')
        ->toContain('Your booking is NOT yet approved')
        ->toContain('Sunset Court Club')
        ->toContain('Court 1')
        ->toContain('02:00 PM');
});

test('booking approval email content includes relevant booking details', function (): void {
    $venue = Venue::factory()->create(['name' => 'Sunset Court Club']);
    $court = Court::factory()->for($venue)->create(['name' => 'Court 2']);
    $booking = Booking::factory()->for($court)->create([
        'name' => 'Maria Santos',
        'email' => 'maria@example.com',
        'status' => 'approved',
        'date' => Carbon::now()->addDays(2)->toDateString(),
        'time_slots' => ['04:00 PM'],
    ]);

    $mail = new BookingApprovedMail($booking);
    $html = $mail->render();

    expect($html)
        ->toContain('Booking Approved')
        ->toContain('Sunset Court Club')
        ->toContain('Court 2')
        ->toContain('04:00 PM')
        ->toContain('Maria Santos');
});
