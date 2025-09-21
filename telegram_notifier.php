// telegram_notifier.php

<?php
/*
=====================================================
    NovelWorld - Telegram Notifier Module
    Version: 1.0
=====================================================
    - این فایل شامل تابعی برای ارسال نوتیفیکیشن‌های فرمت‌بندی شده
      به یک کانال تلگرام از طریق یک ربات است.
    - از متغیرهای محیطی برای نگهداری اطلاعات حساس (توکن ربات و شناسه کانال)
      استفاده می‌کند که روشی امن و استاندارد است.
    - از cURL برای ارسال درخواست به API تلگرام استفاده می‌کند.
*/

// برای جلوگیری از تعریف مجدد تابع در صورت فراخوانی چندباره،
// آن را داخل یک بلوک if قرار می‌دهیم.
if (!function_exists('sendTelegramNotification')) {

    /**
     * یک پیام شامل تصویر، متن و دکمه هایپرلینک به کانال تلگرام ارسال می‌کند.
     *
     * @param string $photoUrl   URL کامل و عمومی تصویر (مثلاً از Cloudinary).
     * @param string $caption    متن پیام. می‌تواند شامل تگ‌های HTML ساده مانند <b> و <i> باشد.
     * @param string $buttonText متنی که روی دکمه نمایش داده می‌شود.
     * @param string $buttonUrl  لینک نسبی در سایت شما (مثلاً 'novel_detail.php?id=123').
     * @return bool              true در صورت ارسال موفق، false در صورت بروز خطا.
     */
    function sendTelegramNotification($photoUrl, $caption, $buttonText, $buttonUrl) {
        
        // --- گام ۱: خواندن اطلاعات محرمانه از متغیرهای محیطی ---
        $botToken = getenv('TELEGRAM_BOT_TOKEN');
        $channelId = getenv('TELEGRAM_CHANNEL_ID');

        // بررسی می‌کنیم که آیا متغیرها در سرور (Render) تنظیم شده‌اند یا نه.
        if ($botToken === false || $channelId === false) {
            // یک خطا در لاگ سرور ثبت می‌کنیم. این خطا به کاربر نمایش داده نمی‌شود.
            error_log("Telegram Notifier Error: TELEGRAM_BOT_TOKEN or TELEGRAM_CHANNEL_ID is not set in environment variables.");
            return false;
        }

        // --- گام ۲: آماده‌سازی داده‌ها برای ارسال به API تلگرام ---

        // آدرس اصلی سایت شما (می‌توانید این را هم به متغیر محیطی منتقل کنید)
        $siteBaseUrl = "https://novel-world.onrender.com"; 
        
        // ساخت URL کامل برای دکمه (اگر لینک ورودی نسبی باشد)
        // این کار تضمین می‌کند که لینک همیشه کامل و صحیح باشد.
        $fullButtonUrl = rtrim($siteBaseUrl, '/') . '/' . ltrim($buttonUrl, '/');

        // ساخت ساختار دکمه هایپرلینک (Inline Keyboard) در فرمت JSON
        $keyboard = [
            'inline_keyboard' => [
                // هر آرایه داخلی یک ردیف از دکمه‌هاست
                [
                    // هر آبجکت داخلی یک دکمه است
                    ['text' => $buttonText, 'url' => $fullButtonUrl]
                ]
            ]
        ];
        $replyMarkup = json_encode($keyboard);

        // آماده‌سازی آرایه نهایی داده‌ها برای ارسال
        $data = [
            'chat_id'    => $channelId,
            'photo'      => $photoUrl,
            'caption'    => $caption,
            'parse_mode' => 'HTML', // به تلگرام می‌گوید که تگ‌های HTML را پردازش کند
            'reply_markup' => $replyMarkup
        ];

        // --- گام ۳: ارسال درخواست به API تلگرام با استفاده از cURL ---

        // ساخت URL API تلگرام برای متد sendPhoto
        $url = "https://api.telegram.org/bot{$botToken}/sendPhoto";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // ۱۰ ثانیه مهلت برای پاسخ

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // --- گام ۴: بررسی نتیجه و مدیریت خطا ---

        if ($error) {
            error_log("Telegram API cURL Error: " . $error);
            return false;
        }

        $response_data = json_decode($response, true);
        if ($http_code !== 200 || !$response_data['ok']) {
            $error_description = $response_data['description'] ?? 'Unknown error';
            error_log("Telegram API Error: (Code {$http_code}) - {$error_description}");
            return false;
        }
        
        // اگر همه چیز موفقیت‌آمیز بود، true برمی‌گردانیم
        return true;
    }
}
?>
