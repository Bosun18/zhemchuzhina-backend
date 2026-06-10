<?php

namespace Tests\Feature\Api;

use App\Mail\BookingCancelled;
use App\Mail\BookingConfirmed;
use App\Mail\NewBookingCreated;
use App\Models\Booking;
use App\Models\Review;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\UserNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BookingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function makeRoom(int $maxGuests = 3): Room
    {
        $roomType = RoomType::factory()->create(['max_guests' => $maxGuests]);

        return Room::factory()->create(['room_type_id' => $roomType->id, 'is_active' => true]);
    }

    public function test_guest_cannot_view_my_bookings(): void
    {
        $response = $this->getJson('/api/my-bookings');

        $response->assertStatus(401);
    }

    public function test_user_can_view_own_bookings(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');
        $room = $this->makeRoom();
        $booking = Booking::factory()->create(['user_id' => $user->id, 'room_id' => $room->id]);
        Booking::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/my-bookings');

        $response->assertOk();
        $response->assertJsonCount(1);
        $this->assertSame($booking->id, $response->json('0.id'));
    }

    public function test_my_bookings_return_null_review_when_no_review_exists(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');
        $room = $this->makeRoom();
        Booking::factory()->create(['user_id' => $user->id, 'room_id' => $room->id]);

        $response = $this->actingAs($user)->getJson('/api/my-bookings');

        $response->assertOk()->assertJsonPath('0.review', null);
    }

    public function test_my_bookings_return_review_id_and_moderation_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');
        $room = $this->makeRoom();
        $booking = Booking::factory()->create(['user_id' => $user->id, 'room_id' => $room->id]);
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->getJson('/api/my-bookings');

        $response->assertOk()
            ->assertJsonPath('0.review.id', $review->id)
            ->assertJsonPath('0.review.status', 'pending')
            ->assertJsonPath('0.review.rating', $review->rating)
            ->assertJsonPath('0.review.text', $review->text);
    }

    public function test_user_can_create_booking(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');
        $room = $this->makeRoom(maxGuests: 3);

        $response = $this->actingAs($user)->postJson('/api/bookings', [
            'room_id' => $room->id,
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(8)->toDateString(),
            'guests_count' => 2,
            'comment' => 'Поздний заезд',
        ]);

        $response->assertCreated()->assertJson([
            'guests_count' => 2,
            'status' => 'pending',
            'comment' => 'Поздний заезд',
            'room' => [
                'id' => $room->id,
            ],
        ]);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $user->id,
            'room_id' => $room->id,
            'status' => 'pending',
        ]);
    }

    public function test_admins_are_notified_when_booking_is_created(): void
    {
        Mail::fake();
        Setting::set('notification_emails_booking', ['director@example.com', 'admin@example.com']);

        $user = User::factory()->create();
        $user->assignRole('guest');
        $room = $this->makeRoom(maxGuests: 3);

        $this->actingAs($user)->postJson('/api/bookings', [
            'room_id' => $room->id,
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(8)->toDateString(),
            'guests_count' => 2,
        ])->assertCreated();

        Mail::assertQueued(NewBookingCreated::class, 2);
        Mail::assertQueued(NewBookingCreated::class, fn (NewBookingCreated $mail) => $mail->hasTo('director@example.com'));
        Mail::assertQueued(NewBookingCreated::class, fn (NewBookingCreated $mail) => $mail->hasTo('admin@example.com'));
    }

    public function test_admin_panel_users_receive_bell_notification_when_booking_is_created(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $director = User::factory()->create();
        $director->assignRole('director');

        $user = User::factory()->create();
        $user->assignRole('guest');
        $room = $this->makeRoom(maxGuests: 3);

        $this->actingAs($user)->postJson('/api/bookings', [
            'room_id' => $room->id,
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(8)->toDateString(),
            'guests_count' => 2,
        ])->assertCreated();

        Notification::assertSentTo(
            $admin,
            UserNotification::class,
            fn (UserNotification $notification) => $notification->title === 'Новое бронирование'
        );

        // Сценарий 2: новое бронирование — только админу, не директору.
        Notification::assertNotSentTo($director, UserNotification::class);
    }

    public function test_booking_fails_when_guests_exceed_room_capacity(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');
        $room = $this->makeRoom(maxGuests: 2);

        $response = $this->actingAs($user)->postJson('/api/bookings', [
            'room_id' => $room->id,
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(8)->toDateString(),
            'guests_count' => 5,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('guests_count');
    }

    public function test_booking_fails_on_overlapping_dates(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');
        $room = $this->makeRoom();

        Booking::factory()->create([
            'room_id' => $room->id,
            'status' => 'confirmed',
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(10)->toDateString(),
        ]);

        $response = $this->actingAs($user)->postJson('/api/bookings', [
            'room_id' => $room->id,
            'check_in' => now()->addDays(7)->toDateString(),
            'check_out' => now()->addDays(12)->toDateString(),
            'guests_count' => 1,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('room_id');
    }

    public function test_back_to_back_booking_starting_on_existing_checkout_is_allowed(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');
        $room = $this->makeRoom();

        Booking::factory()->create([
            'room_id' => $room->id,
            'status' => 'confirmed',
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(10)->toDateString(),
        ]);

        $response = $this->actingAs($user)->postJson('/api/bookings', [
            'room_id' => $room->id,
            'check_in' => now()->addDays(10)->toDateString(),
            'check_out' => now()->addDays(12)->toDateString(),
            'guests_count' => 1,
        ]);

        $response->assertCreated()->assertJson([
            'check_in' => now()->addDays(10)->toDateString(),
            'check_out' => now()->addDays(12)->toDateString(),
        ]);
    }

    public function test_back_to_back_booking_ending_on_existing_checkin_is_allowed(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');
        $room = $this->makeRoom();

        Booking::factory()->create([
            'room_id' => $room->id,
            'status' => 'confirmed',
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(10)->toDateString(),
        ]);

        $response = $this->actingAs($user)->postJson('/api/bookings', [
            'room_id' => $room->id,
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(5)->toDateString(),
            'guests_count' => 1,
        ]);

        $response->assertCreated()->assertJson([
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(5)->toDateString(),
        ]);
    }

    public function test_booking_fails_on_single_night_overlap(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');
        $room = $this->makeRoom();

        Booking::factory()->create([
            'room_id' => $room->id,
            'status' => 'confirmed',
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(10)->toDateString(),
        ]);

        $response = $this->actingAs($user)->postJson('/api/bookings', [
            'room_id' => $room->id,
            'check_in' => now()->addDays(9)->toDateString(),
            'check_out' => now()->addDays(11)->toDateString(),
            'guests_count' => 1,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('room_id');
    }

    public function test_booking_fails_on_identical_dates(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');
        $room = $this->makeRoom();

        Booking::factory()->create([
            'room_id' => $room->id,
            'status' => 'confirmed',
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(10)->toDateString(),
        ]);

        $response = $this->actingAs($user)->postJson('/api/bookings', [
            'room_id' => $room->id,
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(10)->toDateString(),
            'guests_count' => 1,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('room_id');
    }

    public function test_cancelled_booking_does_not_block_dates(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');
        $room = $this->makeRoom();

        Booking::factory()->create([
            'room_id' => $room->id,
            'status' => 'cancelled',
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(10)->toDateString(),
        ]);

        $response = $this->actingAs($user)->postJson('/api/bookings', [
            'room_id' => $room->id,
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(10)->toDateString(),
            'guests_count' => 1,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('bookings', [
            'user_id' => $user->id,
            'room_id' => $room->id,
            'status' => 'pending',
        ]);
    }

    public function test_booking_validates_input(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');

        $response = $this->actingAs($user)->postJson('/api/bookings', [
            'room_id' => 999,
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->subDay()->toDateString(),
            'guests_count' => 0,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['room_id', 'check_out', 'guests_count']);
    }

    public function test_owner_can_cancel_own_booking(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');
        $booking = Booking::factory()->create(['user_id' => $user->id, 'status' => 'pending']);

        $response = $this->actingAs($user)->deleteJson("/api/bookings/{$booking->id}");

        $response->assertOk()->assertJson(['status' => 'cancelled']);
        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_self_cancellation_notifies_guest_admins_and_directors(): void
    {
        Mail::fake();
        Notification::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $director = User::factory()->create();
        $director->assignRole('director');
        $guest = User::factory()->create();
        $guest->assignRole('guest');
        $booking = Booking::factory()->create(['user_id' => $guest->id, 'status' => 'confirmed']);

        $this->actingAs($guest)
            ->deleteJson("/api/bookings/{$booking->id}")
            ->assertOk();

        // Гостю — подтверждение отмены.
        Mail::assertQueued(BookingCancelled::class, fn (BookingCancelled $mail) => $mail->hasTo($guest->email) && ! $mail->isDirector);
        Notification::assertSentTo(
            $guest,
            UserNotification::class,
            fn (UserNotification $notification) => $notification->title === 'Бронирование отменено'
        );

        // Администратору и директору — уведомление о самоотмене гостя.
        Mail::assertQueued(BookingCancelled::class, fn (BookingCancelled $mail) => $mail->hasTo($admin->email) && $mail->isDirector);
        Mail::assertQueued(BookingCancelled::class, fn (BookingCancelled $mail) => $mail->hasTo($director->email) && $mail->isDirector);
        Mail::assertQueued(BookingCancelled::class, 3);

        Notification::assertSentTo(
            $admin,
            UserNotification::class,
            fn (UserNotification $notification) => $notification->title === 'Гость отменил бронирование'
        );
        Notification::assertSentTo(
            $director,
            UserNotification::class,
            fn (UserNotification $notification) => $notification->title === 'Гость отменил бронирование'
        );
    }

    public function test_user_cannot_cancel_someone_elses_booking(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');
        $booking = Booking::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($user)->deleteJson("/api/bookings/{$booking->id}");

        $response->assertStatus(403);
        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_staff_can_cancel_any_booking(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('admin');
        $booking = Booking::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($staff)->deleteJson("/api/bookings/{$booking->id}");

        $response->assertOk()->assertJson(['status' => 'cancelled']);
    }

    public function test_guest_cannot_access_admin_booking_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');

        $response = $this->actingAs($user)->getJson('/api/admin/bookings');

        $response->assertStatus(403);
    }

    public function test_staff_can_list_all_bookings(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('director');
        Booking::factory()->count(2)->create();

        $response = $this->actingAs($staff)->getJson('/api/admin/bookings');

        $response->assertOk();
        $response->assertJsonCount(2);
        $response->assertJsonStructure([
            ['id', 'status', 'room', 'user', 'admin_comment'],
        ]);
    }

    public function test_staff_can_confirm_booking(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('admin');
        $booking = Booking::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($staff)->patchJson("/api/admin/bookings/{$booking->id}/confirm");

        $response->assertOk()->assertJson(['status' => 'confirmed']);
        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_confirming_booking_via_api_notifies_guest(): void
    {
        Mail::fake();
        Notification::fake();

        $staff = User::factory()->create();
        $staff->assignRole('admin');
        $guest = User::factory()->create();
        $guest->assignRole('guest');
        $booking = Booking::factory()->create(['user_id' => $guest->id, 'status' => 'pending']);

        $this->actingAs($staff)
            ->patchJson("/api/admin/bookings/{$booking->id}/confirm")
            ->assertOk();

        Mail::assertQueued(BookingConfirmed::class, fn (BookingConfirmed $mail) => $mail->booking->is($booking)
            && $mail->hasTo($guest->email));

        Notification::assertSentTo(
            $guest,
            UserNotification::class,
            fn (UserNotification $notification) => $notification->title === 'Бронирование подтверждено'
        );
    }

    public function test_cancelling_booking_via_api_notifies_guest_and_directors(): void
    {
        Mail::fake();
        Notification::fake();

        $staff = User::factory()->create();
        $staff->assignRole('admin');
        $director = User::factory()->create();
        $director->assignRole('director');
        $guest = User::factory()->create();
        $guest->assignRole('guest');
        $booking = Booking::factory()->create(['user_id' => $guest->id, 'status' => 'pending']);

        $this->actingAs($staff)
            ->patchJson("/api/admin/bookings/{$booking->id}/cancel")
            ->assertOk();

        Mail::assertQueued(BookingCancelled::class, fn (BookingCancelled $mail) => $mail->booking->is($booking)
            && $mail->hasTo($guest->email)
            && ! $mail->isDirector);
        Mail::assertQueued(BookingCancelled::class, fn (BookingCancelled $mail) => $mail->booking->is($booking)
            && $mail->hasTo($director->email)
            && $mail->isDirector);
        Mail::assertQueued(BookingCancelled::class, 2);

        Notification::assertSentTo(
            $guest,
            UserNotification::class,
            fn (UserNotification $notification) => $notification->title === 'Бронирование отменено'
        );
        Notification::assertSentTo(
            $director,
            UserNotification::class,
            fn (UserNotification $notification) => $notification->title === 'Бронирование отменено'
        );
    }

    public function test_staff_can_create_booking_for_another_user(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('director');
        $guest = User::factory()->create();
        $room = $this->makeRoom();

        $response = $this->actingAs($staff)->postJson('/api/admin/bookings', [
            'user_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(4)->toDateString(),
            'guests_count' => 1,
            'admin_comment' => 'Забронировано по телефону',
        ]);

        $response->assertCreated()->assertJson([
            'status' => 'confirmed',
            'admin_comment' => 'Забронировано по телефону',
            'user' => ['id' => $guest->id],
        ]);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $guest->id,
            'room_id' => $room->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_admin_booking_fails_on_overlap(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('admin');
        $guest = User::factory()->create();
        $room = $this->makeRoom();

        Booking::factory()->create([
            'room_id' => $room->id,
            'status' => 'confirmed',
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(10)->toDateString(),
        ]);

        $response = $this->actingAs($staff)->postJson('/api/admin/bookings', [
            'user_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => now()->addDays(6)->toDateString(),
            'check_out' => now()->addDays(9)->toDateString(),
            'guests_count' => 1,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('room_id');
    }

    public function test_admin_back_to_back_booking_starting_on_existing_checkout_is_allowed(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('admin');
        $guest = User::factory()->create();
        $room = $this->makeRoom();

        Booking::factory()->create([
            'room_id' => $room->id,
            'status' => 'confirmed',
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(10)->toDateString(),
        ]);

        $response = $this->actingAs($staff)->postJson('/api/admin/bookings', [
            'user_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => now()->addDays(10)->toDateString(),
            'check_out' => now()->addDays(12)->toDateString(),
            'guests_count' => 1,
        ]);

        $response->assertCreated()->assertJson([
            'check_in' => now()->addDays(10)->toDateString(),
            'check_out' => now()->addDays(12)->toDateString(),
        ]);
    }

    public function test_admin_booking_fails_on_single_night_overlap(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('admin');
        $guest = User::factory()->create();
        $room = $this->makeRoom();

        Booking::factory()->create([
            'room_id' => $room->id,
            'status' => 'confirmed',
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(10)->toDateString(),
        ]);

        $response = $this->actingAs($staff)->postJson('/api/admin/bookings', [
            'user_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => now()->addDays(9)->toDateString(),
            'check_out' => now()->addDays(11)->toDateString(),
            'guests_count' => 1,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('room_id');
    }
}
