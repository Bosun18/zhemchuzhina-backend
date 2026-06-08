<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Notifications\UserNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function makeGuest(): User
    {
        $user = User::factory()->create();
        $user->assignRole('guest');

        return $user;
    }

    public function test_guest_cannot_view_notifications(): void
    {
        $this->getJson('/api/notifications')->assertStatus(401);
    }

    public function test_user_can_list_their_notifications(): void
    {
        $user = $this->makeGuest();
        $other = $this->makeGuest();

        $user->notify(new UserNotification(
            title: 'Бронирование подтверждено',
            body: 'Ваше бронирование подтверждено.',
            icon: 'heroicon-o-check-circle',
            color: 'success',
            url: 'https://example.com/bookings/1',
        ));
        $other->notify(new UserNotification(title: 'Чужое уведомление'));

        $response = $this->actingAs($user)->getJson('/api/notifications');

        $response->assertOk()->assertJsonCount(1);
        $response->assertJsonFragment([
            'title' => 'Бронирование подтверждено',
            'body' => 'Ваше бронирование подтверждено.',
            'icon' => 'heroicon-o-check-circle',
            'color' => 'success',
            'url' => 'https://example.com/bookings/1',
        ]);
    }

    public function test_unread_count_reflects_only_current_users_unread_notifications(): void
    {
        $user = $this->makeGuest();
        $other = $this->makeGuest();

        $user->notify(new UserNotification(title: 'Первое'));
        $user->notify(new UserNotification(title: 'Второе'));
        $other->notify(new UserNotification(title: 'Чужое'));

        $response = $this->actingAs($user)->getJson('/api/notifications/unread-count');

        $response->assertOk()->assertJson(['count' => 2]);
    }

    public function test_user_can_mark_a_notification_as_read(): void
    {
        $user = $this->makeGuest();
        $user->notify(new UserNotification(title: 'Уведомление'));

        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($user)
            ->patchJson("/api/notifications/{$notification->id}/read")
            ->assertOk();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_mark_someone_elses_notification_as_read(): void
    {
        $user = $this->makeGuest();
        $other = $this->makeGuest();
        $other->notify(new UserNotification(title: 'Чужое уведомление'));

        $notification = $other->notifications()->firstOrFail();

        $this->actingAs($user)
            ->patchJson("/api/notifications/{$notification->id}/read")
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = $this->makeGuest();
        $user->notify(new UserNotification(title: 'Первое'));
        $user->notify(new UserNotification(title: 'Второе'));

        $this->actingAs($user)
            ->postJson('/api/notifications/read-all')
            ->assertOk();

        $this->assertSame(0, $user->unreadNotifications()->count());
    }
}
