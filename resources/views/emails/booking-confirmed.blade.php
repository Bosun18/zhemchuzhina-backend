<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #1a5276; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
        .details { background: white; padding: 15px; border-radius: 6px; margin: 20px 0; }
        .details table { width: 100%; border-collapse: collapse; }
        .details td { padding: 8px 0; border-bottom: 1px solid #eee; }
        .details td:first-child { color: #666; width: 40%; }
        .footer { text-align: center; color: #999; font-size: 12px; padding: 20px; }
        .badge { display: inline-block; background: #27ae60; color: white; padding: 4px 12px; border-radius: 20px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🌊 Гостевой дом «Жемчужина»</h1>
        <p>Пицунда, Абхазия</p>
    </div>
    <div class="content">
        <p>Здравствуйте, <strong>{{ $booking->user->name }}</strong>!</p>
        <p>Ваше бронирование <span class="badge">✓ Подтверждено</span></p>

        <div class="details">
            <table>
                <tr>
                    <td>Номер комнаты:</td>
                    <td><strong>№{{ $booking->room->number }} ({{ $booking->room->roomType->name }}), {{ $booking->room->floor }} этаж</strong></td>
                </tr>
                <tr>
                    <td>Дата заезда:</td>
                    <td><strong>{{ $booking->check_in->format('d.m.Y') }}</strong></td>
                </tr>
                <tr>
                    <td>Дата выезда:</td>
                    <td><strong>{{ $booking->check_out->format('d.m.Y') }}</strong></td>
                </tr>
                <tr>
                    <td>Количество ночей:</td>
                    <td><strong>{{ $booking->check_in->diffInDays($booking->check_out) }}</strong></td>
                </tr>
                <tr>
                    <td>Гостей:</td>
                    <td><strong>{{ $booking->guests_count }}</strong></td>
                </tr>
                @if($booking->admin_comment)
                <tr>
                    <td>Комментарий:</td>
                    <td>{{ $booking->admin_comment }}</td>
                </tr>
                @endif
            </table>
        </div>

        <p>Ждём вас! По всем вопросам свяжитесь с нами:</p>
        <p>📞 Телефон: <a href="tel:+79999999999">+7 (999) 999-99-99</a></p>
        <p>💬 WhatsApp: <a href="https://wa.me/79999999999">Написать в WhatsApp</a></p>
    </div>
    <div class="footer">
        <p>Гостевой дом «Жемчужина» • Абхазия, Пицунда, район Рыбзавод</p>
    </div>
</body>
</html>
