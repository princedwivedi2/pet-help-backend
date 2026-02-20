<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SosRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'pet_id',
        'latitude',
        'longitude',
        'address',
        'description',
        'emergency_type',
        'status',
        'assigned_vet_id',
        'acknowledged_at',
        'completed_at',
        'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'acknowledged_at' => 'datetime',
            'completed_at' => 'datetime',
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

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function assignedVet(): BelongsTo
    {
        return $this->belongsTo(VetProfile::class, 'assigned_vet_id');
    }

    public function incidentLog()
    {
        return $this->hasOne(IncidentLog::class);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'acknowledged', 'in_progress']);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['pending', 'acknowledged', 'in_progress']);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'acknowledged']);
    }

    public function canBeCompleted(): bool
    {
        return in_array($this->status, ['acknowledged', 'in_progress']);
    }
}
