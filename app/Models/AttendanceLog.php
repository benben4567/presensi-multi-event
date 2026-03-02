<?php

namespace App\Models;

use App\Enums\AttendanceAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    /** @use HasFactory<\Database\Factories\AttendanceLogFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'event_participant_id',
        'session_id',
        'action',
        'scanned_at',
        'device_id',
        'operator_user_id',
    ];

    protected function casts(): array
    {
        return [
            'action' => AttendanceAction::class,
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

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_user_id');
    }
}
