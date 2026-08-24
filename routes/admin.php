<?php

use App\Enums\RoleName;
use App\Http\Controllers\Admin\AdminBookingController;
use App\Http\Controllers\Admin\AdminCourtImageController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminRewardController;
use App\Http\Controllers\Admin\AdminStaffController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminVenueController;
use App\Http\Controllers\Admin\AdminVenueImageController;
use App\Http\Controllers\Admin\AppearanceController;
use App\Http\Controllers\Admin\CourtController;
use App\Http\Controllers\Admin\CourtStaffController;
use App\Http\Controllers\Admin\SingleVenueController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'verified',
    'role:'.RoleName::SuperAdmin->value.'|'.RoleName::Admin->value,
])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::resource('courts', CourtController::class);

        // Court Images Management (Upload primary hero display image & promotional images)
        Route::post('courts/{court}/images', [AdminCourtImageController::class, 'store'])
            ->name('courts.images.store');
        Route::patch('courts/{court}/images/{image}/primary', [AdminCourtImageController::class, 'setPrimary'])
            ->name('courts.images.primary');
        Route::delete('courts/{court}/images/{image}', [AdminCourtImageController::class, 'destroy'])
            ->name('courts.images.destroy');

        // Staff Assignment Quick Endpoints
        Route::post('courts/{court}/staff', [CourtStaffController::class, 'store'])
            ->name('courts.staff.store');
        Route::delete('courts/{court}/staff/{user}', [CourtStaffController::class, 'destroy'])
            ->name('courts.staff.destroy');

        // Bookings Management
        Route::get('bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
        Route::post('bookings', [AdminBookingController::class, 'store'])->name('bookings.store');
        Route::get('bookings/{booking}', [AdminBookingController::class, 'show'])->name('bookings.show');
        Route::patch('bookings/{booking}', [AdminBookingController::class, 'update'])->name('bookings.update');
        Route::patch('bookings/{booking}/status', [AdminBookingController::class, 'updateStatus'])->name('bookings.update-status');
        Route::delete('bookings/{booking}', [AdminBookingController::class, 'destroy'])->name('bookings.destroy');

        // Users & Customer Management (CRUD)
        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('users', [AdminUserController::class, 'store'])->name('users.store');
        Route::get('users/{user}', [AdminUserController::class, 'show'])->name('users.show');
        Route::put('users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::post('users/{user}/adjust-points', [AdminUserController::class, 'adjustPoints'])->name('users.adjust-points');
        Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

        // Freebies & Rewards Management (CRUD)
        Route::get('rewards', [AdminRewardController::class, 'index'])->name('rewards.index');
        Route::post('rewards', [AdminRewardController::class, 'store'])->name('rewards.store');
        Route::put('rewards/{reward}', [AdminRewardController::class, 'update'])->name('rewards.update');
        Route::patch('rewards/{reward}/toggle-active', [AdminRewardController::class, 'toggleActive'])->name('rewards.toggle-active');
        Route::delete('rewards/{reward}', [AdminRewardController::class, 'destroy'])->name('rewards.destroy');

        // Reports (venue-scoped for admins, global for super-admins)
        Route::get('reports', [AdminReportController::class, 'index'])->name('reports.index');

        // Venue Profile (accessible to authorized admins & superadmins)
        Route::get('venues/{venue}', [AdminVenueController::class, 'show'])->name('venues.show');
        Route::delete('venues/{venue}/image', [AdminVenueController::class, 'destroyImage'])->name('venues.image.destroy');

        // Venue Gallery Photos (authorization is per-venue via VenuePolicy)
        Route::post('venues/{venue}/images', [AdminVenueImageController::class, 'store'])
            ->name('venues.images.store');
        Route::patch('venues/{venue}/images/{image}/primary', [AdminVenueImageController::class, 'setPrimary'])
            ->name('venues.images.primary');
        Route::delete('venues/{venue}/images/{image}', [AdminVenueImageController::class, 'destroy'])
            ->name('venues.images.destroy');

        // Venue Settings — an admin edits their own assigned venue
        Route::get('settings', [SingleVenueController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SingleVenueController::class, 'update'])->name('settings.update');

        // Super Admin Only (Venues, Staff, Appearance)
        Route::middleware(['role:'.RoleName::SuperAdmin->value])->group(function () {
            // Venues Management (full CRUD)
            Route::resource('venues', AdminVenueController::class)->except(['show']);

            // Staff Accounts Management
            Route::get('staff', [AdminStaffController::class, 'index'])->name('staff.index');
            Route::post('staff', [AdminStaffController::class, 'store'])->name('staff.store');
            Route::put('staff/{user}', [AdminStaffController::class, 'update'])->name('staff.update');
            Route::delete('staff/{user}', [AdminStaffController::class, 'destroy'])->name('staff.destroy');

            // Appearance / Branding Settings
            Route::get('appearance', [AppearanceController::class, 'index'])->name('appearance.index');
            Route::post('appearance/branding', [AppearanceController::class, 'updateBranding'])->name('appearance.branding');
        });
    });
