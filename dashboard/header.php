<?php
// dashboard/header.php

/*
=====================================================
    NovelWorld - Dashboard Header
    Version: 3.0 (Cookie-Session Based, No JWT)
=====================================================
    - این فایل به عنوان هدر برای تمام صفحات داخل پنل نویسندگی (داشبورد) عمل می‌کند.
    - مسئولیت اصلی آن، محافظت از کل بخش داشبورد و اطمینان از لاگین بودن کاربر است.
    - ساختار بصری سایدبار و هدر بالایی داشبورد را رندر می‌کند.
*/

// --- گام ۱: فراخوانی فایل‌های مورد نیاز از ریشه پروژه ---
// از ../ برای بازگشت به پوشه ریشه استفاده می‌کنیم.
require_once __DIR__ . '/../db_connect.php';

// --- گام ۲: منطق احراز هویت (مشابه هدر اصلی) ---
// این تابع برای جلوگیری از تعریف مجدد، داخل یک if قرار گرفته است.

if (!function_exists('get_current_user_dashboard')) {
    function get_current_user_dashboard($conn) {
        if (!isset($_COOKIE['user_session'])) {
            return null;
        }
        
        $session_id = $_COOKIE['user_session'];
        try {
            $stmt = $conn->prepare(
                "SELECT u.id, u.username FROM users u JOIN sessions s ON u.id = s.user_id WHERE s.session_id = ? AND s.expires_at > NOW()"
            );
            $stmt->execute([$session_id]);
            $user = $stmt->fetch();
            return $user ?: null;
        } catch (PDOException $e) {
            error_log("Dashboard Auth Check DB Error: " . $e->getMessage());
            return null;
        }
    }
}

$user = get_current_user_dashboard($conn);

// --- گام ۳: محافظت از داشبورد (بسیار مهم) ---
if (!$user) {
    // اگر کاربر لاگین نکرده بود، او را به صفحه لاگین اصلی سایت هدایت می‌کنیم.
    header("Location: ../login.php"); 
    exit();
}

// حالا که از لاگین بودن کاربر مطمئن هستیم، متغیرها را برای استفاده در صفحات داشبورد تنظیم می‌کنیم.
$is_logged_in = true; // در تمام صفحات داشبورد، این مقدار همیشه true خواهد بود.
$user_id = $user['id'];
$username = htmlspecialchars($user['username']);

// --- گام ۴: رندر کردن بخش HTML ---
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- لینک به فایل CSS اختصاصی داشبورد -->
    <link rel="stylesheet" href="dashboard-style.css"> 
    
    <!-- فونت وزیرمتن -->
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;800&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body">
    <aside id="sidebar-menu" class="sidebar">
        <div class="sidebar-header">
            <a href="../profile.php" class="sidebar-profile-link" title="مشاهده پروفایل عمومی">
                <div class="sidebar-profile-picture">
                    <span><?php echo mb_substr($username, 0, 1, "UTF-8"); ?></span>
                </div>
                <h4 class="sidebar-username"><?php echo $username; ?></h4>
            </a>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php">مدیریت ناول‌ها</a>
            <a href="#">آمار و ارقام</a>
            <a href="#">نظرات</a>
            <hr style="border-color: var(--dash-border); margin: 10px 0;">
            <a href="../index.php">بازگشت به سایت اصلی</a>
            <a href="../logout.php" style="color: #ff8a8a;">خروج از حساب</a>
        </nav>
    </aside>

    <div class="main-container">
        <header class="dashboard-header">
            <button id="hamburger-btn" class="hamburger-btn" aria-label="Toggle Menu">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"></path></svg>
            </button>
            <h1 class="dashboard-title">پنل نویسندگی</h1>
        </header>
        
        <main class="dashboard-content">
            <!-- محتوای اصلی هر صفحه از داشبورد در اینجا قرار می‌گیرد -->
