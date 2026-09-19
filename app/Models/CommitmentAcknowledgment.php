<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommitmentAcknowledgment extends Model
{
    protected $fillable = ['appointment_id', 'user_id', 'form_version', 'ip_address', 'acknowledged_at'];

    protected $casts = [
        'acknowledged_at' => 'datetime',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}