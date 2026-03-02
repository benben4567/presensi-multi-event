<?php

namespace App\Models;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    /** @use HasFactory<\Database\Factories\EventFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'code',
        'name',
        'start_at',
        'end_at',
        'status',
        'override_until',
        'settings',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'override_until' => 'datetime',
            'settings' => 'array',
            'status' => EventStatus::class,
        ];
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(EventSession::class);
    }

    public function eventParticipants(): HasMany
    {
        return $this->hasMany(EventParticipant::class);
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function scanAttempts(): HasMany
    {
        return $this->hasMany(ScanAttempt::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** Apakah presensi masih bisa dilakukan (termasuk grace override). */
    public function isAttendanceOpen(): bool
    {
        $now = now();

        if ($now->lessThanOrEqualTo($this->end_at)) {
            return true;
        }

        return $this->override_until !== null && $now->lessThanOrEqualTo($this->override_until);
    }
}
