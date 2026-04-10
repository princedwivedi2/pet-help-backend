<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'vet_profile_id',
        'appointment_id',
        'sos_request_id',
        'rating',
        'comment',
        'vet_reply',
        'vet_replied_at',
        'is_flagged',
        'flag_reason',
        'is_visible',
        'is_verified_purchase',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_flagged' => 'boolean',
            'is_visible' => 'boolean',
            'is_verified_purchase' => 'boolean',
            'vet_replied_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }

            // Auto-set verified purchase if linked to a completed appointment/SOS
            if (!isset($model->is_verified_purchase)) {
                $model->is_verified_purchase = $model->determineVerifiedPurchase();
            }

            // Default visibility
            if (!isset($model->is_visible)) {
                $model->is_visible = true;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vetProfile(): BelongsTo
    {
        return $this->belongsTo(VetProfile::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function sosRequest(): BelongsTo
    {
        return $this->belongsTo(SosRequest::class);
    }

    public function scopeForVet($query, int $vetProfileId)
    {
        return $query->where('vet_profile_id', $vetProfileId);
    }

    public function scopeNotFlagged($query)
    {
        return $query->where('is_flagged', false);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Check if this review is from a verified transaction.
     */
    public function determineVerifiedPurchase(): bool
    {
        // Check if linked to a completed appointment
        if ($this->appointment_id) {
            $appointment = Appointment::find($this->appointment_id);
            if ($appointment && $appointment->status === Appointment::STATUS_COMPLETED) {
                return true;
            }
        }

        // Check if linked to a resolved SOS
        if ($this->sos_request_id) {
            $sos = SosRequest::find($this->sos_request_id);
            if ($sos && $sos->status === SosRequest::STATUS_RESOLVED) {
                return true;
            }
        }

        return false;
    }
}
