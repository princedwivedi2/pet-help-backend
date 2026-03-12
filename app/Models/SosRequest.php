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
        'user_id',
        'pet_id',
        'latitude',
        'longitude',
        'address',
        'description',
        'emergency_type',
        'status',
        'assigned_vet_id',
        'vet_latitude',
        'vet_longitude',
        'acknowledged_at',
        'completed_at',
        'resolution_notes',
        'response_time_seconds',
        'arrival_time_seconds',
        'vet_departed_at',
        'vet_arrived_at',
        'treatment_started_at',
        'emergency_charge',
        'distance_travelled_km',
        'auto_expire_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'vet_latitude' => 'decimal:8',
            'vet_longitude' => 'decimal:8',
            'acknowledged_at' => 'datetime',
            'completed_at' => 'datetime',
            'vet_departed_at' => 'datetime',
            'vet_arrived_at' => 'datetime',
            'treatment_started_at' => 'datetime',
            'auto_expire_at' => 'datetime',
            'response_time_seconds' => 'integer',
            'arrival_time_seconds' => 'integer',
            'emergency_charge' => 'integer',
            'distance_travelled_km' => 'decimal:2',
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

    public function visitRecord()
    {
        return $this->hasOne(VisitRecord::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'payable_id')
            ->where('payable_type', 'sos_request');
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            'pending', 'acknowledged', 'in_progress',
            'sos_pending', 'sos_accepted', 'vet_on_the_way',
            'arrived', 'treatment_in_progress',
        ]);
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
        return in_array($this->status, [
            'pending', 'acknowledged', 'in_progress',
            'sos_pending', 'sos_accepted', 'vet_on_the_way',
            'arrived', 'treatment_in_progress',
        ]);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            'pending', 'acknowledged', 'in_progress',
            'sos_pending', 'sos_accepted', 'vet_on_the_way',
        ]);
    }

    public function canBeCompleted(): bool
    {
        return in_array($this->status, ['in_progress', 'treatment_in_progress']);
    }
}
