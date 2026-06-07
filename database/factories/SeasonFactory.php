<?php

namespace Database\Factories;

use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Season>
 */
class SeasonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Низкий сезон', 'Высокий сезон', 'Праздничный сезон']),
            'date_from' => fake()->dateTimeBetween('-1 month', '+1 month'),
            'date_to' => fake()->dateTimeBetween('+2 months', '+4 months'),
        ];
    }
}
