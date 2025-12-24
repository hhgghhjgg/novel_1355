<?php
/*
=====================================================
    NovelWorld - Header (Safe Mode)
    Version: Fixed
=====================================================
*/

// جلوگیری از تداخل اگر فایل اتصال قبلاً لود شده باشد
if (!defined('DB_CONNECTED')) {
    // نام فایل اتصال را چک کنید (db_connect.php یا core.php)
    if (file_exists('db_connect.php')) {
        require_once 'db_connect.php';
    } elseif (file_exists('core.php')) {
        require_once 'core.php';
    }
    define('DB_CONNECTED', true);
}

// شروع سشن فقط اگر قبلاً شروع نشده باشد
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// منطق ساده احراز هویت
$is_logged_in = false;
$username = 'مهمان';
$user_avatar = 'https://ui-avatars.com/api/?name=Guest&background=random'; 
$is_admin = false;

// فقط اگر اتصال دیتابیس برقرار بود چک کن
if (isset($conn) && isset($_COOKIE['user_session'])) {
    try {
        $stmt = $conn->prepare("
            SELECT u.id, u.username, u.role, u.profile_picture_url 
            FROM users u 
            JOIN sessions s ON u.id = s.user_id 
            WHERE s.session_id = ? AND s.expires_at > NOW()
        ");
        $stmt->execute([$_COOKIE['user_session']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $is_logged_in = true;
            $user_id = $user['id'];
            $username = htmlspecialchars($user['username']);
            if (!empty($user['profile_picture_url'])) {
                $user_avatar = htmlspecialchars($user['profile_picture_url']);
            }
            if ($user['role'] === 'admin') {
                $is_admin = true;
            }
        }
    } catch (Exception $e) {
        // نادیده گرفتن خطا برای جلوگیری از صفحه سیاه
    }
}
?>
<!-- پایان بخش PHP و شروع HTML -->

<header id="header">
    <div class="header-content">
        <a href="index.php" class="logo">
            <div class="logo-icon"><i class="fas fa-book-open"></i></div>
            <span class="logo-text">ناول‌خونه</span>
        </a>

        <nav>
            <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i> خانه</a>
            <a href="search.php"><i class="fas fa-compass"></i> کشف</a>
            <a href="all_genres.php"><i class="fas fa-layer-group"></i> دسته‌بندی</a>
            <?php if ($is_logged_in): ?>
                <a href="library.php"><i class="fas fa-book-reader"></i> کتابخانه</a>
            <?php endif; ?>
        </nav>

        <div class="header-actions">
            <button class="icon-btn" onclick="window.location.href='search.php'"><i class="fas fa-search"></i></button>
            
            <?php if ($is_logged_in): ?>
                <?php if ($is_admin): ?>
                    <button class="icon-btn" onclick="window.location.href='admin/index.php'" title="مدیریت"><i class="fas fa-cogs"></i></button>
                <?php endif; ?>
                
                <button class="icon-btn" onclick="window.location.href='dashboard/index.php'" title="نویسندگی"><i class="fas fa-pen-nib"></i></button>
                
                <a href="profile.php" class="login-btn" style="background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.3); padding: 8px 16px;">
                    <img src="<?php echo $user_avatar; ?>" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover; margin-left: 8px;">
                    <?php echo $username; ?>
                </a>
            <?php else: ?>
                <a href="login.php" class="login-btn"><i class="fas fa-user"></i> ورود</a>
            <?php endif; ?>
            
            <button class="mobile-menu-btn" id="mobileMenuBtn"><i class="fas fa-bars"></i></button>
        </div>
    </div>
</header>

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
            <a href="register.php"><i class="fas fa-user-plus"></i> ثبت‌نام</a>
        <?php endif; ?>
    </nav>
</div>
