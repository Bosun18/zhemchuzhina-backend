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

<div class="p-2">
    @if ($description === 'updated' && ! empty($new))
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="w-1/4 py-2 px-4 text-left font-medium text-gray-500">Поле</th>
                    <th class="w-[37.5%] py-2 px-4 text-left font-medium text-red-500">Было</th>
                    <th class="w-[37.5%] py-2 px-4 text-left font-medium text-green-500">Стало</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($new as $key => $value)
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <td class="py-2 px-4 text-xs text-gray-500">{{ $fieldLabels[$key] ?? $key }}</td>
                        <td class="py-2 px-4 text-red-600 dark:text-red-400">
                            {{ $formatValue($key, $old[$key] ?? null) }}
                        </td>
                        <td class="py-2 px-4 text-green-600 dark:text-green-400">
                            {{ $formatValue($key, $value) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif ($description === 'deleted' && ! empty($old))
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="w-1/4 py-2 px-4 text-left font-medium text-gray-500">Поле</th>
                    <th class="py-2 px-4 text-left font-medium text-red-500">Значение до удаления</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($old as $key => $value)
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <td class="py-2 px-4 text-xs text-gray-500">{{ $fieldLabels[$key] ?? $key }}</td>
                        <td class="py-2 px-4 text-gray-700 dark:text-gray-300">
                            {{ $formatValue($key, $value) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif ($description === 'created' && ! empty($new))
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="w-1/4 py-2 px-4 text-left font-medium text-gray-500">Поле</th>
                    <th class="py-2 px-4 text-left font-medium text-green-500">Значение</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($new as $key => $value)
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <td class="py-2 px-4 text-xs text-gray-500">{{ $fieldLabels[$key] ?? $key }}</td>
                        <td class="py-2 px-4 text-gray-700 dark:text-gray-300">
                            {{ $formatValue($key, $value) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="py-4 text-center text-sm text-gray-400">Нет данных об изменениях.</p>
    @endif
</div>
