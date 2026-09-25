<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'facility_id', 'activity_type_id', 'activity_title', 'track', 'booking_type',
        'rate_type', 'aircon', 'status',
        'event_date', 'start_time', 'end_time', 'expected_attendees',
        'approval_form_path', 'approval_form_submitted_at', 'approval_form_submitted_by',
        'notes', 'verified_by', 'verified_at',
    ];

    protected $casts = [
        'event_date'                  => 'date',
        'verified_at'                 => 'datetime',
        'aircon'                      => 'boolean',
        'approval_form_submitted_at'  => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function activityType()
    {
        return $this->belongsTo(ActivityType::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function commitmentAcknowledgment()
    {
        return $this->hasOne(CommitmentAcknowledgment::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function reschedulingFees()
    {
        return $this->hasMany(ReschedulingFee::class);
    }

    public function staffAssignments()
    {
        return $this->hasMany(StaffAssignment::class);
    }

    public function assignedStaff()
    {
        return $this->belongsToMany(User::class, 'staff_assignments', 'appointment_id', 'staff_id')
            ->withPivot(['start_time', 'end_time', 'status'])
            ->withTimestamps();
    }

    public function incidentReports()
    {
        return $this->hasMany(IncidentReport::class);
    }

    public function visitorLogs()
    {
        return $this->hasMany(VisitorLog::class);
    }

    public function feedback()
    {
        return $this->hasOne(Feedback::class);
    }

    public function scopeForFacilityOnDate($query, int $facilityId, string $date)
    {
        return $query->where('facility_id', $facilityId)->whereDate('event_date', $date);
    }
}