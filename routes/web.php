<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PosterController;
use App\Http\Controllers\Client\DashboardController as ClientDashboardController;
use App\Http\Controllers\Client\ReservationController;

// route to view admin dashboard
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // route to get posters view
    Route::resource('posters', PosterController::class)
    ->except(['show', 'create', 'edit']); // index, store, update, destroy — edit/add happen in modals on the index page
});

Route::middleware(['auth', 'role:client'])->prefix('client')->name('client.')->group(function () {
    Route::get('/dashboard', [ClientDashboardController::class, 'index'])->name('dashboard');
 
    Route::get('/reservations/create', [ReservationController::class, 'create'])->name('reservations.create');
    Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
 
    // Placeholder — the sidebar already links to this via routeIs('client.appointments*'),
    // build the actual appointments list/booking flow next and swap this in.
    // Route::get('/appointments', [ClientAppointmentController::class, 'index'])->name('appointments.index');
});

Route::get('/', [LandingController::class, 'index'])->name('landing');

// route for login
Route::get('/account', [AuthController::class, 'show'])->name('account.show');
Route::post('/account/login', [AuthController::class, 'login'])->name('account.login');
Route::post('/account/register', [AuthController::class, 'register'])->name('account.register');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

 
