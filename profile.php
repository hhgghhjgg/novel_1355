<?php
/*
=====================================================
    NovelWorld - User Profile Page (Final, Unabridged)
    Version: 2.3 (with Mana Balance)
=====================================================
    - این نسخه نهایی، تمام قابلیت‌های صفحه پروفایل، از جمله
      نمایش موجودی مانا و لینک‌های دسترسی سریع را پیاده‌سازی می‌کند.
*/

// --- گام ۱: فراخوانی هدر و بررسی احراز هویت ---
require_once 'header.php';

// اگر کاربر لاگین نکرده بود، اجازه دسترسی به این صفحه را نمی‌دهیم.
if (!$is_logged_in) {
    header("Location: login.php");
    exit();
}


// --- گام ۲: واکشی اطلاعات تکمیلی کاربر ---
try {
    // ما تمام اطلاعات لازم (تاریخ عضویت و موجودی مانا) را در یک کوئری واکشی می‌کنیم.
    $stmt = $conn->prepare("SELECT created_at, mana_balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]); // $user_id از header.php می‌آید
    $user_data = $stmt->fetch();

    if (!$user_data) {
        // اگر به هر دلیلی کاربری با آن ID یافت نشد، او را از سیستم خارج می‌کنیم.
        header("Location: logout.php");
        exit();
    }

} catch (PDOException $e) {
    error_log("Profile Page Fetch Error: " . $e->getMessage());
    die("<div style='text-align:center; padding: 50px; color: white;'>خطا در بارگذاری اطلاعات پروفایل.</div>");
}


// --- گام ۳: رندر کردن بخش HTML ---
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پروفایل <?php echo $username; ?> - NovelWorld</title>
    <link rel="stylesheet" href="profile-style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,1,0" />
</head>
<body>

<div class="profile-page">
    <div class="profile-card">
        <header class="profile-header">
            <div class="profile-banner"></div>
            <div class="profile-picture-container">
                <div class="profile-picture">
                    <span><?php echo mb_substr($username, 0, 1, "UTF-8"); ?></span>
                </div>
            </div>
            <div class="profile-info">
                <h2><?php echo $username; ?></h2>
                <p>عضو از تاریخ: <?php echo date("Y/m/d", strtotime($user_data['created_at'])); ?></p>
            </div>
        </header>

        <!-- بخش جدید نمایش مانا -->
        <div class="mana-balance-box">
            <span class="material-symbols-outlined mana-icon">diamond</span>
            <div class="mana-text">
                <span>موجودی مانا</span>
                <strong><?php echo htmlspecialchars($user_data['mana_balance']); ?></strong>
            </div>
            <a href="transactions.php" class="history-link">تاریخچه</a>
        </div>

        <nav class="profile-actions-list">
            <a href="library.php" class="action-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
                <span>کتابخانه من</span>
            </a>

            <a href="dashboard/index.php" class="action-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                <span>پنل نویسندگی</span>
            </a>
            
            <a href="edit_profile.php" class="action-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                <span>ویرایش پروفایل</span>
            </a>
            
            <a href="logout.php" class="action-link logout-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                <span>خروج از حساب</span>
            </a>
        </nav>
    </div>
</div>

<?php 
// فراخوانی فوتر مشترک سایت
require_once 'footer.php'; 
?>
</body>
</html>
