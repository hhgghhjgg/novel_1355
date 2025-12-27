<?php
/*
=====================================================
    NovelWorld - Index (ORIGINAL DESIGN - FULL CODE)
    Version: 100% UNTOUCHED DESIGN
=====================================================
*/

// --- 1. تنظیمات و اتصال (بک‌اند) ---
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$conn = null;
$database_url = getenv('DATABASE_URL');
if ($database_url) {
    $db_parts = parse_url($database_url);
    $dsn = "pgsql:host={$db_parts['host']};port=5432;dbname=" . ltrim($db_parts['path'], '/') . ";sslmode=require";
    try {
        $conn = new PDO($dsn, $db_parts['user'], $db_parts['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    } catch (Exception $e) { }
}

// --- 2. منطق کاربری ---
$is_logged_in = false;
$username = 'مهمان';
$user_avatar = 'https://ui-avatars.com/api/?name=Guest&background=random';
$is_admin = false;

if (isset($_COOKIE['user_session']) && $conn) {
    $stmt = $conn->prepare("SELECT id, username, role, profile_picture_url FROM users JOIN sessions s ON users.id = s.user_id WHERE s.session_id = ? AND s.expires_at > NOW()");
    $stmt->execute([$_COOKIE['user_session']]);
    $user = $stmt->fetch();
    if ($user) {
        $is_logged_in = true;
        $username = htmlspecialchars($user['username']);
        $is_admin = ($user['role'] === 'admin');
        if (!empty($user['profile_picture_url'])) $user_avatar = htmlspecialchars($user['profile_picture_url']);
    }
}

// --- 3. واکشی داده‌ها ---
$stats = ['novels'=>0, 'chapters'=>0, 'users'=>0];
$featured = null;
$hot_novels = []; $new_novels = []; $complete_novels = []; 
$updates_data = []; $editors_picks = []; $highest_rated = [];

if ($conn) {
    try {
        // آمار
        $stats['novels'] = $conn->query("SELECT COUNT(*) FROM novels")->fetchColumn();
        $stats['chapters'] = $conn->query("SELECT COUNT(*) FROM chapters")->fetchColumn();
        $stats['users'] = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();

        // ویژه
        $featured = $conn->query("SELECT * FROM novels ORDER BY rating DESC LIMIT 1")->fetch();

        // تابع کمکی
        function get_novels($conn, $order, $limit, $status=null) {
            $sql = "SELECT * FROM novels ";
            if($status) $sql .= "WHERE status='$status' ";
            $sql .= "ORDER BY $order DESC LIMIT $limit";
            $stmt = $conn->query($sql);
            $res = [];
            while($r = $stmt->fetch()) {
                $ch = $conn->query("SELECT COUNT(*) FROM chapters WHERE novel_id={$r['id']}")->fetchColumn();
                $badge = ($r['status']=='completed')?'complete':(($r['rating']>=4.8)?'hot':(($r['created_at']>date('Y-m-d',strtotime('-7 days')))?'new':null));
                $r['chapters_count'] = $ch;
                $r['badge'] = $badge;
                $r['type_fa'] = ($r['type']=='novel')?'ناول':(($r['type']=='manhwa')?'مانهوا':'مانگا');
                $r['genres_arr'] = explode(',', $r['genres']);
                $res[] = $r;
            }
            return $res;
        }

        $hot_novels = get_novels($conn, 'rating', 10);
        $new_novels = get_novels($conn, 'created_at', 10);
        $complete_novels = get_novels($conn, 'rating', 10, 'completed');

        // بروزرسانی‌ها
        $up = $conn->query("SELECT c.*, n.title as nt, n.cover_url as ni, n.type as nty FROM chapters c JOIN novels n ON c.novel_id=n.id WHERE c.status='approved' ORDER BY c.published_at DESC LIMIT 6");
        while($r = $up->fetch()) {
            $updates_data[] = [
                'id' => $r['novel_id'], 'title' => $r['nt'], 'chapter' => "فصل ".$r['chapter_number'],
                'time' => 'جدید', 'type' => ($r['nty']=='novel')?'ناول':'مانهوا', 'image' => $r['ni']
            ];
        }

        // پیشنهاد سردبیر (Random)
        $editors_picks = get_novels($conn, 'RANDOM()', 8);

        // محبوب‌ترین‌ها (Rating)
        $highest_rated = get_novels($conn, 'rating', 10);

    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ناول‌خونه | خانه ترجمه ناول</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* 
           ========================================
           ORIGINAL FULL CSS (NO COMPRESSION)
           ========================================
        */
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
            --accent-glow: rgba(99, 102, 241, 0.5);
            --cyan-accent: #22d3ee;
            --purple-accent: #a78bfa;
            --text-primary: #ffffff;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --gradient-accent: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #6366f1 100%);
            --gradient-accent-alt: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            --gradient-card: linear-gradient(165deg, #12121a 0%, #0a0a0f 100%);
            --gradient-shine: linear-gradient(135deg, #6366f1 0%, #22d3ee 50%, #a78bfa 100%);
            --shadow-accent: 0 0 50px rgba(99, 102, 241, 0.15);
            --shadow-accent-strong: 0 0 80px rgba(99, 102, 241, 0.25);
            --shadow-card: 0 20px 60px rgba(0, 0, 0, 0.7);
            --border-subtle: rgba(255, 255, 255, 0.04);
            --border-accent: rgba(99, 102, 241, 0.15);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; font-size: 16px; }
        body { font-family: 'Vazirmatn', sans-serif; background: var(--bg-primary); color: var(--text-primary); min-height: 100vh; overflow-x: hidden; line-height: 1.6; }
        
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg-secondary); }
        ::-webkit-scrollbar-thumb { background: var(--accent-dark); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--accent-primary); }

        /* HEADER */
        header { position: fixed; top: 0; left: 0; right: 0; z-index: 1000; background: rgba(5, 5, 8, 0.85); backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px); border-bottom: 1px solid var(--border-accent); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        header.scrolled { background: rgba(5, 5, 8, 0.98); box-shadow: var(--shadow-accent); }
        
        .header-content { max-width: 1920px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; height: 72px; padding: 0 32px; }
        
        .logo { display: flex; align-items: center; gap: 14px; text-decoration: none; }
        .logo-icon { width: 50px; height: 50px; background: var(--gradient-accent); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; position: relative; overflow: hidden; box-shadow: 0 4px 20px rgba(99, 102, 241, 0.4); }
        .logo-icon::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.3), transparent); transform: rotate(45deg); animation: logoShine 4s infinite; }
        @keyframes logoShine { 0% { transform: translateX(-100%) rotate(45deg); } 100% { transform: translateX(100%) rotate(45deg); } }
        .logo-text { font-size: 28px; font-weight: 900; background: var(--gradient-shine); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; letter-spacing: -0.5px; }

        nav { display: flex; align-items: center; gap: 4px; }
        nav a { color: var(--text-secondary); text-decoration: none; padding: 10px 18px; border-radius: var(--radius-md); font-weight: 500; font-size: 14px; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px; position: relative; }
        nav a::after { content: ''; position: absolute; bottom: 4px; left: 50%; transform: translateX(-50%) scaleX(0); width: 24px; height: 2px; background: var(--accent-primary); border-radius: 1px; transition: transform 0.3s ease; }
        nav a:hover { color: var(--accent-light); }
        nav a:hover::after { transform: translateX(-50%) scaleX(1); }
        nav a.active { color: white; background: var(--gradient-accent); font-weight: 600; }
        nav a.active::after { display: none; }

        .header-actions { display: flex; align-items: center; gap: 14px; }
        .search-box { position: relative; display: flex; align-items: center; }
        .search-input { width: 260px; height: 46px; padding: 0 50px 0 18px; border-radius: var(--radius-lg); border: 1px solid var(--border-accent); background: rgba(99, 102, 241, 0.03); color: var(--text-primary); font-family: inherit; font-size: 14px; transition: all 0.3s ease; }
        .search-input:focus { outline: none; border-color: var(--accent-primary); background: rgba(99, 102, 241, 0.08); box-shadow: 0 0 25px rgba(99, 102, 241, 0.15); width: 300px; }
        .search-btn { position: absolute; left: 5px; width: 38px; height: 38px; border-radius: var(--radius-md); border: none; background: var(--gradient-accent); color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; }
        .icon-btn { width: 46px; height: 46px; border-radius: var(--radius-md); border: 1px solid var(--border-accent); background: rgba(99, 102, 241, 0.03); color: var(--text-secondary); cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; font-size: 17px; position: relative; }
        .icon-btn:hover { border-color: var(--accent-primary); color: var(--accent-light); background: rgba(99, 102, 241, 0.1); transform: translateY(-2px); }
        .login-btn { padding: 12px 28px; border-radius: var(--radius-md); border: none; background: var(--gradient-accent); color: white; font-weight: 700; font-size: 14px; cursor: pointer; transition: all 0.3s ease; font-family: inherit; display: flex; align-items: center; gap: 8px; text-decoration: none; }
        .login-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4); }
        .mobile-menu-btn { display: none; width: 46px; height: 46px; border-radius: var(--radius-md); border: 1px solid var(--border-accent); background: transparent; color: var(--text-primary); cursor: pointer; align-items: center; justify-content: center; font-size: 22px; transition: all 0.3s ease; }

        /* HERO SECTION */
        .hero { padding: 110px 32px 70px; background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-primary) 100%); position: relative; overflow: hidden; min-height: 100vh; display: flex; align-items: center; }
        .hero-bg { position: absolute; top: 0; left: 0; right: 0; bottom: 0; pointer-events: none; }
        .hero-bg::before { content: ''; position: absolute; top: -40%; right: -20%; width: 70%; height: 140%; background: radial-gradient(ellipse, rgba(99, 102, 241, 0.08) 0%, transparent 60%); }
        .hero-bg::after { content: ''; position: absolute; bottom: -20%; left: -10%; width: 50%; height: 80%; background: radial-gradient(ellipse, rgba(139, 92, 246, 0.06) 0%, transparent 50%); }
        .hero-pattern { position: absolute; inset: 0; opacity: 0.4; background-image: radial-gradient(rgba(99, 102, 241, 0.03) 1px, transparent 1px); background-size: 40px 40px; }
        .hero-content { max-width: 1920px; margin: 0 auto; position: relative; z-index: 1; width: 100%; }
        .hero-grid { display: grid; grid-template-columns: 1fr 1.2fr; gap: 80px; align-items: center; }
        
        .hero-text h1 { font-size: 58px; font-weight: 900; line-height: 1.1; margin-bottom: 28px; letter-spacing: -1px; }
        .hero-text h1 span { background: var(--gradient-shine); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; position: relative; display: inline-block; }
        .hero-description { font-size: 18px; color: var(--text-secondary); line-height: 1.9; margin-bottom: 40px; max-width: 520px; }
        .hero-stats { display: flex; gap: 50px; margin-bottom: 44px; }
        .stat-item { position: relative; }
        .stat-item::before { content: ''; position: absolute; right: -25px; top: 50%; transform: translateY(-50%); width: 1px; height: 50px; background: linear-gradient(180deg, transparent, var(--accent-dark), transparent); }
        .stat-item:last-child::before { display: none; }
        .stat-value { font-size: 46px; font-weight: 900; background: var(--gradient-shine); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; line-height: 1; margin-bottom: 8px; }
        .stat-label { font-size: 14px; color: var(--text-muted); font-weight: 500; }
        .hero-actions { display: flex; gap: 18px; flex-wrap: wrap; }
        
        .btn { padding: 18px 38px; border-radius: var(--radius-lg); font-weight: 700; font-size: 16px; cursor: pointer; transition: all 0.3s ease; font-family: inherit; display: inline-flex; align-items: center; gap: 10px; text-decoration: none; border: none; }
        .btn-primary { background: var(--gradient-accent); color: white; position: relative; overflow: hidden; }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 12px 35px rgba(99, 102, 241, 0.4); }
        .btn-outline { background: rgba(99, 102, 241, 0.05); color: var(--accent-light); border: 2px solid var(--accent-dark); }
        .btn-outline:hover { background: rgba(99, 102, 241, 0.15); border-color: var(--accent-primary); transform: translateY(-3px); }

        /* FEATURED CARD */
        .featured-card { background: var(--gradient-card); border-radius: var(--radius-xl); padding: 28px; border: 1px solid var(--border-accent); box-shadow: var(--shadow-card); position: relative; overflow: hidden; }
        .featured-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: var(--gradient-shine); }
        .featured-ribbon { position: absolute; top: 45px; right: -35px; background: var(--gradient-accent); color: white; padding: 8px 50px; font-size: 12px; font-weight: 700; transform: rotate(45deg); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3); z-index: 10; }
        .featured-cover { position: relative; border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 24px; aspect-ratio: 16/10; }
        .featured-cover img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease; }
        .featured-card:hover .featured-cover img { transform: scale(1.05); }
        .featured-cover-overlay { position: absolute; inset: 0; background: linear-gradient(180deg, transparent 40%, rgba(0, 0, 0, 0.9) 100%); }
        .featured-play { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0.8); width: 80px; height: 80px; background: var(--gradient-accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; color: white; opacity: 0; transition: all 0.4s ease; cursor: pointer; box-shadow: 0 8px 30px rgba(99, 102, 241, 0.5); }
        .featured-card:hover .featured-play { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        .featured-info h3 { font-size: 26px; font-weight: 800; margin-bottom: 12px; line-height: 1.3; }
        .featured-stats { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 18px; }
        .featured-stat { display: flex; align-items: center; gap: 7px; padding: 8px 14px; background: rgba(255, 255, 255, 0.03); border-radius: var(--radius-sm); font-size: 13px; color: var(--text-secondary); }
        .featured-stat i { color: var(--accent-primary); font-size: 12px; }
        .featured-genres { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
        .genre-badge { padding: 8px 16px; background: rgba(99, 102, 241, 0.1); border: 1px solid var(--border-accent); border-radius: 50px; font-size: 12px; font-weight: 600; color: var(--accent-light); transition: all 0.3s ease; }
        .featured-synopsis { font-size: 14px; color: var(--text-secondary); line-height: 1.8; margin-bottom: 22px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .featured-actions { display: flex; gap: 12px; }
        .featured-btn { flex: 1; padding: 16px 20px; border-radius: var(--radius-md); border: none; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s ease; font-family: inherit; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .featured-btn-primary { background: var(--gradient-accent); color: white; }
        .featured-btn-secondary { background: rgba(99, 102, 241, 0.1); color: var(--accent-light); border: 1px solid var(--border-accent); }

        /* SECTIONS & SLIDERS */
        .section { padding: 80px 32px; max-width: 1920px; margin: 0 auto; }
        .section-dark { background: var(--bg-secondary); max-width: none; padding: 80px 0; }
        .section-dark .section-inner { max-width: 1920px; margin: 0 auto; padding: 0 32px; }
        .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 40px; flex-wrap: wrap; gap: 20px; }
        .section-title { display: flex; align-items: center; gap: 18px; }
        .section-icon { width: 56px; height: 56px; background: rgba(99, 102, 241, 0.1); border: 1px solid var(--border-accent); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 24px; color: var(--accent-primary); }
        .section-title-text h2 { font-size: 28px; font-weight: 800; margin-bottom: 6px; }
        .section-title-text p { font-size: 14px; color: var(--text-muted); }
        .section-controls { display: flex; align-items: center; gap: 14px; }
        .nav-btn { width: 48px; height: 48px; border-radius: var(--radius-md); border: 1px solid var(--border-accent); background: rgba(99, 102, 241, 0.03); color: var(--text-secondary); cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; font-size: 16px; }
        .nav-btn:hover { border-color: var(--accent-primary); color: var(--accent-light); background: rgba(99, 102, 241, 0.1); }
        .view-all-btn { display: flex; align-items: center; gap: 10px; padding: 12px 24px; border-radius: var(--radius-md); background: rgba(99, 102, 241, 0.1); border: 1px solid var(--border-accent); color: var(--accent-light); text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.3s ease; }
        .view-all-btn:hover { background: rgba(99, 102, 241, 0.2); gap: 14px; }

        .slider-wrapper { position: relative; }
        .slider { display: flex; gap: 24px; overflow-x: auto; scroll-behavior: smooth; padding: 12px 4px 24px; scrollbar-width: none; scroll-snap-type: x mandatory; }
        .slider::-webkit-scrollbar { display: none; }

        /* NOVEL CARD (VERTICAL) */
        .novel-card { flex: 0 0 260px; background: var(--gradient-card); border-radius: var(--radius-lg); overflow: hidden; border: 1px solid var(--border-subtle); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer; position: relative; scroll-snap-align: start; }
        .novel-card:hover { transform: translateY(-12px); border-color: var(--border-accent); box-shadow: 0 30px 70px rgba(0, 0, 0, 0.5), 0 0 50px rgba(99, 102, 241, 0.1); }
        .card-cover { position: relative; aspect-ratio: 3/4; overflow: hidden; }
        .card-cover img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
        .novel-card:hover .card-cover img { transform: scale(1.08); }
        .card-badges { position: absolute; top: 12px; right: 12px; display: flex; flex-direction: column; gap: 8px; z-index: 5; }
        .card-badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: flex; align-items: center; gap: 5px; backdrop-filter: blur(10px); color:white; }
        .badge-hot { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        .badge-new { background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); }
        .badge-complete { background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); }
        .card-rating { position: absolute; top: 12px; left: 12px; background: rgba(0, 0, 0, 0.85); backdrop-filter: blur(10px); padding: 8px 12px; border-radius: 20px; display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; z-index: 5; }
        .card-rating i { color: #fbbf24; font-size: 11px; }
        .card-type-badge { position: absolute; bottom: 12px; left: 12px; background: rgba(0, 0, 0, 0.85); backdrop-filter: blur(10px); padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 600; color: var(--text-secondary); display: flex; align-items: center; gap: 6px; z-index: 5; }
        .card-body { padding: 18px; }
        .card-title { font-size: 15px; font-weight: 700; margin-bottom: 8px; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 45px; }
        .card-meta { display: flex; align-items: center; gap: 14px; margin-bottom: 14px; font-size: 12px; color: var(--text-muted); }
        .card-genres { display: flex; flex-wrap: wrap; gap: 6px; }
        .card-genre { padding: 5px 10px; background: rgba(99, 102, 241, 0.08); border-radius: 6px; font-size: 11px; color: var(--accent-light); font-weight: 500; }

        /* EDITOR'S PICK (HORIZONTAL CARD - YOUR DESIGN) */
        .ep-slide { flex: 0 0 350px; scroll-snap-align: start; }
        .ep-card { background: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border-subtle); overflow: hidden; height: 160px; display: flex; transition: 0.3s; position: relative; text-decoration: none; color: inherit; }
        .ep-card:hover { border-color: var(--accent-primary); transform: translateY(-5px); box-shadow: 0 10px 30px rgba(99,102,241,0.15); }
        .ep-img { width: 110px; height: 100%; object-fit: cover; flex-shrink: 0; }
        .ep-content { padding: 15px; display: flex; flex-direction: column; justify-content: center; flex: 1; min-width: 0; }
        .ep-tag { font-size: 10px; color: var(--accent-primary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; font-weight: 700; }
        .ep-title { font-size: 16px; font-weight: 800; margin-bottom: 8px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: white; }
        .ep-desc { font-size: 12px; color: var(--text-muted); line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

        /* HIGHEST RATED (HORIZONTAL + RANK) */
        .hr-slide { flex: 0 0 300px; scroll-snap-align: start; }
        .hr-card { display: flex; align-items: center; background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border-subtle); padding: 15px; gap: 15px; position: relative; overflow: hidden; transition: 0.3s; height: 130px; text-decoration: none; color: inherit; }
        .hr-card:hover { border-color: var(--accent-primary); transform: translateY(-3px); }
        .hr-rank { position: absolute; top: -10px; right: -10px; width: 50px; height: 50px; background: var(--accent-primary); color: white; border-radius: 50%; display: flex; align-items: flex-end; justify-content: flex-start; padding: 0 0 12px 14px; font-weight: 900; font-size: 18px; box-shadow: -2px 2px 10px rgba(0,0,0,0.3); z-index: 2; }
        .hr-img { width: 80px; height: 100px; border-radius: 8px; object-fit: cover; flex-shrink: 0; }
        .hr-info { flex: 1; display: flex; flex-direction: column; gap: 5px; }
        .hr-title { font-size: 15px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .hr-rate { color: #fbbf24; font-size: 13px; font-weight: 700; }

        /* UPDATES GRID */
        .updates-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 20px; }
        .update-card { background: var(--bg-card); border-radius: var(--radius-lg); padding: 20px; display: flex; gap: 18px; border: 1px solid var(--border-subtle); transition: all 0.3s ease; cursor: pointer; }
        .update-card:hover { border-color: var(--border-accent); background: var(--bg-card-hover); transform: translateX(-6px); }
        .update-cover { width: 90px; height: 125px; border-radius: var(--radius-md); overflow: hidden; flex-shrink: 0; }
        .update-cover img { width: 100%; height: 100%; object-fit: cover; }
        .update-content { flex: 1; display: flex; flex-direction: column; justify-content: center; }
        .update-title { font-size: 16px; font-weight: 700; margin-bottom: 8px; }
        .update-ch { color: var(--accent-light); font-size: 14px; font-weight: 600; margin-bottom: 8px; }
        .update-time { font-size: 12px; color: var(--text-muted); }

        /* FOOTER */
        footer { background: var(--bg-tertiary); padding: 80px 32px 32px; border-top: 1px solid var(--border-accent); }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1.5fr; gap: 60px; }
        .footer-brand p { color: var(--text-secondary); font-size: 14px; line-height: 1.9; margin: 24px 0 28px; }
        .footer-section h4 { font-size: 16px; font-weight: 700; margin-bottom: 24px; color: var(--accent-light); }
        .footer-links li { margin-bottom: 14px; }
        .footer-links a { color: var(--text-secondary); font-size: 14px; transition: 0.3s; }
        .footer-links a:hover { color: var(--accent-light); }
        
        /* MOBILE MENU */
        .mobile-menu-btn { display: none; background: none; border: none; font-size: 24px; color: white; cursor: pointer; }
        .mobile-menu { position: fixed; top: 0; right: 0; bottom: 0; width: 85%; max-width: 360px; background: var(--bg-secondary); z-index: 1001; padding: 24px; transform: translateX(100%); transition: 0.4s; border-left: 1px solid var(--border-accent); }
        .mobile-menu.active { transform: translateX(0); }
        .mobile-menu-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 1000; }
        .mobile-menu-overlay.active { display: block; }

        @media (max-width: 1024px) {
            nav, .search-box, .icon-btn, .login-btn { display: none; }
            .mobile-menu-btn { display: block; }
            .hero-grid { grid-template-columns: 1fr; gap: 40px; }
            .featured-card { padding: 20px; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 768px) {
            .hero-text h1 { font-size: 36px; }
            .updates-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header id="header">
        <div class="header-content">
            <a href="index.php" class="logo">
                <div class="logo-icon"><i class="fas fa-book-open"></i></div>
                <span class="logo-text">ناول‌خونه</span>
            </a>
            <nav>
                <a href="index.php" class="active"><i class="fas fa-home"></i> خانه</a>
                <a href="search.php"><i class="fas fa-compass"></i> کشف</a>
                <a href="all_genres.php"><i class="fas fa-layer-group"></i> دسته‌بندی</a>
                <?php if($is_logged_in): ?><a href="library.php"><i class="fas fa-book-reader"></i> کتابخانه</a><?php endif; ?>
            </nav>
            <div class="header-actions">
                <a href="search.php" class="icon-btn" style="text-decoration:none;"><i class="fas fa-search"></i></a>
                <?php if($is_logged_in): ?>
                    <a href="profile.php" class="login-btn" style="text-decoration:none;">
                        <img src="<?php echo $user_avatar; ?>" style="width:20px;height:20px;border-radius:50%;margin-left:5px;"> <?php echo $username; ?>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="login-btn" style="text-decoration:none;"><i class="fas fa-user"></i> ورود</a>
                <?php endif; ?>
                <button class="mobile-menu-btn" onclick="toggleMenu()"><i class="fas fa-bars"></i></button>
            </div>
        </div>
    </header>

    <div class="mobile-menu-overlay" id="mobileOverlay" onclick="toggleMenu()"></div>
    <div class="mobile-menu" id="mobileMenu">
        <div style="display:flex;justify-content:space-between;margin-bottom:30px;font-size:20px;font-weight:700">
            <span>منو</span><i class="fas fa-times" onclick="toggleMenu()"></i>
        </div>
        <div style="display:flex;flex-direction:column;gap:15px;">
            <a href="index.php" style="font-size:16px;">خانه</a>
            <a href="search.php" style="font-size:16px;">جستجو</a>
            <a href="all_genres.php" style="font-size:16px;">ژانرها</a>
            <?php if($is_logged_in): ?>
                <a href="profile.php" style="font-size:16px;">پروفایل</a>
                <a href="logout.php" style="color:#ff4d4d;font-size:16px;">خروج</a>
            <?php else: ?>
                <a href="login.php" style="font-size:16px;">ورود</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Hero -->
    <section class="hero">
        <div class="hero-bg"></div>
        <div class="hero-content">
            <div class="hero-grid">
                <div class="hero-text">
                    <h1>دنیای <span>ناول</span> را<br>با ما تجربه کنید</h1>
                    <p class="hero-description">بزرگترین کتابخانه آنلاین ترجمه ناول‌های آسیایی به زبان فارسی.</p>
                    <div class="hero-stats">
                        <div class="stat-item"><div class="stat-value"><?php echo number_format($stats['novels']); ?>+</div><div class="stat-label">ناول</div></div>
                        <div class="stat-item"><div class="stat-value"><?php echo number_format($stats['chapters']); ?>+</div><div class="stat-label">فصل</div></div>
                    </div>
                    <div class="hero-actions">
                        <a href="search.php" class="btn btn-primary" style="text-decoration:none;"><i class="fas fa-rocket"></i> شروع مطالعه</a>
                    </div>
                </div>
                <?php if($featured): ?>
                <div class="hero-featured">
                    <div class="featured-glow"></div>
                    <div class="featured-card">
                        <div class="featured-ribbon"><i class="fas fa-fire"></i> ویژه</div>
                        <div class="featured-cover"><img src="<?php echo htmlspecialchars($featured['cover_url']); ?>" alt="Featured"></div>
                        <div class="featured-info">
                            <h3><?php echo htmlspecialchars($featured['title']); ?></h3>
                            <div class="featured-stats">
                                <span><i class="fas fa-pen"></i> <?php echo htmlspecialchars($featured['author']); ?></span>
                                <span><i class="fas fa-star"></i> <?php echo htmlspecialchars($featured['rating']); ?></span>
                            </div>
                            <p class="featured-synopsis"><?php echo htmlspecialchars($featured['summary']); ?></p>
                            <a href="novel_detail.php?id=<?php echo $featured['id']; ?>" class="featured-btn featured-btn-primary" style="text-decoration:none;text-align:center">خواندن</a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Sliders Logic -->
    <?php function render_slider($id, $title, $icon, $data) { if(empty($data)) return; ?>
    <section class="section">
        <div class="section-header">
            <div class="section-title"><div class="section-icon"><i class="fas <?php echo $icon; ?>"></i></div><div class="section-title-text"><h2><?php echo $title; ?></h2></div></div>
            <div class="section-controls">
                <button class="nav-btn" onclick="document.getElementById('<?php echo $id; ?>').scrollBy({left:300,behavior:'smooth'})"><i class="fas fa-chevron-right"></i></button>
                <button class="nav-btn" onclick="document.getElementById('<?php echo $id; ?>').scrollBy({left:-300,behavior:'smooth'})"><i class="fas fa-chevron-left"></i></button>
            </div>
        </div>
        <div class="slider-wrapper"><div class="slider" id="<?php echo $id; ?>">
            <?php foreach($data as $n): ?>
            <div class="novel-card" onclick="window.location.href='novel_detail.php?id=<?php echo $n['id']; ?>'">
                <div class="card-cover">
                    <img src="<?php echo htmlspecialchars($n['image']); ?>">
                    <div class="card-cover-overlay"></div>
                    <div class="card-rating"><i class="fas fa-star"></i> <?php echo $n['rating']; ?></div>
                    <?php if($n['badge']): ?><div class="card-badges"><span class="card-badge badge-<?php echo $n['badge']; ?>"><?php echo strtoupper($n['badge']); ?></span></div><?php endif; ?>
                </div>
                <div class="card-body">
                    <h3 class="card-title"><?php echo htmlspecialchars($n['title']); ?></h3>
                    <div class="card-meta"><span><?php echo $n['type']; ?></span><span><?php echo $n['chapters']; ?> فصل</span></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div></div>
    </section>
    <?php } ?>

    <?php render_slider('hot-slider', 'داغ‌ترین‌ها', 'fa-fire-alt', $hot_novels); ?>

    <!-- Editor's Pick (Horizontal) -->
    <?php if(!empty($editors_picks)): ?>
    <section class="section">
        <div class="section-header">
            <div class="section-title"><div class="section-icon"><i class="fas fa-pen-nib"></i></div><h2>پیشنهاد سردبیر</h2></div>
            <div class="section-controls">
                <button class="nav-btn" onclick="document.getElementById('ep-slider').scrollBy({left:300,behavior:'smooth'})"><i class="fas fa-chevron-right"></i></button>
                <button class="nav-btn" onclick="document.getElementById('ep-slider').scrollBy({left:-300,behavior:'smooth'})"><i class="fas fa-chevron-left"></i></button>
            </div>
        </div>
        <div class="slider-wrapper"><div class="slider" id="ep-slider">
            <?php foreach($editors_picks as $ep): ?>
            <div class="ep-slide">
                <a href="novel_detail.php?id=<?php echo $ep['id']; ?>" class="ep-card">
                    <div class="ep-content">
                        <span class="ep-tag">پیشنهاد ویژه</span>
                        <h4 class="ep-title"><?php echo htmlspecialchars($ep['title']); ?></h4>
                        <p class="ep-desc"><?php echo htmlspecialchars($ep['summary']); ?></p>
                    </div>
                    <img src="<?php echo htmlspecialchars($ep['image']); ?>" class="ep-img">
                </a>
            </div>
            <?php endforeach; ?>
        </div></div>
    </section>
    <?php endif; ?>

    <!-- Updates -->
    <section class="section section-dark">
        <div class="section-inner">
            <div class="section-header"><div class="section-title"><div class="section-icon"><i class="fas fa-bolt"></i></div><h2>آخرین بروزرسانی‌ها</h2></div></div>
            <div class="updates-grid">
                <?php foreach($updates_data as $up): ?>
                <div class="update-card" onclick="window.location.href='novel_detail.php?id=<?php echo $up['id']; ?>'">
                    <div class="update-cover"><img src="<?php echo htmlspecialchars($up['image']); ?>"></div>
                    <div class="update-content">
                        <span class="update-type"><?php echo $up['type']; ?></span>
                        <h4 class="update-title"><?php echo htmlspecialchars($up['title']); ?></h4>
                        <div class="update-chapter"><?php echo $up['chapter']; ?></div>
                        <div class="update-footer"><span class="update-time"><i class="fas fa-clock"></i> <?php echo $up['time']; ?></span></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php render_slider('new-slider', 'تازه‌ها', 'fa-sparkles', $new_novels); ?>

    <!-- Highest Rated (Horizontal + Rank) -->
    <?php if(!empty($highest_rated)): ?>
    <section class="section section-dark">
        <div class="section-inner">
            <div class="section-header">
                <div class="section-title"><div class="section-icon"><i class="fas fa-trophy"></i></div><h2>محبوب‌ترین‌ها</h2></div>
                <div class="section-controls">
                    <button class="nav-btn" onclick="document.getElementById('hr-slider').scrollBy({left:300,behavior:'smooth'})"><i class="fas fa-chevron-right"></i></button>
                    <button class="nav-btn" onclick="document.getElementById('hr-slider').scrollBy({left:-300,behavior:'smooth'})"><i class="fas fa-chevron-left"></i></button>
                </div>
            </div>
            <div class="slider-wrapper"><div class="slider" id="hr-slider">
                <?php foreach($highest_rated as $i => $hr): ?>
                <div class="hr-slide">
                    <a href="novel_detail.php?id=<?php echo $hr['id']; ?>" class="hr-card">
                        <div class="hr-rank">#<?php echo $i+1; ?></div>
                        <img src="<?php echo htmlspecialchars($hr['image']); ?>" class="hr-img">
                        <div class="hr-info">
                            <h4 class="hr-title"><?php echo htmlspecialchars($hr['title']); ?></h4>
                            <div class="hr-rating"><i class="fas fa-star"></i> <?php echo $hr['rating']; ?></div>
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
        <div class="footer-content">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a href="#" class="logo"><div class="logo-icon"><i class="fas fa-book-open"></i></div><span class="logo-text">ناول‌خونه</span></a>
                    <p>بزرگترین کتابخانه آنلاین ترجمه ناول‌های آسیایی.</p>
                </div>
                <div class="footer-section"><h4>دسترسی سریع</h4><ul class="footer-links"><li><a href="index.php">خانه</a></li><li><a href="search.php">جستجو</a></li></ul></div>
            </div>
            <div class="footer-bottom"><p>© ۱۴۰۳ ناول‌خونه</p></div>
        </div>
    </footer>

    <script>
        function toggleMenu() {
            document.getElementById('mobileMenu').classList.toggle('active');
            document.getElementById('mobileOverlay').classList.toggle('active');
        }
        window.addEventListener('scroll', () => {
            const h = document.getElementById('header');
            if(h) h.classList.toggle('scrolled', window.scrollY > 50);
        });
        document.querySelectorAll('.slider').forEach(slider => {
            slider.addEventListener('wheel', (evt) => {
                evt.preventDefault();
                slider.scrollLeft += evt.deltaY;
            });
        });
    </script>
</body>
</html>
