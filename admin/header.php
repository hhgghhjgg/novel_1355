<?php
// admin/header.php

/*
=====================================================
    NovelWorld - Admin Panel Header
    Version: 1.1 (Final)
=====================================================
    - این فایل به عنوان هدر برای تمام صفحات داخل پنل مدیریت عمل می‌کند.
    - مسئولیت اصلی آن، محافظت از کل بخش ادمین و بررسی نقش 'admin' کاربر است.
*/

// --- گام ۱: فراخوانی فایل هسته از پوشه ریشه ---
// __DIR__ مسیر پوشه فعلی (admin) را برمی‌گرداند.
// /../ به یک پوشه بالاتر (ریشه پروژه) می‌رود.
require_once __DIR__ . '/../core.php';


// --- گام ۲: بررسی امنیت و مجوز دسترسی ادمین ---

// ابتدا چک می‌کنیم که کاربر لاگین کرده باشد.
if (!$is_logged_in) {
    header("Location: ../login.php"); 
    exit();
}

// حالا نقش کاربر را از دیتابیس دوباره چک می‌کنیم تا مطمئن شویم ادمین است.
// این کار امنیت را افزایش می‌دهد.
$is_admin = false;
try {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_role = $stmt->fetchColumn(); // فقط یک ستون را برمی‌گرداند
    
    if ($user_role === 'admin') {
        $is_admin = true;
    }
} catch (PDOException $e) {
    // در صورت خطا، فرض می‌کنیم کاربر ادمین نیست.
    error_log("Admin Header Role Check Error: " . $e->getMessage());
    $is_admin = false;
}

// اگر کاربر ادمین نبود، او را به صفحه اصلی سایت هدایت کن.
if (!$is_admin) {
    header("Location: ../index.php");
    exit();
}


// --- گام ۳: رندر کردن بخش HTML ---
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- لینک به فایل CSS اختصاصی پنل مدیریت -->
    <link rel="stylesheet" href="admin-style.css"> 
    
    <!-- فونت وزیرمتن -->
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;800&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body">
    <aside id="sidebar-menu" class="sidebar">
        <div class="sidebar-header">
            <a href="../profile.php" class="sidebar-profile-link" title="مشاهده پروفایل عمومی">
                <div class="sidebar-profile-picture" style="background-color: #c62828; border: 3px solid #ff8a8a;">
                    <span><?php echo mb_substr($username, 0, 1, "UTF-8"); ?></span>
                </div>
                <h4 class="sidebar-username"><?php echo $username; ?></h4>
                <span style="font-size: 0.8em; color: #ff8a8a; font-weight: bold;">(مدیر کل)</span>
            </a>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php">داشبورد اصلی</a>
            <a href="approve_chapters.php">تایید چپترها</a>
            <a href="manage_novels.php">مدیریت آثار</a>
            <a href="manage_users.php">مدیریت کاربران</a>
            <hr style="border-color: var(--dash-border); margin: 10px 0;">
            <a href="../index.php" target="_blank">مشاهده سایت</a>
            <a href="../logout.php" style="color: #ff8a8a;">خروج از حساب</a>
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
