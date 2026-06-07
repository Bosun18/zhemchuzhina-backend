<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Права
        $permissions = [
            // Бронирования
            'bookings.view',
            'bookings.create',
            'bookings.edit',
            'bookings.delete',
            'bookings.confirm',

            // Номера
            'rooms.view',
            'rooms.create',
            'rooms.edit',
            'rooms.delete',

            // Типы номеров
            'room_types.view',
            'room_types.create',
            'room_types.edit',
            'room_types.delete',

            // Сезоны
            'seasons.view',
            'seasons.create',
            'seasons.edit',
            'seasons.delete',

            // Цены
            'prices.view',
            'prices.create',
            'prices.edit',
            'prices.delete',

            // Отзывы
            'reviews.view',
            'reviews.create',
            'reviews.edit',
            'reviews.delete',
            'reviews.moderate',

            // Новости
            'news.view',
            'news.create',
            'news.edit',
            'news.delete',

            // Услуги
            'services.view',
            'services.create',
            'services.edit',
            'services.delete',

            // Галерея
            'gallery.view',
            'gallery.create',
            'gallery.edit',
            'gallery.delete',

            // Пользователи
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.assign_roles',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Роль: Администратор
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo([
            'bookings.view', 'bookings.edit', 'bookings.confirm',
            'rooms.view',
            'room_types.view',
            'seasons.view',
            'prices.view',
            'reviews.view', 'reviews.moderate',
            'news.view',
            'services.view',
            'gallery.view',
        ]);

        // Роль: Директор
        $director = Role::create(['name' => 'director']);
        $director->givePermissionTo([
            'bookings.view', 'bookings.create', 'bookings.edit', 'bookings.delete', 'bookings.confirm',
            'rooms.view', 'rooms.create', 'rooms.edit', 'rooms.delete',
            'room_types.view', 'room_types.create', 'room_types.edit', 'room_types.delete',
            'seasons.view', 'seasons.create', 'seasons.edit', 'seasons.delete',
            'prices.view', 'prices.create', 'prices.edit', 'prices.delete',
            'reviews.view', 'reviews.edit', 'reviews.delete', 'reviews.moderate',
            'news.view', 'news.create', 'news.edit', 'news.delete',
            'services.view', 'services.create', 'services.edit', 'services.delete',
            'gallery.view', 'gallery.create', 'gallery.edit', 'gallery.delete',
            'users.view',
        ]);

        // Роль: Разработчик — все права
        $developer = Role::create(['name' => 'developer']);
        $developer->givePermissionTo(Permission::all());

        // Роль: Гость — без прав на админку
        Role::create(['name' => 'guest']);
    }
}
