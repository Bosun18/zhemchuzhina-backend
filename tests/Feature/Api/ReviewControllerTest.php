<?php

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_index_returns_only_approved_reviews(): void
    {
        $approved = Review::factory()->create(['status' => 'approved']);
        Review::factory()->create(['status' => 'pending']);
        Review::factory()->create(['status' => 'rejected']);

        $response = $this->getJson('/api/reviews');

        $response->assertOk();
        $response->assertJsonCount(1);
        $this->assertSame($approved->id, $response->json('0.id'));
    }

    public function test_user_can_leave_review_for_own_confirmed_booking(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');
        $booking = Booking::factory()->create(['user_id' => $user->id, 'status' => 'confirmed']);

        $response = $this->actingAs($user)->postJson('/api/reviews', [
            'booking_id' => $booking->id,
            'rating' => 5,
            'text' => 'Отличный отель, всё понравилось!',
        ]);

        $response->assertCreated()->assertJson([
            'rating' => 5,
            'text' => 'Отличный отель, всё понравилось!',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('reviews', [
            'booking_id' => $booking->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_review_someone_elses_booking(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');
        $otherUsersBooking = Booking::factory()->create(['status' => 'confirmed']);

        $response = $this->actingAs($user)->postJson('/api/reviews', [
            'booking_id' => $otherUsersBooking->id,
            'rating' => 4,
            'text' => 'Текст отзыва на чужое бронирование.',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('booking_id');
    }

    public function test_user_cannot_review_unconfirmed_booking(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');
        $booking = Booking::factory()->create(['user_id' => $user->id, 'status' => 'pending']);

        $response = $this->actingAs($user)->postJson('/api/reviews', [
            'booking_id' => $booking->id,
            'rating' => 4,
            'text' => 'Текст отзыва на неподтверждённое бронирование.',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('booking_id');
    }

    public function test_user_cannot_review_booking_twice(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');
        $booking = Booking::factory()->create(['user_id' => $user->id, 'status' => 'confirmed']);
        Review::factory()->create(['user_id' => $user->id, 'booking_id' => $booking->id]);

        $response = $this->actingAs($user)->postJson('/api/reviews', [
            'booking_id' => $booking->id,
            'rating' => 4,
            'text' => 'Повторный отзыв на то же бронирование.',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('booking_id');
    }

    public function test_store_validates_input(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');

        $response = $this->actingAs($user)->postJson('/api/reviews', [
            'booking_id' => 999,
            'rating' => 11,
            'text' => '',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['booking_id', 'rating', 'text']);
    }
}
