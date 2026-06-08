<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PhoneGuestCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_creates_guest_with_placeholder_email_when_email_omitted(): void
    {
        $guest = User::findOrCreatePhoneGuest('Иван по телефону', '+7 (999) 123-45-67');

        $this->assertTrue($guest->wasRecentlyCreated);
        $this->assertSame('79991234567@phone.zhemchuzhina.local', $guest->email);
        $this->assertSame('+7 (999) 123-45-67', $guest->phone);
        $this->assertSame('Россия', $guest->city);
        $this->assertTrue($guest->registered_via_phone);
        $this->assertTrue($guest->hasRole('guest'));
    }

    public function test_uses_real_email_when_provided(): void
    {
        $guest = User::findOrCreatePhoneGuest('Пётр', '+79990000000', 'petr@example.com');

        $this->assertSame('petr@example.com', $guest->email);
        $this->assertTrue($guest->registered_via_phone);
    }

    public function test_stores_a_hashed_non_empty_password(): void
    {
        $guest = User::findOrCreatePhoneGuest('Аноним', '+79991112233');

        $this->assertNotEmpty($guest->password);
        $this->assertFalse(Hash::needsRehash($guest->password));
    }

    public function test_repeat_call_with_same_phone_reuses_the_guest(): void
    {
        $first = User::findOrCreatePhoneGuest('Первый звонок', '+7 (999) 123-45-67');
        $second = User::findOrCreatePhoneGuest('Второй звонок', '+7 999 123 45 67');

        $this->assertSame($first->id, $second->id);
        $this->assertFalse($second->wasRecentlyCreated);
        $this->assertSame(1, User::where('registered_via_phone', true)->count());
    }

    public function test_phone_with_leading_eight_matches_seven_format(): void
    {
        $first = User::findOrCreatePhoneGuest('Через плюс семь', '+79001011010');
        $second = User::findOrCreatePhoneGuest('Через восьмёрку', '89001011010');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, User::count());
    }

    public function test_matches_existing_user_by_phone_in_any_format(): void
    {
        $existing = User::factory()->create(['phone' => '+7 (900) 101-10-10']);

        $guest = User::findOrCreatePhoneGuest('Тот же гость', '89001011010');

        $this->assertSame($existing->id, $guest->id);
        $this->assertFalse($guest->wasRecentlyCreated);
    }
}
