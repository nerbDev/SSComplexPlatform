<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Poster;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * NOTE ON ASSUMPTIONS
     * --------------------
     * Same appointments/unavailable_slots/rates/activity_types tables
     * referenced in the admin DashboardController.
     *
     * appointments.status pipeline now covers both tracks:
     *   paid:      pending -> verified -> payment_submitted -> receipt_confirmed -> scheduled -> finished
     *   free_use:  pending -> verified -> form_submitted -> (staff review, not built yet) -> scheduled -> finished
     *   both:      cancelled / rescheduled as branches
     *
     * "My Activities" buckets these into the four the client cares about
     * via statusBucket(); statusAction() drives the per-row button (or
     * plain-text placeholder) and is where the two tracks diverge —
     * see the class doc comment on ReservationController for the full
     * track/booking_type rationale.
     */

    public function index()
    {
        $hasAppointments = Schema::hasTable('appointments');
        $hasUnavailable  = Schema::hasTable('unavailable_slots');
        $hasRates        = Schema::hasTable('rates');
        $hasActivityTypes = Schema::hasTable('activity_types');

        [$activities, $activityCounts] = $this->myActivities($hasAppointments);

        return view('client.dashboard', [
            'posters'          => $this->posters(),
            'activities'       => $activities,
            'activityCounts'   => $activityCounts,
            'unavailableSlots' => $this->unavailableSlots($hasUnavailable),
            'rates'            => $this->rates($hasRates),
            'activityTypes'    => $this->activityTypes($hasActivityTypes),
        ]);
    }

    private function posters(): array
    {
        if (!Schema::hasTable('posters')) {
            return [];
        }

        return Poster::active()->ordered()->get()->map(function (Poster $poster) {
            return [
                'image'       => $poster->image_path ? 'storage/' . $poster->image_path : null,
                'title'       => $poster->title,
                'date'        => optional($poster->event_date)->format('M j, Y'),
                'time'        => $poster->event_time,
                'unit'        => $poster->unit,
                'description' => $poster->description,
                'status'      => ucfirst($poster->status),
            ];
        })->toArray();
    }

    private function myActivities(bool $hasAppointments): array
    {
        $counts = ['all' => 0, 'ongoing' => 0, 'rescheduled' => 0, 'cancelled' => 0, 'finished' => 0];

        if (!$hasAppointments || !Auth::check()) {
            return [[], $counts];
        }

        $rows = DB::table('appointments')
            ->join('facilities', 'facilities.id', '=', 'appointments.facility_id')
            ->where('appointments.user_id', Auth::id())
            ->orderByDesc('appointments.event_date')
            ->orderByDesc('appointments.start_time')
            ->limit(50)
            ->get([
                'appointments.id', 'appointments.activity_title', 'appointments.status',
                'appointments.booking_type', 'appointments.track', 'appointments.event_date', 'appointments.start_time',
                'facilities.name as facility_name',
            ]);

        $activities = $rows->map(function ($row) use (&$counts) {
            $bucket = $this->statusBucket($row->status);
            $counts[$bucket]++;
            $counts['all']++;

            $dateTime = trim(
                Carbon::parse($row->event_date)->format('M j, Y') .
                ' · ' . Carbon::parse($row->start_time)->format('g:i A')
            );

            return [
                'appointment_id' => $row->id,
                'activity'       => $row->activity_title ?? '—',
                'unit'           => $row->facility_name ?? '—',
                'date_time'      => $dateTime,
                'booking_type'   => $row->booking_type,
                'track'          => $row->track,
                'status_key'     => $bucket,
                'status_label'   => $this->statusLabel($row->status),
                'action'         => $this->statusAction($row->id, $row->status, $row->track),
            ];
        })->toArray();

        return [$activities, $counts];
    }

    private function statusBucket(string $status): string
    {
        return match (strtolower($status)) {
            'rescheduled'                => 'rescheduled',
            'cancelled', 'canceled'      => 'cancelled',
            'finished', 'completed'      => 'finished',
            default                      => 'ongoing', // pending, verified, payment_submitted, receipt_confirmed, form_submitted, scheduled...
        };
    }

    private function statusLabel(string $status): string
    {
        return match (strtolower($status)) {
            'pending'            => 'Pending Staff Review',
            'verified'           => 'Verified',
            'payment_submitted'  => 'Payment Submitted',
            'receipt_confirmed'  => 'Awaiting Scheduling',
            'form_submitted'     => 'Form Submitted',
            'scheduled'          => 'Scheduled',
            'rescheduled'        => 'Re-scheduled',
            'cancelled'          => 'Cancelled',
            'finished',
            'completed'          => 'Finished',
            default              => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    /**
     * The client's next action for a given status, if any — drives the
     * button (or plain-text placeholder) in the dashboard's My Activities
     * table. This is where the paid and free_use tracks diverge:
     *   - paid:     verified -> "Pay" button; payment_submitted -> "Continue"
     *               (to Receipt Confirmation); otherwise a "not yet
     *               applicable for payment" placeholder while pending.
     *   - free_use: verified -> "Attach Form" button (opens the dashboard's
     *               modal); otherwise a "not yet applicable for document
     *               attachment" placeholder while pending.
     *
     * Returns a type so the Blade knows how to render it: 'link' (goes to
     * a route), 'modal' (opens the Attach Form modal via JS), 'text' (a
     * plain non-interactive label), or 'none' (nothing shown).
     */
    private function statusAction(int $appointmentId, string $status, string $track): array
    {
        $status = strtolower($status);
        $isFreeUse = $track === 'free_use';

        if ($status === 'pending') {
            return [
                'type'  => 'text',
                'label' => $isFreeUse ? 'Not yet applicable for document attachment' : 'Not yet applicable for payment',
            ];
        }

        if ($status === 'verified') {
            return $isFreeUse
                ? ['type' => 'modal', 'label' => 'Attach Form', 'appointment_id' => $appointmentId]
                : ['type' => 'link', 'label' => 'Pay', 'route' => route('client.reservations.pay', $appointmentId)];
        }

        if ($status === 'payment_submitted' && !$isFreeUse) {
            return ['type' => 'link', 'label' => 'Continue', 'route' => route('client.reservations.receipt-confirmation', $appointmentId)];
        }

        if ($status === 'form_submitted' && $isFreeUse) {
            return ['type' => 'text', 'label' => 'Submitted — Awaiting Staff Review'];
        }

        return ['type' => 'none'];
    }

    private function unavailableSlots(bool $hasTable): array
    {
        if (!$hasTable) {
            return [];
        }

        return DB::table('unavailable_slots')
            ->whereDate('date', '>=', Carbon::today()->toDateString())
            ->orderBy('date')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $date = Carbon::parse($row->date);
                $timeRange = ($row->start_time && $row->end_time)
                    ? Carbon::parse($row->start_time)->format('g:i A') . ' – ' . Carbon::parse($row->end_time)->format('g:i A')
                    : 'Whole day';

                return [
                    'day'        => $date->format('d'),
                    'month'      => $date->format('M'),
                    'label'      => $date->format('l, M j, Y'),
                    'time_range' => $timeRange,
                    'unit'       => $row->unit ?? null,
                    'reason'     => $row->reason ?? null,
                ];
            })->toArray();
    }

    private function rates(bool $hasTable): array
    {
        if (!$hasTable) {
            return [];
        }

        return DB::table('rates')
            ->orderBy('unit')
            ->get()
            ->map(fn($row) => [
                'unit' => $row->unit,
                'rate' => '₱' . number_format((float) $row->rate_per_hour, 0) . '/hr',
                'note' => $row->note ?? null,
            ])->toArray();
    }

    private function activityTypes(bool $hasTable): array
    {
        if (!$hasTable) {
            return [];
        }

        return DB::table('activity_types')
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->toArray();
    }
}