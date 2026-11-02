<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Opens a refund record for an appointment's payment. Throws \DomainException with a
 * human-readable message when a refund isn't possible (controllers show it to the admin).
 *
 * Standard policy stays 0% for client-initiated cancellations
 * (ReservationController::CANCELLATION_REFUND_PERCENT) — a refund only exists when the OB Admin opens one.
 */
class RefundService
{
    public function open(
        Appointment $appointment,
        string $reasonType,
        ?string $reason,
        int $percent,
        User $actor,
        bool $autoApprove = false,
        ?int $incidentReportId = null
    ): Refund {
        $payment = $appointment->payment;

        if (!$payment || (float) $payment->total_amount <= 0) {
            throw new \DomainException('This appointment has no recorded payment to refund.');
        }

        if ($percent < 1 || $percent > 100) {
            throw new \DomainException('Refund percent must be between 1 and 100.');
        }

        if ($appointment->refunds()->whereIn('status', Refund::ACTIVE)->exists()) {
            throw new \DomainException('This appointment already has an active refund.');
        }

        $total  = (float) $payment->total_amount;
        $amount = round($total * $percent / 100, 2);

        return DB::transaction(function () use ($appointment, $payment, $reasonType, $reason, $percent, $actor, $autoApprove, $incidentReportId, $total, $amount) {
            $refund = Refund::create([
                'appointment_id'     => $appointment->id,
                'payment_id'         => $payment->id,
                'incident_report_id' => $incidentReportId,
                'reason_type'        => $reasonType,
                'reason'             => $reason,
                'payment_total'      => $total,
                'refund_percent'     => $percent,
                'refund_amount'      => $amount,
                'status'             => $autoApprove ? Refund::APPROVED : Refund::PENDING,
                'requested_by'       => $actor->id,
                'decided_by'         => $autoApprove ? $actor->id : null,
                'decided_at'         => $autoApprove ? now() : null,
            ]);

            AuditLog::record(
                'ob.refund.opened',
                $refund,
                "Refund of ₱" . number_format($amount, 2) . " ({$percent}%) opened for \"{$appointment->activity_title}\" — "
                    . (Refund::REASONS[$reasonType] ?? $reasonType) . ($autoApprove ? ' (auto-approved)' : '')
            );

            return $refund;
        });
    }
}