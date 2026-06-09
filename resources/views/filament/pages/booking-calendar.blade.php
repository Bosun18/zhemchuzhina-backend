<x-filament-panels::page>
    {{-- Навигация по месяцам --}}
    <div class="flex items-center justify-between gap-4">
        <a
            href="{{ \App\Filament\Pages\BookingCalendarPage::getUrl(['month' => $prevMonth]) }}"
            class="fi-btn fi-btn-size-md inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-950/10 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-200 dark:ring-white/20 dark:hover:bg-white/10"
        >
            ← Пред. месяц
        </a>

        <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
            {{ \Illuminate\Support\Str::ucfirst($currentMonth->translatedFormat('F Y')) }}
        </h2>

        <a
            href="{{ \App\Filament\Pages\BookingCalendarPage::getUrl(['month' => $nextMonth]) }}"
            class="fi-btn fi-btn-size-md inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-950/10 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-200 dark:ring-white/20 dark:hover:bg-white/10"
        >
            След. месяц →
        </a>
    </div>

    {{-- Таблица календаря --}}
    <div class="overflow-x-auto rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
        <table class="min-w-full border-separate border-spacing-0 text-xs">
            <thead>
                <tr class="bg-gray-50 dark:bg-white/5">
                    <th class="sticky left-0 z-10 bg-gray-50 px-3 py-2 text-left font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        Номер
                    </th>
                    @foreach ($days as $day)
                        <th
                            @class([
                                'min-w-[2.5rem] border-l border-gray-950/10 px-1.5 py-2 text-center font-medium text-gray-600 dark:border-white/10 dark:text-gray-300',
                                'bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400' => $day->isToday(),
                                'bg-gray-100 dark:bg-white/10' => ! $day->isToday() && $day->isWeekend(),
                            ])
                        >
                            <div>{{ $day->format('d') }}</div>
                            <div class="text-[10px] text-gray-400">{{ $day->isoFormat('dd') }}</div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rooms as $room)
                    @php $map = $roomDayMaps[$room->id]; @endphp
                    <tr class="border-t border-gray-100 dark:border-white/5">
                        <th class="sticky left-0 z-10 whitespace-nowrap border-t border-gray-100 bg-white px-3 py-2 text-left font-medium text-gray-950 dark:border-white/5 dark:bg-gray-900 dark:text-white">
                            <div class="font-semibold">№{{ $room->number }}</div>
                            <div class="text-[11px] font-normal text-gray-500 dark:text-gray-400">
                                {{ $room->roomType?->name }} · {{ $room->floor }} эт.
                            </div>
                        </th>

                        @foreach ($days as $day)
                            @php $cell = $map[$day->toDateString()]; @endphp

                            {{-- Дни внутри полосы уже покрыты colspan — пропускаем --}}
                            @continue($cell['type'] === 'continue')

                            @if ($cell['type'] === 'start')
                                @php
                                    $booking = $cell['booking'];
                                    $isPending = $booking->status === 'pending';
                                    $nights = (int) $booking->check_in->diffInDays($booking->check_out);
                                    $mod10 = $nights % 10;
                                    $mod100 = $nights % 100;
                                    $nightsWord = ($mod10 === 1 && $mod100 !== 11)
                                        ? 'ночь'
                                        : (($mod10 >= 2 && $mod10 <= 4 && ! ($mod100 >= 12 && $mod100 <= 14)) ? 'ночи' : 'ночей');
                                    $guestName = $booking->user?->name ?? 'Без имени';
                                    $statusLabel = $isPending ? 'Ожидает' : 'Подтверждено';
                                    $barClass = $isPending
                                        ? 'bg-amber-400 text-amber-950 hover:bg-amber-500'
                                        : 'bg-emerald-500 text-white hover:bg-emerald-600';
                                    $tooltip = $guestName.' · №'.$room->number.' · '
                                        .$booking->check_in->format('d.m').'–'.$booking->check_out->format('d.m')
                                        .' · '.$nights.' '.$nightsWord.' · '.$statusLabel;
                                @endphp
                                <td colspan="{{ $cell['span'] }}" class="border-t border-l border-gray-950/10 p-0.5 align-middle dark:border-white/10">
                                    <a
                                        href="{{ \App\Filament\Resources\Bookings\BookingResource::getUrl('edit', ['record' => $booking->id]) }}"
                                        title="{{ $tooltip }}"
                                        class="flex h-9 w-full items-center gap-1 overflow-hidden rounded px-2 font-medium shadow-sm {{ $barClass }}"
                                    >
                                        <span class="shrink-0">{{ $isPending ? '⏳' : '✓' }}</span>
                                        <span class="truncate">{{ \Illuminate\Support\Str::limit($guestName, 12, '…') }}</span>
                                    </a>
                                </td>
                            @elseif ($cell['type'] === 'past')
                                <td
                                    @class([
                                        'h-10 border-t border-l border-gray-950/10 dark:border-white/10',
                                        'bg-primary-100/60 dark:bg-primary-500/15' => $day->isToday(),
                                        'bg-gray-200 dark:bg-gray-700' => ! $day->isToday(),
                                    ])
                                ></td>
                            @else
                                <td
                                    @class([
                                        'h-10 border-t border-l border-gray-950/10 dark:border-white/10',
                                        'bg-primary-50 dark:bg-primary-500/10' => $day->isToday(),
                                        'bg-emerald-100/70 dark:bg-emerald-900/50' => ! $day->isToday() && $day->isWeekend(),
                                        'bg-emerald-50 dark:bg-emerald-950/40' => ! $day->isToday() && ! $day->isWeekend(),
                                    ])
                                ></td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Легенда --}}
    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 dark:text-gray-300">
        <div class="flex items-center gap-1.5">
            <span class="inline-block h-4 w-4 rounded bg-emerald-50 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:ring-emerald-800"></span> Свободно
        </div>
        <div class="flex items-center gap-1.5">
            <span class="inline-block h-4 w-4 rounded bg-amber-400"></span> Ожидает подтверждения
        </div>
        <div class="flex items-center gap-1.5">
            <span class="inline-block h-4 w-4 rounded bg-emerald-500"></span> Подтверждено
        </div>
        <div class="flex items-center gap-1.5">
            <span class="inline-block h-4 w-4 rounded bg-gray-200 dark:bg-gray-700"></span> Прошедшие даты
        </div>
    </div>
</x-filament-panels::page>
