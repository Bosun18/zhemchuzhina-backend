<?php

namespace Tests\Feature\Filament\Resources\Bookings;

use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Mail\BookingCancelled;
use App\Mail\BookingConfirmed;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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

        $admin = $this->makeAdmin();
        $booking = $this->makeBooking('pending');

        Livewire::actingAs($admin)
            ->test(ListBookings::class)
            ->callAction(TestAction::make('confirm')->table($booking));

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'confirmed',
        ]);

        Mail::assertSent(BookingConfirmed::class, function (BookingConfirmed $mail) use ($booking) {
            return $mail->booking->is($booking)
                && $mail->hasTo($booking->user->email);
        });

        Mail::assertNotSent(BookingCancelled::class);
    }

    public function test_cancel_action_marks_booking_as_cancelled_and_sends_email(): void
    {
        Mail::fake();

        $admin = $this->makeAdmin();
        $booking = $this->makeBooking('pending');

        Livewire::actingAs($admin)
            ->test(ListBookings::class)
            ->callAction(TestAction::make('cancel')->table($booking));

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
        ]);

        Mail::assertSent(BookingCancelled::class, function (BookingCancelled $mail) use ($booking) {
            return $mail->booking->is($booking)
                && $mail->hasTo($booking->user->email);
        });

        Mail::assertNotSent(BookingConfirmed::class);
    }
}
