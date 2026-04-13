<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PetMedication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pet_id',
        'user_id',
        'prescribed_by_vet_id',
        'appointment_id',
        'sos_request_id',
        'medication_name',
        'generic_name',
        'dosage',
        'dosage_unit',
        'frequency',
        'frequency_unit',
        'duration_days',
        'start_date',
        'end_date',
        'administration_method',
        'instructions',
        'side_effects',
        'contraindications',
        'food_instructions',
        'storage_instructions',
        'cost',
        'pharmacy_name',
        'prescription_number',
        'refills_remaining',
        'total_refills',
        'is_active',
        'discontinuation_reason',
        'discontinued_at',
        'notes',
        'reminder_enabled',
        'photo_urls',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'discontinued_at' => 'datetime',
            'duration_days' => 'integer',
            'frequency' => 'integer',
            'cost' => 'decimal:2',
            'refills_remaining' => 'integer',
            'total_refills' => 'integer',
            'is_active' => 'boolean',
            'reminder_enabled' => 'boolean',
            'photo_urls' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }

            // Auto-calculate end_date if duration is provided
            if ($model->start_date && $model->duration_days && !$model->end_date) {
                $model->end_date = $model->start_date->addDays($model->duration_days);
            }
        });

        static::updated(function ($model) {
            // Auto-create reminders when medication is activated
            if ($model->isDirty('is_active') && $model->is_active && $model->reminder_enabled) {
                $model->createDosageReminders();
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

    public function prescribedBy(): BelongsTo
    {
        return $this->belongsTo(VetProfile::class, 'prescribed_by_vet_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function sosRequest(): BelongsTo
    {
        return $this->belongsTo(SosRequest::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(PetReminder::class, 'related_medication_id');
    }

    public function dosageLogs(): HasMany
    {
        return $this->hasMany(PetMedicationLog::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', now());
                    });
    }

    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now());
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->whereBetween('end_date', [now(), now()->addDays($days)]);
    }

    public function scopeByVet($query, int $vetId)
    {
        return $query->where('prescribed_by_vet_id', $vetId);
    }

    public function scopeNeedingRefill($query)
    {
        return $query->where('refills_remaining', '<=', 1)
                    ->where('is_active', true);
    }

    // Methods
    public function discontinue(string $reason): bool
    {
        return $this->update([
            'is_active' => false,
            'discontinuation_reason' => $reason,
            'discontinued_at' => now(),
        ]);
    }

    public function refill(): bool
    {
        if ($this->refills_remaining <= 0) {
            return false;
        }

        return $this->decrement('refills_remaining');
    }

    public function isExpired(): bool
    {
        return $this->end_date && $this->end_date < now();
    }

    public function isExpiringSoon(int $days = 7): bool
    {
        if (!$this->end_date) {
            return false;
        }

        return $this->end_date <= now()->addDays($days) && $this->end_date > now();
    }

    public function needsRefill(): bool
    {
        return $this->refills_remaining <= 1 && $this->is_active;
    }

    public function createDosageReminders(): void
    {
        if (!$this->frequency || !$this->frequency_unit || !$this->reminder_enabled) {
            return;
        }

        // Delete existing reminders for this medication
        $this->reminders()->delete();

        // Calculate reminder interval in minutes
        $intervalMinutes = match($this->frequency_unit) {
            'hours' => 60 / $this->frequency,
            'daily' => 1440 / $this->frequency, // 24 hours = 1440 minutes
            'weekly' => 10080 / $this->frequency, // 7 days = 10080 minutes
            default => 1440, // Default to daily
        };

        // Create reminders from start date to end date
        $currentDate = $this->start_date ?? now();
        $endDate = $this->end_date ?? now()->addDays(30);

        while ($currentDate <= $endDate) {
            PetReminder::create([
                'pet_id' => $this->pet_id,
                'user_id' => $this->user_id,
                'related_medication_id' => $this->id,
                'title' => "Give {$this->medication_name} to {$this->pet->name}",
                'description' => "Dosage: {$this->dosage} {$this->dosage_unit}\nMethod: {$this->administration_method}",
                'reminder_type' => 'medication',
                'scheduled_at' => $currentDate,
                'frequency' => (int) $intervalMinutes,
                'frequency_unit' => 'minutes',
                'end_date' => $this->end_date,
                'priority' => 8, // High priority
                'notification_methods' => ['database', 'email'],
                'advance_notice_minutes' => 5,
            ]);

            $currentDate = $currentDate->addMinutes($intervalMinutes);
        }
    }

    // Route key
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}