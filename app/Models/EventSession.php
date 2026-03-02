<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventSession extends Model
{
    /** @use HasFactory<\Database\Factories\EventSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'start_at',
        'end_at',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class, 'session_id');
    }

    public function scanAttempts(): HasMany
    {
        return $this->hasMany(ScanAttempt::class, 'session_id');
    }
}
