<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\CommitmentAcknowledgment;
use App\Models\Facility;
use App\Services\FacilityPricingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ReservationController extends Controller
{
    /**
     * Booking-type business rules. Payment itself no longer happens here —
     * see PaymentController — these constants are still the source of
     * truth for the numbers shown in Step 2's preview and the commitment
     * form's wording, and PaymentController reads the same ones.
     *
     * A third type was added: 'free_use'. It shares Steps 1–3 with
     * appointment/room_reservation but skips billing entirely — Step 2
     * only asks for a room, no rate/aircon/amount. It maps to
     * `track = 'free_use'` (the other two both use `track = 'paid'`), and
     * instead of paying, the client later attaches an approval form via
     * ApprovalFormController once Staff verifies it.
     */
    public const APPOINTMENT_DOWNPAYMENT_PERCENT = 100;
    public const ROOM_RESERVATION_DOWNPAYMENT_PERCENT = 40;
    public const ROOM_RESERVATION_BALANCE_DUE_DAYS_BEFORE = 3;
    public const CANCELLATION_REFUND_PERCENT = 0;
    public const RESCHEDULING_FEE_PERCENT = 10;
    public const COMMITMENT_FORM_VERSION = 'v1';

    /** Appointment statuses that count as "occupying" a slot for conflict checks. */
    public const ACTIVE_STATUSES = ['pending', 'verified', 'payment_submitted', 'receipt_confirmed', 'form_submitted', 'scheduled'];

    public function __construct(private FacilityPricingService $pricing)
    {
    }

    public function create(): View
    {
        $facilities = Facility::with('rates')->active()->orderBy('name')->get()->map(function (Facility $facility) {
            return [
                'id'       => $facility->id,
                'name'     => $facility->name,
                'capacity' => $facility->capacity,
                'location' => $facility->location,
                'image'    => $facility->image_path ? asset($facility->image_path) : null,
                'schedule' => $facility->rate_schedule,
            ];
        });

        return view('client.reservations.create', [
            'facilities'                        => $facilities,
            'appointmentDownpaymentPercent'     => self::APPOINTMENT_DOWNPAYMENT_PERCENT,
            'roomReservationDownpaymentPercent' => self::ROOM_RESERVATION_DOWNPAYMENT_PERCENT,
            'balanceDueDaysBefore'              => self::ROOM_RESERVATION_BALANCE_DUE_DAYS_BEFORE,
            'cancellationRefundPercent'         => self::CANCELLATION_REFUND_PERCENT,
            'reschedulingFeePercent'            => self::RESCHEDULING_FEE_PERCENT,
            'commitmentFormVersion'             => self::COMMITMENT_FORM_VERSION,
        ]);
    }

    /**
     * AJAX endpoint the wizard calls whenever the client picks/changes a
     * facility or date in Step 2 — returns already-booked time ranges and
     * whether the whole day is blocked.
     */
    public function availability(Request $request): JsonResponse
    {
        $request->validate([
            'facility_id' => ['required', 'exists:facilities,id'],
            'date'        => ['required', 'date'],
        ]);

        $facilityId = $request->integer('facility_id');
        $date = $request->string('date');

        $busyRanges = Appointment::forFacilityOnDate($facilityId, $date)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->orderBy('start_time')
            ->get(['start_time', 'end_time'])
            ->map(fn($a) => [
                'start' => substr($a->start_time, 0, 5),
                'end'   => substr($a->end_time, 0, 5),
            ]);

        $wholeDayBlocked = false;
        $blockReason = null;

        if (Schema::hasTable('unavailable_slots')) {
            $blocks = DB::table('unavailable_slots')
                ->whereDate('date', $date)
                ->where(function ($q) use ($facilityId) {
                    $q->whereNull('unit')->orWhere('unit', function ($sub) use ($facilityId) {
                        $sub->select('name')->from('facilities')->where('id', $facilityId);
                    });
                })
                ->get();

            foreach ($blocks as $block) {
                if (is_null($block->start_time) && is_null($block->end_time)) {
                    $wholeDayBlocked = true;
                    $blockReason = $block->reason ?? 'Not available on this date.';
                } else {
                    $busyRanges->push([
                        'start' => substr($block->start_time, 0, 5),
                        'end'   => substr($block->end_time, 0, 5),
                    ]);
                }
            }
        }

        return response()->json([
            'whole_day_blocked' => $wholeDayBlocked,
            'block_reason'      => $blockReason,
            'busy_ranges'       => $busyRanges->values(),
        ]);
    }

    /**
     * Submits Steps 1–3 (Person Details, Room & Billing, Commitment Form).
     * Creates the Appointment as `status = pending` and stops there.
     * For appointment/room_reservation, payment happens later via
     * PaymentController. For free_use, no Payment row is ever created —
     * the client instead attaches an approval form later via
     * ApprovalFormController, both gated behind Staff setting `verified`.
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'booking_type'       => ['required', 'in:appointment,room_reservation,free_use'],
            'full_name'          => ['required', 'string', 'max:255'],
            'contact_number'     => ['required', 'string', 'max:20'],
            'barangay'           => ['required', 'string', 'max:100'],
            'activity_title'     => ['required', 'string', 'max:255'],
            'expected_attendees' => ['nullable', 'integer', 'min:1'],

            'facility_id' => ['required', 'exists:facilities,id'],
            'aircon'      => ['nullable', 'boolean'],
            // free_use skips billing entirely — no rate mode to pick
            'rate_type'   => ['required_unless:booking_type,free_use', 'nullable', 'in:first_3_hours,succeeding_hour,whole_day,per_hour'],
            'event_date'  => ['required', 'date', 'after_or_equal:today'],
            'start_time'  => ['required', 'date_format:H:i'],
            'end_time'    => ['required', 'date_format:H:i', 'after:start_time'],

            'commitment_acknowledged' => ['required', 'accepted'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $isFreeUse = $data['booking_type'] === 'free_use';

        if ($data['booking_type'] === 'room_reservation') {
            $minDate = Carbon::today()->addDays(self::ROOM_RESERVATION_BALANCE_DUE_DAYS_BEFORE + 1)->toDateString();
            if ($data['event_date'] < $minDate) {
                return back()
                    ->withErrors(['event_date' => 'Room Reservation needs the balance settled ' . self::ROOM_RESERVATION_BALANCE_DUE_DAYS_BEFORE . ' days before the event — pick a date further out, or switch to Appointment for full payment now.'])
                    ->withInput();
            }
        }

        $facility = Facility::with('rates')->findOrFail($data['facility_id']);

        $overlap = Appointment::forFacilityOnDate($facility->id, $data['event_date'])
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->where(function ($q) use ($data) {
                $q->where('start_time', '<', $data['end_time'])
                  ->where('end_time', '>', $data['start_time']);
            })
            ->exists();

        if ($overlap) {
            return back()
                ->withErrors(['event_date' => 'That facility is already booked for an overlapping time slot. Please pick a different date or time.'])
                ->withInput();
        }

        // free_use has no billing at all — skip pricing/rate validation entirely
        if (!$isFreeUse) {
            $aircon = array_key_exists('aircon', $data) ? (bool) $data['aircon'] : null;
            $amount = $this->pricing->computeAmount($facility, $data['rate_type'], $aircon, $data['start_time'], $data['end_time']);

            if ($amount === null) {
                return back()
                    ->withErrors(['rate_type' => 'That rate option isn\'t available for the selected facility.'])
                    ->withInput();
            }
        }

        $appointment = DB::transaction(function () use ($data, $facility, $request, $isFreeUse) {
            $appointment = Appointment::create([
                'user_id'            => Auth::id(),
                'facility_id'        => $facility->id,
                'activity_title'     => $data['activity_title'],
                'track'              => $isFreeUse ? 'free_use' : 'paid',
                'booking_type'       => $data['booking_type'],
                'rate_type'          => $isFreeUse ? null : $data['rate_type'],
                'aircon'             => $isFreeUse ? null : ($data['aircon'] ?? null),
                'status'             => 'pending',
                'event_date'         => $data['event_date'],
                'start_time'         => $data['start_time'],
                'end_time'           => $data['end_time'],
                'expected_attendees' => $data['expected_attendees'] ?? null,
                'notes'              => "Reserved by: {$data['full_name']} ({$data['contact_number']}), {$data['barangay']}",
            ]);

            CommitmentAcknowledgment::create([
                'appointment_id'  => $appointment->id,
                'user_id'         => Auth::id(),
                'form_version'    => self::COMMITMENT_FORM_VERSION,
                'ip_address'      => $request->ip(),
                'acknowledged_at' => now(),
            ]);

            AuditLog::record('reservation.submitted', $appointment, "{$data['booking_type']} submitted for {$facility->name}, pending staff verification");

            return $appointment;
        });

        $followUp = $isFreeUse
            ? 'Staff will review it, then you\'ll be asked to attach your approval form.'
            : 'Staff will review it before payment opens up.';

        return redirect()
            ->route('client.dashboard')
            ->with('status', ucfirst(str_replace('_', ' ', $data['booking_type'])) . " submitted for {$facility->name} on {$appointment->event_date->format('M j, Y')}. {$followUp}");
    }
}