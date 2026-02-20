<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyGuide extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'summary',
        'content',
        'applicable_species',
        'severity_level',
        'estimated_read_minutes',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'applicable_species' => 'array',
            'is_published' => 'boolean',
            'estimated_read_minutes' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EmergencyCategory::class, 'category_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
}
