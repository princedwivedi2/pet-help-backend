<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'vet_profile_id',
        'pet_id',
        'status',
        'appointment_type',
        'is_emergency',
        'scheduled_at',
        'duration_minutes',
        'reason',
        'notes',
        'photo_url',
        'home_address',
        'home_latitude',
        'home_longitude',
        'cancellation_reason',
        'rejection_reason',
        'cancelled_by',
        'payment_status',
        'fee_amount',
        'cancelled_at_slot_release',
        'accepted_at',
        'rejected_at',
        'completed_at',
        'cancelled_at',
        'visit_started_at',
        'visit_ended_at',
        'vet_start_latitude',
        'vet_start_longitude',
        'vet_end_latitude',
        'vet_end_longitude',
        'payment_mode',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at'              => 'datetime',
            'duration_minutes'          => 'integer',
            'fee_amount'                => 'integer',
            'is_emergency'              => 'boolean',
            'cancelled_at_slot_release' => 'datetime',
            'accepted_at'               => 'datetime',
            'rejected_at'               => 'datetime',
            'completed_at'              => 'datetime',
            'cancelled_at'              => 'datetime',
            'visit_started_at'          => 'datetime',
            'visit_ended_at'            => 'datetime',
            'home_latitude'             => 'decimal:8',
            'home_longitude'            => 'decimal:8',
            'vet_start_latitude'        => 'decimal:8',
            'vet_start_longitude'       => 'decimal:8',
            'vet_end_latitude'          => 'decimal:8',
            'vet_end_longitude'         => 'decimal:8',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    // ─── Relationships ──────────────────────────────────────────────

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

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'payable_id')
            ->where('payable_type', 'appointment');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'payable_id')
            ->where('payable_type', 'appointment');
    }

    public function visitRecord(): HasOne
    {
        return $this->hasOne(VisitRecord::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    // ─── Scopes ─────────────────────────────────────────────────────

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForVet($query, int $vetProfileId)
    {
        return $query->where('vet_profile_id', $vetProfileId);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>', now())
            ->whereIn('status', ['pending', 'confirmed', 'accepted']);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOnDate($query, string $date)
    {
        return $query->whereDate('scheduled_at', $date);
    }

    // ─── Helpers ────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function canBeCancelledBy(User $user): bool
    {
        if (!in_array($this->status, ['pending', 'confirmed', 'accepted'])) {
            return false;
        }

        return $user->id === $this->user_id
            || ($user->isVet() && $this->vet_profile_id !== null && $user->vetProfile?->id === $this->vet_profile_id)
            || $user->isAdmin();
    }

    public function canBeConfirmed(): bool
    {
        return in_array($this->status, ['pending', 'accepted']) && $this->scheduled_at->isFuture();
    }

    public function canBeAccepted(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeRejected(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeCompleted(): bool
    {
        return in_array($this->status, ['confirmed', 'accepted', 'in_progress']);
    }

    public function canStartVisit(): bool
    {
        return in_array($this->status, ['confirmed', 'accepted']);
    }

    public function isHomeVisit(): bool
    {
        return $this->appointment_type === 'home_visit';
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
