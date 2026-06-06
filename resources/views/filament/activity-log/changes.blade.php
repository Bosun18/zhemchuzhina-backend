@php
    $changes = $record->attribute_changes?->toArray() ?? [];
    $old = $changes['old'] ?? [];
    $new = $changes['attributes'] ?? [];
    $description = $record->description;
@endphp

<div class="p-2">
    @if ($description === 'updated' && ! empty($new))
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="text-left py-2 px-3 font-medium text-gray-500">Поле</th>
                    <th class="text-left py-2 px-3 font-medium text-red-500">Было</th>
                    <th class="text-left py-2 px-3 font-medium text-green-500">Стало</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($new as $key => $value)
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <td class="py-2 px-3 font-mono text-xs text-gray-400">{{ $key }}</td>
                        <td class="py-2 px-3 text-red-600 dark:text-red-400">
                            {{ isset($old[$key]) ? (is_array($old[$key]) ? json_encode($old[$key]) : $old[$key]) : '—' }}
                        </td>
                        <td class="py-2 px-3 text-green-600 dark:text-green-400">
                            {{ isset($value) ? (is_array($value) ? json_encode($value) : $value) : '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif ($description === 'deleted' && ! empty($old))
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="text-left py-2 px-3 font-medium text-gray-500">Поле</th>
                    <th class="text-left py-2 px-3 font-medium text-red-500">Значение до удаления</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($old as $key => $value)
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <td class="py-2 px-3 font-mono text-xs text-gray-400">{{ $key }}</td>
                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300">
                            {{ isset($value) ? (is_array($value) ? json_encode($value) : $value) : '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif ($description === 'created' && ! empty($new))
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="text-left py-2 px-3 font-medium text-gray-500">Поле</th>
                    <th class="text-left py-2 px-3 font-medium text-green-500">Значение</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($new as $key => $value)
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <td class="py-2 px-3 font-mono text-xs text-gray-400">{{ $key }}</td>
                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300">
                            {{ isset($value) ? (is_array($value) ? json_encode($value) : $value) : '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="py-4 text-center text-sm text-gray-400">Нет данных об изменениях.</p>
    @endif
</div>
