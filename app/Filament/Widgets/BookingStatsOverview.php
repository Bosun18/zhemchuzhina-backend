<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BookingStatsOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $bookingsByMonth = Booking::selectRaw('MONTH(check_in) as month, COUNT(*) as count')
            ->whereYear('check_in', now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $chartData = array_values(array_replace(array_fill(1, 12, 0), $bookingsByMonth));

        return [
            Stat::make('Всего бронирований', Booking::count())
                ->description('За всё время')
                ->color('primary')
                ->chart($chartData),

            Stat::make('Подтверждено', Booking::where('status', 'confirmed')->count())
                ->description('Активных броней')
                ->color('success'),

            Stat::make('Ожидают подтверждения', Booking::where('status', 'pending')->count())
                ->description('Требуют обработки')
                ->color('warning'),

            Stat::make('Гостей зарегистрировано', User::role('guest')->count())
                ->description('Всего в системе')
                ->color('info'),
        ];
    }
}
