<?php

namespace Tests\Feature\Api;

use App\Models\Gallery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GalleryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_gallery_items_ordered_by_sort_order(): void
    {
        $second = Gallery::factory()->create(['sort_order' => 2]);
        $first = Gallery::factory()->create(['sort_order' => 1]);

        $response = $this->getJson('/api/gallery');

        $response->assertOk();
        $response->assertJsonCount(2);
        $this->assertSame($first->id, $response->json('0.id'));
        $this->assertSame($second->id, $response->json('1.id'));
    }
}
