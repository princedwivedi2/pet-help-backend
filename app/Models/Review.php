<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'vet_profile_id',
        'appointment_id',
        'sos_request_id',
        'rating',
        'comment',
        'vet_reply',
        'vet_replied_at',
        'is_flagged',
        'flag_reason',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_flagged' => 'boolean',
            'vet_replied_at' => 'datetime',
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

    public function vetProfile(): BelongsTo
    {
        return $this->belongsTo(VetProfile::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function sosRequest(): BelongsTo
    {
        return $this->belongsTo(SosRequest::class);
    }

    public function scopeForVet($query, int $vetProfileId)
    {
        return $query->where('vet_profile_id', $vetProfileId);
    }

    public function scopeNotFlagged($query)
    {
        return $query->where('is_flagged', false);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
