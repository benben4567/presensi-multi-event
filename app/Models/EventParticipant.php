<?php

namespace App\Models;

use App\Enums\AccessStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EventParticipant extends Model
{
    /** @use HasFactory<\Database\Factories\EventParticipantFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'participant_id',
        'meta',
        'access_status',
        'access_reason',
        'access_updated_at',
        'access_updated_by',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'access_status' => AccessStatus::class,
            'access_updated_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function invitation(): HasOne
    {
        return $this->hasOne(Invitation::class);
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function scanAttempts(): HasMany
    {
        return $this->hasMany(ScanAttempt::class);
    }

    public function accessUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'access_updated_by');
    }

    public function isAllowed(): bool
    {
        return $this->access_status === AccessStatus::Allowed;
    }
}
