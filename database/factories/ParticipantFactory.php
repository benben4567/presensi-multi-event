<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Participant>
 */
class ParticipantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone_e164' => '+628'.fake()->unique()->numerify('#########'),
            'meta' => null,
        ];
    }
}
