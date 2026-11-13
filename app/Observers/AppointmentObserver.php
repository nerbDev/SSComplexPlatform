<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Models\AuditLog;

/**
 * Safety net for the audit trail: any time an appointment's status changes through Eloquent, one
 * `appointment.status_changed` entry records from → to, who did it (if someone is logged in) and
 * from where. The modules also write their own descriptive entries; this one guarantees the timeline
 * of status transitions is complete even for code paths that forget to log.
 *
 * (Bulk updates through the query builder skip model events and aren't captured here.)
 */
class AppointmentObserver
{
    public function updated(Appointment $appointment): void
    {
        if (!$appointment->wasChanged('status')) {
            return;
        }

        $from = $appointment->getOriginal('status') ?? '—';
        $to   = $appointment->status;

        AuditLog::record(
            'appointment.status_changed',
            $appointment,
            "Status changed: {$from} → {$to} (\"{$appointment->activity_title}\")"
        );
    }
}