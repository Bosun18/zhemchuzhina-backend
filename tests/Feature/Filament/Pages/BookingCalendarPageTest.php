<?php

namespace Tests\Feature\Filament\Pages;

use App\Filament\Pages\BookingCalendarPage;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class BookingCalendarPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        Mail::fake();
        Notification::fake();
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function makeRoom(int $number = 1): Room
    {
        $roomType = RoomType::factory()->create();

        return Room::factory()->create(['room_type_id' => $roomType->id, 'number' => $number]);
    }

    public function test_calendar_renders_booking_bar_with_guest_name(): void
    {
        $admin = $this->makeAdmin();
        $room = $this->makeRoom(5);
        $guest = User::factory()->create(['name' => 'Иван Иванов']);

        Booking::factory()->create([
            'room_id' => $room->id,
            'user_id' => $guest->id,
            'check_in' => '2026-06-10',
            'check_out' => '2026-06-15',
            'status' => 'pending',
        ]);

        Livewire::actingAs($admin)
            ->withQueryParams(['month' => '2026-06'])
            ->test(BookingCalendarPage::class)
            ->assertOk()
            ->assertSee('Иван Иванов')
            ->assertSee('№5')
            ->assertSee('10.06–15.06')
            ->assertSee('5 ночей')
            ->assertSee('Ожидает');
    }

    public function test_calendar_marks_a_five_night_bar_with_matching_colspan(): void
    {
        $admin = $this->makeAdmin();
        $room = $this->makeRoom();

        Booking::factory()->create([
            'room_id' => $room->id,
            'check_in' => '2026-06-10',
            'check_out' => '2026-06-15',
            'status' => 'confirmed',
        ]);

        // 10–14 июня заняты (5 ночей) → одна полоса colspan=5
        Livewire::actingAs($admin)
            ->withQueryParams(['month' => '2026-06'])
            ->test(BookingCalendarPage::class)
            ->assertOk()
            ->assertSee('colspan="5"', escape: false)
            ->assertSee('Подтверждено');
    }

    public function test_calendar_excludes_cancelled_bookings(): void
    {
        $admin = $this->makeAdmin();
        $room = $this->makeRoom();
        $guest = User::factory()->create(['name' => 'Отменённый Гость']);

        Booking::factory()->create([
            'room_id' => $room->id,
            'user_id' => $guest->id,
            'check_in' => '2026-06-10',
            'check_out' => '2026-06-15',
            'status' => 'cancelled',
        ]);

        Livewire::actingAs($admin)
            ->withQueryParams(['month' => '2026-06'])
            ->test(BookingCalendarPage::class)
            ->assertOk()
            ->assertDontSee('Отменённый Гость');
    }
}
