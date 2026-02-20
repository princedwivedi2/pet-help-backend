<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class IncidentLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'pet_id',
        'sos_request_id',
        'vet_profile_id',
        'title',
        'description',
        'incident_type',
        'status',
        'incident_date',
        'follow_up_date',
        'attachments',
        'vet_notes',
    ];

    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
            'follow_up_date' => 'date',
            'attachments' => 'array',
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

    public function sosRequest(): BelongsTo
    {
        return $this->belongsTo(SosRequest::class);
    }

    public function vetProfile(): BelongsTo
    {
        return $this->belongsTo(VetProfile::class);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForPet($query, $petId)
    {
        return $query->where('pet_id', $petId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeDateRange($query, $from, $to)
    {
        if ($from) {
            $query->where('incident_date', '>=', $from);
        }
        if ($to) {
            $query->where('incident_date', '<=', $to);
        }
        return $query;
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
