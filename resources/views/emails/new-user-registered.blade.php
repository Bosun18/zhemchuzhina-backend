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
        <p>Уведомление администратора</p>
    </div>
    <div class="content">
        <p><span class="badge">👤 Новый пользователь</span></p>

        <div class="details">
            <table>
                <tr>
                    <td>Имя:</td>
                    <td><strong>{{ $user->name }}</strong></td>
                </tr>
                <tr>
                    <td>Email:</td>
                    <td><strong>{{ $user->email }}</strong></td>
                </tr>
                <tr>
                    <td>Телефон:</td>
                    <td><strong>{{ $user->phone ?? '—' }}</strong></td>
                </tr>
                <tr>
                    <td>Город:</td>
                    <td><strong>{{ $user->city }}</strong></td>
                </tr>
                <tr>
                    <td>Зарегистрирован:</td>
                    <td><strong>{{ $user->created_at->format('d.m.Y H:i') }}</strong></td>
                </tr>
            </table>
        </div>
    </div>
    <div class="footer">
        <p>Гостевой дом «Жемчужина» • Абхазия, Пицунда, район Рыбзавод</p>
    </div>
</body>
</html>
