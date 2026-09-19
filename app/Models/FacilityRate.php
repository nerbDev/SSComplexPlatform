<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityRate extends Model
{
    protected $fillable = ['facility_id', 'rate_type', 'aircon', 'amount'];

    protected $casts = [
        'aircon' => 'boolean',
        'amount' => 'decimal:2',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}