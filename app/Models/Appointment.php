<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'vet_profile_id',
        'pet_id',
        'status',
        'scheduled_at',
        'duration_minutes',
        'reason',
        'notes',
        'cancellation_reason',
        'cancelled_by',
        'payment_status',
        'fee_amount',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at'     => 'datetime',
            'duration_minutes' => 'integer',
            'fee_amount'       => 'integer',
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
            ->whereIn('status', ['pending', 'confirmed']);
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
        if (!in_array($this->status, ['pending', 'confirmed'])) {
            return false;
        }

        return $user->id === $this->user_id
            || ($user->isVet() && $user->vetProfile?->id === $this->vet_profile_id)
            || $user->isAdmin();
    }

    public function canBeConfirmed(): bool
    {
        return $this->status === 'pending' && $this->scheduled_at->isFuture();
    }

    public function canBeCompleted(): bool
    {
        return $this->status === 'confirmed';
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
