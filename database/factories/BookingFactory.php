<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'room_id' => Room::factory(),
            'check_in' => fake()->dateTimeBetween('+1 day', '+10 days'),
            'check_out' => fake()->dateTimeBetween('+11 days', '+20 days'),
            'guests_count' => fake()->numberBetween(1, 4),
            'status' => fake()->randomElement(['pending', 'confirmed', 'cancelled']),
            'comment' => fake()->optional()->sentence(),
            'admin_comment' => null,
        ];
    }
}
