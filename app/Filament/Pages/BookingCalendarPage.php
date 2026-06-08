<?php

namespace App\Filament\Pages;

use App\Models\Booking;
use App\Models\Room;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;
use UnitEnum;

class BookingCalendarPage extends Page
{
    protected string $view = 'filament.pages.booking-calendar';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Календарь';

    protected static ?string $title = 'Календарь бронирований';

    protected static string|UnitEnum|null $navigationGroup = 'Бронирования';

    protected static ?int $navigationSort = 3;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $month = $this->resolveMonth();
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();
        $today = Carbon::today();

        $days = [];
        for ($day = $monthStart->copy(); $day->lte($monthEnd); $day->addDay()) {
            $days[] = $day->copy();
        }

        $rooms = Room::query()
            ->with('roomType')
            ->orderBy('number')
            ->get();

        $bookingsByRoom = Booking::query()
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('check_in', '<=', $monthEnd)
            ->where('check_out', '>', $monthStart)
            ->with(['user:id,name'])
            ->orderBy('check_in')
            ->get()
            ->groupBy('room_id');

        // Для каждого номера — карта дней месяца с типом ячейки (start/continue/free/past)
        $roomDayMaps = [];
        foreach ($rooms as $room) {
            $roomDayMaps[$room->id] = $this->buildRoomDayMap(
                $bookingsByRoom->get($room->id, collect()),
                $days,
                $today,
            );
        }

        return [
            'rooms' => $rooms,
            'days' => $days,
            'today' => $today,
            'roomDayMaps' => $roomDayMaps,
            'currentMonth' => $month,
            'prevMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
        ];
    }

    /**
     * Построить помесячную карту дней для одного номера.
     *
     * @param  Collection<int, Booking>  $bookings
     * @param  array<int, Carbon>  $days
     * @return array<string, array{type: string, booking: Booking|null, span: int}>
     */
    private function buildRoomDayMap(Collection $bookings, array $days, Carbon $today): array
    {
        // Бронь, занимающая каждую ночь: check_in <= ночь < check_out
        $bookingByDate = [];
        foreach ($bookings as $booking) {
            $night = $booking->check_in->copy();
            while ($night->lt($booking->check_out)) {
                $bookingByDate[$night->toDateString()] = $booking;
                $night->addDay();
            }
        }

        $map = [];
        $skipUntilIndex = -1;
        $count = count($days);

        foreach ($days as $index => $day) {
            $key = $day->toDateString();
            $booking = $bookingByDate[$key] ?? null;

            // День уже покрыт colspan предыдущей полосы
            if ($index <= $skipUntilIndex) {
                $map[$key] = ['type' => 'continue', 'booking' => $booking, 'span' => 0];

                continue;
            }

            if ($booking !== null) {
                // Длина полосы: подряд идущие дни этой же брони в пределах месяца
                $span = 0;
                for ($j = $index; $j < $count; $j++) {
                    if (($bookingByDate[$days[$j]->toDateString()] ?? null) === $booking) {
                        $span++;
                    } else {
                        break;
                    }
                }

                $skipUntilIndex = $index + $span - 1;
                $map[$key] = ['type' => 'start', 'booking' => $booking, 'span' => $span];

                continue;
            }

            $map[$key] = [
                'type' => $day->lt($today) ? 'past' : 'free',
                'booking' => null,
                'span' => 1,
            ];
        }

        return $map;
    }

    private function resolveMonth(): Carbon
    {
        $param = Request::query('month');

        if (is_string($param)) {
            try {
                return Carbon::createFromFormat('Y-m', $param)->startOfMonth();
            } catch (\Throwable) {
                // Некорректный формат — откатываемся к текущему месяцу
            }
        }

        return Carbon::now()->startOfMonth();
    }
}
