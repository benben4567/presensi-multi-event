<?php

namespace Database\Factories;

use App\Enums\AccessStatus;
use App\Models\Event;
use App\Models\Participant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventParticipant>
 */
class EventParticipantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'participant_id' => Participant::factory(),
            'meta' => null,
            'access_status' => AccessStatus::Allowed,
            'access_reason' => null,
            'access_updated_at' => null,
            'access_updated_by' => null,
        ];
    }

    public function disabled(): static
    {
        return $this->state([
            'access_status' => AccessStatus::Disabled,
            'access_reason' => 'Dinonaktifkan oleh admin.',
            'access_updated_at' => now(),
        ]);
    }

    public function blacklisted(): static
    {
        return $this->state([
            'access_status' => AccessStatus::Blacklisted,
            'access_reason' => 'Melanggar peraturan.',
            'access_updated_at' => now(),
        ]);
    }
}
