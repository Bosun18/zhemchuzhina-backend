<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guest_cannot_view_profile(): void
    {
        $response = $this->getJson('/api/profile');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_view_profile(): void
    {
        $user = User::factory()->create(['city' => 'Москва']);
        $user->assignRole('guest');

        $response = $this->actingAs($user)->getJson('/api/profile');

        $response->assertOk()->assertJson([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'city' => 'Москва',
            'role' => 'guest',
        ]);
    }

    public function test_user_can_update_name_and_city(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');

        $response = $this->actingAs($user)->patchJson('/api/profile', [
            'name' => 'Новое имя',
            'city' => 'Санкт-Петербург',
        ]);

        $response->assertOk()->assertJson([
            'name' => 'Новое имя',
            'city' => 'Санкт-Петербург',
        ]);

        $this->assertSame('Новое имя', $user->fresh()->name);
        $this->assertSame('Санкт-Петербург', $user->fresh()->city);
    }

    public function test_user_can_update_password(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');

        $response = $this->actingAs($user)->patchJson('/api/profile', [
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ]);

        $response->assertOk();

        $this->assertTrue(Hash::check('new-secret-password', $user->fresh()->password));
    }

    public function test_update_validates_input(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');

        $response = $this->actingAs($user)->patchJson('/api/profile', [
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('password');
    }
}
