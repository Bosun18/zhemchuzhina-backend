<?php

namespace Database\Seeders;

use App\Models\Price;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Season;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        // Типы номеров
        $standard = RoomType::create([
            'name' => 'Стандарт',
            'description' => 'Уютный номер с двуспальной кроватью и диваном. TV, холодильник, кондиционер, Wi-Fi.',
            'max_guests' => 3,
        ]);

        $double = RoomType::create([
            'name' => 'Двухкомнатный',
            'description' => 'Просторный двухкомнатный номер увеличенной вместимости. TV, холодильник, кондиционер, Wi-Fi.',
            'max_guests' => 5,
        ]);

        // Номера (8 стандартных + 2 двухкомнатных)
        foreach (range(1, 8) as $i) {
            Room::create([
                'number' => $i,
                'floor' => $i <= 4 ? 1 : 2,
                'room_type_id' => $standard->id,
                'is_active' => true,
            ]);
        }
        foreach ([9, 10] as $i) {
            Room::create([
                'number' => $i,
                'floor' => $i === 9 ? 1 : 2,
                'room_type_id' => $double->id,
                'is_active' => true,
            ]);
        }

        // Сезоны и цены
        $seasons = [
            ['name' => 'Межсезонье', 'date_from' => '2026-04-01', 'date_to' => '2026-06-14'],
            ['name' => 'Июнь-Июль', 'date_from' => '2026-06-15', 'date_to' => '2026-07-31'],
            ['name' => 'Август', 'date_from' => '2026-08-01', 'date_to' => '2026-08-31'],
            ['name' => 'Сентябрь', 'date_from' => '2026-09-01', 'date_to' => '2026-09-30'],
        ];

        $prices = [
            'Межсезонье' => ['Стандарт' => 2500, 'Двухкомнатный' => 4000],
            'Июнь-Июль' => ['Стандарт' => 3500, 'Двухкомнатный' => 5500],
            'Август' => ['Стандарт' => 4500, 'Двухкомнатный' => 7000],
            'Сентябрь' => ['Стандарт' => 3000, 'Двухкомнатный' => 4500],
        ];

        foreach ($seasons as $s) {
            $season = Season::create($s);
            foreach ([$standard, $double] as $type) {
                Price::create([
                    'room_type_id' => $type->id,
                    'season_id' => $season->id,
                    'price_per_night' => $prices[$s['name']][$type->name],
                ]);
            }
        }

        // Тестовые пользователи
        $admin = User::factory()->create([
            'name' => 'Администратор',
            'email' => 'admin@zhemchuzhina.com',
            'city' => 'Москва',
        ]);
        $admin->assignRole('admin');

        User::factory(5)->create()->each(function ($user) {
            $user->assignRole('guest');
        });

        $this->call([
            ServicesSeeder::class,
            NewsSeeder::class,
            GallerySeeder::class,
        ]);
    }
}
