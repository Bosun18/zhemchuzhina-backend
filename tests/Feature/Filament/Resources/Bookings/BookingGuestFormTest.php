<?php

namespace Tests\Feature\Filament\Resources\Bookings;

use App\Filament\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Resources\Bookings\Pages\EditBooking;
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

class BookingGuestFormTest extends TestCase
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

    private function makeRoom(): Room
    {
        $roomType = RoomType::factory()->create();

        return Room::factory()->create(['room_type_id' => $roomType->id]);
    }

    public function test_creating_a_booking_with_a_new_phone_creates_a_phone_guest(): void
    {
        $admin = $this->makeAdmin();
        $room = $this->makeRoom();

        Livewire::actingAs($admin)
            ->test(CreateBooking::class)
            ->fillForm([
                'guest_phone' => '+7 (900) 101-10-10',
                'guest_name' => 'Телефонный Гость',
                'room_id' => $room->id,
                'check_in' => '2026-07-01',
                'check_out' => '2026-07-05',
                'guests_count' => 2,
                'status' => 'pending',
                'comment' => 'Нужен трансфер',
                'guest_notes' => 'Постоянный гость, давать скидку',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $guest = User::where('registered_via_phone', true)->sole();
        $this->assertSame('Телефонный Гость', $guest->name);
        $this->assertSame('79001011010@phone.zhemchuzhina.local', $guest->email);
        $this->assertSame('Постоянный гость, давать скидку', $guest->admin_notes);
        $this->assertTrue($guest->hasRole('guest'));

        $this->assertDatabaseHas('bookings', [
            'user_id' => $guest->id,
            'room_id' => $room->id,
            'comment' => 'Нужен трансфер',
            'source' => 'admin',
        ]);
    }

    public function test_creating_a_booking_with_existing_phone_reuses_the_user(): void
    {
        $admin = $this->makeAdmin();
        $room = $this->makeRoom();
        $existing = User::factory()->create(['phone' => '+7 (900) 101-10-10']);

        Livewire::actingAs($admin)
            ->test(CreateBooking::class)
            ->fillForm([
                'guest_phone' => '89001011010',
                'guest_name' => 'Игнорируемое имя',
                'room_id' => $room->id,
                'check_in' => '2026-07-01',
                'check_out' => '2026-07-05',
                'guests_count' => 1,
                'status' => 'pending',
                'guest_notes' => 'Заметка о существующем',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(0, User::where('registered_via_phone', true)->count());
        $this->assertDatabaseHas('bookings', ['user_id' => $existing->id]);
        $this->assertSame('Заметка о существующем', $existing->fresh()->admin_notes);
    }

    public function test_editing_a_booking_prefills_and_saves_guest_notes(): void
    {
        $admin = $this->makeAdmin();
        $room = $this->makeRoom();
        $guest = User::factory()->create([
            'name' => 'Иван Гостев',
            'phone' => '+79001011010',
            'admin_notes' => 'Старая заметка',
        ]);
        $booking = Booking::factory()->create([
            'user_id' => $guest->id,
            'room_id' => $room->id,
            'status' => 'pending',
        ]);

        Livewire::actingAs($admin)
            ->test(EditBooking::class, ['record' => $booking->getKey()])
            ->assertFormSet([
                'guest_phone' => '+79001011010',
                'guest_name' => 'Иван Гостев',
                'guest_notes' => 'Старая заметка',
            ])
            ->fillForm(['guest_notes' => 'Новая заметка'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Новая заметка', $guest->fresh()->admin_notes);
    }
}
