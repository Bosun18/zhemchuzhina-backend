<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #1a5276; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
        .review-box { background: white; border-left: 4px solid #2980b9; padding: 15px; margin: 15px 0; border-radius: 0 6px 6px 0; }
        .footer { text-align: center; color: #999; font-size: 12px; padding: 20px; }
        .badge { display: inline-block; background: #2980b9; color: white; padding: 4px 12px; border-radius: 20px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🌊 Гостевой дом «Жемчужина»</h1>
        <p>Уведомление администратора</p>
    </div>
    <div class="content">
        <p><span class="badge">📝 Новый отзыв</span></p>

        <p><strong>Гость:</strong> {{ $review->user->name }}</p>

        <div class="review-box">
            <p style="margin:0; font-style: italic;">«{{ $review->text }}»</p>
            <p style="margin: 8px 0 0; color: #666; font-size: 13px;">Оценка: {{ $review->rating }}/10</p>
        </div>

        <p style="color: #666; font-size: 13px;">Создан: {{ $review->created_at->format('d.m.Y H:i') }}</p>

        <p>Пожалуйста, одобрите или отклоните отзыв в админ-панели.</p>
    </div>
    <div class="footer">
        <p>Гостевой дом «Жемчужина» • Абхазия, Пицунда, район Рыбзавод</p>
    </div>
</body>
</html>
