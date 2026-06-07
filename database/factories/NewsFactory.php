<?php

namespace Database\Factories;

use App\Models\News;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<News>
 */
class NewsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(6),
            'content' => fake()->paragraphs(3, true),
            'image' => 'news/'.fake()->uuid().'.jpg',
            'is_published' => true,
            'published_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
