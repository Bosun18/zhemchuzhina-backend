<?php

namespace Tests\Feature\Api;

use App\Mail\NewUserRegistered;
use App\Mail\Welcome;
use App\Models\Setting;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admins_are_notified_when_user_registers(): void
    {
        Mail::fake();
        Setting::set('notification_emails_registration', ['director@example.com']);

        $this->postJson('/api/register', [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+79991234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'city' => 'Москва',
        ])->assertCreated();

        Mail::assertQueued(NewUserRegistered::class, fn (NewUserRegistered $mail) => $mail->hasTo('director@example.com')
            && $mail->user->email === 'ivan@example.com');
    }

    public function test_user_receives_welcome_email_after_registration(): void
    {
        Mail::fake();

        $this->postJson('/api/register', [
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+79991234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'city' => 'Москва',
        ])->assertCreated();

        Mail::assertSent(Welcome::class, fn (Welcome $mail) => $mail->hasTo('ivan@example.com')
            && $mail->user->email === 'ivan@example.com');
    }
}
