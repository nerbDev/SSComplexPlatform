<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    const UPDATED_AT = null; // audit rows are immutable — created_at only

    protected $fillable = [
        'user_id', 'action', 'subject_type', 'subject_id', 'description', 'ip_address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public static function record(string $action, ?\Illuminate\Database\Eloquent\Model $subject = null, ?string $description = null): self
    {
        return static::create([
            'user_id'      => auth()->id(),
            'action'       => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->getKey(),
            'description'  => $description,
            'ip_address'   => request()?->ip(),
        ]);
    }
}