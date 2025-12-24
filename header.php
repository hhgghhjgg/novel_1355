<?php
// header.php

// 1. فقط اگر اتصال برقرار نیست، فایل اتصال را بخوان
if (!isset($conn)) {
    require_once __DIR__ . '/db_connect.php';
}

// 2. شروع سشن (فقط یکبار)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. چک کردن لاگین (ساده و امن)
$is_logged_in = false;
$username = 'مهمان';
$user_avatar = 'https://ui-avatars.com/api/?name=Guest&background=random';
$is_admin = false;

if (isset($_COOKIE['user_session']) && isset($conn)) {
    try {
        $stmt = $conn->prepare("SELECT id, username, role, profile_picture_url FROM users JOIN sessions s ON users.id = s.user_id WHERE s.session_id = ?");
        $stmt->execute([$_COOKIE['user_session']]);
        $user = $stmt->fetch();
        
        if ($user) {
            $is_logged_in = true;
            $username = htmlspecialchars($user['username']);
            $is_admin = ($user['role'] === 'admin');
            if (!empty($user['profile_picture_url'])) {
                $user_avatar = htmlspecialchars($user['profile_picture_url']);
            }
        }
    } catch (Exception $e) {
        // نادیده گرفتن خطا
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ناول‌خونه</title>
    <!-- استایل‌ها -->
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header id="header">
    <div class="header-content">
        <a href="index.php" class="logo">
            <div class="logo-icon"><i class="fas fa-book-open"></i></div>
            <span class="logo-text">ناول‌خونه</span>
        </a>

        <nav>
            <a href="index.php"><i class="fas fa-home"></i> خانه</a>
            <a href="search.php"><i class="fas fa-compass"></i> کشف</a>
            <a href="all_genres.php"><i class="fas fa-layer-group"></i> دسته‌بندی</a>
            <?php if ($is_logged_in): ?>
                <a href="library.php"><i class="fas fa-book-reader"></i> کتابخانه</a>
            <?php endif; ?>
        </nav>

        <div class="header-actions">
            <a href="search.php" class="icon-btn"><i class="fas fa-search"></i></a>
            
            <?php if ($is_logged_in): ?>
                <?php if ($is_admin): ?>
                    <a href="admin/index.php" class="icon-btn" title="مدیریت"><i class="fas fa-cogs"></i></a>
                <?php endif; ?>
                
                <a href="dashboard/index.php" class="icon-btn" title="نویسندگی"><i class="fas fa-pen-nib"></i></a>
                
                <a href="profile.php" class="login-btn" style="background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.3); padding: 8px 16px; text-decoration: none;">
                    <img src="<?php echo $user_avatar; ?>" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover; margin-left: 8px;">
                    <?php echo $username; ?>
                </a>
            <?php else: ?>
                <a href="login.php" class="login-btn" style="text-decoration: none;"><i class="fas fa-user"></i> ورود</a>
            <?php endif; ?>
            
            <button class="mobile-menu-btn" id="mobileMenuBtn"><i class="fas fa-bars"></i></button>
        </div>
    </div>
</header>

<!-- منوی موبایل -->
<div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-header">
        <span class="logo-text">منو</span>
        <button class="mobile-menu-close" id="mobileMenuClose"><i class="fas fa-times"></i></button>
    </div>
    <nav class="mobile-nav">
        <a href="index.php"><i class="fas fa-home"></i> خانه</a>
        <a href="search.php"><i class="fas fa-search"></i> جستجو</a>
        <?php if ($is_logged_in): ?>
            <a href="profile.php"><i class="fas fa-user"></i> پروفایل</a>
            <a href="logout.php" style="color: #ff4d4d;"><i class="fas fa-sign-out-alt"></i> خروج</a>
        <?php else: ?>
            <a href="login.php"><i class="fas fa-sign-in-alt"></i> ورود</a>
        <?php endif; ?>
    </nav>
</div>
