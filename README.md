# Жемчужина — Бэкенд

API-сервер для сайта гостевого дома «Жемчужина» (Абхазия, Пицунда).
Построен на Laravel 12 + Filament 4 + MySQL.

## Стек

- PHP 8.5
- Laravel 12
- Filament 4 (админ-панель)
- MySQL 8
- Laravel Sanctum (аутентификация)
- Spatie Laravel Permission (роли и права)
- Spatie Laravel Activitylog (журнал действий)

## Функционал

### Публичный API
- Список номеров и их доступность по датам
- Новости и акции
- Дополнительные услуги
- Галерея
- Отзывы

### API для авторизованных пользователей
- Регистрация и вход (токены Sanctum)
- Онлайн-бронирование с выбором номера и дат
- Личный кабинет (история броней)
- Написание отзывов (только после подтверждённого бронирования)

### Админ-панель (Filament)
Доступна по адресу `/admin`. Роли: администратор, директор, разработчик.
- Управление номерами и типами номеров
- Подтверждение и отклонение бронирований
- Сезонные цены
- Модерация отзывов
- Новости и акции
- Дополнительные услуги
- Галерея
- Управление пользователями (с заметками)
- Журнал активности (admin / user / system)

### Роли
| Роль | Описание |
|------|----------|
| guest | Гость — только фронтенд |
| admin | Администратор — бронирования и отзывы |
| director | Директор — полный доступ к контенту |
| developer | Разработчик — полный доступ включая пользователей |

## Установка

### Требования
- PHP 8.2+
- Composer
- MySQL 8+
- Node.js (для сборки Filament-ассетов)

### Шаги

1. Клонировать репозиторий:
```bash
git clone git@github.com:Bosun18/zhemchuzhina-backend.git
cd zhemchuzhina-backend
```

2. Установить зависимости:
```bash
composer install
```

3. Скопировать и настроить .env:
```bash
cp .env.example .env
php artisan key:generate
```

4. Заполнить .env — подключение к БД:
DB_DATABASE=zhemchuzhina
DB_USERNAME=root
DB_PASSWORD=ваш_пароль

5. Создать БД и запустить миграции:
```bash
mysql -u root -p -e "CREATE DATABASE zhemchuzhina;"
php artisan migrate
```

6. Создать администратора:
```bash
php artisan make:filament-user
```

7. Назначить роль разработчика:
```bash
php artisan tinker
$user = \App\Models\User::where('email', 'ваш@email.com')->first();
$user->assignRole('developer');
exit
```

8. Запустить сервер:
```bash
php artisan serve
```

Админ-панель откроется по адресу: http://localhost:8000/admin

## Тестовые данные

Для заполнения БД тестовыми данными (только для разработки):

```bash
php artisan migrate:fresh --seed
```

⚠️ Перед продакшеном — см. файл `TEST_CLEANUP.md`

## API

Базовый URL: `/api`

Документация по эндпоинтам:
- [API_FRONTEND.md](API_FRONTEND.md) — для клиентского приложения (фронтенд)
- [API_ADMIN.md](API_ADMIN.md) — для персонала (управление бронированиями)

Список маршрутов также можно посмотреть в файле `routes/api.php`.

Для тестирования API рекомендуется использовать [Postman](https://www.postman.com) или [Insomnia](https://insomnia.rest).

## Структура проекта
app/
├── Filament/Resources/   — ресурсы админ-панели
├── Http/Controllers/Api/ — API контроллеры
├── Models/               — модели Eloquent
database/
├── migrations/           — миграции БД
├── seeders/              — сидеры
├── factories/            — фабрики для тестов
routes/
└── api.php               — API маршруты

## Лицензия

Частный проект. Все права защищены.
