<?php // <<-- فایل باید دقیقاً با این تگ شروع شود
/*
=====================================================
    NovelWorld - Admin Panel Header (Final, Patched)
    Version: 1.1
=====================================================
    - این فایل دروازه ورود به کل پنل ادمین است.
    - این نسخه به صورت مستقل عمل کرده و تمام منطق لازم برای احراز هویت
      و بررسی نقش ادمین را در خود دارد.
    - مشکل "headers already sent" با قرار دادن تمام کدها در داخل تگ PHP حل شده است.
*/

// --- گام ۱: فراخوانی فایل اتصال به دیتابیس ---
require_once __DIR__ . '/../db_connect.php';

// --- گام ۲: منطق کامل احراز هویت و بررسی نقش ادمین ---
$is_logged_in = false;
$is_admin = false;
$user_id = null;
$username = '';

if (isset($_COOKIE['user_session'])) {
    $session_id = $_COOKIE['user_session'];
    try {
        $stmt = $conn->prepare(
            "SELECT u.id, u.username, u.role 
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
            if ($user['role'] === 'admin') {
                $is_admin = true;
            }
        }
    } catch (PDOException $e) {
        error_log("Admin Header Auth Error: " . $e->getMessage());
    }
}

// --- گام ۳: محافظت نهایی از پنل ادمین ---
if (!$is_admin) {
    // این header() اکنون باید بدون مشکل کار کند.
    header("Location: ../login.php");
    exit();
}

// --- گام ۴: رندر کردن HTML پنل ---
// از اینجا به بعد خروجی HTML شروع می‌شود.
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="admin-style.css"> 
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;800&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body">
    <aside id="sidebar-menu" class="sidebar">
        <div class="sidebar-header">
            <a href="../profile.php" class="sidebar-profile-link">
                <div class="sidebar-profile-picture" style="background-color: #d32f2f;">
                    <span><?php echo mb_substr($username, 0, 1, "UTF-8"); ?></span>
                </div>
                <h4 class="sidebar-username"><?php echo $username; ?> (ادمین)</h4>
            </a>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php">داشبورد اصلی</a>
            <a href="approve_chapters.php">تایید چپترها</a>
            <a href="manage_novels.php">مدیریت آثار</a>
            <a href="manage_users.php">مدیریت کاربران</a>
            <hr style="border-color: var(--dash-border); margin: 10px 0;">
            <a href="../index.php">بازگشت به سایت</a>
            <a href="../logout.php" style="color: #ff8a8a;">خروج</a>
        </nav>
    </aside>
    <div class="main-container">
        <header class="dashboard-header">
            <button id="hamburger-btn" class="hamburger-btn" aria-label="Toggle Menu">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"></path></svg>
            </button>
            <h1 class="dashboard-title">پنل مدیریت</h1>
        </header>
        <main class="dashboard-content">
            <!-- محتوای اصلی هر صفحه از پنل ادمین در اینجا قرار می‌گیرد -->
