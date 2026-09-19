<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\CommitmentAcknowledgment;
use App\Models\Facility;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ReservationController extends Controller
{
    /** Business rules for the Reservation flow (paid track). Rescheduling
     *  fee itself is applied later, when a reservation actually gets
     *  rescheduled (Sprint 6) — these are just the constants both the form
     *  and that future flow read from. */
    public const DOWNPAYMENT_PERCENT = 40;
    public const CANCELLATION_REFUND_PERCENT = 0;
    public const RESCHEDULING_FEE_PERCENT = 10;
    public const COMMITMENT_FORM_VERSION = 'v1';

    public function create(): View
    {
        $facilities = Facility::with('rates')->active()->orderBy('name')->get()->map(function (Facility $facility) {
            return [
                'id'       => $facility->id,
                'name'     => $facility->name,
                'capacity' => $facility->capacity,
                'schedule' => $facility->rate_schedule, // e.g. ['first_3_hours' => ['default' => 5000], ...]
            ];
        });

        return view('client.reservations.create', [
            'facilities'               => $facilities,
            'downpaymentPercent'       => self::DOWNPAYMENT_PERCENT,
            'cancellationRefundPercent'=> self::CANCELLATION_REFUND_PERCENT,
            'reschedulingFeePercent'   => self::RESCHEDULING_FEE_PERCENT,
            'commitmentFormVersion'    => self::COMMITMENT_FORM_VERSION,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            // Step 1 — person details
            'full_name'          => ['required', 'string', 'max:255'],
            'contact_number'     => ['required', 'string', 'max:20'],
            'barangay'           => ['required', 'string', 'max:100'],
            'activity_title'     => ['required', 'string', 'max:255'],
            'expected_attendees' => ['nullable', 'integer', 'min:1'],

            // Step 2 — room & billing
            'facility_id' => ['required', 'exists:facilities,id'],
            'aircon'      => ['nullable', 'boolean'],
            'rate_type'   => ['required', 'in:first_3_hours,succeeding_hour,whole_day,per_hour'],
            'event_date'  => ['required', 'date', 'after_or_equal:today'],
            'start_time'  => ['required', 'date_format:H:i'],
            'end_time'    => ['required', 'date_format:H:i', 'after:start_time'],

            // Step 3 — payment
            'gcash_reference' => ['nullable', 'string', 'max:100'],

            // Step 4 — commitment form
            'commitment_acknowledged' => ['required', 'accepted'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        $facility = Facility::with('rates')->findOrFail($data['facility_id']);

        // conflict-free scheduling check — same facility, same date, overlapping time
        $overlap = Appointment::forFacilityOnDate($facility->id, $data['event_date'])
            ->whereIn('status', ['pending', 'verified', 'confirmed', 'scheduled', 'paid', 'admin_verified'])
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

        $amount = $this->computeAmount($facility, $data);

        if ($amount === null) {
            return back()
                ->withErrors(['rate_type' => 'That rate option isn\'t available for the selected facility.'])
                ->withInput();
        }

        $ingressBefore = 0; // not yet priced — see Payment migration note
        $ingressAfter  = 0;
        $total         = $amount + $ingressBefore + $ingressAfter;
        $downpayment   = round($total * self::DOWNPAYMENT_PERCENT / 100, 2);
        $balance       = $total - $downpayment;

        $appointment = DB::transaction(function () use ($data, $facility, $amount, $ingressBefore, $ingressAfter, $total, $downpayment, $balance, $request) {
            $appointment = Appointment::create([
                'user_id'             => Auth::id(),
                'facility_id'         => $facility->id,
                'activity_title'      => $data['activity_title'],
                'track'               => 'paid',
                'status'              => 'pending',
                'event_date'          => $data['event_date'],
                'start_time'          => $data['start_time'],
                'end_time'            => $data['end_time'],
                'expected_attendees'  => $data['expected_attendees'] ?? null,
                'notes'               => "Reserved by: {$data['full_name']} ({$data['contact_number']}), {$data['barangay']}",
            ]);

            Payment::create([
                'appointment_id'              => $appointment->id,
                'base_amount'                 => $amount,
                'tax_amount'                  => 0, // ingress tax rule TBD w/ SSC Treasurer's Office
                'total_amount'                => $total,
                'downpayment_percent'         => self::DOWNPAYMENT_PERCENT,
                'downpayment_amount'          => $downpayment,
                'balance_amount'              => $balance,
                'ingress_before'              => $ingressBefore,
                'ingress_after'               => $ingressAfter,
                'cancellation_refund_percent' => self::CANCELLATION_REFUND_PERCENT,
                'gcash_reference'             => $data['gcash_reference'] ?? null,
                'status'                      => 'pending',
            ]);

            CommitmentAcknowledgment::create([
                'appointment_id'  => $appointment->id,
                'user_id'         => Auth::id(),
                'form_version'    => self::COMMITMENT_FORM_VERSION,
                'ip_address'      => $request->ip(),
                'acknowledged_at' => now(),
            ]);

            AuditLog::record('reservation.created', $appointment, "Reservation created for {$facility->name}");

            return $appointment;
        });

        return redirect()
            ->route('client.dashboard')
            ->with('status', "Reservation submitted for {$facility->name} on {$appointment->event_date->format('M j, Y')}. It's now pending staff verification.");
    }

    /**
     * Server-side amount computation — never trusts a client-submitted total.
     * Mirrors the Payment Order Slip's modes of rent:
     *   - whole_day: flat rate regardless of duration
     *   - per_hour: hourly rate x duration (aircon-dependent where applicable)
     *   - first_3_hours: flat rate + (hours beyond 3) x succeeding_hour rate
     *   - succeeding_hour: hourly rate x duration (client explicitly chose hourly-only)
     */
    private function computeAmount(Facility $facility, array $data): ?float
    {
        $schedule = $facility->rate_schedule;
        $rateType = $data['rate_type'];
        $aircon   = $data['aircon'] ?? null;

        $hours = (strtotime($data['end_time']) - strtotime($data['start_time'])) / 3600;

        $rateFor = function (string $type) use ($schedule, $aircon) {
            if (!isset($schedule[$type])) {
                return null;
            }
            if (isset($schedule[$type]['default'])) {
                return $schedule[$type]['default'];
            }
            return $aircon ? ($schedule[$type]['aircon'] ?? null) : ($schedule[$type]['no_aircon'] ?? null);
        };

        return match ($rateType) {
            'whole_day' => $rateFor('whole_day'),
            'per_hour'  => ($r = $rateFor('per_hour')) !== null ? round($r * $hours, 2) : null,
            'succeeding_hour' => ($r = $rateFor('succeeding_hour')) !== null ? round($r * $hours, 2) : null,
            'first_3_hours' => (function () use ($rateFor, $hours) {
                $base = $rateFor('first_3_hours');
                if ($base === null) {
                    return null;
                }
                $extraHours = max(0, $hours - 3);
                $succeeding = $rateFor('succeeding_hour') ?? 0;
                return round($base + $extraHours * $succeeding, 2);
            })(),
            default => null,
        };
    }
}