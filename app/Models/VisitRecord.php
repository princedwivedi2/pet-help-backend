<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class VisitRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'appointment_id',
        'sos_request_id',
        'vet_profile_id',
        'user_id',
        'pet_id',
        'visit_notes',
        'diagnosis',
        'prescription_file_url',
        'before_images',
        'after_images',
        'treatment_cost_breakdown',
        'total_treatment_cost',
        'follow_up_date',
        'follow_up_notes',
    ];

    protected function casts(): array
    {
        return [
            'before_images' => 'array',
            'after_images' => 'array',
            'treatment_cost_breakdown' => 'array',
            'total_treatment_cost' => 'integer',
            'follow_up_date' => 'date',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
