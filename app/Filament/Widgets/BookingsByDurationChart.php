<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class BookingsByDurationChart extends ChartWidget
{
    protected ?string $heading = 'Длительность бронирований';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $raw = Booking::select(DB::raw('DATEDIFF(check_out, check_in) as nights, COUNT(*) as count'))
            ->groupBy('nights')
            ->orderBy('nights')
            ->pluck('count', 'nights')
            ->toArray();

        $buckets = [
            '1 ночь' => 0,
            '2-3 ночи' => 0,
            '4-7 ночей' => 0,
            '1-2 недели' => 0,
            'Более 2 недель' => 0,
        ];

        foreach ($raw as $nights => $count) {
            if ($nights === 1) {
                $buckets['1 ночь'] += $count;
            } elseif ($nights <= 3) {
                $buckets['2-3 ночи'] += $count;
            } elseif ($nights <= 7) {
                $buckets['4-7 ночей'] += $count;
            } elseif ($nights <= 14) {
                $buckets['1-2 недели'] += $count;
            } else {
                $buckets['Более 2 недель'] += $count;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Бронирований',
                    'data' => array_values($buckets),
                    'backgroundColor' => [
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(251, 191, 36, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                    ],
                ],
            ],
            'labels' => array_keys($buckets),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
