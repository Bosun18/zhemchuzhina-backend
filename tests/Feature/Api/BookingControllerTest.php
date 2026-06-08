<?php

namespace Tests\Feature\Api;

use App\Mail\NewBookingCreated;
use App\Models\Booking;
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
}
