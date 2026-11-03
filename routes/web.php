<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PosterController;
use App\Http\Controllers\Admin\AppointmentController as AdminAppointmentController;
use App\Http\Controllers\Admin\AppointmentStaffController;
use App\Http\Controllers\Admin\AdminTypeController;
use App\Http\Controllers\Admin\OperationBuilding\EventReportController;
use App\Http\Controllers\Admin\OperationBuilding\DamagedItemController;
use App\Http\Controllers\Admin\OperationBuilding\RefundController;
use App\Http\Controllers\Admin\OperationBuilding\CancellationController;
use App\Http\Controllers\Admin\OperationBuilding\IngressTaxController;
use App\Http\Controllers\Admin\SportsActivity\InventoryOverviewController;
use App\Http\Controllers\Admin\SportsActivity\MaintenanceController;
use App\Http\Controllers\Admin\SportsActivity\ReportController as SportsReportController;
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

// Admin focus-area switcher (sidebar dropdown): changes the logged-in admin's own admin_type.
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/focus-area', [AdminTypeController::class, 'update'])->name('focus.switch');
});

// ---------------------------------------------------------------------------------------------
// Sprint 8 — OB Admin specialized panel (role:admin + admin_type:operation_building only).
// ---------------------------------------------------------------------------------------------
Route::middleware(['auth', 'role:admin', 'admin_type:operation_building'])->prefix('admin/ob')->name('admin.ob.')->group(function () {
    // Event reports (per-event view). /export must stay above /{appointment}.
    Route::get('/event-reports', [EventReportController::class, 'index'])->name('event-reports.index');
    Route::get('/event-reports/export', [EventReportController::class, 'export'])->name('event-reports.export');
    Route::get('/event-reports/{appointment}', [EventReportController::class, 'show'])->name('event-reports.show');

    // Damaged items — the authority-to-act side of staff incident reports.
    Route::get('/damaged-items', [DamagedItemController::class, 'index'])->name('damaged-items.index');
    Route::get('/damaged-items/{incident}', [DamagedItemController::class, 'show'])->name('damaged-items.show');
    Route::post('/damaged-items/{incident}/review', [DamagedItemController::class, 'startReview'])->name('damaged-items.review');
    Route::post('/damaged-items/{incident}/decisions', [DamagedItemController::class, 'storeDecision'])->name('damaged-items.decisions.store');
    Route::delete('/damaged-items/{incident}/decisions/{decision}', [DamagedItemController::class, 'destroyDecision'])->name('damaged-items.decisions.destroy');
    Route::post('/damaged-items/{incident}/resolve', [DamagedItemController::class, 'resolve'])->name('damaged-items.resolve');
    Route::post('/damaged-items/{incident}/reopen', [DamagedItemController::class, 'reopen'])->name('damaged-items.reopen');

    // Refunds: pending -> approved -> released (or rejected).
    Route::get('/refunds', [RefundController::class, 'index'])->name('refunds.index');
    Route::post('/refunds', [RefundController::class, 'store'])->name('refunds.store');
    Route::post('/refunds/{refund}/approve', [RefundController::class, 'approve'])->name('refunds.approve');
    Route::post('/refunds/{refund}/reject', [RefundController::class, 'reject'])->name('refunds.reject');
    Route::post('/refunds/{refund}/release', [RefundController::class, 'release'])->name('refunds.release');

    // Cancellations: history + cancel on a client's behalf.
    Route::get('/cancellations', [CancellationController::class, 'index'])->name('cancellations.index');
    Route::post('/cancellations/{appointment}', [CancellationController::class, 'cancel'])->name('cancellations.cancel');

    // Ingress fee / tax oversight (read-only).
    Route::get('/ingress-tax', [IngressTaxController::class, 'index'])->name('ingress.index');
    Route::get('/ingress-tax/export', [IngressTaxController::class, 'export'])->name('ingress.export');
});

// ---------------------------------------------------------------------------------------------
// Sprint 9 — Sports/Activity Admin specialized panel (role:admin + admin_type:sports_activity only).
// ---------------------------------------------------------------------------------------------
Route::middleware(['auth', 'role:admin', 'admin_type:sports_activity'])->prefix('admin/sa')->name('admin.sa.')->group(function () {
    // Inventory overview (admin-side view; staff enter items in Sprint 10).
    Route::get('/inventory', [InventoryOverviewController::class, 'index'])->name('inventory.index');

    // Maintenance overview + scheduling: scheduled -> in_progress -> completed (or cancelled).
    Route::get('/maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');
    Route::post('/maintenance', [MaintenanceController::class, 'store'])->name('maintenance.store');
    Route::put('/maintenance/{schedule}', [MaintenanceController::class, 'update'])->name('maintenance.update');
    Route::post('/maintenance/{schedule}/start', [MaintenanceController::class, 'start'])->name('maintenance.start');
    Route::post('/maintenance/{schedule}/complete', [MaintenanceController::class, 'complete'])->name('maintenance.complete');
    Route::post('/maintenance/{schedule}/cancel', [MaintenanceController::class, 'cancel'])->name('maintenance.cancel');

    // Staff-filed maintenance reports: mark in progress / resolve (scheduling one links the job to the report).
    Route::post('/maintenance/reports/{report}/progress', [MaintenanceController::class, 'progressReport'])->name('maintenance.reports.progress');
    Route::post('/maintenance/reports/{report}/resolve', [MaintenanceController::class, 'resolveReport'])->name('maintenance.reports.resolve');

    // Reports scoped to inventory/maintenance. /export must stay a distinct path.
    Route::get('/reports', [SportsReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [SportsReportController::class, 'export'])->name('reports.export');
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