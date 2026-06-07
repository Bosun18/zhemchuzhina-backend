<?php

namespace Database\Factories;

use App\Models\Gallery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gallery>
 */
class GalleryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'image' => 'gallery/'.fake()->uuid().'.jpg',
            'caption' => fake()->optional()->sentence(6),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
