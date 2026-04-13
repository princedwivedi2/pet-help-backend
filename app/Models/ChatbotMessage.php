<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ChatbotMessage extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'session_id',
        'role',
        'content',
        'tokens_used',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatbotSession::class, 'session_id');
    }
}
