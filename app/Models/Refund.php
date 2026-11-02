<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    public const PENDING  = 'pending';
    public const APPROVED = 'approved';
    public const RELEASED = 'released';
    public const REJECTED = 'rejected';

    /** Statuses that count as "this appointment already has a refund in play". */
    public const ACTIVE = [self::PENDING, self::APPROVED, self::RELEASED];

    public const REASONS = [
        'ssc_cancellation' => 'Cancelled by SSC',
        'force_majeure'    => 'Force majeure',
        'incident'         => 'Incident / damage related',
        'overpayment'      => 'Overpayment / double payment',
        'other'            => 'Other',
    ];

    protected $fillable = [
        'appointment_id', 'payment_id', 'incident_report_id',
        'reason_type', 'reason',
        'payment_total', 'refund_percent', 'refund_amount', 'status',
        'requested_by', 'decided_by', 'decided_at', 'decision_note',
        'release_reference', 'released_by', 'released_at',
    ];

    protected $casts = [
        'payment_total' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'decided_at'    => 'datetime',
        'released_at'   => 'datetime',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function incidentReport()
    {
        return $this->belongsTo(IncidentReport::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decidedBy()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function releasedBy()
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function getReasonLabelAttribute(): string
    {
        return self::REASONS[$this->reason_type] ?? ucfirst(str_replace('_', ' ', (string) $this->reason_type));
    }
}