<?php

namespace Database\Factories;

use App\Models\EventParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invitation>
 */
class InvitationFactory extends Factory
{
    public function definition(): array
    {
        $eventParticipant = EventParticipant::factory()->create();

        return [
            'event_participant_id' => $eventParticipant->id,
            'token_hash' => hash('sha256', Str::random(64)),
            'invitation_code' => null,
            'issued_at' => now(),
            'expires_at' => $eventParticipant->event->end_at,
            'revoked_at' => null,
            'revoked_reason' => null,
            'revoked_by' => null,
        ];
    }

    public function revoked(): static
    {
        return $this->state([
            'revoked_at' => now(),
            'revoked_reason' => 'Akses dicabut.',
        ]);
    }
}
