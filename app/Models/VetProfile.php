<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class VetProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'clinic_name',
        'vet_name',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'postal_code',
        'latitude',
        'longitude',
        'qualifications',
        'license_number',
        'years_of_experience',
        'license_document_url',
        'services',
        'accepted_species',
        'is_emergency_available',
        'is_24_hours',
        'is_verified',
        'rejection_reason',
        'verified_at',
        'verified_by',
        'is_active',
        'rating',
        'review_count',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'services' => 'array',
            'accepted_species' => 'array',
            'is_emergency_available' => 'boolean',
            'is_24_hours' => 'boolean',
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
            'rating' => 'decimal:1',
            'review_count' => 'integer',
            'years_of_experience' => 'integer',
            'verified_at' => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifiedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function verificationLogs(): HasMany
    {
        return $this->hasMany(VetVerificationLog::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(VetAvailability::class);
    }

    public function sosRequests(): HasMany
    {
        return $this->hasMany(SosRequest::class, 'assigned_vet_id');
    }

    public function incidentLogs(): HasMany
    {
        return $this->hasMany(IncidentLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeEmergencyAvailable($query)
    {
        return $query->where('is_emergency_available', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeUnverified($query)
    {
        return $query->where('is_verified', false);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
