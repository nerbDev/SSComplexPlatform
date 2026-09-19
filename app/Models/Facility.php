<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'location', 'capacity', 'safety_buffer_percent', 'description', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function maintenanceReports()
    {
        return $this->hasMany(MaintenanceReport::class);
    }

    public function incidentReports()
    {
        return $this->hasMany(IncidentReport::class);
    }

    public function inventory()
    {
        return $this->hasMany(EquipmentInventory::class);
    }

    public function rates()
    {
        return $this->hasMany(FacilityRate::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Rate schedule grouped for the reservation form's JS: e.g.
     * ['first_3_hours' => ['default' => 5000], 'per_hour' => ['aircon' => 3000, 'no_aircon' => 1000]]
     */
    public function getRateScheduleAttribute(): array
    {
        $schedule = [];

        foreach ($this->rates as $rate) {
            if ($rate->aircon === null) {
                $schedule[$rate->rate_type]['default'] = (float) $rate->amount;
            } else {
                $schedule[$rate->rate_type][$rate->aircon ? 'aircon' : 'no_aircon'] = (float) $rate->amount;
            }
        }

        return $schedule;
    }

    /**
     * Effective bookable capacity after applying the safety buffer.
     * e.g. 2000 capacity x 20% buffer -> 1600 bookable.
     */
    public function getSafeCapacityAttribute(): int
    {
        return (int) floor($this->capacity * (1 - $this->safety_buffer_percent / 100));
    }
}