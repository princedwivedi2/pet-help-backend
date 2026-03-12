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
        'profile_photo',
        'address',
        'city',
        'state',
        'postal_code',
        'latitude',
        'longitude',
        'qualifications',
        'specialization',
        'license_number',
        'years_of_experience',
        'license_document_url',
        'degree_certificate_url',
        'government_id_url',
        'verification_documents',
        'services',
        'accepted_species',
        'working_hours',
        'is_emergency_available',
        'is_24_hours',
        'vet_status',
        'verification_status',
        'is_active',
        'availability_status',
        'consultation_fee',
        'home_visit_fee',
        'consultation_types',
        'is_featured',
        'featured_until',
        'total_appointments',
        'completed_appointments',
        'acceptance_rate',
        'avg_response_minutes',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'services' => 'array',
            'accepted_species' => 'array',
            'working_hours' => 'array',
            'verification_documents' => 'array',
            'consultation_types' => 'array',
            'is_emergency_available' => 'boolean',
            'is_24_hours' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'years_of_experience' => 'integer',
            'consultation_fee' => 'integer',
            'total_appointments' => 'integer',
            'completed_appointments' => 'integer',
            'featured_until' => 'datetime',
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

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function wallet()
    {
        return $this->hasOne(VetWallet::class);
    }

    public function visitRecords(): HasMany
    {
        return $this->hasMany(VisitRecord::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
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
        return $query->where(function ($q) {
                        $q->where('vet_status', 'pending')
                            ->orWhere('verification_status', 'needs_information');
        });
    }

    public function scopeByStatus($query, string $status)
    {
        if ($status === 'pending') {
            return $query->where(function ($q) {
                $q->where('vet_status', 'pending')
                  ->orWhere('verification_status', 'needs_information');
            });
        }

        return $query->where(function ($q) use ($status) {
            $q->where('vet_status', $status)
              ->orWhere('verification_status', $status);
        });
    }

    // ─── Helpers ────────────────────────────────────────────────────

    public function isApproved(): bool
    {
        return $this->vet_status === 'approved' || $this->verification_status === 'approved';
    }

    public function isPending(): bool
    {
        if ($this->vet_status === 'pending') {
            return true;
        }

        if (in_array($this->vet_status, ['approved', 'rejected', 'suspended'], true)) {
            return false;
        }

        return in_array($this->verification_status, ['pending', 'needs_information'], true);
    }

    public function isSuspended(): bool
    {
        return $this->vet_status === 'suspended' || $this->verification_status === 'suspended';
    }

    public function isRejected(): bool
    {
        return $this->vet_status === 'rejected' || $this->verification_status === 'rejected';
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}