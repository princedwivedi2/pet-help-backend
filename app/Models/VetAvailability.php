<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VetAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'vet_profile_id',
        'day_of_week',
        'open_time',
        'close_time',
        'is_emergency_hours',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'open_time' => 'datetime:H:i',
            'close_time' => 'datetime:H:i',
            'is_emergency_hours' => 'boolean',
        ];
    }

    public function vetProfile(): BelongsTo
    {
        return $this->belongsTo(VetProfile::class);
    }

    public static function dayName(int $day): string
    {
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        return $days[$day] ?? 'Unknown';
    }
}
