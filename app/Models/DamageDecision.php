<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DamageDecision extends Model
{
    public const DECISIONS = [
        'charge_client' => 'Charge client',
        'waive'         => 'Waive (no charge)',
        'repair'        => 'Repair',
        'replace'       => 'Replace',
        'no_damage'     => 'No damage found',
    ];

    protected $fillable = [
        'incident_report_id', 'decided_by', 'decision',
        'equipment_inventory_id', 'item_label', 'quantity_affected', 'approved_amount', 'notes',
    ];

    protected $casts = [
        'approved_amount' => 'decimal:2',
    ];

    public function incidentReport()
    {
        return $this->belongsTo(IncidentReport::class);
    }

    public function decidedBy()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function equipment()
    {
        return $this->belongsTo(EquipmentInventory::class, 'equipment_inventory_id');
    }

    public function getDecisionLabelAttribute(): string
    {
        return self::DECISIONS[$this->decision] ?? ucfirst(str_replace('_', ' ', (string) $this->decision));
    }
}