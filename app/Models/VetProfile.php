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
        'user_id',
        'clinic_name',
        'vet_name',
        'phone',
        'email',
        'address',
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
        'vet_status',
        'is_active',
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
            'is_active' => 'boolean',
            'years_of_experience' => 'integer',
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

    /**
     * @deprecated Use verifications() relationship instead.
     * Alias kept for backward compatibility during transition.
     */
    public function verificationLogs(): HasMany
    {
        return $this->verifications();
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(VetVerification::class);
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

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
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
        return $query->where('vet_status', 'approved');
    }

    public function scopeUnverified($query)
    {
        return $query->where('vet_status', 'pending');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('vet_status', $status);
    }

    // ─── Helpers ────────────────────────────────────────────────────

    public function isApproved(): bool
    {
        return $this->vet_status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->vet_status === 'pending';
    }

    public function isSuspended(): bool
    {
        return $this->vet_status === 'suspended';
    }

    public function isRejected(): bool
    {
        return $this->vet_status === 'rejected';
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}