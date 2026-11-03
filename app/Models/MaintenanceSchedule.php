<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceSchedule extends Model
{
    public const TYPES = [
        'routine'    => 'Routine maintenance',
        'inspection' => 'Inspection',
        'repair'     => 'Repair',
        'cleaning'   => 'Deep cleaning',
    ];

    public const STATUSES = [
        'scheduled'   => 'Scheduled',
        'in_progress' => 'In progress',
        'completed'   => 'Completed',
        'cancelled'   => 'Cancelled',
    ];

    /** Statuses that still occupy the facility / need action. */
    public const OPEN = ['scheduled', 'in_progress'];

    protected $fillable = [
        'facility_id', 'equipment_inventory_id', 'maintenance_report_id', 'type', 'title', 'description',
        'scheduled_date', 'start_time', 'end_time', 'status',
        'assigned_to', 'created_by', 'started_at', 'completed_at', 'outcome_condition', 'completion_notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'started_at'     => 'datetime',
        'completed_at'   => 'datetime',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function equipment()
    {
        return $this->belongsTo(EquipmentInventory::class, 'equipment_inventory_id');
    }

    public function report()
    {
        return $this->belongsTo(MaintenanceReport::class, 'maintenance_report_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst(str_replace('_', ' ', (string) $this->type));
    }
}