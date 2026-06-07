<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #1a5276; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
        .review-box { background: white; border-left: 4px solid #e74c3c; padding: 15px; margin: 15px 0; border-radius: 0 6px 6px 0; }
        .reason-box { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 6px; margin: 15px 0; }
        .footer { text-align: center; color: #999; font-size: 12px; padding: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🌊 Гостевой дом «Жемчужина»</h1>
    </div>
    <div class="content">
        @if($isDirector)
            <p>Уведомление для директора: отзыв гостя <strong>{{ $review->user->name }}</strong> был отклонён.</p>
        @else
            <p>Здравствуйте, <strong>{{ $review->user->name }}</strong>!</p>
            <p>К сожалению, ваш отзыв не прошёл модерацию и не будет опубликован на сайте.</p>
        @endif

        <p><strong>Текст отзыва:</strong></p>
        <div class="review-box">
            <p style="margin:0; font-style: italic;">«{{ $review->text }}»</p>
            <p style="margin: 8px 0 0; color: #666; font-size: 13px;">Оценка: {{ $review->rating }}/10</p>
        </div>

        @if($review->admin_comment)
        <div class="reason-box">
            <p style="margin:0;"><strong>Причина отклонения:</strong><br>{{ $review->admin_comment }}</p>
        </div>
        @endif

        @if(!$isDirector)
        <p>Если у вас есть вопросы, свяжитесь с нами через страницу контактов.</p>
        @endif
    </div>
    <div class="footer">
        <p>Гостевой дом «Жемчужина» · Абхазия, Пицунда, район Рыбзавод</p>
    </div>
</body>
</html>
