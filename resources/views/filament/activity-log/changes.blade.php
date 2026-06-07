@php
    $changes = $record->attribute_changes?->toArray() ?? [];
    $old = $changes['old'] ?? [];
    $new = $changes['attributes'] ?? [];
    $description = $record->description;

    $fieldLabels = [
        'id'                => 'ID',
        'name'              => 'Имя',
        'email'             => 'Email',
        'password'          => 'Пароль',
        'city'              => 'Город',
        'admin_notes'       => 'Заметки',
        'role'              => 'Роль',
        'email_verified_at' => 'Email подтверждён',
        'remember_token'    => 'Токен',
        'created_at'        => 'Создано',
        'updated_at'        => 'Обновлено',
        'number'            => 'Номер',
        'floor'             => 'Этаж',
        'room_type_id'      => 'Тип номера',
        'is_active'         => 'Активен',
        'title'             => 'Заголовок',
        'description'       => 'Описание',
        'content'           => 'Содержание',
        'image'             => 'Изображение',
        'is_published'      => 'Опубликовано',
        'published_at'      => 'Дата публикации',
        'price'             => 'Цена',
        'price_per_night'   => 'Цена за ночь',
        'season_id'         => 'Сезон',
        'max_guests'        => 'Макс. гостей',
        'date_from'         => 'Дата начала',
        'date_to'           => 'Дата конца',
        'user_id'           => 'Пользователь',
        'booking_id'        => 'Бронирование',
        'check_in'          => 'Заезд',
        'check_out'         => 'Выезд',
        'guests_count'      => 'Кол-во гостей',
        'status'            => 'Статус',
        'comment'           => 'Комментарий',
        'admin_comment'     => 'Ответ администратора',
        'rating'            => 'Оценка',
        'text'              => 'Текст',
        'caption'           => 'Подпись',
        'sort_order'        => 'Порядок',
    ];

    $dateFields = ['created_at', 'updated_at', 'published_at', 'email_verified_at', 'date_from', 'date_to', 'check_in', 'check_out'];

    // Filament не подключает Tailwind-сборку приложения для кастомных view, поэтому здесь
    // используются инлайн-стили — utility-классы из этого файла не попадают в бандл и не работают.
    $borderColor = 'rgba(120, 120, 120, 0.35)';
    $borderColorSoft = 'rgba(120, 120, 120, 0.18)';
    $labelColor = '#9ca3af';
    $oldColor = '#ef4444';
    $newColor = '#22c55e';
    $cellStyle = 'padding: 0.5rem 0.75rem; vertical-align: top; word-break: break-word; overflow-wrap: anywhere;';

    $formatValue = function ($key, $value) use ($dateFields) {
        if (is_array($value)) {
            return json_encode($value);
        }
        if (isset($value) && in_array($key, $dateFields)) {
            try {
                return \Carbon\Carbon::parse($value)->format('d.m.Y H:i');
            } catch (\Exception) {
                return $value;
            }
        }
        return $value ?? '—';
    };
@endphp

<div style="padding: 0.5rem;">
    @if ($description === 'updated' && ! empty($new))
        <table style="width: 100%; table-layout: fixed; border-collapse: collapse; font-size: 0.875rem;">
            <colgroup>
                <col style="width: 33.333%">
                <col style="width: 33.333%">
                <col style="width: 33.333%">
            </colgroup>
            <thead>
                <tr style="border-bottom: 1px solid {{ $borderColor }};">
                    <th style="padding: 0.5rem 0.75rem; text-align: left; font-weight: 500; color: {{ $labelColor }};">Поле</th>
                    <th style="padding: 0.5rem 0.75rem; text-align: center; font-weight: 500; color: {{ $oldColor }}; border-left: 1px solid {{ $borderColor }};">Было</th>
                    <th style="padding: 0.5rem 0.75rem; text-align: right; font-weight: 500; color: {{ $newColor }}; border-left: 1px solid {{ $borderColor }};">Стало</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($new as $key => $value)
                    <tr style="border-bottom: 1px solid {{ $borderColorSoft }};">
                        <td style="{{ $cellStyle }} text-align: left; font-size: 0.75rem; color: {{ $labelColor }};">{{ $fieldLabels[$key] ?? $key }}</td>
                        <td style="{{ $cellStyle }} text-align: center; color: {{ $oldColor }}; border-left: 1px solid {{ $borderColorSoft }};">
                            {{ $formatValue($key, $old[$key] ?? null) }}
                        </td>
                        <td style="{{ $cellStyle }} text-align: right; color: {{ $newColor }}; border-left: 1px solid {{ $borderColorSoft }};">
                            {{ $formatValue($key, $value) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif ($description === 'deleted' && ! empty($old))
        <table style="width: 100%; table-layout: fixed; border-collapse: collapse; font-size: 0.875rem;">
            <colgroup>
                <col style="width: 30%">
                <col style="width: 70%">
            </colgroup>
            <thead>
                <tr style="border-bottom: 1px solid {{ $borderColor }};">
                    <th style="padding: 0.5rem 0.75rem; text-align: left; font-weight: 500; color: {{ $labelColor }};">Поле</th>
                    <th style="padding: 0.5rem 0.75rem; text-align: left; font-weight: 500; color: {{ $oldColor }}; border-left: 1px solid {{ $borderColor }};">Значение до удаления</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($old as $key => $value)
                    <tr style="border-bottom: 1px solid {{ $borderColorSoft }};">
                        <td style="{{ $cellStyle }} text-align: left; font-size: 0.75rem; color: {{ $labelColor }};">{{ $fieldLabels[$key] ?? $key }}</td>
                        <td style="{{ $cellStyle }} text-align: left; border-left: 1px solid {{ $borderColorSoft }};">
                            {{ $formatValue($key, $value) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif ($description === 'created' && ! empty($new))
        <table style="width: 100%; table-layout: fixed; border-collapse: collapse; font-size: 0.875rem;">
            <colgroup>
                <col style="width: 30%">
                <col style="width: 70%">
            </colgroup>
            <thead>
                <tr style="border-bottom: 1px solid {{ $borderColor }};">
                    <th style="padding: 0.5rem 0.75rem; text-align: left; font-weight: 500; color: {{ $labelColor }};">Поле</th>
                    <th style="padding: 0.5rem 0.75rem; text-align: left; font-weight: 500; color: {{ $newColor }}; border-left: 1px solid {{ $borderColor }};">Значение</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($new as $key => $value)
                    <tr style="border-bottom: 1px solid {{ $borderColorSoft }};">
                        <td style="{{ $cellStyle }} text-align: left; font-size: 0.75rem; color: {{ $labelColor }};">{{ $fieldLabels[$key] ?? $key }}</td>
                        <td style="{{ $cellStyle }} text-align: left; border-left: 1px solid {{ $borderColorSoft }};">
                            {{ $formatValue($key, $value) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="padding: 1rem 0; text-align: center; font-size: 0.875rem; color: {{ $labelColor }};">Нет данных об изменениях.</p>
    @endif
</div>
