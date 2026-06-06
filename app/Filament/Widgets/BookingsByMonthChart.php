<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;

class BookingsByMonthChart extends ChartWidget
{
    protected ?string $heading = 'Бронирования по месяцам';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $data = Booking::selectRaw('MONTH(check_in) as month, COUNT(*) as count')
            ->whereYear('check_in', now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $counts = array_values(array_replace(array_fill(1, 12, 0), $data));

        return [
            'datasets' => [
                [
                    'label' => 'Бронирований',
                    'data' => $counts,
                    'backgroundColor' => 'rgba(251, 191, 36, 0.2)',
                    'borderColor' => 'rgba(251, 191, 36, 1)',
                    'fill' => true,
                ],
            ],
            'labels' => ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
