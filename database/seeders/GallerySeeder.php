<?php

namespace Database\Seeders;

use App\Models\Gallery;
use Illuminate\Database\Seeder;

class GallerySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            [
                'caption' => 'Вид на море с территории',
                'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1200',
                'sort_order' => 1,
            ],
            [
                'caption' => 'Пляж в Пицунде',
                'image' => 'https://images.unsplash.com/photo-1519046904884-53103b34b206?w=1200',
                'sort_order' => 2,
            ],
            [
                'caption' => 'Реликтовый сосновый бор',
                'image' => 'https://images.unsplash.com/photo-1448375240586-882707db888b?w=1200',
                'sort_order' => 3,
            ],
            [
                'caption' => 'Двор с террасой',
                'image' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1200',
                'sort_order' => 4,
            ],
            [
                'caption' => 'Номер стандарт',
                'image' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=1200',
                'sort_order' => 5,
            ],
            [
                'caption' => 'Горы Абхазии',
                'image' => 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=1200',
                'sort_order' => 6,
            ],
        ];

        foreach ($items as $data) {
            Gallery::create($data);
        }
    }
}
