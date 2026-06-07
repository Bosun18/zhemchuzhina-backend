<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->randomElement(['Завтрак в номер', 'Трансфер', 'СПА-процедуры', 'Прокат велосипедов', 'Экскурсия по городу']),
            'description' => fake()->sentence(10),
            'price' => fake()->numberBetween(500, 5000),
            'image' => 'services/'.fake()->uuid().'.jpg',
            'is_active' => true,
        ];
    }
}
