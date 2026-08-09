<?php

namespace Tests\Feature;

use App\Console\Commands\BrowseBookings;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BrowseBookingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_rows_sorted_by_check_in_descending(): void
    {
        $room = Room::factory()->create(['number' => 7]);
        $guest = User::factory()->create(['name' => 'Сергей Свириденко']);

        Booking::factory()->create([
            'user_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-06-01',
            'check_out' => '2026-06-04',
            'status' => 'cancelled',
        ]);
        Booking::factory()->create([
            'user_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-07-10',
            'check_out' => '2026-07-11',
            'status' => 'confirmed',
        ]);

        $rows = app(BrowseBookings::class)->rows();

        $this->assertSame([
            [
                'check_in' => strtotime('2026-07-10'),
                'check_out' => strtotime('2026-07-11'),
                'guest' => 'Сергей Свириденко',
                'room' => 7,
                'status' => 'confirmed',
                'nights' => 1,
            ],
            [
                'check_in' => strtotime('2026-06-01'),
                'check_out' => strtotime('2026-06-04'),
                'guest' => 'Сергей Свириденко',
                'room' => 7,
                'status' => 'cancelled',
                'nights' => 3,
            ],
        ], $rows);
    }

    public function test_it_loads_guests_and_rooms_without_n_plus_one_queries(): void
    {
        Booking::factory()->count(5)->create();

        DB::enableQueryLog();

        app(BrowseBookings::class)->rows();

        $this->assertCount(3, DB::getQueryLog());
    }
}
