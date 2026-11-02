<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The one place an appointment actually gets cancelled, so the client's Cancel button and the
 * OB Admin's "cancel on behalf" behave identically: status -> cancelled, who/when/why recorded,
 * assigned staff released, unpaid reschedule requests dropped, audit log written.
 *
 * Refunds are a separate decision (see RefundService) — cancelling never refunds by itself.
 */
class AppointmentCancellationService
{
    /** @param string $byRole 'client' | 'admin' @return string the status the appointment had before */
    public function cancel(Appointment $appointment, ?string $reason, User $actor, string $byRole): string
    {
        $from = $appointment->status;

        DB::transaction(function () use ($appointment, $reason, $actor, $byRole, $from) {
            $appointment->update([
                'status'              => 'cancelled',
                'cancelled_at'        => now(),
                'cancelled_by'        => $actor->id,
                'cancelled_by_role'   => $byRole,
                'cancellation_reason' => $reason,
                'notes'               => $reason
                    ? trim($appointment->notes . " | Cancellation reason ({$byRole}): {$reason}")
                    : $appointment->notes,
            ]);

            // Free up any staff the admin had already assigned.
            DB::table('staff_assignments')
                ->where('appointment_id', $appointment->id)
                ->where('status', 'assigned')
                ->update(['status' => 'cancelled', 'updated_at' => now()]);

            // Drop an unpaid reschedule request (a paid one stays as a payment record).
            $appointment->reschedulingFees()->where('status', 'pending')->update(['status' => 'cancelled']);

            AuditLog::record(
                "{$byRole}.appointment.cancelled",
                $appointment,
                "Cancelled \"{$appointment->activity_title}\" (was {$from})"
                    . ($byRole === 'client' ? '; no refund' : '; refund decided separately')
                    . ($reason ? " — {$reason}" : '')
            );
        });

        return $from;
    }
}