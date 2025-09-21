// db_connect.php

<?php
/*
=====================================================
    NovelWorld - Database Connection
    Version: 2.1 (Final - Using Database URL)
=====================================================
    - این فایل اتصال به دیتابیس Neon (PostgreSQL) را با استفاده از PDO مدیریت می‌کند.
    - از یک متغیر محیطی واحد (DATABASE_URL) برای تمام اطلاعات اتصال استفاده می‌کند.
    - این روش برای پلتفرم‌های ابری مانند Render استاندارد و بسیار امن است.
*/

// --- گام ۱: خواندن رشته اتصال از متغیر محیطی ---
$database_url = getenv('DATABASE_URL');

// بررسی می‌کنیم که آیا متغیر در محیط سرور (Render) تنظیم شده است یا نه.
if ($database_url === false) {
    // اگر متغیر تنظیم نشده بود، اجرای اسکریپت با یک خطای واضح متوقف می‌شود.
    die("خطای پیکربندی: متغیر محیطی DATABASE_URL در سرور تنظیم نشده است.");
}

// --- گام ۲: تجزیه (Parse) کردن رشته اتصال ---
// تابع parse_url() رشته را به بخش‌های مختلف (host, user, pass, path) تقسیم می‌کند.
$db_parts = parse_url($database_url);

if ($db_parts === false) {
    die("خطای پیکربندی: فرمت DATABASE_URL نامعتبر است.");
}

// استخراج اطلاعات از آرایه تجزیه شده
$db_host = $db_parts['host'];
$db_user = $db_parts['user'];
$db_pass = $db_parts['pass'];
// نام دیتابیس در بخش 'path' قرار دارد و یک '/' اضافی در ابتدای آن است که باید حذف شود.
$db_name = ltrim($db_parts['path'], '/');

// --- گام ۳: ساخت رشته DSN و اتصال با PDO در بلوک try-catch ---
// DSN (Data Source Name) شامل تمام اطلاعات مورد نیاز برای اتصال است.
// sslmode=require برای اتصال امن به Neon ضروری است.
$dsn = "pgsql:host={$db_host};port=5432;dbname={$db_name};sslmode=require";

try {
    // ایجاد یک نمونه جدید از کلاس PDO با تنظیمات بهینه
    $conn = new PDO($dsn, $db_user, $db_pass, [
        // این گزینه به PDO می‌گوید که در صورت بروز خطا، یک Exception پرتاب کند.
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        
        // این گزینه مشخص می‌کند که نتایج به صورت آرایه انجمنی (associative array) بازگردانده شوند.
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

} catch (PDOException $e) {
    // در محیط واقعی، بهتر است جزئیات خطا را لاگ کنید و به کاربر نمایش ندهید.
    error_log('Database Connection Error: ' . $e->getMessage());
    // یک پیام خطای عمومی به کاربر نمایش می‌دهیم.
    die("خطا: اتصال به پایگاه داده با شکست مواجه شد. لطفاً بعداً تلاش کنید.");
}
?>
