<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class BookingsByCityChart extends ChartWidget
{
    protected ?string $heading = 'Бронирования по городам';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $data = Booking::join('users', 'bookings.user_id', '=', 'users.id')
            ->select('users.city', DB::raw('COUNT(*) as count'))
            ->whereNotNull('users.city')
            ->where('users.city', '!=', '')
            ->groupBy('users.city')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Бронирований',
                    'data' => $data->pluck('count')->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.7)',
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $data->pluck('city')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
