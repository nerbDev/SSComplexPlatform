<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'appointment_id', 'base_amount', 'tax_amount', 'total_amount',
        'downpayment_percent', 'downpayment_amount', 'balance_amount', 'balance_due_date',
        'ingress_before', 'ingress_after', 'cancellation_refund_percent',
        'gcash_reference', 'status', 'paid_at', 'receipt_path',
        'proof_of_payment_path', 'client_notes', 'receipt_confirmed_at',
        'verified_by_staff_id', 'verified_at', 'forwarded_to_admin_id', 'forwarded_at',
    ];

    protected $casts = [
        'base_amount'          => 'decimal:2',
        'tax_amount'           => 'decimal:2',
        'total_amount'         => 'decimal:2',
        'downpayment_amount'   => 'decimal:2',
        'balance_amount'       => 'decimal:2',
        'balance_due_date'     => 'date',
        'ingress_before'       => 'decimal:2',
        'ingress_after'        => 'decimal:2',
        'paid_at'              => 'datetime',
        'verified_at'          => 'datetime',
        'forwarded_at'         => 'datetime',
        'receipt_confirmed_at' => 'datetime',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function verifiedByStaff()
    {
        return $this->belongsTo(User::class, 'verified_by_staff_id');
    }

    public function forwardedToAdmin()
    {
        return $this->belongsTo(User::class, 'forwarded_to_admin_id');
    }
}