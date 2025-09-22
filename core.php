<?php
// core.php

/*
=====================================================
    NovelWorld - Core Application File
    Version: 1.0
=====================================================
    - این فایل هسته اصلی اپلیکیشن است و کاملاً مستقل عمل می‌کند.
    - اتصال به دیتابیس را برقرار می‌کند.
    - وضعیت لاگین کاربر را از طریق کوکی 'user_session' بررسی می‌کند.
    - متغیرهای سراسری ($conn, $is_logged_in, $user_id, $username) را
      برای استفاده در تمام بخش‌های سایت تعریف می‌کند.
    - هیچ خروجی HTML ای تولید نمی‌کند.
*/

// --- گام ۱: آماده‌سازی متغیرهای پیش‌فرض ---
$conn = null;
$is_logged_in = false;
$user_id = null;
$username = 'کاربر مهمان';


// --- گام ۲: خواندن اطلاعات اتصال و برقراری ارتباط با دیتابیس ---
$database_url = getenv('DATABASE_URL');
if ($database_url === false) {
    // اگر متغیر محیطی وجود نداشت، یک خطا در لاگ ثبت کن و ادامه نده
    error_log("Core Error: DATABASE_URL environment variable is not set.");
    // در فایل‌های پردازشی، این باعث می‌شود $conn برابر null باقی بماند.
    // در فایل‌های نمایشی، db_connect.php خطا را مدیریت خواهد کرد.
} else {
    try {
        $db_parts = parse_url($database_url);
        $dsn = "pgsql:host={$db_parts['host']};port=5432;dbname=".ltrim($db_parts['path'], '/').";sslmode=require";
        
        // برقراری اتصال با دیتابیس
        $conn = new PDO($dsn, $db_parts['user'], $db_parts['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

    } catch (PDOException | Exception $e) {
        error_log("Core DB Connection Error: " . $e->getMessage());
        $conn = null; // در صورت خطا، اتصال را null قرار بده
    }
}


// --- گام ۳: بررسی کوکی و احراز هویت کاربر ---
// این بخش فقط در صورتی اجرا می‌شود که اتصال دیتابیس موفقیت‌آمیز بوده باشد.
if ($conn && isset($_COOKIE['user_session'])) {
    $session_id = $_COOKIE['user_session'];
    
    try {
        // جستجوی سشن در دیتابیس و اتصال به کاربر
        $stmt = $conn->prepare(
            "SELECT u.id, u.username 
             FROM users u 
             JOIN sessions s ON u.id = s.user_id 
             WHERE s.session_id = ? AND s.expires_at > NOW()"
        );
        $stmt->execute([$session_id]);
        $user = $stmt->fetch();
        
        if ($user) {
            $is_logged_in = true;
            $user_id = $user['id'];
            $username = htmlspecialchars($user['username']);
        }
    } catch (PDOException $e) {
        error_log("Core Auth Error: " . $e->getMessage());
    }
}
?>
