<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentWaitlist extends Model
{
    protected $table = 'appointment_waitlist';

    protected $fillable = [
        'uuid',
        'user_id',
        'vet_profile_id',
        'pet_id',
        'preferred_date',
        'preferred_time_start',
        'preferred_time_end',
        'consultation_type',
        'is_notified',
        'notified_at',
        'expires_at',
    ];

    protected $casts = [
        'preferred_date' => 'date',
        'is_notified' => 'boolean',
        'notified_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vetProfile(): BelongsTo
    {
        return $this->belongsTo(VetProfile::class);
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }
}
