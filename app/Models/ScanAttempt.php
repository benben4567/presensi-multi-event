<?php

namespace App\Models;

use App\Enums\ScanResultCode;
use App\Enums\ScanSource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanAttempt extends Model
{
    /** @use HasFactory<\Database\Factories\ScanAttemptFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'event_participant_id',
        'session_id',
        'device_uuid',
        'operator_user_id',
        'source',
        'result',
        'code',
        'message',
        'token_fingerprint',
        'manual_note',
        'scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'source' => ScanSource::class,
            'code' => ScanResultCode::class,
            'scanned_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function eventParticipant(): BelongsTo
    {
        return $this->belongsTo(EventParticipant::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(EventSession::class, 'session_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_user_id');
    }
}
