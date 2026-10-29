<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\ReschedulingFee;
use App\Services\FacilityPricingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * Client rescheduling.
 *
 * Paid track:
 *   1. create()/store()                  pick a new date + start time (same length as the original slot)
 *                                        -> a rescheduling_fees row is created as `pending` (10% of the booking value)
 *   2. pay()/payStore()                  GCash reference for the 10% fee -> Payment Order Slip PDF -> `submitted`
 *   3. receipt()/receiptStore()          client confirms the receipt -> fee `paid`, the NEW slot is applied and the
 *                                        appointment goes back to Admin for scheduling (receipt_confirmed), with any
 *                                        assigned staff released.
 * Free-use track: no fee (`waived`); the new slot is applied right away and the appointment returns to Admin
 *                 (form_submitted) for re-scheduling.
 *
 * The ORIGINAL slot stays reserved the whole time; it only moves once the fee is confirmed.
 * One open request (pending/submitted) per appointment — store() updates it instead of creating another.
 */
class RescheduleController extends Controller
{
    private const FEE_OPEN = ['pending', 'submitted'];

    /** Which appointment statuses may be rescheduled, per track. Adjust here if your flow differs. */
    private const ELIGIBLE = [
        'paid'     => ['receipt_confirmed', 'scheduled'],
        'free_use' => ['form_submitted', 'scheduled'],
    ];

    public function __construct(private FacilityPricingService $pricing)
    {
    }

    public static function isEligible(Appointment $a): bool
    {
        return in_array($a->status, self::ELIGIBLE[$a->track] ?? [], true)
            && $a->event_date
            && $a->event_date->gte(Carbon::today());
    }

    // ------------------------------------------------------------------ step 1: pick the new slot

    public function create(Appointment $appointment): View|RedirectResponse
    {
        if ($redirect = $this->guard($appointment)) {
            return $redirect;
        }

        $isFree  = $appointment->track === 'free_use';
        $percent = ReservationController::RESCHEDULING_FEE_PERCENT;
        $total   = $isFree ? 0 : $this->totalValue($appointment);

        return view('client.appointments.reschedule', [
            'appointment' => $appointment->load('facility'),
            'openFee'     => $this->openFee($appointment),
            'isFree'      => $isFree,
            'percent'     => $percent,
            'feeAmount'   => round($total * $percent / 100, 2),
            'duration'    => $this->durationMinutes($appointment),
        ]);
    }

    public function availability(Request $request, Appointment $appointment): JsonResponse
    {
        abort_unless($appointment->user_id === Auth::id(), 403);
        $request->validate(['date' => ['required', 'date']]);

        $date = $request->string('date')->toString();

        $busy = Appointment::forFacilityOnDate($appointment->facility_id, $date)
            ->whereIn('status', ReservationController::ACTIVE_STATUSES)
            ->where('id', '!=', $appointment->id)
            ->orderBy('start_time')
            ->get(['start_time', 'end_time'])
            ->map(fn($a) => ['start' => substr($a->start_time, 0, 5), 'end' => substr($a->end_time, 0, 5)]);

        $blocks = $this->blocks($appointment->facility_id, $date);
        foreach ($blocks['ranges'] as $r) {
            $busy->push($r);
        }

        return response()->json([
            'whole_day_blocked' => $blocks['whole_day'],
            'block_reason'      => $blocks['reason'],
            'busy_ranges'       => $busy->values(),
        ]);
    }

    public function store(Request $request, Appointment $appointment): RedirectResponse
    {
        if ($redirect = $this->guard($appointment)) {
            return $redirect;
        }

        $validator = Validator::make($request->all(), [
            'new_date'   => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data  = $validator->validated();
        $start = Carbon::createFromFormat('H:i', $data['start_time']);
        $end   = $start->copy()->addMinutes($this->durationMinutes($appointment));

        if ($end->toDateString() !== $start->toDateString()) {
            return back()->withErrors(['start_time' => 'That start time would run past midnight. Please pick an earlier time.'])->withInput();
        }

        $newDate  = $data['new_date'];
        $newStart = $start->format('H:i:s');
        $newEnd   = $end->format('H:i:s');

        if ($appointment->event_date->toDateString() === $newDate && substr($appointment->start_time, 0, 5) === $data['start_time']) {
            return back()->withErrors(['new_date' => 'That is already your current date and time.'])->withInput();
        }

        if ($problem = $this->slotProblem($appointment, $newDate, $newStart, $newEnd)) {
            return back()->withErrors(['new_date' => $problem])->withInput();
        }

        $isFree  = $appointment->track === 'free_use';
        $openFee = $this->openFee($appointment);

        $slot = [
            'new_date'       => $newDate,
            'new_start_time' => $newStart,
            'new_end_time'   => $newEnd,
        ];

        if ($isFree) {
            DB::transaction(function () use ($appointment, $slot) {
                $fee = ReschedulingFee::create($slot + $this->originalSlot($appointment) + [
                    'appointment_id' => $appointment->id,
                    'fee_percent'    => 0,
                    'base_total'     => 0,
                    'fee_amount'     => 0,
                    'status'         => 'waived',
                    'confirmed_at'   => now(),
                ]);
                $this->applyReschedule($appointment, $fee);
            });

            return redirect()
                ->route('client.appointments.index')
                ->with('status', 'Your free-use appointment was rescheduled. The Admin will schedule it again for the new date.');
        }

        if ($openFee) {
            $openFee->update($slot);
            AuditLog::record('client.reschedule.slot_changed', $appointment, "Client changed requested reschedule slot to {$newDate}");

            return redirect()->route(
                $openFee->status === 'pending' ? 'client.appointments.reschedule.pay' : 'client.appointments.reschedule.receipt',
                $appointment
            )->with('status', 'Your new date was updated.');
        }

        $percent = ReservationController::RESCHEDULING_FEE_PERCENT;
        $total   = $this->totalValue($appointment);

        ReschedulingFee::create($slot + $this->originalSlot($appointment) + [
            'appointment_id' => $appointment->id,
            'fee_percent'    => $percent,
            'base_total'     => $total,
            'fee_amount'     => round($total * $percent / 100, 2),
            'status'         => 'pending',
        ]);

        AuditLog::record('client.reschedule.requested', $appointment, "Client requested reschedule to {$newDate}; {$percent}% fee pending payment");

        return redirect()
            ->route('client.appointments.reschedule.pay', $appointment)
            ->with('status', 'New date saved. Pay the rescheduling fee to complete the change.');
    }

    // ------------------------------------------------------------------ step 2: pay the fee

    public function pay(Appointment $appointment): View|RedirectResponse
    {
        if ($redirect = $this->guard($appointment)) {
            return $redirect;
        }

        $fee = $this->openFee($appointment);

        if (!$fee) {
            return redirect()->route('client.appointments.reschedule', $appointment);
        }
        if ($fee->status !== 'pending') {
            return redirect()->route('client.appointments.reschedule.receipt', $appointment);
        }

        return view('client.appointments.reschedule-pay', [
            'appointment' => $appointment->load('facility'),
            'facility'    => $appointment->facility,
            'fee'         => $fee,
        ]);
    }

    public function payStore(Request $request, Appointment $appointment): RedirectResponse
    {
        if ($redirect = $this->guard($appointment)) {
            return $redirect;
        }

        $fee = $this->openFee($appointment);

        if (!$fee || $fee->status !== 'pending') {
            return redirect()->route('client.appointments.reschedule', $appointment);
        }

        $validator = Validator::make($request->all(), [
            'gcash_reference' => ['required', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $payee     = Auth::user();
        $payeeName = trim(($payee->first_name ?? '') . ' ' . ($payee->last_name ?? ''));

        DB::transaction(function () use ($appointment, $fee, $request, $payeeName) {
            $fee->update([
                'gcash_reference' => $request->input('gcash_reference'),
                'status'          => 'submitted',
                'submitted_at'    => now(),
            ]);

            $pdf = Pdf::loadView('client.appointments.reschedule-receipt-pdf', [
                'appointment' => $appointment,
                'facility'    => $appointment->facility,
                'fee'         => $fee->fresh(),
                'payeeName'   => $payeeName,
            ]);

            $path = "receipts/reschedule-fee-slip-{$fee->id}.pdf";
            Storage::disk('public')->put($path, $pdf->output());
            $fee->update(['receipt_path' => $path]);

            AuditLog::record('client.reschedule.fee_submitted', $appointment, "GCash reference {$request->input('gcash_reference')} submitted for rescheduling fee, slip generated");
        });

        return redirect()
            ->route('client.appointments.reschedule.receipt', $appointment)
            ->with('status', 'Payment submitted. Your Payment Order Slip has been generated below.');
    }

    // ------------------------------------------------------------------ step 3: confirm receipt -> apply

    public function receipt(Appointment $appointment): View|RedirectResponse
    {
        if ($redirect = $this->guard($appointment)) {
            return $redirect;
        }

        $fee = $this->openFee($appointment);

        if (!$fee || $fee->status !== 'submitted') {
            return redirect()->route('client.appointments.index')->with('error', 'There\'s no rescheduling receipt awaiting confirmation.');
        }

        return view('client.appointments.reschedule-receipt', [
            'appointment' => $appointment->load('facility'),
            'fee'         => $fee,
        ]);
    }

    public function receiptStore(Request $request, Appointment $appointment): RedirectResponse
    {
        if ($redirect = $this->guard($appointment)) {
            return $redirect;
        }

        $fee = $this->openFee($appointment);

        if (!$fee || $fee->status !== 'submitted') {
            return redirect()->route('client.appointments.index')->with('error', 'There\'s no rescheduling receipt awaiting confirmation.');
        }

        $validator = Validator::make($request->all(), [
            'proof_of_payment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'client_notes'     => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        // The slot may have been taken while the client was paying — the fee payment is kept, they just pick again.
        $problem = $this->slotProblem($appointment, $fee->new_date->toDateString(), $fee->new_start_time, $fee->new_end_time);
        if ($problem) {
            return redirect()
                ->route('client.appointments.reschedule', $appointment)
                ->with('error', $problem . ' Your fee payment is saved — pick another date below, no need to pay again.');
        }

        DB::transaction(function () use ($request, $appointment, $fee) {
            $proofPath = $fee->proof_of_payment_path;
            if ($request->hasFile('proof_of_payment')) {
                $proofPath = $request->file('proof_of_payment')->store('proof-of-payment', 'public');
            }

            $fee->update([
                'proof_of_payment_path' => $proofPath,
                'client_notes'          => $request->input('client_notes'),
                'confirmed_at'          => now(),
                'status'                => 'paid',
            ]);

            $this->applyReschedule($appointment, $fee);
        });

        return redirect()
            ->route('client.appointments.index')
            ->with('status', 'Rescheduling fee confirmed. Your appointment moved to the new date and is back with the Admin for scheduling.');
    }

    public function abandon(Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->user_id === Auth::id(), 403);

        $fee = $this->openFee($appointment);

        if (!$fee || $fee->status !== 'pending') {
            return redirect()->route('client.appointments.index')
                ->with('error', 'Only an unpaid reschedule request can be dropped.');
        }

        $fee->update(['status' => 'cancelled']);
        AuditLog::record('client.reschedule.abandoned', $appointment, 'Client dropped an unpaid reschedule request');

        return redirect()->route('client.appointments.index')->with('status', 'Reschedule request dropped. Your original date is unchanged.');
    }

    // ------------------------------------------------------------------ helpers

    /** 403 for non-owners; redirect back to the list when the appointment can't be rescheduled (anymore). */
    private function guard(Appointment $appointment): ?RedirectResponse
    {
        abort_unless($appointment->user_id === Auth::id(), 403);

        if (!self::isEligible($appointment)) {
            return redirect()->route('client.appointments.index')
                ->with('error', 'This appointment can\'t be rescheduled right now.');
        }

        return null;
    }

    private function openFee(Appointment $appointment): ?ReschedulingFee
    {
        return $appointment->reschedulingFees()->whereIn('status', self::FEE_OPEN)->latest('id')->first();
    }

    private function originalSlot(Appointment $a): array
    {
        return [
            'original_date'       => $a->event_date->toDateString(),
            'original_start_time' => $a->start_time,
            'original_end_time'   => $a->end_time,
        ];
    }

    private function durationMinutes(Appointment $a): int
    {
        return (int) Carbon::parse($a->start_time)->diffInMinutes(Carbon::parse($a->end_time));
    }

    /** The booking's total value — what the 10% is calculated on. */
    private function totalValue(Appointment $a): float
    {
        if ($a->payment && $a->payment->total_amount !== null) {
            return (float) $a->payment->total_amount;
        }

        $amount = $this->pricing->computeAmount($a->facility, $a->rate_type, $a->aircon, $a->start_time, $a->end_time);

        return (float) ($amount ?? 0);
    }

    /** Whole-day / time-range blocks from unavailable_slots (same table the booking wizard uses). */
    private function blocks(int $facilityId, string $date): array
    {
        $out = ['whole_day' => false, 'reason' => null, 'ranges' => []];

        if (!Schema::hasTable('unavailable_slots')) {
            return $out;
        }

        $rows = DB::table('unavailable_slots')
            ->whereDate('date', $date)
            ->where(function ($q) use ($facilityId) {
                $q->whereNull('unit')->orWhere('unit', function ($sub) use ($facilityId) {
                    $sub->select('name')->from('facilities')->where('id', $facilityId);
                });
            })
            ->get();

        foreach ($rows as $row) {
            if (is_null($row->start_time) && is_null($row->end_time)) {
                $out['whole_day'] = true;
                $out['reason']    = $row->reason ?? 'Not available on this date.';
            } else {
                $out['ranges'][] = ['start' => substr($row->start_time, 0, 5), 'end' => substr($row->end_time, 0, 5)];
            }
        }

        return $out;
    }

    /** Returns a human-readable reason the slot can't be used, or null when it's free. */
    private function slotProblem(Appointment $a, string $date, string $start, string $end): ?string
    {
        $taken = Appointment::forFacilityOnDate($a->facility_id, $date)
            ->whereIn('status', ReservationController::ACTIVE_STATUSES)
            ->where('id', '!=', $a->id)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->exists();

        if ($taken) {
            return 'That facility is already booked for an overlapping time on the new date.';
        }

        $blocks = $this->blocks($a->facility_id, $date);

        if ($blocks['whole_day']) {
            return $blocks['reason'] ?: 'The facility is not available on that date.';
        }

        foreach ($blocks['ranges'] as $r) {
            if ($r['start'] < substr($end, 0, 5) && $r['end'] > substr($start, 0, 5)) {
                return 'The facility is unavailable during part of that time.';
            }
        }

        return null;
    }

    /**
     * Moves the appointment to the fee's new slot and sends it back to the Admin queue
     * (paid -> receipt_confirmed, free_use -> form_submitted). Any assigned staff are released
     * because the date changed. Call inside a transaction.
     */
    private function applyReschedule(Appointment $a, ReschedulingFee $fee): void
    {
        $oldDate = $a->event_date->format('M j, Y');
        $oldTime = Carbon::parse($a->start_time)->format('g:i A');
        $newDate = $fee->new_date->format('M j, Y');
        $newTime = Carbon::parse($fee->new_start_time)->format('g:i A');
        $from    = $a->status;

        $a->update([
            'event_date' => $fee->new_date->toDateString(),
            'start_time' => $fee->new_start_time,
            'end_time'   => $fee->new_end_time,
            'status'     => $a->track === 'free_use' ? 'form_submitted' : 'receipt_confirmed',
            'notes'      => trim($a->notes . " | Rescheduled by client from {$oldDate} {$oldTime} to {$newDate} {$newTime}"),
        ]);

        DB::table('staff_assignments')
            ->where('appointment_id', $a->id)
            ->where('status', 'assigned')
            ->update(['status' => 'cancelled', 'updated_at' => now()]);

        AuditLog::record(
            'client.appointment.rescheduled',
            $a,
            "Client rescheduled \"{$a->activity_title}\" from {$oldDate} {$oldTime} to {$newDate} {$newTime} (was {$from}); fee {$fee->status}"
        );
    }
}