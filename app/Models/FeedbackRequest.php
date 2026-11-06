<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackRequest extends Model
{
    protected $fillable = ['appointment_id', 'user_id', 'token', 'sent_at', 'reminded_at', 'completed_at'];

    protected $casts = [
        'sent_at'      => 'datetime',
        'reminded_at'  => 'datetime',
        'completed_at' => 'datetime',
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