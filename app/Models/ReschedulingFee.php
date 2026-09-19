<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReschedulingFee extends Model
{
    protected $fillable = [
        'appointment_id', 'original_date', 'new_date', 'fee_percent', 'fee_amount', 'status',
    ];

    protected $casts = [
        'original_date' => 'date',
        'new_date'      => 'date',
        'fee_amount'    => 'decimal:2',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}