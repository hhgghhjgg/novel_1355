<?php
// header.php (نسخه نهایی - بدون JWT)

// --- گام ۱: فراخوانی فایل اتصال به دیتابیس ---
// این فایل باید قبل از هر منطق دیگری فراخوانی شود.
require_once 'db_connect.php';

// --- گام ۲: آماده‌سازی متغیرهای پیش‌فرض کاربر ---
$is_logged_in = false;
$user_id = null;
$username = 'کاربر مهمان';

// --- گام ۳: بررسی کوکی و احراز هویت کاربر ---
if (isset($_COOKIE['user_session'])) {
    $session_id = $_COOKIE['user_session'];
    
    try {
        // کوئری برای پیدا کردن کاربر معتبر از طریق شناسه سشن
        // ما جدول users و sessions را به هم متصل (JOIN) می‌کنیم
        // و چک می‌کنیم که سشن منقضی نشده باشد (expires_at > NOW())
        $stmt = $conn->prepare(
            "SELECT u.id, u.username 
             FROM users u 
             JOIN sessions s ON u.id = s.user_id 
             WHERE s.session_id = ? AND s.expires_at > NOW()"
        );
        $stmt->execute([$session_id]);
        $user = $stmt->fetch();
        
        // اگر کاربری با این سشن معتبر پیدا شد، متغیرها را به‌روز می‌کنیم.
        if ($user) {
            $is_logged_in = true;
            $user_id = $user['id'];
            $username = htmlspecialchars($user['username']);
        } else {
            // اگر سشن نامعتبر یا منقضی بود، کوکی را پاک می‌کنیم.
            setcookie('user_session', '', time() - 3600, '/');
        }
    } catch (PDOException $e) {
        // در صورت بروز خطای دیتابیس، کاربر لاگین نشده باقی می‌ماند.
        // بهتر است خطا را برای بررسی‌های بعدی لاگ کنیم.
        error_log("Header Auth Check DB Error: " . $e->getMessage());
    }
}


// --- گام ۴: رندر کردن بخش HTML ---
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NovelWorld - دنیای ناول</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="header-style.css"> 
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;800&display=swap" rel="stylesheet">
</head>
<body>
    
    <aside id="sidebar-menu" class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-profile-picture">
                <span><?php echo $is_logged_in ? mb_substr($username, 0, 1, "UTF-8") : 'G'; ?></span>
            </div>
            <h4 class="sidebar-username"><?php echo $username; ?></h4>
        </div>
        
        <div class="sidebar-content">
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <h5 class="nav-section-title">دسترسی سریع</h5>
                    <a href="search.php" class="nav-link">
                        <span>جستجو</span>
                    </a>
                    <?php if ($is_logged_in): ?>
                        <a href="profile.php" class="nav-link">
                            <span>پروفایل</span>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="nav-link">
                            <span>ورود / ثبت‌نام</span>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="nav-section">
                    <h5 class="nav-section-title">اطلاعات</h5>
                    <a href="#" class="nav-link">
                        <span>شرایط و قوانین</span>
                    </a>
                </div>
                <?php if ($is_logged_in): ?>
                    <div class="nav-section">
                         <a href="logout.php" class="nav-link logout-link">
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
