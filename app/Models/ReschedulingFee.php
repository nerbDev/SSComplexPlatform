<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReschedulingFee extends Model
{
    protected $fillable = [
        'appointment_id',
        'original_date', 'original_start_time', 'original_end_time',
        'new_date', 'new_start_time', 'new_end_time',
        'fee_percent', 'base_total', 'fee_amount', 'status',
        'gcash_reference', 'receipt_path', 'proof_of_payment_path', 'client_notes',
        'submitted_at', 'confirmed_at',
    ];

    protected $casts = [
        'original_date' => 'date',
        'new_date'      => 'date',
        'base_total'    => 'decimal:2',
        'fee_amount'    => 'decimal:2',
        'submitted_at'  => 'datetime',
        'confirmed_at'  => 'datetime',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}