<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Online consultation between a pet owner and a vet (video / audio / chat).
 *
 * Covers both:
 *   - Instant consult: user opens app, picks issue, system matches an available vet.
 *   - Scheduled consult: linked to an Appointment; user joins at the booked slot.
 *
 * `status` follows the lifecycle documented in the migration. Side-effects (refund
 * decisions, notifications) are driven from status transitions in ConsultationService.
 */
class ConsultationSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'vet_profile_id',
        'pet_id',
        'origin',
        'appointment_id',
        'modality',
        'issue_category',
        'issue_description',
        'status',
        'matched_at',
        'vet_joined_at',
        'user_joined_at',
        'started_at',
        'ended_at',
        'duration_seconds',
        'vet_no_show_check_at',
        'connection_failures',
        'auto_refund_triggered',
        'refund_reason',
        'room_provider',
        'room_id',
        'room_metadata',
        'payment_id',
        'fee_amount',
        'vet_notes',
        'diagnosis',
        'prescription',
    ];

    protected function casts(): array
    {
        return [
            'matched_at' => 'datetime',
            'vet_joined_at' => 'datetime',
            'user_joined_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'vet_no_show_check_at' => 'datetime',
            'duration_seconds' => 'integer',
            'connection_failures' => 'integer',
            'auto_refund_triggered' => 'boolean',
            'fee_amount' => 'integer',
            'room_metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (empty($m->uuid)) {
                $m->uuid = Str::uuid()->toString();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

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

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConsultationMessage::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['matched', 'joining', 'active'], true);
    }

    public function isFinal(): bool
    {
        return in_array($this->status, ['completed', 'cancelled', 'expired', 'failed'], true);
    }

    public function isRefundEligible(): bool
    {
        // Spec: vet no-show, repeated connection fail, vet cancellation, technical failure.
        return in_array($this->status, ['expired', 'failed'], true)
            || $this->auto_refund_triggered;
    }
}
