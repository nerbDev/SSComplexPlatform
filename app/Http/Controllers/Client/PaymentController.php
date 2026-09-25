<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Services\FacilityPricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Handles the part of the flow that only opens up AFTER staff verifies a
 * reservation (Appointment::status === 'verified'):
 *   1. pay()/store()                     — client submits their GCash
 *      reference, system computes the charge, generates the official
 *      Payment Order Slip (receipt) PDF.
 *   2. receiptConfirmation()/confirmReceipt() — "Step 4": client reviews
 *      the generated receipt (optionally attaches their own proof of
 *      payment) and confirms, which hands the whole package to Admin for
 *      scheduling.
 *
 * REQUIRES: composer require barryvdh/laravel-dompdf
 */
class PaymentController extends Controller
{
    public function __construct(private FacilityPricingService $pricing)
    {
    }

    private function authorizeOwner(Appointment $appointment): void
    {
        abort_unless($appointment->user_id === Auth::id(), 403);
    }

    public function pay(Appointment $appointment): View|RedirectResponse
    {
        $this->authorizeOwner($appointment);

        if ($appointment->track !== 'paid') {
            return redirect()->route('client.dashboard')->with('status', 'Free-use bookings don\'t require payment.');
        }

        if ($appointment->status !== 'verified') {
            return redirect()->route('client.dashboard')
                ->with('status', 'This reservation isn\'t ready for payment yet.');
        }

        $facility = $appointment->facility;
        $amount = $this->pricing->computeAmount(
            $facility,
            $appointment->rate_type,
            $appointment->aircon,
            $appointment->start_time,
            $appointment->end_time
        );

        $downpaymentPercent = $appointment->booking_type === 'room_reservation'
            ? ReservationController::ROOM_RESERVATION_DOWNPAYMENT_PERCENT
            : ReservationController::APPOINTMENT_DOWNPAYMENT_PERCENT;

        $ingressBefore = 0;
        $ingressAfter = 0;
        $total = $amount + $ingressBefore + $ingressAfter;
        $downpayment = round($total * $downpaymentPercent / 100, 2);
        $balance = $total - $downpayment;

        return view('client.reservations.pay', [
            'appointment'        => $appointment,
            'facility'           => $facility,
            'amount'             => $amount,
            'ingressBefore'      => $ingressBefore,
            'ingressAfter'       => $ingressAfter,
            'total'              => $total,
            'downpaymentPercent' => $downpaymentPercent,
            'downpayment'        => $downpayment,
            'balance'            => $balance,
            'balanceDueDate'     => $appointment->booking_type === 'room_reservation'
                ? $appointment->event_date->copy()->subDays(ReservationController::ROOM_RESERVATION_BALANCE_DUE_DAYS_BEFORE)
                : null,
        ]);
    }

    public function store(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorizeOwner($appointment);

        if ($appointment->track !== 'paid') {
            return redirect()->route('client.dashboard')->with('status', 'Free-use bookings don\'t require payment.');
        }

        if ($appointment->status !== 'verified') {
            return redirect()->route('client.dashboard')->with('status', 'This reservation isn\'t ready for payment yet.');
        }

        $validator = Validator::make($request->all(), [
            'gcash_reference' => ['required', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $facility = $appointment->facility;
        $amount = $this->pricing->computeAmount(
            $facility,
            $appointment->rate_type,
            $appointment->aircon,
            $appointment->start_time,
            $appointment->end_time
        );

        $downpaymentPercent = $appointment->booking_type === 'room_reservation'
            ? ReservationController::ROOM_RESERVATION_DOWNPAYMENT_PERCENT
            : ReservationController::APPOINTMENT_DOWNPAYMENT_PERCENT;

        $ingressBefore = 0;
        $ingressAfter = 0;
        $total = $amount + $ingressBefore + $ingressAfter;
        $downpayment = round($total * $downpaymentPercent / 100, 2);
        $balance = $total - $downpayment;
        $balanceDueDate = $appointment->booking_type === 'room_reservation'
            ? $appointment->event_date->copy()->subDays(ReservationController::ROOM_RESERVATION_BALANCE_DUE_DAYS_BEFORE)
            : null;

        $payment = DB::transaction(function () use (
            $appointment, $facility, $amount, $ingressBefore, $ingressAfter, $total,
            $downpaymentPercent, $downpayment, $balance, $balanceDueDate, $request
        ) {
            $payment = Payment::create([
                'appointment_id'              => $appointment->id,
                'base_amount'                 => $amount,
                'tax_amount'                  => 0,
                'total_amount'                => $total,
                'downpayment_percent'         => $downpaymentPercent,
                'downpayment_amount'          => $downpayment,
                'balance_amount'              => $balance,
                'balance_due_date'            => $balanceDueDate,
                'ingress_before'              => $ingressBefore,
                'ingress_after'               => $ingressAfter,
                'cancellation_refund_percent' => ReservationController::CANCELLATION_REFUND_PERCENT,
                'gcash_reference'             => $request->input('gcash_reference'),
                'status'                      => 'submitted', // client says they paid; staff/admin still verifies the actual GCash transaction
            ]);

            // generate the official Payment Order Slip (receipt) PDF
            $pdf = Pdf::loadView('client.reservations.receipt-pdf', [
                'appointment' => $appointment,
                'facility'    => $facility,
                'payment'     => $payment,
            ]);

            $receiptPath = "receipts/payment-order-slip-{$appointment->id}.pdf";
            Storage::disk('public')->put($receiptPath, $pdf->output());
            $payment->update(['receipt_path' => $receiptPath]);

            $appointment->update(['status' => 'payment_submitted']);

            AuditLog::record('payment.submitted', $appointment, "GCash reference {$request->input('gcash_reference')} submitted, receipt generated");

            return $payment;
        });

        return redirect()
            ->route('client.reservations.receipt-confirmation', $appointment)
            ->with('status', 'Payment submitted. Your Payment Order Slip has been generated below.');
    }

    public function receiptConfirmation(Appointment $appointment): View|RedirectResponse
    {
        $this->authorizeOwner($appointment);

        if ($appointment->status !== 'payment_submitted') {
            return redirect()->route('client.dashboard')
                ->with('status', 'There\'s no receipt awaiting confirmation for this reservation.');
        }

        return view('client.reservations.receipt-confirmation', [
            'appointment' => $appointment,
            'payment'     => $appointment->payment,
        ]);
    }

    public function confirmReceipt(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorizeOwner($appointment);

        if ($appointment->status !== 'payment_submitted') {
            return redirect()->route('client.dashboard')->with('status', 'There\'s no receipt awaiting confirmation for this reservation.');
        }

        $validator = Validator::make($request->all(), [
            'proof_of_payment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'client_notes'     => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $payment = $appointment->payment;

        $proofPath = $payment->proof_of_payment_path;
        if ($request->hasFile('proof_of_payment')) {
            $proofPath = $request->file('proof_of_payment')->store('proof-of-payment', 'public');
        }

        $payment->update([
            'proof_of_payment_path' => $proofPath,
            'client_notes'          => $request->input('client_notes'),
            'receipt_confirmed_at'  => now(),
        ]);

        $appointment->update(['status' => 'receipt_confirmed']);

        AuditLog::record('receipt.confirmed', $appointment, 'Client confirmed receipt — ready for Admin scheduling');

        return redirect()
            ->route('client.dashboard')
            ->with('status', 'Receipt confirmed. Your reservation is now with the Admin for scheduling.');
    }
}