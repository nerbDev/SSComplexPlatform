<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentReport extends Model
{
    protected $fillable = [
        'facility_id', 'appointment_id', 'reported_by', 'description', 'damaged_items',
        'estimated_cost', 'refund_related', 'photo_path', 'status',
        'reviewed_by', 'resolution_notes', 'reviewed_at',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'refund_related' => 'boolean',
        'reviewed_at'    => 'datetime',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}