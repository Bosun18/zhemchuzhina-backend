# Тестовые данные и изменения для удаления перед продакшеном

## Файлы которые нужно удалить или очистить

### database/seeders/DatabaseSeeder.php
- Удалить всё содержимое run() кроме вызова RolesAndPermissionsSeeder
- Оставить только: $this->call(RolesAndPermissionsSeeder::class);

### database/factories/ — удалить все фабрики кроме UserFactory:
- BookingFactory.php
- GalleryFactory.php
- NewsFactory.php
- PriceFactory.php
- ReviewFactory.php
- RoomFactory.php
- RoomTypeFactory.php
- SeasonFactory.php
- ServiceFactory.php
- SettingFactory.php

## Тестовые данные в БД (залиты через migrate:fresh --seed)

### Таблица room_types
- Стандарт (max_guests: 3)
- Двухкомнатный (max_guests: 5)

### Таблица rooms
- Номера 1-8: Стандарт, этажи 1-2 (1-4 → 1 этаж, 5-8 → 2 этаж)
- Номера 9-10: Двухкомнатный (9 → 1 этаж, 10 → 2 этаж)

### Таблица seasons + prices
- Межсезонье (01.04 - 14.06): Стандарт 2500₽, Двухкомнатный 4000₽
- Июнь-Июль (15.06 - 31.07): Стандарт 3500₽, Двухкомнатный 5500₽
- Август (01.08 - 31.08): Стандарт 4500₽, Двухкомнатный 7000₽
- Сентябрь (01.09 - 30.09): Стандарт 3000₽, Двухкомнатный 4500₽

### Таблица users (тестовые)
- admin@zhemchuzhina.com — роль admin (пароль: password)
- 5 случайных пользователей — роль guest (пароль: password)

## Что ОСТАВИТЬ при чистке
- Реальные типы номеров, комнаты, сезоны и цены — создать заново вручную через админку
- RolesAndPermissionsSeeder — оставить
- UserFactory — оставить (используется в тестах)
- Реального пользователя developer (beliy-81@mail.ru) — оставить
