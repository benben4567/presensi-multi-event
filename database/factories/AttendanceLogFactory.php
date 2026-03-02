<?php

namespace Database\Factories;

use App\Enums\AttendanceAction;
use App\Models\Device;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AttendanceLog>
 */
class AttendanceLogFactory extends Factory
{
    public function definition(): array
    {
        $event = Event::factory()->create();
        $session = EventSession::factory()->for($event)->create();
        $enrollment = EventParticipant::factory()->for($event)->create();

        return [
            'event_id' => $event->id,
            'event_participant_id' => $enrollment->id,
            'session_id' => $session->id,
            'action' => AttendanceAction::CheckIn,
            'scanned_at' => now(),
            'device_id' => Device::factory(),
            'operator_user_id' => User::factory(),
        ];
    }

    public function checkOut(): static
    {
        return $this->state(['action' => AttendanceAction::CheckOut]);
    }
}
