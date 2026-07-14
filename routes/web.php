<?php

use App\Http\Controllers\Site\BookingController;
use App\Http\Controllers\Site\PageController;
use App\Models\Booking;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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
    Route::get('/dashboard', function () {
        $user = request()->user();

        if ($user->canManageAllCourts()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->isStaff()) {
            return redirect()->route('staff.dashboard');
        }

        $bookings = Booking::with('court')
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($b) {
                return [
                    'id' => $b->id,
                    'reference_code' => 'DY-RESRV-'.str_pad((string) $b->id, 6, '0', STR_PAD_LEFT),
                    'court' => $b->court ? [
                        'id' => $b->court->id,
                        'name' => $b->court->name,
                        'sport_type' => $b->court->sport_type?->label() ?? $b->court->sport_type,
                    ] : null,
                    'name' => $b->name,
                    'email' => $b->email,
                    'phone' => $b->phone,
                    'date' => $b->date,
                    'time_slots' => $b->time_slots,
                    'total_price' => number_format((float) $b->total_price, 2, '.', ''),
                    'receipt_url' => $b->receipt_url,
                    'status' => $b->status,
                    'notes' => $b->notes,
                ];
            });

        return Inertia::render('Dashboard', [
            'bookings' => $bookings,
        ]);
    })->name('dashboard');

    Route::patch('/bookings/{booking}', [BookingController::class, 'update'])->name('site.bookings.update');
    Route::delete('/bookings/{booking}', [BookingController::class, 'destroy'])->name('site.bookings.destroy');
});

require __DIR__.'/admin.php';
require __DIR__.'/staff.php';
require __DIR__.'/settings.php';
