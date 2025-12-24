<?php
/*
=====================================================
    NovelWorld - Header (Fixed & Modern)
    Version: 4.0
=====================================================
*/

// 1. اتصال به دیتابیس
require_once 'db_connect.php';

// 2. شروع سشن (اگر قبلاً شروع نشده باشد)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. منطق احراز هویت (چک کردن کوکی و سشن)
$is_logged_in = false;
$user_id = null;
$username = 'مهمان';
$user_avatar = 'assets/default_avatar.png'; // مسیر پیش‌فرض آواتار
$is_admin = false;

if (isset($_COOKIE['user_session'])) {
    $session_id = $_COOKIE['user_session'];
    try {
        $stmt = $conn->prepare("
            SELECT u.id, u.username, u.role, u.profile_picture_url 
            FROM users u 
            JOIN sessions s ON u.id = s.user_id 
            WHERE s.session_id = ? AND s.expires_at > NOW()
        ");
        $stmt->execute([$session_id]);
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
    } catch (PDOException $e) {
        // خطا را نادیده می‌گیریم تا سایت بالا بیاید (به عنوان مهمان)
    }
}
?>

<!-- شروع HTML هدر (نوار ناوبری) -->
<!-- توجه: تگ‌های html و head و body در فایل index.php قرار دارند تا تداخل ایجاد نشود -->

<header id="header">
    <div class="header-content">
        <!-- لوگو -->
        <a href="index.php" class="logo">
            <div class="logo-icon"><i class="fas fa-book-open"></i></div>
            <span class="logo-text">ناول‌خونه</span>
        </a>

        <!-- منوی وسط -->
        <nav>
            <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> خانه
            </a>
            <a href="search.php"><i class="fas fa-compass"></i> کشف</a>
            <a href="all_genres.php"><i class="fas fa-layer-group"></i> دسته‌بندی</a>
            <?php if ($is_logged_in): ?>
                <a href="library.php"><i class="fas fa-book-reader"></i> کتابخانه من</a>
            <?php endif; ?>
        </nav>

        <!-- دکمه‌های سمت چپ -->
        <div class="header-actions">
            <!-- جستجو -->
            <form action="search.php" method="GET" class="search-box">
                <input type="text" name="q" class="search-input" placeholder="جستجوی ناول...">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
            </form>

            <?php if ($is_logged_in): ?>
                <!-- حالت لاگین شده: نمایش پروفایل و پنل -->
                
                <?php if ($is_admin): ?>
                    <a href="admin/index.php" class="icon-btn" title="پنل مدیریت">
                        <i class="fas fa-cogs"></i>
                    </a>
                <?php endif; ?>

                <a href="dashboard/index.php" class="icon-btn" title="پنل نویسندگی">
                    <i class="fas fa-pen-nib"></i>
                </a>

                <a href="profile.php" class="login-btn" style="background: rgba(99, 102, 241, 0.1); border: 1px solid var(--border-accent);">
                    <img src="<?php echo $user_avatar; ?>" alt="Avatar" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;">
                    <?php echo $username; ?>
                </a>
            <?php else: ?>
                <!-- حالت مهمان: دکمه ورود -->
                <a href="login.php" class="login-btn">
                    <i class="fas fa-user"></i> ورود / عضویت
                </a>
            <?php endif; ?>

            <!-- دکمه منوی موبایل -->
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</header>

<!-- منوی موبایل (کشویی) -->
<div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-header">
        <a href="index.php" class="logo">
            <div class="logo-icon" style="width:40px;height:40px;font-size:18px;">
                <i class="fas fa-book-open"></i>
            </div>
            <span class="logo-text" style="font-size:20px;">ناول‌خونه</span>
        </a>
        <button class="mobile-menu-close" id="mobileMenuClose">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <form action="search.php" method="GET" class="mobile-search">
        <input type="text" name="q" placeholder="جستجوی ناول...">
    </form>

    <nav class="mobile-nav">
        <a href="index.php" class="active"><i class="fas fa-home"></i> خانه</a>
        <a href="search.php"><i class="fas fa-compass"></i> کشف کنید</a>
        <a href="all_genres.php"><i class="fas fa-layer-group"></i> دسته‌بندی</a>
        
        <?php if ($is_logged_in): ?>
            <a href="library.php"><i class="fas fa-book-reader"></i> کتابخانه من</a>
            <a href="profile.php"><i class="fas fa-user"></i> پروفایل من</a>
            <a href="dashboard/index.php"><i class="fas fa-pen"></i> پنل نویسندگی</a>
            <?php if ($is_admin): ?>
                <a href="admin/index.php"><i class="fas fa-cogs"></i> پنل مدیریت</a>
            <?php endif; ?>
            <a href="logout.php" style="color: #ff4d4d;"><i class="fas fa-sign-out-alt"></i> خروج</a>
        <?php else: ?>
            <a href="login.php"><i class="fas fa-sign-in-alt"></i> ورود به حساب</a>
            <a href="register.php"><i class="fas fa-user-plus"></i> ثبت نام</a>
        <?php endif; ?>
    </nav>
</div>
