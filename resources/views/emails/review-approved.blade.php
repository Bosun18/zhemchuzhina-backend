<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #1a5276; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
        .review-box { background: white; border-left: 4px solid #27ae60; padding: 15px; margin: 15px 0; border-radius: 0 6px 6px 0; }
        .comment-box { background: #eafaf1; border: 1px solid #27ae60; padding: 15px; border-radius: 6px; margin: 15px 0; }
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
        <p>Здравствуйте, <strong>{{ $review->user->name }}</strong>!</p>
        <p>Ваш отзыв <span class="badge">✓ Одобрен</span> и опубликован на сайте.</p>

        <p><strong>Текст отзыва:</strong></p>
        <div class="review-box">
            <p style="margin:0; font-style: italic;">«{{ $review->text }}»</p>
            <p style="margin: 8px 0 0; color: #666; font-size: 13px;">Оценка: {{ $review->rating }}/10</p>
        </div>

        @if($review->admin_comment)
        <div class="comment-box">
            <p style="margin:0;"><strong>Комментарий администратора:</strong><br>{{ $review->admin_comment }}</p>
        </div>
        @endif

        <p>Спасибо, что поделились впечатлениями!</p>
    </div>
    <div class="footer">
        <p>Гостевой дом «Жемчужина» • Абхазия, Пицунда, район Рыбзавод</p>
    </div>
</body>
</html>
