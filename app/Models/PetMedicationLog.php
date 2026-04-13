<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PetMedicationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'pet_medication_id',
        'user_id',
        'administered_at',
        'administered_by',
        'dosage_given',
        'dosage_unit',
        'administration_method',
        'was_successful',
        'pet_reaction',
        'side_effects_observed',
        'notes',
        'photo_urls',
        'location',
        'next_dose_at',
    ];

    protected function casts(): array
    {
        return [
            'administered_at' => 'datetime',
            'next_dose_at' => 'datetime',
            'was_successful' => 'boolean',
            'photo_urls' => 'array',
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
    public function medication(): BelongsTo
    {
        return $this->belongsTo(PetMedication::class, 'pet_medication_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeSuccessful($query)
    {
        return $query->where('was_successful', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('was_successful', false);
    }

    public function scopeWithSideEffects($query)
    {
        return $query->whereNotNull('side_effects_observed');
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('administered_at', '>=', now()->subDays($days));
    }

    // Route key
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}