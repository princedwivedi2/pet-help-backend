<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'species',
        'breed',
        'birth_date',
        'weight_kg',
        'photo_url',
        'medical_notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'weight_kg' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sosRequests(): HasMany
    {
        return $this->hasMany(SosRequest::class);
    }

    public function incidentLogs(): HasMany
    {
        return $this->hasMany(IncidentLog::class);
    }

    // New Pet Management Features
    public function notes(): HasMany
    {
        return $this->hasMany(PetNote::class)->latest();
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(PetReminder::class)->orderBy('scheduled_at');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PetDocument::class)->latest();
    }

    public function medications(): HasMany
    {
        return $this->hasMany(PetMedication::class)->latest();
    }

    public function activeMedications(): HasMany
    {
        return $this->hasMany(PetMedication::class)->active();
    }

    public function upcomingReminders(): HasMany
    {
        return $this->hasMany(PetReminder::class)->upcoming();
    }

    public function overdueReminders(): HasMany
    {
        return $this->hasMany(PetReminder::class)->overdue();
    }

    // Helper methods
    public function getAgeInMonths(): ?int
    {
        if (!$this->birth_date) {
            return null;
        }

        return $this->birth_date->diffInMonths(now());
    }

    public function getAgeString(): string
    {
        if (!$this->birth_date) {
            return 'Unknown age';
        }

        $months = $this->getAgeInMonths();
        $years = floor($months / 12);
        $remainingMonths = $months % 12;

        if ($years == 0) {
            return $months == 1 ? '1 month' : "{$months} months";
        }

        if ($remainingMonths == 0) {
            return $years == 1 ? '1 year' : "{$years} years";
        }

        return "{$years} year" . ($years > 1 ? 's' : '') . 
               ", {$remainingMonths} month" . ($remainingMonths > 1 ? 's' : '');
    }

    public function hasActiveMedications(): bool
    {
        return $this->activeMedications()->exists();
    }

    public function hasUpcomingReminders(): bool
    {
        return $this->upcomingReminders()->exists();
    }

    public function hasOverdueReminders(): bool
    {
        return $this->overdueReminders()->exists();
    }
    public function medicalRecords(): HasMany
    {
        return $this->hasMany(PetMedicalRecord::class)->orderByDesc('recorded_at');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function visitRecords(): HasMany
    {
        return $this->hasMany(VisitRecord::class);
    }
}
