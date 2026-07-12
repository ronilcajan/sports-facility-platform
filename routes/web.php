<?php

use App\Http\Controllers\Site\BookingController;
use App\Http\Controllers\Site\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('site.about');
Route::get('/courts', [PageController::class, 'courts'])->name('site.courts');
Route::get('/courts/{court:slug}', [PageController::class, 'show'])->name('site.courts.show');
Route::get('/pricing', [PageController::class, 'pricing'])->name('site.pricing');
Route::get('/gallery', [PageController::class, 'gallery'])->name('site.gallery');
Route::get('/privacy', [PageController::class, 'privacy'])->name('site.privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('site.terms');
Route::post('/bookings', [BookingController::class, 'store'])->name('site.bookings.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
