<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PosterController;
use App\Http\Controllers\Admin\AppointmentController as AdminAppointmentController;
use App\Http\Controllers\Admin\AppointmentStaffController;
use App\Http\Controllers\Client\DashboardController as ClientDashboardController;
use App\Http\Controllers\Client\ReservationController;
use App\Http\Controllers\Client\PaymentController;
use App\Http\Controllers\Client\ApprovalFormController;
use App\Http\Controllers\Client\MyAppointmentController;
use App\Http\Controllers\Client\RescheduleController;
use App\Http\Controllers\Staff\DashboardController as StaffDashboardController;
use App\Http\Controllers\Staff\VerifyController;
use App\Http\Controllers\Staff\AssignmentController as StaffAssignmentController;

// route to view admin dashboard
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // route to get posters view
    Route::resource('posters', PosterController::class)
    ->except(['show', 'create', 'edit']); // index, store, update, destroy — edit/add happen in modals on the index page

    // Appointment monitor: list every status, Review (modal), Schedule -> scheduled list.
    // /scheduled must stay above any {appointment} route.
    Route::get('/appointments', [AdminAppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/scheduled', [AdminAppointmentController::class, 'scheduled'])->name('appointments.scheduled');
    Route::post('/appointments/{appointment}/schedule', [AdminAppointmentController::class, 'schedule'])->name('appointments.schedule');

    // Staff assignment for status=scheduled appointments (assign / cancel / edit any time).
    Route::get('/appointments/{appointment}/staff', [AppointmentStaffController::class, 'index'])->name('appointments.staff.index');
    Route::post('/appointments/{appointment}/staff/{staff}', [AppointmentStaffController::class, 'assign'])->name('appointments.staff.assign');
    Route::delete('/appointments/{appointment}/staff/{staff}', [AppointmentStaffController::class, 'unassign'])->name('appointments.staff.unassign');
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
 
    // My Appointments: list + Cancel (no refund). The sidebar link matches routeIs('client.appointments*').
    Route::get('/appointments', [MyAppointmentController::class, 'index'])->name('appointments.index');
    Route::post('/appointments/{appointment}/cancel', [MyAppointmentController::class, 'cancel'])->name('appointments.cancel');

    // Reschedule: pick new slot -> pay 10% fee (paid track) -> confirm receipt -> new slot applied.
    Route::get('/appointments/{appointment}/reschedule', [RescheduleController::class, 'create'])->name('appointments.reschedule');
    Route::get('/appointments/{appointment}/reschedule/availability', [RescheduleController::class, 'availability'])->name('appointments.reschedule.availability');
    Route::post('/appointments/{appointment}/reschedule', [RescheduleController::class, 'store'])->name('appointments.reschedule.store');
    Route::get('/appointments/{appointment}/reschedule/pay', [RescheduleController::class, 'pay'])->name('appointments.reschedule.pay');
    Route::post('/appointments/{appointment}/reschedule/pay', [RescheduleController::class, 'payStore'])->name('appointments.reschedule.pay.store');
    Route::get('/appointments/{appointment}/reschedule/receipt-confirmation', [RescheduleController::class, 'receipt'])->name('appointments.reschedule.receipt');
    Route::post('/appointments/{appointment}/reschedule/receipt-confirmation', [RescheduleController::class, 'receiptStore'])->name('appointments.reschedule.receipt.store');
    Route::post('/appointments/{appointment}/reschedule/abandon', [RescheduleController::class, 'abandon'])->name('appointments.reschedule.abandon');
});


Route::middleware(['auth', 'role:staff'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('dashboard');
 
    Route::get('/verify', [VerifyController::class, 'index'])->name('verify.index');
    Route::post('/verify/{appointment}/verify', [VerifyController::class, 'verify'])->name('verify.do');
    Route::post('/verify/{appointment}/cancel', [VerifyController::class, 'cancel'])->name('verify.cancel');
    Route::post('/verify/{appointment}/reschedule', [VerifyController::class, 'reschedule'])->name('verify.reschedule');

    // Appointments this staff member was assigned to (+ who else is on the team).
    Route::get('/assignments', [StaffAssignmentController::class, 'index'])->name('assignments.index');
});

Route::get('/', [LandingController::class, 'index'])->name('landing');

// route for login
Route::get('/account', [AuthController::class, 'show'])->name('account.show');
Route::post('/account/login', [AuthController::class, 'login'])->name('account.login');
Route::post('/account/register', [AuthController::class, 'register'])->name('account.register');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');