<?php

use App\Http\Controllers\CustomerBookingController;
use App\Http\Controllers\CustomerRewardController;
use App\Http\Controllers\Site\BookingController;
use App\Http\Controllers\Site\PageController;
use App\Models\Court;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('site.about');
Route::get('/courts', [PageController::class, 'courts'])->name('site.courts');
Route::get('/courts/{court:slug}', [PageController::class, 'show'])->name('site.courts.show');
Route::get('/venues/{venue:slug}', [PageController::class, 'venueShow'])->name('site.venues.show');
Route::get('/gallery', [PageController::class, 'gallery'])->name('site.gallery');
Route::get('/privacy', [PageController::class, 'privacy'])->name('site.privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('site.terms');
Route::get('/bookings/availability', [BookingController::class, 'availability'])->name('site.bookings.availability');
Route::post('/bookings', [BookingController::class, 'store'])->name('site.bookings.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        $user = request()->user();

        if ($user->canManageAllCourts()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->isStaff()) {
            return redirect()->route('staff.dashboard');
        }

        return Inertia::render('Dashboard', [
            'bookings' => CustomerBookingController::bookingsFor($user),
            'courts' => Court::where('is_active', true)->get(['id', 'name', 'sport_type', 'base_price'])->map(function ($court) {
                return [
                    'id' => $court->id,
                    'name' => $court->name,
                    'sport_type' => $court->sport_type?->label() ?? $court->sport_type,
                    'base_price' => (string) $court->base_price,
                ];
            }),
            'loyaltySummary' => $user->getLoyaltySummary(),
        ]);
    })->name('dashboard');

    Route::get('/my-bookings', [CustomerBookingController::class, 'index'])->name('customer.bookings.index');

    // Customer Rewards & Points
    Route::get('/customer/rewards', [CustomerRewardController::class, 'index'])->name('customer.rewards.index');
    Route::post('/customer/rewards/{reward}/claim', [CustomerRewardController::class, 'claim'])->name('customer.rewards.claim');

    Route::patch('/bookings/{booking}', [BookingController::class, 'update'])->name('site.bookings.update');
    Route::delete('/bookings/{booking}', [BookingController::class, 'destroy'])->name('site.bookings.destroy');
});

require __DIR__.'/admin.php';
require __DIR__.'/staff.php';
require __DIR__.'/settings.php';
