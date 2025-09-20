// header.php

<?php
/*
=====================================================
    NovelWorld - Main Site Header
    Version: 2.0 (Serverless Ready - JWT Auth Check)
=====================================================
    - این فایل به عنوان هدر اصلی برای تمام صفحات عمومی سایت استفاده می‌شود.
    - منطق بررسی احراز هویت کاربر را از طریق توکن JWT موجود در کوکی‌ها پیاده‌سازی می‌کند.
    - متغیرهای سراسری مانند $is_logged_in, $current_user, $username, $user_id را
      برای استفاده در صفحات بعدی تعریف می‌کند.
*/

// --- گام ۱: فراخوانی Autoloader کامپوزر ---
// این خط برای دسترسی به کتابخانه‌های نصب شده (مانند JWT) ضروری است.
require_once 'vendor/autoload.php';

// استفاده از کلاس‌های کتابخانه firebase/php-jwt
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

// --- گام ۲: تعریف تابع برای بررسی و اعتبارسنجی توکن JWT ---
if (!function_exists('get_current_user')) {
    /**
     * توکن JWT را از کوکی‌ها می‌خواند، آن را اعتبارسنجی می‌کند و در صورت موفقیت،
     * اطلاعات کاربر (payload) را برمی‌گرداند.
     *
     * @return object|null آبجکتی شامل اطلاعات کاربر یا null در صورت عدم لاگین یا توکن نامعتبر.
     */
    function get_current_user() {
        // اگر کوکی توکن وجود نداشت، کاربر لاگین نکرده است.
        if (!isset($_COOKIE['auth_token'])) {
            return null;
        }

        try {
            // کلید محرمانه را از متغیرهای محیطی می‌خوانیم.
            $secret_key = getenv('JWT_SECRET_KEY');
            if (!$secret_key) {
                // اگر کلید تنظیم نشده بود، یک خطا در لاگ سرور ثبت می‌کنیم.
                error_log('JWT_SECRET_KEY is not set in environment variables.');
                return null;
            }

            // تلاش برای رمزگشایی (decode) توکن
            $token = JWT::decode($_COOKIE['auth_token'], new Key($secret_key, 'HS256'));

            // اگر رمزگشایی موفق بود، آبجکت data که حاوی اطلاعات کاربر است را برمی‌گردانیم.
            return $token->data;

        } catch (ExpiredException $e) {
            // اگر توکن منقضی شده بود، آن را نادیده می‌گیریم.
            // می‌توان در اینجا منطقی برای رفرش کردن توکن پیاده‌سازی کرد (برای آینده).
            return null;
        } catch (Exception $e) {
            // اگر هر خطای دیگری در اعتبارسنجی توکن رخ داد (مثلاً امضای نامعتبر).
            // برای امنیت، بهتر است خطا را لاگ کنیم اما به کاربر اطلاعاتی ندهیم.
            error_log('JWT Decode Error: ' . $e->getMessage());
            return null;
        }
    }
}


// --- گام ۳: اجرای تابع و تنظیم متغیرهای سراسری ---

// اطلاعات کاربر فعلی را دریافت می‌کنیم.
$current_user = get_current_user();

// متغیرهای بولین (boolean) برای بررسی آسان وضعیت لاگین در صفحات.
$is_logged_in = ($current_user !== null);

// متغیرهایی برای نمایش اطلاعات کاربر در هدر و سایر بخش‌ها.
$username = $is_logged_in ? htmlspecialchars($current_user->username) : 'کاربر مهمان';
$user_id = $is_logged_in ? $current_user->user_id : null;


// --- گام ۴: رندر کردن بخش HTML هدر ---
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- لینک به فایل‌های CSS اصلی -->
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="header-style.css"> 
    
    <!-- فونت وزیرمتن از گوگل فونت -->
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <aside id="sidebar-menu" class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-profile-picture">
                <!-- نمایش حرف اول نام کاربری یا یک آیکون مهمان -->
                <span><?php echo $is_logged_in ? mb_substr($username, 0, 1, "UTF-8") : 'G'; ?></span>
            </div>
            <h4 class="sidebar-username"><?php echo $username; ?></h4>
        </div>
        
        <div class="sidebar-content">
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <h5 class="nav-section-title">دسترسی سریع</h5>
                    <a href="search.php" class="nav-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <span>جستجو</span>
                    </a>
                    <?php if ($is_logged_in): ?>
                        <a href="profile.php" class="nav-link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            <span>پروفایل</span>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="nav-link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                            <span>ورود / ثبت‌نام</span>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="nav-section">
                    <h5 class="nav-section-title">اطلاعات</h5>
                    <a href="#" class="nav-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>
                        <span>شرایط و قوانین</span>
                    </a>
                </div>
                <?php if ($is_logged_in): ?>
                    <div class="nav-section">
                         <a href="logout.php" class="nav-link logout-link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                            <span>خروج از حساب</span>
                        </a>
                    </div>
                <?php endif; ?>
            </nav>
        </div>
    </aside>
    <div id="sidebar-overlay" class="sidebar-overlay"></div>

    <header class="main-header">
        <nav class="navbar">
            <a href="index.php" class="logo">Novel<span>World</span></a>
            
            <button id="hamburger-btn" class="hamburger-btn" aria-label="Open menu">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"></path></svg>
            </button>
        </nav>
    </header>
    
    <div class="main-content">
        <!-- محتوای اصلی هر صفحه در اینجا قرار می‌گیرد -->
