<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitation extends Model
{
    /** @use HasFactory<\Database\Factories\InvitationFactory> */
    use HasFactory;

    protected $fillable = [
        'event_participant_id',
        'token_hash',
        'token',
        'invitation_code',
        'issued_at',
        'expires_at',
        'revoked_at',
        'revoked_reason',
        'revoked_by',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function eventParticipant(): BelongsTo
    {
        return $this->belongsTo(EventParticipant::class);
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return now()->isAfter($this->expires_at);
    }

    public function isValid(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }

    /**
     * Generate the next sequential invitation code for an event.
     *
     * Returns null when the event has no code — sticker labels require a code.
     *
     * Format: {EVENT_CODE}-{NNNN}
     */
    public static function nextCodeForEvent(Event $event): ?string
    {
        if (! $event->code) {
            return null;
        }

        $prefix = strtoupper($event->code).'-';

        /** @var string|null $maxCode */
        $maxCode = static::query()
            ->whereHas('eventParticipant', fn (Builder $q) => $q->where('event_id', $event->id))
            ->where('invitation_code', 'like', $prefix.'%')
            ->max('invitation_code');

        $next = $maxCode === null ? 1 : ((int) substr($maxCode, -4)) + 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
