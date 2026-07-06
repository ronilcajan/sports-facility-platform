<?php

use App\Enums\RoleName;
use App\Http\Controllers\Admin\CourtController;
use App\Http\Controllers\Admin\CourtStaffController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'verified',
    'role:'.RoleName::SuperAdmin->value.'|'.RoleName::Admin->value,
])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('courts', CourtController::class);

        Route::post('courts/{court}/staff', [CourtStaffController::class, 'store'])
            ->name('courts.staff.store');
        Route::delete('courts/{court}/staff/{user}', [CourtStaffController::class, 'destroy'])
            ->name('courts.staff.destroy');
    });
