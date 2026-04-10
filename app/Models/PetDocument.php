<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PetDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pet_id',
        'user_id',
        'appointment_id',
        'sos_request_id',
        'vet_profile_id',
        'title',
        'description',
        'document_type',
        'file_path',
        'file_url',
        'file_name',
        'file_size',
        'mime_type',
        'document_date',
        'expiry_date',
        'tags',
        'is_confidential',
        'sharing_permissions',
        'qr_code_data',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'datetime',
            'expiry_date' => 'datetime',
            'file_size' => 'integer',
            'tags' => 'array',
            'is_confidential' => 'boolean',
            'sharing_permissions' => 'array',
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

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function sosRequest(): BelongsTo
    {
        return $this->belongsTo(SosRequest::class);
    }

    public function vetProfile(): BelongsTo
    {
        return $this->belongsTo(VetProfile::class);
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    public function scopePublic($query)
    {
        return $query->where('is_confidential', false);
    }

    public function scopeConfidential($query)
    {
        return $query->where('is_confidential', true);
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('expiry_date')
                    ->where('expiry_date', '<=', now()->addDays($days))
                    ->where('expiry_date', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
                    ->where('expiry_date', '<', now());
    }

    // Methods
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date < now();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        
        return $this->expiry_date <= now()->addDays($days) && $this->expiry_date > now();
    }

    public function generateQrCode(): string
    {
        // Generate QR code data for easy sharing
        return json_encode([
            'pet_id' => $this->pet_id,
            'document_id' => $this->uuid,
            'type' => $this->document_type,
            'date' => $this->document_date?->format('Y-m-d'),
            'vet' => $this->vetProfile?->vet_name,
        ]);
    }

    // Route key
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}