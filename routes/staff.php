<?php

use App\Enums\RoleName;
use App\Http\Controllers\Staff\StaffBookingController;
use App\Http\Controllers\Staff\StaffCourtController;
use App\Http\Controllers\Staff\StaffDashboardController;
use App\Http\Controllers\Staff\StaffNotificationController;
use App\Http\Controllers\Staff\StaffReportController;
use App\Http\Controllers\Staff\StaffScheduleController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'verified',
    'role:'.RoleName::Staff->value.'|'.RoleName::SuperAdmin->value.'|'.RoleName::Admin->value,
])
    ->prefix('staff')
    ->name('staff.')
    ->group(function () {
        Route::get('dashboard', [StaffDashboardController::class, 'index'])->name('dashboard');

        // Courts (Assigned Scoped - CR Court)
        Route::get('courts', [StaffCourtController::class, 'index'])->name('courts.index');
        Route::post('courts', [StaffCourtController::class, 'store'])->name('courts.store');

        // Bookings (Assigned Court Scoped - CR Bookings)
        Route::get('bookings', [StaffBookingController::class, 'index'])->name('bookings.index');
        Route::post('bookings', [StaffBookingController::class, 'store'])->name('bookings.store');
        Route::get('bookings/{booking}', [StaffBookingController::class, 'show'])->name('bookings.show');
        Route::patch('bookings/{booking}/status', [StaffBookingController::class, 'updateStatus'])->name('bookings.update-status');

        // Schedules & Blackout Management
        Route::get('schedules', [StaffScheduleController::class, 'index'])->name('schedules.index');
        Route::post('schedules', [StaffScheduleController::class, 'store'])->name('schedules.store');
        Route::delete('schedules/{unavailability}', [StaffScheduleController::class, 'destroy'])->name('schedules.destroy');

        // Court Reports
        Route::get('reports', [StaffReportController::class, 'index'])->name('reports.index');

        // Notifications
        Route::patch('notifications/{id}/read', [StaffNotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::patch('notifications/read-all', [StaffNotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    });
