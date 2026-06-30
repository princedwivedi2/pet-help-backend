<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Chat message inside a ConsultationSession.
 * `type=system_event` is used for status transitions (e.g. "vet joined").
 */
class ConsultationMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_session_id',
        'sender_id',
        'sender_role',
        'type',
        'body',
        'attachment_path',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ConsultationSession::class, 'consultation_session_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
