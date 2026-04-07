<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PrintTemplate>
 */
class PrintTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'page_width_mm' => 80,
            'page_height_mm' => 105,
            'background_image_path' => 'print-templates/placeholder.jpg',
            'qr_x_mm' => 20.0,
            'qr_y_mm' => 30.0,
            'qr_w_mm' => 40.0,
            'qr_h_mm' => 40.0,
            'is_active' => true,
            'created_by' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
