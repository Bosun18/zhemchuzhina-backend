<?php

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RoomControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeRoom(int $number = 1): Room
    {
        $roomType = RoomType::factory()->create();

        return Room::factory()->create([
            'room_type_id' => $roomType->id,
            'number' => $number,
            'is_active' => true,
        ]);
    }

    public function test_calendar_returns_active_rooms_with_pending_and_confirmed_bookings(): void
    {
        $room = $this->makeRoom();

        $confirmed = Booking::factory()->create([
            'room_id' => $room->id,
            'check_in' => '2026-06-05',
            'check_out' => '2026-06-10',
            'status' => 'confirmed',
        ]);
        Booking::factory()->create([
            'room_id' => $room->id,
            'check_in' => '2026-06-15',
            'check_out' => '2026-06-18',
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/rooms/calendar?from=2026-06-01&to=2026-06-30');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $room->id)
            ->assertJsonPath('0.type.name', $room->roomType->name)
            ->assertJsonCount(2, '0.bookings')
            ->assertJsonPath('0.bookings.0.check_in', '2026-06-05')
            ->assertJsonPath('0.bookings.0.status', 'confirmed');

        // Имя гостя не раскрывается
        $response->assertJsonMissing(['name' => $confirmed->user->name]);
    }

    public function test_calendar_excludes_cancelled_bookings(): void
    {
        $room = $this->makeRoom();

        Booking::factory()->create([
            'room_id' => $room->id,
            'check_in' => '2026-06-05',
            'check_out' => '2026-06-10',
            'status' => 'cancelled',
        ]);

        $this->getJson('/api/rooms/calendar?from=2026-06-01&to=2026-06-30')
            ->assertOk()
            ->assertJsonCount(0, '0.bookings');
    }

    public function test_calendar_excludes_bookings_outside_the_range(): void
    {
        $room = $this->makeRoom();

        Booking::factory()->create([
            'room_id' => $room->id,
            'check_in' => '2026-08-05',
            'check_out' => '2026-08-10',
            'status' => 'confirmed',
        ]);

        $this->getJson('/api/rooms/calendar?from=2026-06-01&to=2026-06-30')
            ->assertOk()
            ->assertJsonCount(0, '0.bookings');
    }

    public function test_calendar_validates_range_within_three_months(): void
    {
        $this->makeRoom();

        $this->getJson('/api/rooms/calendar?from=2026-06-01&to=2026-10-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors('to');
    }

    public function test_calendar_requires_to_after_from(): void
    {
        $this->getJson('/api/rooms/calendar?from=2026-06-10&to=2026-06-05')
            ->assertStatus(422)
            ->assertJsonValidationErrors('to');
    }

    public function test_availability_marks_room_available_for_back_to_back_dates(): void
    {
        $room = $this->makeRoom();

        Booking::factory()->create([
            'room_id' => $room->id,
            'status' => 'confirmed',
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(10)->toDateString(),
        ]);

        $checkIn = now()->addDays(10)->toDateString();
        $checkOut = now()->addDays(12)->toDateString();

        $this->getJson("/api/rooms/availability?check_in={$checkIn}&check_out={$checkOut}")
            ->assertOk()
            ->assertJsonPath('0.id', $room->id)
            ->assertJsonPath('0.is_available', true);
    }

    public function test_availability_marks_room_unavailable_on_single_night_overlap(): void
    {
        $room = $this->makeRoom();

        Booking::factory()->create([
            'room_id' => $room->id,
            'status' => 'confirmed',
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(10)->toDateString(),
        ]);

        $checkIn = now()->addDays(9)->toDateString();
        $checkOut = now()->addDays(11)->toDateString();

        $this->getJson("/api/rooms/availability?check_in={$checkIn}&check_out={$checkOut}")
            ->assertOk()
            ->assertJsonPath('0.id', $room->id)
            ->assertJsonPath('0.is_available', false);
    }

    public function test_room_payload_includes_room_type_photo_urls(): void
    {
        $roomType = RoomType::factory()->create([
            'photos' => ['room-types/one.jpg', 'room-types/two.jpg'],
        ]);
        $room = Room::factory()->create([
            'room_type_id' => $roomType->id,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/rooms/'.$room->id);

        $response->assertOk()
            ->assertJsonPath('type.photos.0', Storage::url('room-types/one.jpg'))
            ->assertJsonPath('type.photos.1', Storage::url('room-types/two.jpg'));
    }
}
