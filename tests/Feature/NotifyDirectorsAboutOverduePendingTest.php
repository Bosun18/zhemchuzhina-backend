<?php

namespace Tests\Feature;

use App\Mail\BookingPendingTooLong;
use App\Mail\ReviewPendingTooLong;
use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotifyDirectorsAboutOverduePendingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_it_notifies_directors_about_overdue_pending_bookings_and_reviews_once(): void
    {
        Mail::fake();

        $director = User::factory()->create();
        $director->assignRole('director');

        $overdueBooking = Booking::factory()->create([
            'status' => 'pending',
            'created_at' => now()->subDays(2),
        ]);
        $recentBooking = Booking::factory()->create([
            'status' => 'pending',
            'created_at' => now()->subHours(2),
        ]);
        $alreadyNotifiedBooking = Booking::factory()->create([
            'status' => 'pending',
            'created_at' => now()->subDays(2),
            'pending_notified_at' => now()->subHour(),
        ]);

        $overdueReview = Review::factory()->create([
            'status' => 'pending',
            'created_at' => now()->subDays(2),
        ]);
        $recentReview = Review::factory()->create([
            'status' => 'pending',
            'created_at' => now()->subHours(2),
        ]);

        $this->artisan('app:notify-directors-about-overdue-pending')->assertExitCode(0);

        Mail::assertSent(BookingPendingTooLong::class, function (BookingPendingTooLong $mail) use ($overdueBooking, $director) {
            return $mail->booking->is($overdueBooking)
                && $mail->hasTo($director->email);
        });
        Mail::assertSent(BookingPendingTooLong::class, 1);

        Mail::assertSent(ReviewPendingTooLong::class, function (ReviewPendingTooLong $mail) use ($overdueReview, $director) {
            return $mail->review->is($overdueReview)
                && $mail->hasTo($director->email);
        });
        Mail::assertSent(ReviewPendingTooLong::class, 1);

        $this->assertNotNull($overdueBooking->fresh()->pending_notified_at);
        $this->assertNotNull($overdueReview->fresh()->pending_notified_at);
        $this->assertNull($recentBooking->fresh()->pending_notified_at);
        $this->assertNull($recentReview->fresh()->pending_notified_at);

        Mail::fake();
        $this->artisan('app:notify-directors-about-overdue-pending')->assertExitCode(0);

        Mail::assertNothingSent();
    }
}
