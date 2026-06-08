<?php

namespace Tests\Feature\Filament\Resources\Bookings;

use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Mail\BookingCancelled;
use App\Mail\BookingConfirmed;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Notifications\UserNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class BookingsTableActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function makeBooking(string $status): Booking
    {
        $roomType = RoomType::factory()->create();
        $room = Room::factory()->create(['room_type_id' => $roomType->id]);

        return Booking::factory()->create([
            'room_id' => $room->id,
            'status' => $status,
        ]);
    }

    public function test_confirm_action_is_only_visible_for_pending_bookings(): void
    {
        $admin = $this->makeAdmin();
        $pending = $this->makeBooking('pending');
        $confirmed = $this->makeBooking('confirmed');

        Livewire::actingAs($admin)
            ->test(ListBookings::class)
            ->assertActionVisible(TestAction::make('confirm')->table($pending))
            ->assertActionHidden(TestAction::make('confirm')->table($confirmed));
    }

    public function test_cancel_action_is_hidden_for_already_cancelled_bookings(): void
    {
        $admin = $this->makeAdmin();
        $pending = $this->makeBooking('pending');
        $cancelled = $this->makeBooking('cancelled');

        Livewire::actingAs($admin)
            ->test(ListBookings::class)
            ->assertActionVisible(TestAction::make('cancel')->table($pending))
            ->assertActionHidden(TestAction::make('cancel')->table($cancelled));
    }

    public function test_confirm_action_marks_booking_as_confirmed_and_sends_email(): void
    {
        Mail::fake();
        Notification::fake();

        $admin = $this->makeAdmin();
        $booking = $this->makeBooking('pending');

        Livewire::actingAs($admin)
            ->test(ListBookings::class)
            ->callAction(TestAction::make('confirm')->table($booking));

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'confirmed',
        ]);

        Mail::assertQueued(BookingConfirmed::class, function (BookingConfirmed $mail) use ($booking) {
            return $mail->booking->is($booking)
                && $mail->hasTo($booking->user->email);
        });

        Mail::assertNotQueued(BookingCancelled::class);

        Notification::assertSentTo(
            $booking->user,
            UserNotification::class,
            fn (UserNotification $notification) => $notification->title === 'Бронирование подтверждено'
        );
    }

    public function test_cancel_action_marks_booking_as_cancelled_and_notifies_guest_and_directors(): void
    {
        Mail::fake();
        Notification::fake();

        $admin = $this->makeAdmin();
        $director = User::factory()->create();
        $director->assignRole('director');
        $booking = $this->makeBooking('pending');

        Livewire::actingAs($admin)
            ->test(ListBookings::class)
            ->callAction(TestAction::make('cancel')->table($booking));

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
        ]);

        Mail::assertQueued(BookingCancelled::class, function (BookingCancelled $mail) use ($booking) {
            return $mail->booking->is($booking)
                && $mail->hasTo($booking->user->email)
                && ! $mail->isDirector;
        });

        Mail::assertQueued(BookingCancelled::class, function (BookingCancelled $mail) use ($booking, $director) {
            return $mail->booking->is($booking)
                && $mail->hasTo($director->email)
                && $mail->isDirector;
        });

        Mail::assertQueued(BookingCancelled::class, 2);

        Mail::assertNotQueued(BookingConfirmed::class);

        Notification::assertSentTo(
            $booking->user,
            UserNotification::class,
            fn (UserNotification $notification) => $notification->title === 'Бронирование отменено'
        );
        Notification::assertSentTo(
            $director,
            UserNotification::class,
            fn (UserNotification $notification) => $notification->title === 'Бронирование отменено'
        );
    }

    public function test_cancel_action_saves_reason_and_sends_it_to_the_guest(): void
    {
        Mail::fake();
        Notification::fake();

        $admin = $this->makeAdmin();
        $booking = $this->makeBooking('pending');

        Livewire::actingAs($admin)
            ->test(ListBookings::class)
            ->callAction(TestAction::make('cancel')->table($booking), ['reason' => 'Нет свободных номеров на эти даты']);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
            'admin_comment' => 'Нет свободных номеров на эти даты',
        ]);

        $mail = new BookingCancelled($booking->fresh(['room.roomType', 'user']));
        $this->assertStringContainsString('Нет свободных номеров на эти даты', $mail->render());
    }

    public function test_guest_notes_are_shown_only_for_website_bookings(): void
    {
        $admin = $this->makeAdmin();
        $roomType = RoomType::factory()->create();
        $room = Room::factory()->create(['room_type_id' => $roomType->id]);
        $guest = User::factory()->create(['admin_notes' => 'Был неадекватен в прошлый раз']);

        $fromWebsite = Booking::factory()->create([
            'user_id' => $guest->id,
            'room_id' => $room->id,
            'source' => 'website',
        ]);
        $fromAdmin = Booking::factory()->create([
            'user_id' => $guest->id,
            'room_id' => $room->id,
            'source' => 'admin',
        ]);

        Livewire::actingAs($admin)
            ->test(ListBookings::class)
            ->assertTableColumnStateSet('guest_notes', 'Был неадекватен в прошлый раз', $fromWebsite)
            ->assertTableColumnStateSet('guest_notes', null, $fromAdmin);
    }
}
