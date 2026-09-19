<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentInventory extends Model
{
    protected $table = 'equipment_inventory';

    protected $fillable = [
        'facility_id', 'item_name', 'quantity', 'unit', 'condition', 'managed_by', 'last_checked_at',
    ];

    protected $casts = [
        'last_checked_at' => 'datetime',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function managedBy()
    {
        return $this->belongsTo(User::class, 'managed_by');
    }
}