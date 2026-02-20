<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class BlogTag extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'slug',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    public function blogPosts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_tag')->withTimestamps();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
