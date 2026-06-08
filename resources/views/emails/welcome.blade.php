<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #1a5276; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
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
        <p>Здравствуйте, <strong>{{ $user->name }}</strong>!</p>
        <p>Регистрация на сайте гостевого дома «Жемчужина» <span class="badge">✓ Завершена</span></p>
        <p>Теперь вы можете бронировать номера и оставлять отзывы после проживания.</p>

        <p>Ждём вас! По всем вопросам свяжитесь с нами:</p>
        <p>📞 Телефон: <a href="tel:+79999999999">+7 (999) 999-99-99</a></p>
        <p>💬 WhatsApp: <a href="https://wa.me/79999999999">Написать в WhatsApp</a></p>
    </div>
    <div class="footer">
        <p>Гостевой дом «Жемчужина» • Абхазия, Пицунда, район Рыбзавод</p>
    </div>
</body>
</html>
