<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PosterController;
use App\Http\Controllers\Client\DashboardController as ClientDashboardController;
use App\Http\Controllers\Client\ReservationController;
use App\Http\Controllers\Client\PaymentController;
use App\Http\Controllers\Client\ApprovalFormController;
use App\Http\Controllers\Staff\DashboardController as StaffDashboardController;
use App\Http\Controllers\Staff\VerifyController;

// route to view admin dashboard
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // route to get posters view
    Route::resource('posters', PosterController::class)
    ->except(['show', 'create', 'edit']); // index, store, update, destroy — edit/add happen in modals on the index page
});

Route::middleware(['auth', 'role:client'])->prefix('client')->name('client.')->group(function () {
    Route::get('/dashboard', [ClientDashboardController::class, 'index'])->name('dashboard');
 
    // Steps 1–3: Person Details (+ booking type: appointment | room_reservation | free_use),
    // Room & Billing (billing skipped entirely for free_use), Commitment Form.
    // Creates the Appointment as status=pending — no payment/attachment yet.
    Route::get('/reservations/create', [ReservationController::class, 'create'])->name('reservations.create');
    Route::get('/reservations/availability', [ReservationController::class, 'availability'])->name('reservations.availability');
    Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
 
    // paid track only (appointment | room_reservation) — opens up once
    // Staff sets status=verified. Generates the Payment Order Slip receipt.
    Route::get('/reservations/{appointment}/pay', [PaymentController::class, 'pay'])->name('reservations.pay');
    Route::post('/reservations/{appointment}/pay', [PaymentController::class, 'store'])->name('reservations.pay.store');
 
    // paid track only — Step 4: Receipt Confirmation, shown after payment is submitted.
    Route::get('/reservations/{appointment}/receipt-confirmation', [PaymentController::class, 'receiptConfirmation'])->name('reservations.receipt-confirmation');
    Route::post('/reservations/{appointment}/receipt-confirmation', [PaymentController::class, 'confirmReceipt'])->name('reservations.receipt-confirmation.store');
 
    // free_use track only — the "Attach Form" modal on the client dashboard
    // posts here once Staff sets status=verified.
    Route::post('/reservations/{appointment}/approval-form', [ApprovalFormController::class, 'store'])->name('reservations.approval-form.store');
 
    // Placeholder — the sidebar already links to this via routeIs('client.appointments*'),
    // build the actual appointments list/booking flow next and swap this in.
    // Route::get('/appointments', [ClientAppointmentController::class, 'index'])->name('appointments.index');
});


Route::middleware(['auth', 'role:staff'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('dashboard');
 
    Route::get('/verify', [VerifyController::class, 'index'])->name('verify.index');
    Route::post('/verify/{appointment}/verify', [VerifyController::class, 'verify'])->name('verify.do');
    Route::post('/verify/{appointment}/cancel', [VerifyController::class, 'cancel'])->name('verify.cancel');
    Route::post('/verify/{appointment}/reschedule', [VerifyController::class, 'reschedule'])->name('verify.reschedule');
});

Route::get('/', [LandingController::class, 'index'])->name('landing');

// route for login
Route::get('/account', [AuthController::class, 'show'])->name('account.show');
Route::post('/account/login', [AuthController::class, 'login'])->name('account.login');
Route::post('/account/register', [AuthController::class, 'register'])->name('account.register');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

 
