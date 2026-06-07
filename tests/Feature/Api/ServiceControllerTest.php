<?php

namespace Tests\Feature\Api;

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_only_active_services_ordered_by_title(): void
    {
        $b = Service::factory()->create(['title' => 'Б услуга', 'is_active' => true]);
        $a = Service::factory()->create(['title' => 'А услуга', 'is_active' => true]);
        Service::factory()->create(['title' => 'В услуга', 'is_active' => false]);

        $response = $this->getJson('/api/services');

        $response->assertOk();
        $response->assertJsonCount(2);
        $this->assertSame($a->id, $response->json('0.id'));
        $this->assertSame($b->id, $response->json('1.id'));
    }
}
