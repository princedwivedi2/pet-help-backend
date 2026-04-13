<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PetReminder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pet_id',
        'user_id',
        'title',
        'description',
        'reminder_type',
        'scheduled_at',
        'frequency',
        'frequency_unit',
        'end_date',
        'is_completed',
        'completed_at',
        'notification_methods',
        'advance_notice_minutes',
        'priority',
        'location',
        'cost_estimate',
        'notes',
        'related_medication_id',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'end_date' => 'datetime', 
            'completed_at' => 'datetime',
            'is_completed' => 'boolean',
            'frequency' => 'integer',
            'advance_notice_minutes' => 'integer',
            'notification_methods' => 'array',
            'cost_estimate' => 'decimal:2',
            'priority' => 'integer',
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

    // Relationships
    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function medication(): BelongsTo
    {
        return $this->belongsTo(PetMedication::class, 'related_medication_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('is_completed', false)
                    ->where('scheduled_at', '>', now());
    }

    public function scopeOverdue($query)
    {
        return $query->where('is_completed', false)
                    ->where('scheduled_at', '<', now());
    }

    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('reminder_type', $type);
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', '>=', 8);
    }

    public function scopeUpcoming($query, int $days = 7)
    {
        return $query->where('is_completed', false)
                    ->whereBetween('scheduled_at', [now(), now()->addDays($days)]);
    }

    // Methods
    public function markCompleted(): bool
    {
        return $this->update([
            'is_completed' => true,
            'completed_at' => now(),
        ]);
    }

    public function isOverdue(): bool
    {
        return !$this->is_completed && $this->scheduled_at < now();
    }

    public function getNextOccurrence(): ?self
    {
        if (!$this->frequency || !$this->frequency_unit) {
            return null;
        }

        $nextDate = match($this->frequency_unit) {
            'minutes' => $this->scheduled_at->addMinutes($this->frequency),
            'hours' => $this->scheduled_at->addHours($this->frequency),
            'days' => $this->scheduled_at->addDays($this->frequency),
            'weeks' => $this->scheduled_at->addWeeks($this->frequency),
            'months' => $this->scheduled_at->addMonths($this->frequency),
            'years' => $this->scheduled_at->addYears($this->frequency),
            default => null,
        };

        if (!$nextDate || ($this->end_date && $nextDate > $this->end_date)) {
            return null;
        }

        $next = $this->replicate();
        $next->scheduled_at = $nextDate;
        $next->is_completed = false;
        $next->completed_at = null;
        
        return $next;
    }

    // Route key
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}