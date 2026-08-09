<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Bosun18\TuiDataTable\Align;
use Bosun18\TuiDataTable\Column;
use Bosun18\TuiDataTable\SortDirection;
use Bosun18\TuiDataTable\TableWidget;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Symfony\Component\Tui\Event\ChangeEvent;
use Symfony\Component\Tui\Input\Key;
use Symfony\Component\Tui\Input\Keybindings;
use Symfony\Component\Tui\Tui;
use Symfony\Component\Tui\Widget\InputWidget;
use Symfony\Component\Tui\Widget\TextWidget;
use Symfony\Component\Tui\Widget\Util\StringUtils;

#[Signature('bookings:browse')]
#[Description('Интерактивная таблица броней в терминале')]
class BrowseBookings extends Command
{
    /**
     * Порядок статусов — по жизненному циклу брони, а не по алфавиту.
     */
    private const array STATUS_WEIGHT = ['pending' => 0, 'confirmed' => 1, 'cancelled' => 2];

    private const array STATUS_LABELS = ['pending' => 'Ожидает', 'confirmed' => 'Подтверждена', 'cancelled' => 'Отменена'];

    private const string FILTER_KEY = 'f';

    public function handle(): int
    {
        $rows = $this->rows();

        if ($rows === []) {
            $this->components->warn('Броней нет.');

            return self::SUCCESS;
        }

        $table = new TableWidget(
            columns: $this->columns(),
            rows: $rows,
            keybindings: new Keybindings(['cancel' => [Key::ESCAPE, 'ctrl+c', 'q']]),
        );

        $table
            ->setNoMatchText('Ничего не найдено')
            ->expandVertically(true);

        $statusLine = new TextWidget;
        $filterInput = new InputWidget()->setPrompt('  Фильтр: ');
        $hints = new TextWidget('  ↑↓ строка · PgUp/PgDn страница · ←→ колонка · s сортировка · f фильтр · q выход');

        $tui = new Tui;
        $tui->addStyleSheet(TableWidget::defaultStyleSheet());

        $total = \count($rows);

        $refreshStatusLine = function () use ($statusLine, $table, $total): void {
            $statusLine->setText(\sprintf(
                '  Броней: %d из %d · сортировка: %s',
                $table->getVisibleRowCount(),
                $total,
                $this->describeSort($table),
            ));
        };

        $table
            ->onSortChange($refreshStatusLine)
            ->onFilterChange($refreshStatusLine)
            ->onCancel(static fn () => $tui->stop())
            ->onInput(static function (string $data) use ($tui, $filterInput): bool {
                if ($data !== self::FILTER_KEY) {
                    return false;
                }

                $tui->setFocus($filterInput);

                return true;
            });

        $filterInput
            ->onChange(static function (ChangeEvent $event) use ($table): void {
                $event->isBlank() ? $table->clearFilter() : $table->setFilter($event->getValue());
            })
            ->onSubmit(static fn () => $tui->setFocus($table))
            ->onCancel(static function () use ($tui, $table, $filterInput): void {
                $filterInput->setValue('');
                $table->clearFilter();
                $tui->setFocus($table);
            });

        $table->sortBy('check_in', SortDirection::Desc);

        $tui->add($statusLine)->add($table)->add($filterInput)->add($hints);
        $tui->setFocus($table);
        $tui->run();

        return self::SUCCESS;
    }

    /**
     * Строки таблицы: даты лежат в ячейке как timestamp и превращаются в
     * читаемый вид форматтером, поэтому `<=>` сортирует их как даты.
     *
     * @return list<array{check_in: int, check_out: int, guest: string, room: int, status: string, nights: int}>
     */
    public function rows(): array
    {
        return Booking::query()
            ->with(['user:id,name', 'room:id,number'])
            ->orderByDesc('check_in')
            ->get()
            ->map(static fn (Booking $booking): array => [
                'check_in' => $booking->check_in->getTimestamp(),
                'check_out' => $booking->check_out->getTimestamp(),
                'guest' => StringUtils::stripControlBytes($booking->user?->name ?? '—'),
                'room' => (int) ($booking->room?->number ?? 0),
                'status' => $booking->status,
                'nights' => (int) $booking->check_in->diffInDays($booking->check_out),
            ])
            ->all();
    }

    /**
     * @return list<Column>
     */
    private function columns(): array
    {
        $date = static fn (mixed $value): string => date('d.m.Y', (int) $value);

        return [
            new Column('check_in', 'Заезд', width: 12, formatter: $date),
            new Column('check_out', 'Выезд', width: 12, formatter: $date),
            new Column('guest', 'Гость'),
            new Column('room', 'Номер', width: 7, align: Align::Right, formatter: static fn (mixed $value): string => '№'.$value),
            new Column(
                'status',
                'Статус',
                width: 14,
                formatter: static fn (mixed $value): string => self::STATUS_LABELS[$value] ?? (string) $value,
                comparator: static fn (mixed $left, mixed $right): int => (self::STATUS_WEIGHT[$left] ?? \PHP_INT_MAX) <=> (self::STATUS_WEIGHT[$right] ?? \PHP_INT_MAX),
            ),
            new Column('nights', 'Ночей', width: 12, align: Align::Right, formatter: static fn (mixed $value): string => self::nightsLabel((int) $value)),
        ];
    }

    private function describeSort(TableWidget $table): string
    {
        $sort = $table->getSort();

        if ($sort === null) {
            return 'исходный порядок';
        }

        $headers = [];

        foreach ($table->getColumns() as $column) {
            $headers[$column->key] = $column->header;
        }

        return $headers[$sort['key']].($sort['direction'] === SortDirection::Asc ? ' ↑' : ' ↓');
    }

    private static function nightsLabel(int $nights): string
    {
        $lastTwo = $nights % 100;
        $last = $nights % 10;

        $word = match (true) {
            $lastTwo >= 11 && $lastTwo <= 14 => 'ночей',
            $last === 1 => 'ночь',
            $last >= 2 && $last <= 4 => 'ночи',
            default => 'ночей',
        };

        return $nights.' '.$word;
    }
}
