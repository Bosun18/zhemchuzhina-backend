<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => fake()->unique()->numberBetween(1, 10),
            'floor' => fake()->numberBetween(1, 2),
            'room_type_id' => RoomType::inRandomOrder()->first()?->id
                ?? RoomType::factory(),
            'is_active' => true,
        ];
    }
}
