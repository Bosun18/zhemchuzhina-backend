<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenInactivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_request_succeeds_when_token_was_recently_used(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');
        $token = $user->createToken('auth_token');
        $token->accessToken->forceFill(['last_used_at' => now()->subMinutes(10)])->save();

        $response = $this->withToken($token->plainTextToken)->getJson('/api/profile');

        $response->assertOk();
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $token->accessToken->id]);
    }

    public function test_request_is_rejected_and_token_revoked_after_30_minutes_of_inactivity(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');
        $token = $user->createToken('auth_token');
        $token->accessToken->forceFill(['last_used_at' => now()->subMinutes(31)])->save();

        $response = $this->withToken($token->plainTextToken)->getJson('/api/profile');

        $response->assertStatus(401);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
    }

    public function test_token_with_no_recorded_usage_is_treated_as_active(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');
        $token = $user->createToken('auth_token');

        $this->assertNull($token->accessToken->last_used_at);

        $response = $this->withToken($token->plainTextToken)->getJson('/api/profile');

        $response->assertOk();
    }
}
