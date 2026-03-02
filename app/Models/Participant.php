<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    /** @use HasFactory<\Database\Factories\ParticipantFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'phone_e164',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function eventParticipants(): HasMany
    {
        return $this->hasMany(EventParticipant::class);
    }
}
