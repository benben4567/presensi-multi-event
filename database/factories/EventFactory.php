<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+1 month');
        $end = fake()->dateTimeBetween($start, '+1 month +3 days');

        return [
            'code' => strtoupper(fake()->unique()->bothify('EVT-####')),
            'name' => fake()->sentence(3),
            'start_at' => $start,
            'end_at' => $end,
            'status' => 'draft',
            'override_until' => null,
            'settings' => [
                'enable_checkout' => false,
                'operator_display_fields' => ['name', 'phone_e164'],
            ],
        ];
    }

    public function open(): static
    {
        return $this->state(['status' => 'open']);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
            'start_at' => fake()->dateTimeBetween('-2 months', '-1 month'),
            'end_at' => fake()->dateTimeBetween('-1 month', '-1 day'),
        ]);
    }

    public function withCheckout(): static
    {
        return $this->state(fn (array $attributes) => [
            'settings' => array_merge($attributes['settings'] ?? [], ['enable_checkout' => true]),
        ]);
    }
}
