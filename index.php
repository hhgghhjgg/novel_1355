<?php
/*
=====================================================
    NovelWorld - Index (Ultimate Edition)
    Version: 12.0 (Exact Design + Full Dynamic Backend)
=====================================================
*/

// --- تنظیمات اولیه ---
ini_set('display_errors', 0); // عدم نمایش خطا به کاربر برای حفظ ظاهر
error_reporting(E_ALL);

// شروع سشن
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- اتصال به دیتابیس ---
$conn = null;
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
        // در صورت عدم اتصال، آرایه‌ها خالی می‌مانند ولی سایت بالا می‌آید
    }
}

// --- احراز هویت ---
$is_logged_in = false;
$username = 'مهمان';
$user_avatar = 'https://ui-avatars.com/api/?name=Guest&background=random&color=fff';
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

// --- توابع کمکی ---
function time_ago($datetime) {
    if(!$datetime) return '';
    $time = strtotime($datetime);
    $diff = time() - $time;
    if($diff < 60) return 'لحظاتی پیش';
    if($diff < 3600) return floor($diff/60) . ' دقیقه پیش';
    if($diff < 86400) return floor($diff/3600) . ' ساعت پیش';
    return floor($diff/86400) . ' روز پیش';
}

function get_type_fa($type) {
    $map = ['novel'=>'ناول', 'manhwa'=>'مانهوا', 'manga'=>'مانگا'];
    return $map[strtolower($type ?? '')] ?? 'آسیایی';
}

function get_badge_class($status, $rating, $date) {
    if ($status === 'completed') return ['class'=>'complete', 'text'=>'تکمیل'];
    if ($rating >= 4.8) return ['class'=>'hot', 'text'=>'داغ'];
    if (strtotime($date) > strtotime('-7 days')) return ['class'=>'new', 'text'=>'جدید'];
    return null;
}

// --- واکشی داده‌ها ---
$hot_novels = []; $new_novels = []; $complete_novels = [];
$updates_list = []; $editors_picks = []; $highest_rated = [];
$stats = ['novels'=>0, 'chapters'=>0, 'users'=>0];
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

        // تابع دریافت لیست
        function fetch_list($conn, $order_by, $limit, $status=null) {
            $sql = "SELECT * FROM novels ";
            if($status) $sql .= "WHERE status='$status' ";
            $sql .= "ORDER BY $order_by DESC LIMIT $limit";
            $stmt = $conn->query($sql);
            $res = [];
            while($r = $stmt->fetch()) {
                $chs = $conn->query("SELECT COUNT(*) FROM chapters WHERE novel_id={$r['id']}")->fetchColumn();
                $badgeInfo = get_badge_class($r['status'], $r['rating'], $r['created_at']);
                $res[] = [
                    'id' => $r['id'],
                    'title' => $r['title'],
                    'author' => $r['author'],
                    'rating' => $r['rating'],
                    'views' => 'Top', // اگر ستون بازدید ندارید
                    'chapters' => $chs,
                    'genres' => explode(',', $r['genres']),
                    'badge' => $badgeInfo,
                    'type' => get_type_fa($r['type']),
                    'image' => $r['cover_url']
                ];
            }
            return $res;
        }

        $hot_novels = fetch_list($conn, 'rating', 10);
        $new_novels = fetch_list($conn, 'created_at', 10);
        $complete_novels = fetch_list($conn, 'rating', 10, 'completed');

        // بروزرسانی‌ها
        $up_q = $conn->query("SELECT c.chapter_number, c.published_at, n.id, n.title, n.cover_url, n.type FROM chapters c JOIN novels n ON c.novel_id=n.id WHERE c.status='approved' ORDER BY c.published_at DESC LIMIT 6");
        while($r = $up_q->fetch()) {
            $updates_list[] = [
                'id' => $r['id'],
                'title' => $r['title'],
                'chapter' => "فصل " . $r['chapter_number'],
                'time' => time_ago($r['published_at']),
                'type' => get_type_fa($r['type']),
                'image' => $r['cover_url']
            ];
        }

        // پیشنهاد سردبیر (تصادفی از بین خوب‌ها)
        $ep_q = $conn->query("SELECT * FROM novels WHERE rating > 4.0 ORDER BY RANDOM() LIMIT 8");
        while($r = $ep_q->fetch()) {
            $editors_picks[] = [
                'id' => $r['id'],
                'title' => $r['title'],
                'summary' => $r['summary'],
                'genre' => explode(',', $r['genres'])[0],
                'image' => $r['cover_url']
            ];
        }

        // محبوب‌ترین‌ها (بالاترین امتیاز)
        $hr_q = $conn->query("SELECT * FROM novels ORDER BY rating DESC LIMIT 10");
        while($r = $hr_q->fetch()) {
            $highest_rated[] = [
                'id' => $r['id'],
                'title' => $r['title'],
                'rating' => $r['rating'],
                'status' => $r['status'],
                'image' => $r['cover_url']
            ];
        }

    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ناول‌خونه | مرجع ناول‌های آسیایی</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ==================== CSS کامل و بدون تغییر ظاهری ==================== */
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
            --radius-md: 12px; --radius-lg: 16px; --radius-xl: 24px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; font-size: 16px; }
        body { font-family: 'Vazirmatn', sans-serif; background: var(--bg-primary); color: var(--text-primary); min-height: 100vh; overflow-x: hidden; line-height: 1.6; }
        a { text-decoration: none; color: inherit; transition: 0.3s; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg-secondary); }
        ::-webkit-scrollbar-thumb { background: var(--accent-dark); border-radius: 3px; }
        
        /* HEADER */
        header { position: fixed; top: 0; left: 0; right: 0; z-index: 1000; background: rgba(5, 5, 8, 0.95); backdrop-filter: blur(20px); border-bottom: 1px solid var(--border-accent); height: 70px; }
        .header-content { max-width: 1920px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; height: 100%; padding: 0 32px; }
        .logo { display: flex; align-items: center; gap: 14px; font-weight: 900; font-size: 24px; color: white; }
        .logo i { color: var(--accent-primary); font-size: 28px; }
        .logo-text { background: var(--gradient-shine); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        nav { display: flex; gap: 20px; }
        nav a { font-size: 14px; font-weight: 500; color: var(--text-secondary); padding: 8px 12px; border-radius: 8px; display: flex; align-items: center; gap: 8px; }
        nav a:hover, nav a.active { color: white; background: rgba(99,102,241,0.1); }
        .header-actions { display: flex; gap: 14px; align-items: center; }
        .icon-btn { width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; border-radius: 12px; background: var(--bg-card); border: 1px solid var(--border-subtle); color: var(--text-secondary); cursor: pointer; position: relative; }
        .icon-btn:hover { border-color: var(--accent-primary); color: var(--accent-light); }
        .login-btn { padding: 10px 24px; background: var(--gradient-accent); color: white; border-radius: 12px; font-weight: 700; font-size: 14px; display: flex; align-items: center; gap: 8px; }
        .mobile-menu-btn { display: none; background: none; border: none; color: white; font-size: 24px; }
        
        /* HERO */
        .hero { padding: 120px 32px 80px; background: radial-gradient(circle at 50% 0%, #1a1a2e 0%, var(--bg-primary) 70%); min-height: 80vh; display: flex; align-items: center; }
        .hero-inner { max-width: 1920px; margin: 0 auto; width: 100%; display: grid; grid-template-columns: 1fr 1.2fr; gap: 80px; align-items: center; }
        .hero-text h1 { font-size: 58px; font-weight: 900; line-height: 1.1; margin-bottom: 24px; }
        .hero-text h1 span { background: var(--gradient-shine); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero-desc { font-size: 18px; color: var(--text-secondary); line-height: 1.8; margin-bottom: 40px; max-width: 500px; }
        .hero-stats { display: flex; gap: 50px; margin-bottom: 40px; }
        .stat-val { font-size: 42px; font-weight: 900; background: var(--gradient-shine); -webkit-background-clip: text; -webkit-text-fill-color: transparent; line-height: 1; }
        .stat-lbl { font-size: 14px; color: var(--text-muted); margin-top: 5px; }
        .btn-hero { padding: 18px 40px; background: var(--gradient-accent); color: white; border-radius: 16px; font-weight: 700; font-size: 16px; display: inline-flex; align-items: center; gap: 10px; transition: 0.3s; }
        .btn-hero:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(99,102,241,0.4); }

        /* FEATURED CARD */
        .featured-card { background: var(--gradient-card); border-radius: 24px; padding: 30px; border: 1px solid var(--border-accent); display: flex; gap: 30px; position: relative; overflow: hidden; box-shadow: var(--shadow-card); }
        .featured-ribbon { position: absolute; top: 30px; right: -35px; background: var(--gradient-accent); color: white; padding: 8px 50px; font-size: 12px; font-weight: 700; transform: rotate(45deg); z-index: 5; }
        .featured-cover { width: 200px; flex-shrink: 0; border-radius: 16px; overflow: hidden; position: relative; aspect-ratio: 2/3; }
        .featured-cover img { width: 100%; height: 100%; object-fit: cover; }
        .featured-info { flex: 1; display: flex; flex-direction: column; justify-content: center; }
        .featured-info h3 { font-size: 28px; font-weight: 800; margin-bottom: 15px; color: white; }
        .f-meta { display: flex; gap: 20px; font-size: 14px; color: var(--text-secondary); margin-bottom: 20px; }
        .f-meta i { color: var(--accent-primary); margin-left: 5px; }
        .f-genres { display: flex; gap: 10px; margin-bottom: 25px; }
        .f-badge { padding: 6px 14px; background: rgba(99,102,241,0.1); border: 1px solid var(--border-accent); border-radius: 50px; font-size: 12px; color: var(--accent-light); }
        .f-synopsis { font-size: 14px; color: var(--text-secondary); line-height: 1.8; margin-bottom: 25px; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

        /* SECTIONS */
        .section { padding: 60px 32px; max-width: 1920px; margin: 0 auto; }
        .sec-dark { background: var(--bg-secondary); max-width: none; padding: 60px 0; }
        .sec-dark .container { max-width: 1920px; margin: 0 auto; padding: 0 32px; }
        .sec-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .sec-title { display: flex; align-items: center; gap: 15px; }
        .sec-icon { width: 50px; height: 50px; background: rgba(99,102,241,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--accent-primary); font-size: 22px; }
        .sec-title h2 { font-size: 26px; font-weight: 800; margin: 0; }
        .sec-nav { display: flex; gap: 10px; }
        .nav-arrow { width: 44px; height: 44px; border-radius: 12px; background: var(--bg-card); border: 1px solid var(--border-subtle); color: var(--text-secondary); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.3s; }
        .nav-arrow:hover { background: var(--accent-primary); color: white; }

        /* SLIDERS */
        .slider-wrap { position: relative; }
        .slider { display: flex; gap: 20px; overflow-x: auto; padding-bottom: 20px; scrollbar-width: none; scroll-snap-type: x mandatory; }
        .slider::-webkit-scrollbar { display: none; }
        
        /* CARD (VERTICAL) */
        .card { flex: 0 0 240px; background: var(--gradient-card); border-radius: 16px; border: 1px solid var(--border-subtle); overflow: hidden; transition: 0.4s; cursor: pointer; scroll-snap-align: start; position: relative; }
        .card:hover { transform: translateY(-10px); border-color: var(--border-accent); box-shadow: 0 15px 40px rgba(0,0,0,0.5); }
        .card-img { width: 100%; aspect-ratio: 2/3; object-fit: cover; }
        .card-ovl { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.9), transparent 50%); }
        .c-badge { position: absolute; top: 10px; right: 10px; font-size: 10px; font-weight: 700; padding: 4px 8px; border-radius: 6px; color: white; }
        .bg-hot { background: #ef4444; } .bg-new { background: #06b6d4; } .bg-complete { background: #22c55e; }
        .c-rate { position: absolute; top: 10px; left: 10px; background: rgba(0,0,0,0.8); padding: 4px 8px; border-radius: 8px; font-size: 12px; font-weight: 700; color: var(--gold); }
        .card-body { padding: 15px; }
        .c-title { font-size: 15px; font-weight: 700; margin-bottom: 8px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .c-meta { display: flex; justify-content: space-between; font-size: 12px; color: var(--text-muted); }

        /* UPDATES GRID */
        .up-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }
        .up-card { background: var(--bg-card); border-radius: 16px; padding: 15px; border: 1px solid var(--border-subtle); display: flex; gap: 15px; align-items: center; transition: 0.3s; cursor: pointer; }
        .up-card:hover { border-color: var(--border-accent); transform: translateX(-5px); }
        .up-img { width: 70px; height: 95px; border-radius: 10px; object-fit: cover; }
        .up-info h4 { font-size: 15px; margin-bottom: 5px; }
        .up-ch { font-size: 13px; color: var(--accent-light); font-weight: 600; margin-bottom: 5px; }
        .up-time { font-size: 11px; color: var(--text-muted); }

        /* EDITOR'S PICK (HORIZONTAL WIREFRAME) */
        .ep-slide { flex: 0 0 360px; scroll-snap-align: start; }
        .ep-card { display: flex; background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border-subtle); overflow: hidden; height: 160px; transition: 0.3s; position: relative; }
        .ep-card:hover { border-color: var(--gold); transform: translateY(-5px); }
        .ep-img { width: 110px; height: 100%; object-fit: cover; flex-shrink: 0; }
        .ep-info { padding: 15px; display: flex; flex-direction: column; justify-content: center; flex: 1; min-width: 0; }
        .ep-cat { font-size: 10px; color: var(--gold); text-transform: uppercase; margin-bottom: 5px; font-weight: 700; }
        .ep-title { font-size: 16px; font-weight: 800; margin-bottom: 8px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ep-sum { font-size: 12px; color: var(--text-muted); line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

        /* HIGHEST RATED (HORIZONTAL + RANK) */
        .hr-slide { flex: 0 0 300px; scroll-snap-align: start; }
        .hr-card { display: flex; align-items: center; background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border-subtle); padding: 15px; gap: 15px; position: relative; overflow: hidden; transition: 0.3s; height: 130px; }
        .hr-card:hover { border-color: var(--accent-primary); transform: translateY(-3px); }
        .hr-rank { position: absolute; top: -10px; right: -10px; width: 50px; height: 50px; background: var(--accent-primary); color: white; border-radius: 50%; display: flex; align-items: flex-end; justify-content: flex-start; padding: 0 0 12px 14px; font-weight: 900; font-size: 18px; box-shadow: -2px 2px 10px rgba(0,0,0,0.3); z-index: 2; }
        .hr-img { width: 75px; height: 100px; border-radius: 10px; object-fit: cover; flex-shrink: 0; }
        .hr-info { flex: 1; display: flex; flex-direction: column; gap: 5px; }
        .hr-title { font-size: 15px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .hr-rate { color: var(--gold); font-size: 13px; font-weight: 700; }

        /* FOOTER & MOBILE */
        footer { background: var(--bg-tertiary); padding: 50px 0; border-top: 1px solid var(--border-accent); margin-top: 50px; }
        .foot-content { max-width: 1920px; margin: 0 auto; padding: 0 32px; text-align: center; }
        .foot-logo { font-size: 24px; font-weight: 900; color: white; margin-bottom: 10px; display: inline-block; }
        .foot-copy { color: var(--text-muted); font-size: 14px; }
        
        @media(max-width:1024px){.hero-inner{grid-template-columns:1fr;gap:40px;text-align:center}.hero-stats{justify-content:center}.featured-card{flex-direction:column}.featured-cover{width:100%;aspect-ratio:16/9}nav,.search-box,.login-btn.desktop{display:none}.mobile-menu-btn{display:block}}
        @media(max-width:768px){.hero-text h1{font-size:36px}.stat-value{font-size:32px}.card{flex:0 0 180px}.up-grid{grid-template-columns:1fr}}
        .mobile-menu-btn{display:none;background:none;border:none;color:white;font-size:24px;cursor:pointer}
        .mob-menu{position:fixed;top:0;right:-100%;width:280px;height:100%;background:var(--bg-secondary);z-index:2000;padding:20px;transition:0.3s;border-left:1px solid var(--border-accent)}
        .mob-menu.active{right:0}.mob-ovl{position:fixed;inset:0;background:rgba(0,0,0,0.8);z-index:1999;display:none}.mob-ovl.active{display:block}
        .mob-links a{display:block;padding:15px;border-bottom:1px solid var(--border-subtle);color:var(--text-secondary);font-weight:500}
    </style>
</head>
<body>

    <!-- Header -->
    <header id="header">
        <div class="header-content">
            <a href="index.php" class="logo"><i class="fas fa-book-open"></i> <span class="logo-text">ناول‌خونه</span></a>
            <nav>
                <a href="index.php" class="active"><i class="fas fa-home"></i> خانه</a>
                <a href="search.php"><i class="fas fa-compass"></i> کشف</a>
                <a href="all_genres.php"><i class="fas fa-layer-group"></i> دسته‌بندی</a>
                <?php if($is_logged_in): ?><a href="library.php"><i class="fas fa-book-reader"></i> کتابخانه</a><?php endif; ?>
            </nav>
            <div class="header-actions">
                <a href="search.php" class="icon-btn"><i class="fas fa-search"></i></a>
                <?php if($is_logged_in): ?>
                    <?php if($is_admin): ?><a href="admin/index.php" class="icon-btn"><i class="fas fa-cogs"></i></a><?php endif; ?>
                    <a href="dashboard/index.php" class="icon-btn"><i class="fas fa-pen-nib"></i></a>
                    <a href="profile.php" class="login-btn desktop" style="background:rgba(99,102,241,0.1);border:1px solid rgba(99,102,241,0.3)">
                        <img src="<?php echo $user_avatar; ?>" style="width:20px;height:20px;border-radius:50%"> <?php echo $username; ?>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="login-btn desktop"><i class="fas fa-user"></i> ورود</a>
                <?php endif; ?>
                <button class="mobile-menu-btn" onclick="toggleMob()"><i class="fas fa-bars"></i></button>
            </div>
        </div>
    </header>

    <!-- Mobile Menu -->
    <div class="mob-ovl" id="mobOvl" onclick="toggleMob()"></div>
    <div class="mob-menu" id="mobMenu">
        <div style="display:flex;justify-content:space-between;margin-bottom:30px;font-size:18px;font-weight:700">
            <span>منو</span><i class="fas fa-times" onclick="toggleMob()" style="cursor:pointer"></i>
        </div>
        <div class="mob-links">
            <a href="index.php">خانه</a>
            <a href="search.php">جستجو</a>
            <a href="all_genres.php">ژانرها</a>
            <?php if($is_logged_in): ?>
                <a href="profile.php">پروفایل</a>
                <a href="library.php">کتابخانه</a>
                <a href="logout.php" style="color:#ff4d4d">خروج</a>
            <?php else: ?>
                <a href="login.php">ورود</a>
                <a href="register.php">ثبت‌نام</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Hero -->
    <section class="hero">
        <div class="hero-inner">
            <div class="hero-text">
                <h1>دنیای <span>ناول</span> را<br>با ما تجربه کنید</h1>
                <p class="hero-desc">بزرگترین کتابخانه آنلاین ناول‌های ترجمه شده فارسی. هزاران ناول چینی، کره‌ای و ژاپنی با بهترین کیفیت.</p>
                <div class="hero-stats">
                    <div><div class="stat-num"><?php echo number_format($stats['novels']); ?>+</div><div class="stat-label">ناول</div></div>
                    <div><div class="stat-num"><?php echo number_format($stats['chapters']); ?>+</div><div class="stat-label">فصل</div></div>
                    <div><div class="stat-num"><?php echo number_format($stats['users']); ?>+</div><div class="stat-label">کاربر</div></div>
                </div>
                <a href="all_genres.php" class="btn-hero"><i class="fas fa-rocket"></i> شروع مطالعه</a>
            </div>
            
            <?php if($featured): ?>
            <div class="featured-wrapper">
                <div class="featured-card">
                    <div class="featured-ribbon"><i class="fas fa-fire"></i> ویژه</div>
                    <div class="featured-cover"><img src="<?php echo htmlspecialchars($featured['cover_url']); ?>" alt="Featured"></div>
                    <div class="featured-info">
                        <span class="f-tag">پیشنهاد ما</span>
                        <h3 class="f-title"><?php echo htmlspecialchars($featured['title']); ?></h3>
                        <div class="f-meta">
                            <span><i class="fas fa-pen"></i> <?php echo htmlspecialchars($featured['author']); ?></span>
                            <span><i class="fas fa-star"></i> <?php echo htmlspecialchars($featured['rating']); ?></span>
                        </div>
                        <p class="f-synopsis"><?php echo htmlspecialchars($featured['summary']); ?></p>
                        <a href="novel_detail.php?id=<?php echo $featured['id']; ?>" class="login-btn" style="width:fit-content;text-decoration:none">خواندن</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Sliders Logic -->
    <?php function render_slider($id, $title, $icon, $data) { if(empty($data)) return; ?>
    <section class="section">
        <div class="container">
            <div class="sec-header">
                <div class="sec-title"><div class="sec-icon"><i class="fas <?php echo $icon; ?>"></i></div><h2><?php echo $title; ?></h2></div>
                <div class="sec-nav">
                    <button class="nav-arrow" onclick="document.getElementById('<?php echo $id; ?>').scrollBy({left:300,behavior:'smooth'})"><i class="fas fa-chevron-right"></i></button>
                    <button class="nav-arrow" onclick="document.getElementById('<?php echo $id; ?>').scrollBy({left:-300,behavior:'smooth'})"><i class="fas fa-chevron-left"></i></button>
                </div>
            </div>
            <div class="slider-wrap"><div class="slider" id="<?php echo $id; ?>">
                <?php foreach($data as $n): ?>
                <div class="card" onclick="window.location.href='novel_detail.php?id=<?php echo $n['id']; ?>'">
                    <div class="card-img-wrap" style="position:relative">
                        <img src="<?php echo htmlspecialchars($n['image']); ?>" class="card-img">
                        <div class="card-ovl"></div>
                        <div class="card-rating"><i class="fas fa-star"></i> <?php echo $n['rating']; ?></div>
                        <?php if($n['badge']): ?><div class="c-badge bg-<?php echo $n['badge']['class']; ?>"><?php echo $n['badge']['text']; ?></div><?php endif; ?>
                    </div>
                    <div class="card-body">
                        <h4 class="c-title"><?php echo htmlspecialchars($n['title']); ?></h4>
                        <div class="c-meta"><span><?php echo $n['type']; ?></span><span><?php echo $n['chapters']; ?> فصل</span></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div></div>
        </div>
    </section>
    <?php } ?>

    <?php render_slider('hot-slider', 'داغ‌ترین‌ها', 'fa-fire', $hot_novels); ?>

    <!-- Updates -->
    <section class="section sec-dark">
        <div class="container">
            <div class="sec-header"><div class="sec-title"><div class="sec-icon"><i class="fas fa-bolt"></i></div><h2>آخرین بروزرسانی‌ها</h2></div></div>
            <div class="up-grid">
                <?php foreach($updates_list as $u): ?>
                <div class="up-card" onclick="window.location.href='novel_detail.php?id=<?php echo $u['id']; ?>'">
                    <img src="<?php echo htmlspecialchars($u['image']); ?>" class="up-img">
                    <div class="up-info">
                        <h4 class="up-title"><?php echo htmlspecialchars($u['title']); ?></h4>
                        <div class="up-ch"><?php echo htmlspecialchars($u['chapter']); ?></div>
                        <div class="up-time"><i class="far fa-clock"></i> <?php echo $u['time']; ?></div>
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
            <div class="sec-header"><div class="sec-title"><div class="sec-icon"><i class="fas fa-pen-nib"></i></div><h2>پیشنهاد سردبیر</h2></div>
            <div class="sec-nav"><button class="nav-arrow" onclick="document.getElementById('ep-s').scrollBy({left:300,behavior:'smooth'})"><i class="fas fa-chevron-right"></i></button><button class="nav-arrow" onclick="document.getElementById('ep-s').scrollBy({left:-300,behavior:'smooth'})"><i class="fas fa-chevron-left"></i></button></div></div>
            <div class="slider-wrap"><div class="slider" id="ep-s">
                <?php foreach($editors_picks as $ep): ?>
                <div class="ep-slide">
                    <a href="novel_detail.php?id=<?php echo $ep['id']; ?>" class="ep-card">
                        <div class="ep-info">
                            <span class="ep-cat"><?php echo htmlspecialchars($ep['genre']); ?></span>
                            <h4 class="ep-title"><?php echo htmlspecialchars($ep['title']); ?></h4>
                            <p class="ep-sum"><?php echo htmlspecialchars($ep['summary']); ?></p>
                        </div>
                        <img src="<?php echo htmlspecialchars($ep['image']); ?>" class="ep-img">
                    </a>
                </div>
                <?php endforeach; ?>
            </div></div>
        </div>
    </section>
    <?php endif; ?>

    <?php render_slider('new-slider', 'تازه‌ها', 'fa-sparkles', $new_novels); ?>

    <!-- Highest Rated (Horizontal Rank) -->
    <?php if(!empty($highest_rated)): ?>
    <section class="section sec-dark">
        <div class="container">
            <div class="sec-header"><div class="sec-title"><div class="sec-icon"><i class="fas fa-trophy"></i></div><h2>محبوب‌ترین‌ها</h2></div>
            <div class="sec-nav"><button class="nav-arrow" onclick="document.getElementById('hr-s').scrollBy({left:300,behavior:'smooth'})"><i class="fas fa-chevron-right"></i></button><button class="nav-arrow" onclick="document.getElementById('hr-s').scrollBy({left:-300,behavior:'smooth'})"><i class="fas fa-chevron-left"></i></button></div></div>
            <div class="slider-wrap"><div class="slider" id="hr-s">
                <?php foreach($highest_rated as $i => $hr): ?>
                <div class="hr-slide">
                    <a href="novel_detail.php?id=<?php echo $hr['id']; ?>" class="hr-card">
                        <div class="hr-rank">#<?php echo $i+1; ?></div>
                        <img src="<?php echo htmlspecialchars($hr['image']); ?>" class="hr-img">
                        <div class="hr-info">
                            <h4 class="hr-title"><?php echo htmlspecialchars($hr['title']); ?></h4>
                            <div class="hr-rate"><i class="fas fa-star"></i> <?php echo $hr['rating']; ?></div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div></div>
        </div>
    </section>
    <?php endif; ?>

    <?php render_slider('comp-slider', 'تکمیل شده', 'fa-check-double', $complete_novels); ?>

    <footer>
        <div class="foot-content">
            <div class="foot-logo"><i class="fas fa-book-open"></i> ناول‌خونه</div>
            <p class="foot-copy">© ۱۴۰۳ ناول‌خونه - تمامی حقوق محفوظ است</p>
        </div>
    </footer>

    <script>
        function toggleMob() {
            document.getElementById('mobMenu').classList.toggle('active');
            document.getElementById('mobOvl').classList.toggle('active');
        }
        document.querySelectorAll('.slider').forEach(slider => {
            slider.addEventListener('wheel', (evt) => {
                evt.preventDefault();
                slider.scrollLeft += evt.deltaY;
            });
        });
    </script>
</body>
</html>
