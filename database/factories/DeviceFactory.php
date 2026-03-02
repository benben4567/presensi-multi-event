<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Device>
 */
class DeviceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'device_uuid' => Str::uuid()->toString(),
            'name' => fake()->optional()->words(2, true),
            'last_seen_at' => now(),
        ];
    }
}
