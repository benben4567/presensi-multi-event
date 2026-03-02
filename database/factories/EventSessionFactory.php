<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventSession>
 */
class EventSessionFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+1 month');

        return [
            'event_id' => Event::factory(),
            'name' => \Carbon\Carbon::parse($start)->isoFormat('dddd, D MMMM YYYY'),
            'start_at' => $start,
            'end_at' => \Carbon\Carbon::parse($start)->endOfDay(),
            'type' => 'day',
        ];
    }
}
