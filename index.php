<?php
/*
=====================================================
    NovelWorld - Index (The Monolith)
    Version: 10.0 (Standalone, Full CSS, Wireframe Design)
=====================================================
*/

// --- 1. تنظیمات سیستم ---
ini_set('display_errors', 0); // در محیط واقعی خطا را به کاربر نشان نده
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 2. اتصال به دیتابیس (داخلی) ---
$conn = null;
$db_error = null;
$database_url = getenv('DATABASE_URL');

if ($database_url) {
    $db_parts = parse_url($database_url);
    $dsn = "pgsql:host={$db_parts['host']};port=5432;dbname=" . ltrim($db_parts['path'], '/') . ";sslmode=require";
    try {
        $conn = new PDO($dsn, $db_parts['user'], $db_parts['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        $db_error = "عدم اتصال به دیتابیس";
        error_log($e->getMessage());
    }
}

// --- 3. احراز هویت کاربر ---
$is_logged_in = false;
$username = 'مهمان';
$user_avatar = 'https://ui-avatars.com/api/?name=Guest&background=random&color=fff&background=333';
$is_admin = false;

if (isset($_COOKIE['user_session']) && $conn) {
    try {
        $stmt = $conn->prepare("SELECT id, username, role, profile_picture_url FROM users JOIN sessions s ON users.id = s.user_id WHERE s.session_id = ? AND s.expires_at > NOW()");
        $stmt->execute([$_COOKIE['user_session']]);
        $user = $stmt->fetch();
        if ($user) {
            $is_logged_in = true;
            $username = htmlspecialchars($user['username']);
            $is_admin = ($user['role'] === 'admin');
            if (!empty($user['profile_picture_url'])) $user_avatar = htmlspecialchars($user['profile_picture_url']);
        }
    } catch (Exception $e) {}
}

// --- 4. توابع کمکی ---
function time_elapsed_string($datetime) {
    try {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
        if ($diff->d < 1) {
            if ($diff->h < 1) return $diff->i . ' دقیقه پیش';
            return $diff->h . ' ساعت پیش';
        }
        return $diff->d . ' روز پیش';
    } catch (Exception $e) { return 'نامشخص'; }
}

function map_type($type) {
    $map = ['novel' => 'ناول', 'manhwa' => 'مانهوا', 'manga' => 'مانگا'];
    return $map[strtolower($type ?? '')] ?? 'آسیایی';
}

// --- 5. واکشی داده‌ها ---
// متغیرهای پیش‌فرض
$hot_data = []; $new_data = []; $comp_data = []; $updates_data = [];
$editors_picks = []; $highest_rated = []; 
$stats = ['novels' => 0, 'chapters' => 0, 'users' => 0];
$featured = null;

if ($conn) {
    try {
        // آمار
        $stats['novels'] = $conn->query("SELECT COUNT(*) FROM novels")->fetchColumn();
        $stats['chapters'] = $conn->query("SELECT COUNT(*) FROM chapters")->fetchColumn();
        $stats['users'] = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();

        // اثر ویژه (Featured)
        $feat = $conn->query("SELECT * FROM novels ORDER BY rating DESC LIMIT 1")->fetch();
        if ($feat) {
            $featured = $feat;
            $featured['genres'] = explode(',', $feat['genres']);
        }

        // تابع کمکی واکشی لیست
        function get_list($conn, $order, $limit, $status = null) {
            $sql = "SELECT * FROM novels ";
            if ($status) $sql .= "WHERE status = '$status' ";
            $sql .= "ORDER BY $order DESC LIMIT $limit";
            $stmt = $conn->query($sql);
            $res = [];
            while($row = $stmt->fetch()) {
                $ch = $conn->query("SELECT COUNT(*) FROM chapters WHERE novel_id={$row['id']}")->fetchColumn();
                $badge = ($row['status']=='completed')?'complete':(($row['rating']>=4.8)?'hot':null);
                $res[] = array_merge($row, ['chapters'=>$ch, 'badge'=>$badge, 'type_fa'=>map_type($row['type'])]);
            }
            return $res;
        }

        $hot_data = get_list($conn, 'rating', 8);
        $new_data = get_list($conn, 'created_at', 8);
        $comp_data = get_list($conn, 'rating', 8, 'completed');

        // پیشنهاد سردبیر (Random High Rated)
        $ep_stmt = $conn->query("SELECT * FROM novels WHERE rating > 7.0 ORDER BY RANDOM() LIMIT 8");
        while($row = $ep_stmt->fetch()) {
            $row['chapters'] = $conn->query("SELECT COUNT(*) FROM chapters WHERE novel_id={$row['id']}")->fetchColumn();
            $editors_picks[] = $row;
        }

        // محبوب‌ترین‌ها (Top Rated)
        $hr_stmt = $conn->query("SELECT * FROM novels ORDER BY rating DESC LIMIT 10");
        $highest_rated = $hr_stmt->fetchAll();

        // بروزرسانی‌ها
        $up_stmt = $conn->query("SELECT c.*, n.title as nt, n.cover_url as ni, n.type as nty FROM chapters c JOIN novels n ON c.novel_id=n.id WHERE c.status='approved' ORDER BY c.published_at DESC LIMIT 6");
        while($r = $up_stmt->fetch()) {
            $updates_data[] = [
                'title' => $r['nt'], 'chapter' => "فصل ".$r['chapter_number'],
                'time' => time_elapsed_string($r['published_at']),
                'type' => map_type($r['nty']), 'image' => $r['ni']
            ];
        }

    } catch (Exception $e) { error_log($e->getMessage()); }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ناول‌خونه | دنیای داستان‌های آسیایی</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        /* ==================== CSS کامل ==================== */
        :root {
            --bg-primary: #050508;
            --bg-secondary: #0a0a0f;
            --bg-tertiary: #0f0f16;
            --bg-card: #0c0c12;
            --bg-card-hover: #12121a;
            --accent-primary: #6366f1;
            --accent-secondary: #818cf8;
            --accent-light: #a5b4fc;
            --accent-dark: #4f46e5;
            --gold: #F5D020;
            --text-primary: #ffffff;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --gradient-accent: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #6366f1 100%);
            --gradient-card: linear-gradient(165deg, #12121a 0%, #0a0a0f 100%);
            --gradient-shine: linear-gradient(135deg, #6366f1 0%, #22d3ee 50%, #a78bfa 100%);
            --shadow-card: 0 20px 60px rgba(0, 0, 0, 0.7);
            --border-subtle: rgba(255, 255, 255, 0.04);
            --border-accent: rgba(99, 102, 241, 0.15);
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Vazirmatn', sans-serif; background: var(--bg-primary); color: var(--text-primary); min-height: 100vh; overflow-x: hidden; line-height: 1.6; }
        a { text-decoration: none; color: inherit; transition: 0.3s; }
        ul { list-style: none; }

        /* HEADER */
        header { position: fixed; top: 0; left: 0; right: 0; z-index: 1000; background: rgba(5, 5, 8, 0.95); backdrop-filter: blur(20px); border-bottom: 1px solid var(--border-accent); height: 70px; }
        .header-content { max-width: 1400px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; height: 100%; padding: 0 20px; }
        .logo { display: flex; align-items: center; gap: 10px; font-weight: 900; font-size: 22px; color: white; }
        .logo i { color: var(--accent-primary); }
        nav { display: flex; gap: 20px; }
        nav a { font-size: 14px; font-weight: 500; color: var(--text-secondary); padding: 8px 12px; border-radius: 8px; }
        nav a.active, nav a:hover { color: white; background: rgba(99,102,241,0.1); }
        .header-actions { display: flex; gap: 10px; align-items: center; }
        .search-btn, .icon-btn { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%; background: var(--bg-card); border: 1px solid var(--border-subtle); color: var(--text-secondary); cursor: pointer; }
        .login-btn { padding: 8px 20px; background: var(--gradient-accent); color: white; border-radius: 8px; font-weight: 700; font-size: 14px; display: flex; align-items: center; gap: 8px; }
        
        /* HERO */
        .hero { padding-top: 100px; padding-bottom: 50px; background: radial-gradient(circle at 50% 0%, #1a1a2e 0%, var(--bg-primary) 70%); }
        .hero-inner { max-width: 1400px; margin: 0 auto; padding: 0 20px; display: grid; grid-template-columns: 1fr 1.5fr; gap: 50px; align-items: center; }
        .hero-text h1 { font-size: 48px; font-weight: 900; line-height: 1.2; margin-bottom: 20px; }
        .hero-text h1 span { background: var(--gradient-shine); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero-desc { color: var(--text-secondary); font-size: 16px; margin-bottom: 30px; line-height: 1.8; }
        .hero-stats { display: flex; gap: 40px; margin-bottom: 30px; }
        .stat-num { font-size: 32px; font-weight: 800; color: white; }
        .stat-label { font-size: 13px; color: var(--text-muted); }
        .btn-hero { padding: 15px 35px; background: var(--gradient-accent); color: white; border-radius: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 10px; }

        /* FEATURED CARD */
        .featured-wrapper { position: relative; }
        .featured-card { background: var(--bg-card); border-radius: 20px; border: 1px solid var(--border-accent); padding: 25px; display: flex; gap: 25px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
        .featured-cover { width: 180px; flex-shrink: 0; border-radius: 12px; overflow: hidden; position: relative; }
        .featured-cover img { width: 100%; height: 100%; object-fit: cover; }
        .featured-info { flex: 1; display: flex; flex-direction: column; justify-content: center; }
        .f-tag { display: inline-block; background: rgba(245, 208, 32, 0.15); color: var(--gold); padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; margin-bottom: 10px; width: fit-content; }
        .f-title { font-size: 24px; font-weight: 800; margin-bottom: 10px; color: white; }
        .f-meta { display: flex; gap: 15px; font-size: 13px; color: var(--text-muted); margin-bottom: 15px; }
        .f-meta i { color: var(--accent-primary); }
        .f-synopsis { font-size: 13px; color: var(--text-secondary); line-height: 1.7; margin-bottom: 20px; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

        /* SECTIONS */
        .section { padding: 50px 0; }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .sec-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .sec-title { display: flex; align-items: center; gap: 15px; }
        .sec-icon { width: 45px; height: 45px; background: rgba(255,255,255,0.05); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--accent-primary); font-size: 20px; }
        .sec-title h2 { font-size: 24px; font-weight: 800; }
        .sec-nav { display: flex; gap: 10px; }
        .nav-btn { width: 40px; height: 40px; border-radius: 50%; background: var(--bg-card); border: 1px solid var(--border-subtle); color: var(--text-secondary); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.3s; }
        .nav-btn:hover { background: var(--accent-primary); color: white; }

        /* SLIDERS */
        .slider-container { position: relative; }
        .slider { display: flex; gap: 20px; overflow-x: auto; padding-bottom: 20px; scrollbar-width: none; scroll-behavior: smooth; }
        .slider::-webkit-scrollbar { display: none; }

        /* VERTICAL CARD (Standard) */
        .card { flex: 0 0 220px; background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border-subtle); overflow: hidden; transition: 0.3s; cursor: pointer; position: relative; }
        .card:hover { transform: translateY(-8px); border-color: var(--border-accent); }
        .card-img { width: 100%; aspect-ratio: 2/3; object-fit: cover; }
        .card-badge { position: absolute; top: 10px; right: 10px; background: var(--accent-primary); color: white; font-size: 10px; padding: 4px 8px; border-radius: 4px; font-weight: 700; }
        .card-badge.hot { background: #ef4444; }
        .card-badge.new { background: #06b6d4; }
        .card-rating { position: absolute; top: 10px; left: 10px; background: rgba(0,0,0,0.8); color: var(--gold); font-size: 11px; padding: 3px 6px; border-radius: 6px; font-weight: 700; display: flex; gap: 3px; align-items: center; }
        .card-body { padding: 15px; }
        .card-title { font-size: 15px; font-weight: 700; margin-bottom: 8px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .card-info { display: flex; justify-content: space-between; font-size: 12px; color: var(--text-muted); }

        /* UPDATES GRID */
        .update-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }
        .up-card { background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 15px; display: flex; gap: 15px; align-items: center; transition: 0.3s; }
        .up-card:hover { border-color: var(--border-accent); transform: translateX(-5px); }
        .up-img { width: 70px; height: 90px; border-radius: 8px; object-fit: cover; }
        .up-info { flex: 1; }
        .up-title { font-size: 15px; font-weight: 700; margin-bottom: 5px; }
        .up-ch { color: var(--accent-light); font-size: 13px; font-weight: 600; margin-bottom: 5px; }
        .up-time { font-size: 11px; color: var(--text-muted); }

        /* === EDITOR'S PICK (HORIZONTAL WIREFRAME) === */
        .ep-slide { flex: 0 0 360px; scroll-snap-align: start; }
        .ep-card { display: flex; background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: 16px; overflow: hidden; height: 160px; transition: 0.3s; position: relative; }
        .ep-card:hover { border-color: var(--gold); transform: translateY(-5px); }
        .ep-img { width: 110px; height: 100%; object-fit: cover; flex-shrink: 0; }
        .ep-content { padding: 15px; display: flex; flex-direction: column; justify-content: center; flex: 1; min-width: 0; }
        .ep-tag { font-size: 10px; color: var(--gold); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .ep-title { font-size: 16px; font-weight: 800; margin-bottom: 8px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: white; }
        .ep-desc { font-size: 12px; color: var(--text-muted); line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

        /* === HIGHEST RATED (HORIZONTAL + RANK) === */
        .hr-slide { flex: 0 0 300px; scroll-snap-align: start; }
        .hr-card { display: flex; align-items: center; background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: 16px; padding: 15px; gap: 15px; position: relative; overflow: hidden; transition: 0.3s; height: 130px; }
        .hr-card:hover { border-color: var(--accent-primary); transform: translateY(-3px); }
        .hr-rank { position: absolute; top: -10px; right: -10px; width: 50px; height: 50px; background: var(--accent-primary); color: white; border-radius: 50%; display: flex; align-items: flex-end; justify-content: flex-start; padding: 0 0 12px 16px; font-weight: 900; font-size: 18px; box-shadow: -2px 2px 10px rgba(0,0,0,0.3); z-index: 2; }
        .hr-img { width: 80px; height: 100px; border-radius: 8px; object-fit: cover; flex-shrink: 0; }
        .hr-info { flex: 1; display: flex; flex-direction: column; gap: 5px; }
        .hr-title { font-size: 15px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .hr-rating { color: var(--gold); font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 5px; }
        .hr-status { font-size: 11px; color: var(--text-muted); background: rgba(255,255,255,0.05); padding: 2px 8px; border-radius: 4px; width: fit-content; }

        /* FOOTER */
        footer { background: var(--bg-tertiary); padding: 60px 0 20px; margin-top: 60px; border-top: 1px solid var(--border-accent); }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 40px; margin-bottom: 40px; }
        .f-logo { font-size: 24px; font-weight: 900; color: white; display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
        .f-desc { color: var(--text-secondary); font-size: 14px; line-height: 1.8; }
        .f-links h4 { color: white; margin-bottom: 20px; font-size: 16px; }
        .f-links ul li { margin-bottom: 10px; }
        .f-links a { color: var(--text-muted); font-size: 14px; }
        .f-links a:hover { color: var(--accent-light); }
        .f-bottom { border-top: 1px solid var(--border-subtle); padding-top: 20px; text-align: center; color: var(--text-muted); font-size: 13px; }

        @media (max-width: 1024px) {
            .hero-grid { grid-template-columns: 1fr; gap: 40px; }
            .hero-text { text-align: center; }
            .hero-stats { justify-content: center; }
            .hero-actions { justify-content: center; }
            .featured-card { flex-direction: column; }
            .featured-cover { width: 100%; aspect-ratio: 16/9; }
            nav, .search-box { display: none; }
            .mobile-menu-btn { display: flex !important; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }
        
        .mobile-menu-btn { display: none; background: none; border: none; color: white; font-size: 24px; cursor: pointer; }
        /* Mobile Menu Overlay */
        .mobile-menu { position: fixed; top: 0; right: -300px; width: 280px; height: 100%; background: var(--bg-secondary); z-index: 1002; padding: 20px; transition: 0.3s; border-left: 1px solid var(--border-accent); }
        .mobile-menu.active { right: 0; }
        .mm-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; font-size: 18px; font-weight: 700; }
        .mm-nav a { display: block; padding: 15px; border-bottom: 1px solid var(--border-subtle); color: var(--text-secondary); }
    </style>
</head>
<body>

<!-- Header -->
<header>
    <div class="header-content">
        <a href="index.php" class="logo"><i class="fas fa-book-open"></i> ناول‌خونه</a>
        <nav>
            <a href="index.php" class="active">خانه</a>
            <a href="search.php">کشف</a>
            <a href="all_genres.php">ژانرها</a>
            <?php if($is_logged_in): ?><a href="library.php">کتابخانه</a><?php endif; ?>
        </nav>
        <div class="header-actions">
            <a href="search.php" class="icon-btn"><i class="fas fa-search"></i></a>
            <?php if($is_logged_in): ?>
                <?php if($is_admin): ?><a href="admin/index.php" class="icon-btn"><i class="fas fa-cogs"></i></a><?php endif; ?>
                <a href="dashboard/index.php" class="icon-btn"><i class="fas fa-pen-nib"></i></a>
                <a href="profile.php" class="login-btn">
                    <img src="<?php echo $user_avatar; ?>" style="width:20px;height:20px;border-radius:50%;"> <?php echo $username; ?>
                </a>
            <?php else: ?>
                <a href="login.php" class="login-btn"><i class="fas fa-user"></i> ورود</a>
            <?php endif; ?>
            <button class="mobile-menu-btn" onclick="toggleMenu()"><i class="fas fa-bars"></i></button>
        </div>
    </div>
</header>

<!-- Mobile Menu -->
<div class="mobile-menu" id="mobileMenu">
    <div class="mm-header">منو <i class="fas fa-times" onclick="toggleMenu()" style="cursor:pointer;"></i></div>
    <div class="mm-nav">
        <a href="index.php">خانه</a>
        <a href="search.php">جستجو</a>
        <?php if($is_logged_in): ?>
            <a href="profile.php">پروفایل</a>
            <a href="library.php">کتابخانه</a>
            <a href="logout.php" style="color: #ff4d4d;">خروج</a>
        <?php else: ?>
            <a href="login.php">ورود</a>
            <a href="register.php">ثبت‌نام</a>
        <?php endif; ?>
    </div>
</div>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-inner">
        <div class="hero-text">
            <h1>دنیای <span>ناول</span> را<br>تجربه کنید</h1>
            <p class="hero-desc">بزرگترین آرشیو ناول‌های آسیایی با ترجمه فارسی روان. همین حالا شروع کنید!</p>
            <div class="hero-stats">
                <div><div class="stat-num"><?php echo $stats['novels']; ?>+</div><div class="stat-label">ناول</div></div>
                <div><div class="stat-num"><?php echo $stats['chapters']; ?>+</div><div class="stat-label">فصل</div></div>
                <div><div class="stat-num"><?php echo $stats['users']; ?>+</div><div class="stat-label">کاربر</div></div>
            </div>
            <a href="all_genres.php" class="btn-hero"><i class="fas fa-rocket"></i> شروع مطالعه</a>
        </div>
        
        <?php if($featured): ?>
        <div class="featured-wrapper">
            <div class="featured-card">
                <div class="featured-cover">
                    <img src="<?php echo htmlspecialchars($featured['cover_url']); ?>" alt="Featured">
                </div>
                <div class="featured-info">
                    <span class="f-tag">ویژه سردبیر</span>
                    <h3 class="f-title"><?php echo htmlspecialchars($featured['title']); ?></h3>
                    <div class="f-meta">
                        <span><i class="fas fa-pen"></i> <?php echo htmlspecialchars($featured['author']); ?></span>
                        <span><i class="fas fa-star"></i> <?php echo htmlspecialchars($featured['rating']); ?></span>
                    </div>
                    <p class="f-synopsis"><?php echo htmlspecialchars($featured['summary']); ?></p>
                    <a href="novel_detail.php?id=<?php echo $featured['id']; ?>" class="login-btn" style="width:fit-content">خواندن</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Sliders Logic (PHP Render) -->
<?php function render_slider($id, $title, $icon, $data) { if(empty($data)) return; ?>
<section class="section">
    <div class="container">
        <div class="sec-header">
            <div class="sec-title"><div class="sec-icon"><i class="fas <?php echo $icon; ?>"></i></div><h2><?php echo $title; ?></h2></div>
            <div class="sec-nav">
                <button class="nav-btn" onclick="document.getElementById('<?php echo $id; ?>').scrollBy({left: 300, behavior:'smooth'})"><i class="fas fa-chevron-right"></i></button>
                <button class="nav-btn" onclick="document.getElementById('<?php echo $id; ?>').scrollBy({left: -300, behavior:'smooth'})"><i class="fas fa-chevron-left"></i></button>
            </div>
        </div>
        <div class="slider-container">
            <div class="slider" id="<?php echo $id; ?>">
                <?php foreach($data as $novel): ?>
                <div class="card" onclick="window.location.href='novel_detail.php?id=<?php echo $novel['id']; ?>'">
                    <div class="card-cover">
                        <img src="<?php echo htmlspecialchars($novel['image']); ?>" class="card-img">
                        <?php if($novel['badge']): ?><span class="card-badge <?php echo $novel['badge']; ?>"><?php echo strtoupper($novel['badge']); ?></span><?php endif; ?>
                        <span class="card-rating"><i class="fas fa-star"></i> <?php echo $novel['rating']; ?></span>
                    </div>
                    <div class="card-body">
                        <h4 class="card-title"><?php echo htmlspecialchars($novel['title']); ?></h4>
                        <div class="card-info">
                            <span><?php echo $novel['type']; ?></span>
                            <span><?php echo $novel['chapters']; ?> فصل</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php } ?>

<?php render_slider('hot-slider', 'داغ‌ترین‌ها', 'fa-fire', $hot_data); ?>

<!-- Updates -->
<section class="section" style="background: var(--bg-secondary);">
    <div class="container">
        <div class="sec-header"><div class="sec-title"><div class="sec-icon"><i class="fas fa-bolt"></i></div><h2>بروزرسانی‌ها</h2></div></div>
        <div class="update-grid">
            <?php foreach($updates_data as $up): ?>
            <div class="up-card">
                <img src="<?php echo htmlspecialchars($up['image']); ?>" class="up-img">
                <div class="up-info">
                    <h4 class="up-title"><?php echo htmlspecialchars($up['title']); ?></h4>
                    <div class="up-ch"><?php echo htmlspecialchars($up['chapter']); ?></div>
                    <div class="up-time"><i class="far fa-clock"></i> <?php echo $up['time']; ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Editor's Pick (Horizontal) -->
<?php if(!empty($editors_picks)): ?>
<section class="section">
    <div class="container">
        <div class="sec-header">
            <div class="sec-title"><div class="sec-icon"><i class="fas fa-pen-nib"></i></div><h2>پیشنهاد سردبیر</h2></div>
            <div class="sec-nav">
                <button class="nav-btn" onclick="document.getElementById('ep-slider').scrollBy({left: 300, behavior:'smooth'})"><i class="fas fa-chevron-right"></i></button>
                <button class="nav-btn" onclick="document.getElementById('ep-slider').scrollBy({left: -300, behavior:'smooth'})"><i class="fas fa-chevron-left"></i></button>
            </div>
        </div>
        <div class="slider" id="ep-slider">
            <?php foreach($editors_picks as $novel): ?>
            <div class="ep-slide">
                <a href="novel_detail.php?id=<?php echo $novel['id']; ?>" class="ep-card">
                    <div class="ep-content">
                        <span class="ep-tag"><?php echo htmlspecialchars(explode(',', $novel['genres'])[0]); ?></span>
                        <h4 class="ep-title"><?php echo htmlspecialchars($novel['title']); ?></h4>
                        <p class="ep-desc"><?php echo htmlspecialchars($novel['summary']); ?></p>
                    </div>
                    <img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" class="ep-img">
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php render_slider('new-slider', 'تازه‌ها', 'fa-sparkles', $new_data); ?>

<!-- Highest Rated (Horizontal + Rank) -->
<?php if(!empty($highest_rated)): ?>
<section class="section" style="background: var(--bg-secondary);">
    <div class="container">
        <div class="sec-header">
            <div class="sec-title"><div class="sec-icon"><i class="fas fa-trophy"></i></div><h2>محبوب‌ترین‌ها</h2></div>
            <div class="sec-nav">
                <button class="nav-btn" onclick="document.getElementById('hr-slider').scrollBy({left: 300, behavior:'smooth'})"><i class="fas fa-chevron-right"></i></button>
                <button class="nav-btn" onclick="document.getElementById('hr-slider').scrollBy({left: -300, behavior:'smooth'})"><i class="fas fa-chevron-left"></i></button>
            </div>
        </div>
        <div class="slider" id="hr-slider">
            <?php foreach($highest_rated as $i => $novel): ?>
            <div class="hr-slide">
                <a href="novel_detail.php?id=<?php echo $novel['id']; ?>" class="hr-card">
                    <div class="hr-rank">#<?php echo $i+1; ?></div>
                    <img src="<?php echo htmlspecialchars($novel['cover_url']); ?>" class="hr-img">
                    <div class="hr-info">
                        <h4 class="hr-title"><?php echo htmlspecialchars($novel['title']); ?></h4>
                        <div class="hr-rating"><i class="fas fa-star"></i> <?php echo $novel['rating']; ?></div>
                        <span class="hr-status"><?php echo $novel['status']; ?></span>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<footer>
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="f-logo"><i class="fas fa-book-open"></i> ناول‌خونه</div>
                <p class="f-desc">بزرگترین مرجع ناول‌های فارسی با ترجمه اختصاصی و کیفیت بالا.</p>
            </div>
            <div class="f-links">
                <h4>دسترسی سریع</h4>
                <ul>
                    <li><a href="index.php">خانه</a></li>
                    <li><a href="search.php">جستجو</a></li>
                    <li><a href="login.php">ورود</a></li>
                </ul>
            </div>
            <div class="f-links">
                <h4>ژانرها</h4>
                <ul>
                    <li><a href="search.php?genres[]=اکشن">اکشن</a></li>
                    <li><a href="search.php?genres[]=فانتزی">فانتزی</a></li>
                    <li><a href="search.php?genres[]=عاشقانه">عاشقانه</a></li>
                </ul>
            </div>
            <div class="f-links">
                <h4>پشتیبانی</h4>
                <ul>
                    <li><a href="#">تماس با ما</a></li>
                    <li><a href="#">قوانین</a></li>
                </ul>
            </div>
        </div>
        <div class="f-bottom">© ۱۴۰۳ ناول‌خونه - تمامی حقوق محفوظ است.</div>
    </div>
</footer>

<script>
    function toggleMenu() {
        document.getElementById('mobileMenu').classList.toggle('active');
    }
</script>

</body>
</html>
