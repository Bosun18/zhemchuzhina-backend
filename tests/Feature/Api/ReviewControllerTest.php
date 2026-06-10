<?php

namespace Tests\Feature\Api;

use App\Mail\NewReviewSubmitted;
use App\Models\Booking;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\UserNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
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

    public function test_admins_are_notified_when_review_is_submitted(): void
    {
        Mail::fake();
        Setting::set('notification_emails_review', ['director@example.com']);

        $user = User::factory()->create();
        $user->assignRole('guest');
        $booking = Booking::factory()->create(['user_id' => $user->id, 'status' => 'confirmed']);

        $this->actingAs($user)->postJson('/api/reviews', [
            'booking_id' => $booking->id,
            'rating' => 5,
            'text' => 'Отличный отель, всё понравилось!',
        ])->assertCreated();

        Mail::assertQueued(NewReviewSubmitted::class, fn (NewReviewSubmitted $mail) => $mail->hasTo('director@example.com')
            && $mail->review->booking_id === $booking->id);
    }

    public function test_admin_panel_users_receive_bell_notification_when_review_is_submitted(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $director = User::factory()->create();
        $director->assignRole('director');

        $user = User::factory()->create();
        $user->assignRole('guest');
        $booking = Booking::factory()->create(['user_id' => $user->id, 'status' => 'confirmed']);

        $this->actingAs($user)->postJson('/api/reviews', [
            'booking_id' => $booking->id,
            'rating' => 5,
            'text' => 'Отличный отель, всё понравилось!',
        ])->assertCreated();

        Notification::assertSentTo(
            $admin,
            UserNotification::class,
            fn (UserNotification $notification) => $notification->title === 'Новый отзыв на модерации'
        );

        // Сценарий 2: новый отзыв — только админу, не директору.
        Notification::assertNotSentTo($director, UserNotification::class);
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

    public function test_database_rejects_a_second_review_for_the_same_booking(): void
    {
        $booking = Booking::factory()->create(['status' => 'confirmed']);
        Review::factory()->create(['booking_id' => $booking->id]);

        $this->expectException(UniqueConstraintViolationException::class);

        Review::factory()->create(['booking_id' => $booking->id]);
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

    public function test_owner_can_update_own_review(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'admin_comment' => 'Спасибо за отзыв!',
        ]);

        $response = $this->actingAs($user)->patchJson("/api/reviews/{$review->id}", [
            'rating' => 8,
            'text' => 'Обновлённый текст отзыва.',
        ]);

        $response->assertOk()->assertJson([
            'id' => $review->id,
            'rating' => 8,
            'text' => 'Обновлённый текст отзыва.',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 8,
            'text' => 'Обновлённый текст отзыва.',
            'status' => 'pending',
            'admin_comment' => null,
        ]);
    }

    public function test_updated_approved_review_disappears_from_public_feed(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');
        $review = Review::factory()->create(['user_id' => $user->id, 'status' => 'approved']);

        $this->getJson('/api/reviews')->assertOk()->assertJsonCount(1);

        $this->actingAs($user)->patchJson("/api/reviews/{$review->id}", [
            'rating' => 7,
            'text' => 'Передумал, дополняю отзыв.',
        ])->assertOk();

        $this->getJson('/api/reviews')->assertOk()->assertJsonCount(0);
    }

    public function test_user_cannot_update_someone_elses_review(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');
        $review = Review::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($user)->patchJson("/api/reviews/{$review->id}", [
            'rating' => 1,
            'text' => 'Попытка изменить чужой отзыв.',
        ]);

        $response->assertStatus(403);
        $this->assertNotSame('Попытка изменить чужой отзыв.', $review->fresh()->text);
    }

    public function test_guest_cannot_update_review(): void
    {
        $review = Review::factory()->create();

        $response = $this->patchJson("/api/reviews/{$review->id}", [
            'rating' => 5,
            'text' => 'Неавторизованная попытка.',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_validates_input(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');
        $review = Review::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->patchJson("/api/reviews/{$review->id}", [
            'rating' => 11,
            'text' => '',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['rating', 'text']);
    }

    public function test_staff_are_notified_when_review_is_updated(): void
    {
        Mail::fake();
        Notification::fake();
        Setting::set('notification_emails_review', ['director@example.com']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();
        $user->assignRole('guest');
        $review = Review::factory()->create(['user_id' => $user->id, 'status' => 'approved']);

        $this->actingAs($user)->patchJson("/api/reviews/{$review->id}", [
            'rating' => 9,
            'text' => 'Обновлённый текст отзыва.',
        ])->assertOk();

        Mail::assertQueued(NewReviewSubmitted::class, fn (NewReviewSubmitted $mail) => $mail->hasTo('director@example.com')
            && $mail->review->is($review));

        Notification::assertSentTo(
            $admin,
            UserNotification::class,
            fn (UserNotification $notification) => $notification->title === 'Отзыв изменён и ожидает повторной модерации'
        );
    }
}
