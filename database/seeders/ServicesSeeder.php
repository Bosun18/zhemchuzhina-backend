<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'title' => 'Трансфер из аэропорта',
                'description' => 'Встретим вас в аэропорту Сочи или на ж/д вокзале Адлера и доставим прямо к гостевому дому. Уточняйте при бронировании.',
                'price' => 2500,
                'is_active' => true,
            ],
            [
                'title' => 'Морские прогулки на лодке',
                'description' => 'Прогулки вдоль абхазского побережья. Можем организовать рыбалку или поездку к мысу Пицунда.',
                'price' => 1500,
                'is_active' => true,
            ],
            [
                'title' => 'Экскурсии по Абхазии',
                'description' => 'Организуем поездки к озеру Рица, в Новый Афон, к водопадам. Опытный местный гид.',
                'price' => 1200,
                'is_active' => true,
            ],
            [
                'title' => 'Мангал и беседка',
                'description' => 'Беседка во дворе с мангалом в вашем распоряжении. Уголь и дрова предоставляем.',
                'price' => 500,
                'is_active' => true,
            ],
            [
                'title' => 'Прокат велосипедов',
                'description' => 'Исследуйте Пицунду на велосипеде. Сосновый бор и набережная — идеально для прогулок.',
                'price' => 300,
                'is_active' => true,
            ],
            [
                'title' => 'Скидки в кафе и ресторанах',
                'description' => 'Гости «Жемчужины» получают скидку 10% в партнёрских заведениях района Рыбзавод.',
                'price' => null,
                'is_active' => true,
            ],
        ];

        foreach ($services as $data) {
            Service::create($data);
        }
    }
}
