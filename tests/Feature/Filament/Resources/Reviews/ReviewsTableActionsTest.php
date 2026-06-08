<?php

namespace Tests\Feature\Filament\Resources\Reviews;

use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Mail\ReviewApproved;
use App\Mail\ReviewRejected;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewsTableActionsTest extends TestCase
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

    public function test_approve_action_marks_review_as_approved_and_sends_email(): void
    {
        Mail::fake();

        $admin = $this->makeAdmin();
        $review = Review::factory()->create(['status' => 'pending']);

        Livewire::actingAs($admin)
            ->test(ListReviews::class)
            ->callAction(TestAction::make('approve')->table($review), data: [
                'admin_comment' => 'Спасибо за отзыв!',
            ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'status' => 'approved',
            'admin_comment' => 'Спасибо за отзыв!',
        ]);

        Mail::assertSent(ReviewApproved::class, function (ReviewApproved $mail) use ($review) {
            return $mail->review->is($review)
                && $mail->hasTo($review->user->email);
        });

        Mail::assertNotSent(ReviewRejected::class);
    }
}
