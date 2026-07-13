<?php

use App\Enums\RoleName;
use App\Http\Controllers\Staff\StaffBookingController;
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

        // Bookings (Assigned Court Scoped)
        Route::get('bookings', [StaffBookingController::class, 'index'])->name('bookings.index');
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
