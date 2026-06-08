<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'phone', 'password', 'city', 'admin_notes', 'work_schedule', 'registered_via_phone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return ! $this->hasRole('guest');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('admin');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'work_schedule' => 'array',
            'registered_via_phone' => 'boolean',
        ];
    }

    /**
     * Находит или создаёт гостя для брони, оформленной по телефону.
     *
     * Сначала ищем существующего гостя по номеру (с нормализацией формата,
     * чтобы 8XXX и +7XXX считались одним номером). Если не нашли и email не
     * указан — подставляем плейсхолдер из цифр телефона, чтобы пройти
     * ограничение уникальности и сразу было видно «телефонного» гостя.
     */
    public static function findOrCreatePhoneGuest(string $name, string $phone, ?string $email = null): self
    {
        if (filled($email)) {
            $existing = static::where('email', $email)->first();
        } else {
            $existing = static::findByPhone($phone);
            $email = static::phonePlaceholderEmail($phone);
        }

        if ($existing) {
            return $existing;
        }

        $guest = static::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'city' => 'Россия',
            'password' => Str::password(),
            'registered_via_phone' => true,
        ]);

        $guest->assignRole('guest');

        return $guest;
    }

    /**
     * Ищет пользователя по номеру телефона без привязки к формату записи
     * (срезает разделители и приводит 8XXX/10-значный к каноничному виду).
     * Сравнение делается на стороне PHP ради совместимости MySQL и SQLite.
     */
    public static function findByPhone(string $phone): ?self
    {
        $normalized = static::normalizePhone($phone);

        if ($normalized === '') {
            return null;
        }

        return static::whereNotNull('phone')
            ->get(['id', 'name', 'phone', 'admin_notes'])
            ->first(fn (self $user): bool => static::normalizePhone($user->phone) === $normalized);
    }

    /**
     * Приводит телефон к каноничному виду 7XXXXXXXXXX:
     * убирает все нецифры, заменяет ведущую 8 на 7 и дополняет
     * 10-значный номер кодом страны.
     */
    public static function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return '';
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '8')) {
            return '7'.substr($digits, 1);
        }

        if (strlen($digits) === 10) {
            return '7'.$digits;
        }

        return $digits;
    }

    /**
     * Формирует уникальный плейсхолдер-email из нормализованного телефона.
     */
    private static function phonePlaceholderEmail(string $phone): string
    {
        return static::normalizePhone($phone).'@phone.zhemchuzhina.local';
    }
}
