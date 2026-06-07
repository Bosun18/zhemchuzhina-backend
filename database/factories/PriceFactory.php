<?php

namespace Database\Factories;

use App\Models\Price;
use App\Models\RoomType;
use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Price>
 */
class PriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_type_id' => RoomType::factory(),
            'season_id' => Season::factory(),
            'price_per_night' => fake()->numberBetween(2000, 15000),
        ];
    }
}
