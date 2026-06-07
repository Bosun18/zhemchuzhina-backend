<?php

namespace Tests\Feature\Api;

use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_only_published_news_ordered_by_date_desc(): void
    {
        $older = News::factory()->create(['is_published' => true, 'published_at' => now()->subDays(5)]);
        $newer = News::factory()->create(['is_published' => true, 'published_at' => now()->subDay()]);
        News::factory()->create(['is_published' => false, 'published_at' => now()]);

        $response = $this->getJson('/api/news');

        $response->assertOk();
        $response->assertJsonCount(2);
        $this->assertSame($newer->id, $response->json('0.id'));
        $this->assertSame($older->id, $response->json('1.id'));
    }

    public function test_show_returns_published_news(): void
    {
        $news = News::factory()->create(['is_published' => true]);

        $response = $this->getJson("/api/news/{$news->id}");

        $response->assertOk()->assertJson([
            'id' => $news->id,
            'title' => $news->title,
        ]);
    }

    public function test_show_returns_404_for_unpublished_news(): void
    {
        $news = News::factory()->create(['is_published' => false]);

        $response = $this->getJson("/api/news/{$news->id}");

        $response->assertStatus(404);
    }
}
