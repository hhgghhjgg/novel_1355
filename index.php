<?php
/*
=====================================================
    NovelWorld - Main Index Page (Theme: NovelKhone)
    Version: 6.1 (Fixed PHP 8.2 Deprecation Error)
=====================================================
*/

require_once 'header.php'; // اتصال به دیتابیس

// --- توابع کمکی (اصلاح شده برای رفع ارور) ---
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    // محاسبه دستی هفته‌ها برای جلوگیری از ارور PHP 8.2
    $weeks = floor($diff->d / 7);
    $days = $diff->d - ($weeks * 7);

    $string = array(
        'y' => $diff->y ? $diff->y . ' سال' : null,
        'm' => $diff->m ? $diff->m . ' ماه' : null,
        'w' => $weeks > 0 ? $weeks . ' هفته' : null,
        'd' => $days > 0 ? $days . ' روز' : null,
        'h' => $diff->h ? $diff->h . ' ساعت' : null,
        'i' => $diff->i ? $diff->i . ' دقیقه' : null,
        's' => $diff->s ? $diff->s . ' ثانیه' : null,
    );

    // حذف مقادیر خالی
    $string = array_filter($string);

    if (!$string) return 'لحظاتی پیش';
    
    // دریافت اولین آیتم (بزرگترین بازه زمانی)
    $string = array_slice($string, 0, 1);
    return implode(', ', $string) . ' پیش';
}

function map_type_to_farsi($type) {
    $map = ['novel' => 'ناول', 'manhwa' => 'مانهوا', 'manga' => 'مانگا', 'korean' => 'کره‌ای', 'chinese' => 'چینی'];
    return $map[strtolower($type)] ?? 'آسیایی';
}

// --- آماده‌سازی داده‌ها برای جاوااسکریپت (Backend Data Fetching) ---

// 1. Hot Novels (بر اساس امتیاز)
$hot_stmt = $conn->query("SELECT id, title, author, rating, cover_url, type, genres, status FROM novels ORDER BY rating DESC LIMIT 8");
$hot_data = [];
while($row = $hot_stmt->fetch(PDO::FETCH_ASSOC)) {
    $ch_count = $conn->query("SELECT COUNT(*) FROM chapters WHERE novel_id = {$row['id']}")->fetchColumn();
    // تعیین بج (Badge)
    $badge = null;
    if ($row['status'] == 'completed') $badge = 'complete';
    elseif ($row['rating'] >= 4.8) $badge = 'hot';
    elseif ($row['rating'] >= 4.5) $badge = 'vip';

    $hot_data[] = [
        'title' => $row['title'],
        'author' => $row['author'],
        'rating' => $row['rating'],
        'views' => 'Top', // اگر ستون بازدید ندارید
        'chapters' => $ch_count,
        'totalChapters' => $ch_count, 
        'genres' => explode(',', $row['genres']),
        'badge' => $badge,
        'type' => map_type_to_farsi($row['type']),
        'image' => $row['cover_url']
    ];
}

// 2. New Novels (بر اساس تاریخ)
$new_stmt = $conn->query("SELECT id, title, author, rating, cover_url, type, genres FROM novels ORDER BY created_at DESC LIMIT 8");
$new_data = [];
while($row = $new_stmt->fetch(PDO::FETCH_ASSOC)) {
    $ch_count = $conn->query("SELECT COUNT(*) FROM chapters WHERE novel_id = {$row['id']}")->fetchColumn();
    $new_data[] = [
        'title' => $row['title'],
        'author' => $row['author'],
        'rating' => $row['rating'],
        'views' => 'New',
        'chapters' => $ch_count,
        'totalChapters' => $ch_count,
        'genres' => explode(',', $row['genres']),
        'badge' => 'new',
        'type' => map_type_to_farsi($row['type']),
        'image' => $row['cover_url']
    ];
}

// 3. Completed Novels
$comp_stmt = $conn->query("SELECT id, title, author, rating, cover_url, type, genres FROM novels WHERE status = 'completed' LIMIT 8");
$comp_data = [];
while($row = $comp_stmt->fetch(PDO::FETCH_ASSOC)) {
    $ch_count = $conn->query("SELECT COUNT(*) FROM chapters WHERE novel_id = {$row['id']}")->fetchColumn();
    $comp_data[] = [
        'title' => $row['title'],
        'author' => $row['author'],
        'rating' => $row['rating'],
        'views' => 'Full',
        'chapters' => $ch_count,
        'totalChapters' => $ch_count,
        'genres' => explode(',', $row['genres']),
        'badge' => 'complete',
        'type' => map_type_to_farsi($row['type']),
        'image' => $row['cover_url']
    ];
}

// 4. Latest Updates (چپترها)
$update_stmt = $conn->query("
    SELECT c.chapter_number, c.title as ch_title, c.published_at, n.title, n.cover_url, n.type 
    FROM chapters c 
    JOIN novels n ON c.novel_id = n.id 
    WHERE c.status = 'approved' 
    ORDER BY c.published_at DESC LIMIT 6
");
$updates_data = [];
while($row = $update_stmt->fetch(PDO::FETCH_ASSOC)) {
    $updates_data[] = [
        'title' => $row['title'],
        'chapter' => "فصل " . $row['chapter_number'],
        'time' => time_elapsed_string($row['published_at']),
        'views' => 'UP',
        'type' => map_type_to_farsi($row['type']),
        'isNew' => (strtotime($row['published_at']) > strtotime('-2 days')),
        'image' => $row['cover_url']
    ];
}

// 5. Rankings (رتبه‌بندی)
$rank_stmt = $conn->query("SELECT title, cover_url, rating FROM novels ORDER BY rating DESC LIMIT 5");
$rank_data_weekly = [];
while($row = $rank_stmt->fetch(PDO::FETCH_ASSOC)) {
    $rank_data_weekly[] = [
        'title' => $row['title'],
        'views' => 'Top',
        'votes' => $row['rating'],
        'trend' => 'up',
        'image' => $row['cover_url']
    ];
}
// برای پر کردن بقیه تب‌های رتبه‌بندی فعلاً از همین داده استفاده می‌کنیم
$rank_data_trending = $rank_data_weekly;
$rank_data_alltime = $rank_data_weekly;

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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
            font-size: 16px;
        }

        body {
            font-family: 'Vazirmatn', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
            line-height: 1.6;
        }

        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-secondary);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--accent-dark);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--accent-primary);
        }

        /* ==================== HEADER ==================== */
        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(5, 5, 8, 0.85);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-bottom: 1px solid var(--border-accent);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        header.scrolled {
            background: rgba(5, 5, 8, 0.98);
            box-shadow: var(--shadow-accent);
        }

        .header-content {
            max-width: 1920px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 72px;
            padding: 0 32px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
        }

        .logo-icon {
            width: 50px;
            height: 50px;
            background: var(--gradient-accent);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.4);
        }

        .logo-icon::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transform: rotate(45deg);
            animation: logoShine 4s infinite;
        }

        @keyframes logoShine {
            0% { transform: translateX(-100%) rotate(45deg); }
            100% { transform: translateX(100%) rotate(45deg); }
        }

        .logo-text {
            font-size: 28px;
            font-weight: 900;
            background: var(--gradient-shine);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
        }

        nav {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        nav a {
            color: var(--text-secondary);
            text-decoration: none;
            padding: 10px 18px;
            border-radius: var(--radius-md);
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
        }

        nav a::after {
            content: '';
            position: absolute;
            bottom: 4px;
            left: 50%;
            transform: translateX(-50%) scaleX(0);
            width: 24px;
            height: 2px;
            background: var(--accent-primary);
            border-radius: 1px;
            transition: transform 0.3s ease;
        }

        nav a:hover {
            color: var(--accent-light);
        }

        nav a:hover::after {
            transform: translateX(-50%) scaleX(1);
        }

        nav a.active {
            color: white;
            background: var(--gradient-accent);
            font-weight: 600;
        }

        nav a.active::after {
            display: none;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .search-box {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-input {
            width: 260px;
            height: 46px;
            padding: 0 50px 0 18px;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-accent);
            background: rgba(99, 102, 241, 0.03);
            color: var(--text-primary);
            font-family: inherit;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent-primary);
            background: rgba(99, 102, 241, 0.08);
            box-shadow: 0 0 25px rgba(99, 102, 241, 0.15);
            width: 300px;
        }

        .search-input::placeholder {
            color: var(--text-muted);
        }

        .search-btn {
            position: absolute;
            left: 5px;
            width: 38px;
            height: 38px;
            border-radius: var(--radius-md);
            border: none;
            background: var(--gradient-accent);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
        }

        .icon-btn {
            width: 46px;
            height: 46px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-accent);
            background: rgba(99, 102, 241, 0.03);
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            position: relative;
        }

        .icon-btn:hover {
            border-color: var(--accent-primary);
            color: var(--accent-light);
            background: rgba(99, 102, 241, 0.1);
            transform: translateY(-2px);
        }

        .icon-btn .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            background: #ef4444;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            border: 2px solid var(--bg-primary);
        }

        .login-btn {
            padding: 12px 28px;
            border-radius: var(--radius-md);
            border: none;
            background: var(--gradient-accent);
            color: white;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
        }

        .mobile-menu-btn {
            display: none;
            width: 46px;
            height: 46px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-accent);
            background: transparent;
            color: var(--text-primary);
            cursor: pointer;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            transition: all 0.3s ease;
        }

        .mobile-menu-btn:hover {
            border-color: var(--accent-primary);
            color: var(--accent-light);
        }

        /* Mobile Menu */
        .mobile-menu {
            display: none;
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            width: 85%;
            max-width: 360px;
            background: var(--bg-secondary);
            z-index: 1001;
            padding: 24px;
            transform: translateX(100%);
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            border-left: 1px solid var(--border-accent);
        }

        .mobile-menu.active {
            transform: translateX(0);
        }

        .mobile-menu-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .mobile-menu-overlay.active {
            opacity: 1;
        }

        .mobile-menu-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 32px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-subtle);
        }

        .mobile-menu-close {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-accent);
            background: transparent;
            color: var(--text-secondary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .mobile-search {
            margin-bottom: 24px;
        }

        .mobile-search input {
            width: 100%;
            height: 50px;
            padding: 0 16px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-accent);
            background: rgba(99, 102, 241, 0.03);
            color: var(--text-primary);
            font-family: inherit;
            font-size: 15px;
        }

        .mobile-search input:focus {
            outline: none;
            border-color: var(--accent-primary);
        }

        .mobile-nav {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 32px;
        }

        .mobile-nav a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 18px;
            border-radius: var(--radius-md);
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
        }

        .mobile-nav a:hover,
        .mobile-nav a.active {
            color: white;
            background: var(--gradient-accent);
            border-color: transparent;
        }

        .mobile-nav a i {
            width: 24px;
            text-align: center;
            font-size: 18px;
        }

        .mobile-user-actions {
            display: flex;
            gap: 12px;
        }

        .mobile-user-actions button {
            flex: 1;
            padding: 14px;
            border-radius: var(--radius-md);
            border: none;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .mobile-login-btn {
            background: var(--gradient-accent);
            color: white;
        }

        .mobile-register-btn {
            background: rgba(99, 102, 241, 0.1);
            color: var(--accent-light);
            border: 1px solid var(--border-accent) !important;
        }

        /* ==================== HERO ==================== */
        .hero {
            padding: 110px 32px 70px;
            background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
            position: relative;
            overflow: hidden;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .hero-bg {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
        }

        .hero-bg::before {
            content: '';
            position: absolute;
            top: -40%;
            right: -20%;
            width: 70%;
            height: 140%;
            background: radial-gradient(ellipse, rgba(99, 102, 241, 0.08) 0%, transparent 60%);
        }

        .hero-bg::after {
            content: '';
            position: absolute;
            bottom: -20%;
            left: -10%;
            width: 50%;
            height: 80%;
            background: radial-gradient(ellipse, rgba(139, 92, 246, 0.06) 0%, transparent 50%);
        }

        .hero-pattern {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            opacity: 0.4;
            background-image: radial-gradient(rgba(99, 102, 241, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        .hero-content {
            max-width: 1920px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
            width: 100%;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 80px;
            align-items: center;
        }

        .hero-text h1 {
            font-size: 58px;
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 28px;
            letter-spacing: -1px;
        }

        .hero-text h1 span {
            background: var(--gradient-shine);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
            display: inline-block;
        }

        .hero-text h1 span::after {
            content: '';
            position: absolute;
            bottom: 8px;
            left: 0;
            right: 0;
            height: 10px;
            background: rgba(99, 102, 241, 0.2);
            border-radius: 5px;
            z-index: -1;
        }

        .hero-description {
            font-size: 18px;
            color: var(--text-secondary);
            line-height: 1.9;
            margin-bottom: 40px;
            max-width: 520px;
        }

        .hero-stats {
            display: flex;
            gap: 50px;
            margin-bottom: 44px;
        }

        .stat-item {
            position: relative;
        }

        .stat-item::before {
            content: '';
            position: absolute;
            right: -25px;
            top: 50%;
            transform: translateY(-50%);
            width: 1px;
            height: 50px;
            background: linear-gradient(180deg, transparent, var(--accent-dark), transparent);
        }

        .stat-item:last-child::before {
            display: none;
        }

        .stat-value {
            font-size: 46px;
            font-weight: 900;
            background: var(--gradient-shine);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .hero-actions {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 18px 38px;
            border-radius: var(--radius-lg);
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            border: none;
        }

        .btn-primary {
            background: var(--gradient-accent);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(99, 102, 241, 0.4);
        }

        .btn-outline {
            background: rgba(99, 102, 241, 0.05);
            color: var(--accent-light);
            border: 2px solid var(--accent-dark);
        }

        .btn-outline:hover {
            background: rgba(99, 102, 241, 0.15);
            border-color: var(--accent-primary);
            transform: translateY(-3px);
        }

        /* Featured Card */
        .hero-featured {
            position: relative;
        }

        .featured-glow {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            height: 90%;
            background: radial-gradient(ellipse, rgba(99, 102, 241, 0.15) 0%, transparent 65%);
            filter: blur(50px);
            pointer-events: none;
        }

        .featured-card {
            background: var(--gradient-card);
            border-radius: var(--radius-xl);
            padding: 28px;
            border: 1px solid var(--border-accent);
            box-shadow: var(--shadow-card);
            position: relative;
            overflow: hidden;
        }

        .featured-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-shine);
        }

        .featured-ribbon {
            position: absolute;
            top: 45px;
            right: -35px;
            background: var(--gradient-accent);
            color: white;
            padding: 8px 50px;
            font-size: 12px;
            font-weight: 700;
            transform: rotate(45deg);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            z-index: 10;
        }

        .featured-cover {
            position: relative;
            border-radius: var(--radius-lg);
            overflow: hidden;
            margin-bottom: 24px;
            aspect-ratio: 16/10;
        }

        .featured-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .featured-card:hover .featured-cover img {
            transform: scale(1.05);
        }

        .featured-cover-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent 40%, rgba(0, 0, 0, 0.9) 100%);
        }

        .featured-play {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.8);
            width: 80px;
            height: 80px;
            background: var(--gradient-accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            opacity: 0;
            transition: all 0.4s ease;
            cursor: pointer;
            box-shadow: 0 8px 30px rgba(99, 102, 241, 0.5);
        }

        .featured-card:hover .featured-play {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }

        .featured-play:hover {
            transform: translate(-50%, -50%) scale(1.1);
        }

        .featured-play i {
            margin-right: -4px;
        }

        .featured-info h3 {
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 12px;
            line-height: 1.3;
        }

        .featured-author {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .featured-author-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 2px solid var(--accent-primary);
            overflow: hidden;
        }

        .featured-author-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .featured-author-name {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .featured-author-name strong {
            color: var(--text-primary);
        }

        .featured-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 18px;
        }

        .featured-stat {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 8px 14px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: var(--radius-sm);
            font-size: 13px;
            color: var(--text-secondary);
        }

        .featured-stat i {
            color: var(--accent-primary);
            font-size: 12px;
        }

        .featured-genres {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .genre-badge {
            padding: 8px 16px;
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid var(--border-accent);
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            color: var(--accent-light);
            transition: all 0.3s ease;
        }

        .genre-badge:hover {
            background: rgba(99, 102, 241, 0.2);
            transform: translateY(-2px);
        }

        .featured-synopsis {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.8;
            margin-bottom: 22px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .featured-progress-section {
            margin-bottom: 22px;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .progress-label {
            font-size: 13px;
            color: var(--text-muted);
        }

        .progress-value {
            font-size: 13px;
            color: var(--accent-light);
            font-weight: 600;
        }

        .progress-track {
            height: 8px;
            background: var(--bg-primary);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--gradient-shine);
            border-radius: 4px;
            position: relative;
        }

        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 30px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4));
            animation: progressShine 2s infinite;
        }

        @keyframes progressShine {
            0%, 100% { opacity: 0; }
            50% { opacity: 1; }
        }

        .featured-actions {
            display: flex;
            gap: 12px;
        }

        .featured-btn {
            flex: 1;
            padding: 16px 20px;
            border-radius: var(--radius-md);
            border: none;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .featured-btn-primary {
            background: var(--gradient-accent);
            color: white;
        }

        .featured-btn-primary:hover {
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
            transform: translateY(-2px);
        }

        .featured-btn-secondary {
            background: rgba(99, 102, 241, 0.1);
            color: var(--accent-light);
            border: 1px solid var(--border-accent);
        }

        .featured-btn-secondary:hover {
            background: rgba(99, 102, 241, 0.2);
        }

        .featured-btn-icon {
            flex: 0 0 52px;
            width: 52px;
        }

        /* ==================== SECTIONS ==================== */
        .section {
            padding: 80px 32px;
            max-width: 1920px;
            margin: 0 auto;
        }

        .section-dark {
            background: var(--bg-secondary);
            max-width: none;
            padding-left: 0;
            padding-right: 0;
        }

        .section-dark .section-inner {
            max-width: 1920px;
            margin: 0 auto;
            padding: 0 32px;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 40px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .section-icon {
            width: 56px;
            height: 56px;
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid var(--border-accent);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--accent-primary);
        }

        .section-title-text h2 {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .section-title-text p {
            font-size: 14px;
            color: var(--text-muted);
        }

        .section-controls {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .nav-btn {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-accent);
            background: rgba(99, 102, 241, 0.03);
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .nav-btn:hover:not(:disabled) {
            border-color: var(--accent-primary);
            color: var(--accent-light);
            background: rgba(99, 102, 241, 0.1);
        }

        .nav-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .view-all-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            border-radius: var(--radius-md);
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid var(--border-accent);
            color: var(--accent-light);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .view-all-btn:hover {
            background: rgba(99, 102, 241, 0.2);
            gap: 14px;
        }

        /* ==================== SLIDER ==================== */
        .slider-wrapper {
            position: relative;
        }

        .slider {
            display: flex;
            gap: 24px;
            overflow-x: auto;
            scroll-behavior: smooth;
            padding: 12px 4px 24px;
            scrollbar-width: none;
            scroll-snap-type: x mandatory;
        }

        .slider::-webkit-scrollbar {
            display: none;
        }

        /* ==================== NOVEL CARD ==================== */
        .novel-card {
            flex: 0 0 260px;
            background: var(--gradient-card);
            border-radius: var(--radius-lg);
            overflow: hidden;
            border: 1px solid var(--border-subtle);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            scroll-snap-align: start;
        }

        .novel-card:hover {
            transform: translateY(-12px);
            border-color: var(--border-accent);
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.5), 0 0 50px rgba(99, 102, 241, 0.1);
        }

        .card-cover {
            position: relative;
            aspect-ratio: 3/4;
            overflow: hidden;
        }

        .card-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .novel-card:hover .card-cover img {
            transform: scale(1.08);
        }

        .card-cover-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent 50%, rgba(0, 0, 0, 0.95) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .novel-card:hover .card-cover-overlay {
            opacity: 1;
        }

        .card-badges {
            position: absolute;
            top: 12px;
            right: 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            z-index: 5;
        }

        .card-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 5px;
            backdrop-filter: blur(10px);
        }

        .badge-hot {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .badge-new {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            color: white;
        }

        .badge-vip {
            background: var(--gradient-accent);
            color: white;
        }

        .badge-complete {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
        }

        .card-rating {
            position: absolute;
            top: 12px;
            left: 12px;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(10px);
            padding: 8px 12px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 700;
            z-index: 5;
        }

        .card-rating i {
            color: #fbbf24;
            font-size: 11px;
        }

        .card-type-badge {
            position: absolute;
            bottom: 12px;
            left: 12px;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(10px);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 6px;
            z-index: 5;
        }

        .card-type-badge i {
            color: var(--accent-primary);
        }

        .card-quick-actions {
            position: absolute;
            bottom: 16px;
            left: 16px;
            right: 16px;
            display: flex;
            gap: 10px;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
            z-index: 10;
        }

        .novel-card:hover .card-quick-actions {
            opacity: 1;
            transform: translateY(0);
        }

        .quick-action-btn {
            flex: 1;
            padding: 12px;
            border-radius: var(--radius-sm);
            border: none;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            font-family: inherit;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.3s ease;
        }

        .quick-action-btn-primary {
            background: var(--gradient-accent);
            color: white;
        }

        .quick-action-btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            backdrop-filter: blur(10px);
        }

        .quick-action-btn:hover {
            transform: scale(1.03);
        }

        .card-body {
            padding: 18px;
        }

        .card-title {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 8px;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 45px;
        }

        .card-author {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 12px;
        }

        .card-author i {
            color: var(--accent-dark);
        }

        .card-meta {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 14px;
        }

        .card-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            color: var(--text-muted);
        }

        .card-meta-item i {
            color: var(--accent-dark);
            font-size: 11px;
        }

        .card-genres {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .card-genre {
            padding: 5px 10px;
            background: rgba(99, 102, 241, 0.08);
            border-radius: 6px;
            font-size: 11px;
            color: var(--accent-light);
            font-weight: 500;
        }

        .card-progress {
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid var(--border-subtle);
        }

        .card-progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .card-progress-label {
            font-size: 11px;
            color: var(--text-muted);
        }

        .card-progress-value {
            font-size: 11px;
            color: var(--accent-light);
            font-weight: 600;
        }

        .card-progress-track {
            height: 4px;
            background: var(--bg-primary);
            border-radius: 2px;
            overflow: hidden;
        }

        .card-progress-fill {
            height: 100%;
            background: var(--gradient-shine);
            border-radius: 2px;
        }

        /* ==================== UPDATES ==================== */
        .updates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
        }

        .update-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 20px;
            display: flex;
            gap: 18px;
            border: 1px solid var(--border-subtle);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .update-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 4px;
            height: 100%;
            background: var(--gradient-shine);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .update-card:hover {
            border-color: var(--border-accent);
            background: var(--bg-card-hover);
            transform: translateX(-6px);
        }

        .update-card:hover::before {
            opacity: 1;
        }

        .update-cover {
            width: 90px;
            height: 125px;
            border-radius: var(--radius-md);
            overflow: hidden;
            flex-shrink: 0;
            position: relative;
        }

        .update-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .update-card:hover .update-cover img {
            transform: scale(1.05);
        }

        .update-new-indicator {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 12px;
            height: 12px;
            background: #ef4444;
            border-radius: 50%;
            border: 2px solid var(--bg-card);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.5); }
            50% { transform: scale(1.1); box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
        }

        .update-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .update-type {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: var(--accent-primary);
            font-weight: 600;
            margin-bottom: 8px;
        }

        .update-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .update-chapter {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--accent-light);
            font-weight: 600;
            margin-bottom: 12px;
        }

        .update-chapter i {
            font-size: 12px;
        }

        .update-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: auto;
        }

        .update-stats {
            display: flex;
            gap: 16px;
        }

        .update-stat {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            color: var(--text-muted);
        }

        .update-stat i {
            color: var(--accent-dark);
            font-size: 11px;
        }

        .update-time {
            font-size: 12px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* ==================== CATEGORIES ==================== */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 18px;
        }

        .category-card {
            background: var(--gradient-card);
            border-radius: var(--radius-lg);
            padding: 28px 20px;
            text-align: center;
            border: 1px solid var(--border-subtle);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .category-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at center, rgba(99, 102, 241, 0.1) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .category-card:hover {
            border-color: var(--border-accent);
            transform: translateY(-8px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4), 0 0 40px rgba(99, 102, 241, 0.1);
        }

        .category-card:hover::before {
            opacity: 1;
        }

        .category-icon {
            width: 72px;
            height: 72px;
            border-radius: var(--radius-lg);
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid var(--border-accent);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            font-size: 34px;
            transition: all 0.4s ease;
            position: relative;
            z-index: 1;
        }

        .category-card:hover .category-icon {
            background: var(--gradient-accent);
            transform: scale(1.15) rotate(5deg);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
        }

        .category-name {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }

        .category-count {
            font-size: 13px;
            color: var(--text-muted);
            position: relative;
            z-index: 1;
        }

        /* ==================== RANKINGS ==================== */
        .rankings-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 28px;
        }

        .ranking-card {
            background: var(--gradient-card);
            border-radius: var(--radius-xl);
            padding: 28px;
            border: 1px solid var(--border-subtle);
            transition: all 0.3s ease;
        }

        .ranking-card:hover {
            border-color: var(--border-accent);
        }

        .ranking-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 28px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-subtle);
        }

        .ranking-icon {
            width: 54px;
            height: 54px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .ranking-icon-gold {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: white;
        }

        .ranking-icon-fire {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .ranking-icon-star {
            background: var(--gradient-accent);
            color: white;
        }

        .ranking-header-text h3 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .ranking-header-text p {
            font-size: 13px;
            color: var(--text-muted);
        }

        .ranking-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .ranking-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px;
            border-radius: var(--radius-md);
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid transparent;
        }

        .ranking-item:hover {
            background: rgba(99, 102, 241, 0.05);
            border-color: var(--border-accent);
        }

        .ranking-position {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 800;
            flex-shrink: 0;
        }

        .ranking-position-1 {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(251, 191, 36, 0.3);
        }

        .ranking-position-2 {
            background: linear-gradient(135deg, #d1d5db 0%, #9ca3af 100%);
            color: var(--bg-primary);
        }

        .ranking-position-3 {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            color: white;
        }

        .ranking-position-default {
            background: var(--bg-primary);
            color: var(--text-muted);
            border: 1px solid var(--border-subtle);
        }

        .ranking-cover {
            width: 55px;
            height: 75px;
            border-radius: var(--radius-sm);
            overflow: hidden;
            flex-shrink: 0;
        }

        .ranking-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .ranking-info {
            flex: 1;
            min-width: 0;
        }

        .ranking-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ranking-stats {
            display: flex;
            gap: 14px;
        }

        .ranking-stat {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            color: var(--text-muted);
        }

        .ranking-stat i {
            color: var(--accent-dark);
            font-size: 10px;
        }

        .ranking-trend {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 6px;
        }

        .ranking-trend-up {
            color: #22c55e;
            background: rgba(34, 197, 94, 0.1);
        }

        .ranking-trend-down {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
        }

        .ranking-trend-same {
            color: var(--text-muted);
            background: rgba(255, 255, 255, 0.05);
        }

        /* ==================== FOOTER ==================== */
        footer {
            background: var(--bg-tertiary);
            padding: 80px 32px 32px;
            border-top: 1px solid var(--border-accent);
        }

        .footer-content {
            max-width: 1920px;
            margin: 0 auto;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1.5fr;
            gap: 60px;
            margin-bottom: 60px;
        }

        .footer-brand p {
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.9;
            margin: 24px 0 28px;
        }

        .footer-social {
            display: flex;
            gap: 12px;
        }

        .social-link {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-accent);
            background: rgba(99, 102, 241, 0.03);
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 20px;
            transition: all 0.3s ease;
        }

        .social-link:hover {
            border-color: var(--accent-primary);
            color: var(--accent-light);
            background: rgba(99, 102, 241, 0.1);
            transform: translateY(-4px);
        }

        .footer-section h4 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 24px;
            color: var(--accent-light);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-section h4::before {
            content: '';
            width: 4px;
            height: 20px;
            background: var(--gradient-accent);
            border-radius: 2px;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 14px;
        }

        .footer-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .footer-links a i {
            font-size: 10px;
            transition: transform 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--accent-light);
        }

        .footer-links a:hover i {
            transform: translateX(-6px);
        }

        .footer-newsletter {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 28px;
            border: 1px solid var(--border-accent);
        }

        .footer-newsletter p {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 18px;
            line-height: 1.7;
        }

        .newsletter-form {
            display: flex;
            gap: 10px;
        }

        .newsletter-form input {
            flex: 1;
            padding: 14px 18px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-subtle);
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: inherit;
            font-size: 14px;
        }

        .newsletter-form input:focus {
            outline: none;
            border-color: var(--accent-primary);
        }

        .newsletter-form button {
            padding: 14px 22px;
            border-radius: var(--radius-md);
            border: none;
            background: var(--gradient-accent);
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 16px;
        }

        .newsletter-form button:hover {
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
        }

        .footer-bottom {
            padding-top: 32px;
            border-top: 1px solid var(--border-subtle);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .footer-bottom p {
            color: var(--text-muted);
            font-size: 13px;
        }

        .footer-bottom p i.fa-heart {
            color: #ef4444;
            animation: heartbeat 1.5s infinite;
        }

        @keyframes heartbeat {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.15); }
        }

        .footer-bottom-links {
            display: flex;
            gap: 28px;
        }

        .footer-bottom-links a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 13px;
            transition: color 0.3s ease;
        }

        .footer-bottom-links a:hover {
            color: var(--accent-light);
        }

        /* Back to Top */
        .back-to-top {
            position: fixed;
            bottom: 32px;
            left: 32px;
            width: 54px;
            height: 54px;
            border-radius: var(--radius-md);
            border: none;
            background: var(--gradient-accent);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 999;
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
        }

        .back-to-top.visible {
            opacity: 1;
            visibility: visible;
        }

        .back-to-top:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(99, 102, 241, 0.5);
        }

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 1400px) {
            .hero-grid {
                gap: 50px;
            }

            .hero-text h1 {
                font-size: 48px;
            }

            .footer-grid {
                grid-template-columns: 2fr 1fr 1fr 1fr;
            }

            .footer-newsletter {
                grid-column: span 4;
                margin-top: 20px;
            }
        }

        @media (max-width: 1200px) {
            .rankings-grid {
                grid-template-columns: 1fr 1fr;
            }

            .ranking-card:last-child {
                grid-column: span 2;
            }
        }

        @media (max-width: 1024px) {
            .header-content {
                padding: 0 24px;
            }

            nav, .search-box, .icon-btn, .login-btn {
                display: none;
            }

            .mobile-menu-btn {
                display: flex;
            }

            .mobile-menu, .mobile-menu-overlay {
                display: block;
            }

            .hero {
                padding: 100px 24px 60px;
                min-height: auto;
            }

            .hero-grid {
                grid-template-columns: 1fr;
                gap: 50px;
            }

            .hero-text h1 {
                font-size: 40px;
            }

            .hero-description {
                font-size: 16px;
            }

            .hero-stats {
                gap: 36px;
            }

            .stat-value {
                font-size: 38px;
            }

            .section {
                padding: 60px 24px;
            }

            .section-dark .section-inner {
                padding: 0 24px;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .section-controls {
                width: 100%;
                justify-content: space-between;
            }

            .rankings-grid {
                grid-template-columns: 1fr;
            }

            .ranking-card:last-child {
                grid-column: span 1;
            }

            .footer-grid {
                grid-template-columns: 1fr 1fr;
                gap: 40px;
            }

            .footer-brand {
                grid-column: span 2;
            }

            .footer-newsletter {
                grid-column: span 2;
            }
        }

        @media (max-width: 768px) {
            html {
                font-size: 15px;
            }

            .header-content {
                height: 64px;
                padding: 0 16px;
            }

            .logo-icon {
                width: 42px;
                height: 42px;
                font-size: 20px;
            }

            .logo-text {
                font-size: 22px;
            }

            .hero {
                padding: 90px 16px 50px;
            }

            .hero-text h1 {
                font-size: 32px;
                margin-bottom: 20px;
            }

            .hero-text h1 span::after {
                height: 6px;
                bottom: 4px;
            }

            .hero-description {
                font-size: 15px;
                margin-bottom: 32px;
            }

            .hero-stats {
                flex-wrap: wrap;
                gap: 24px;
                margin-bottom: 36px;
            }

            .stat-item {
                flex: 1 1 45%;
            }

            .stat-item::before {
                display: none;
            }

            .stat-value {
                font-size: 32px;
            }

            .stat-label {
                font-size: 13px;
            }

            .hero-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
                padding: 16px 32px;
            }

            .featured-card {
                padding: 20px;
            }

            .featured-ribbon {
                font-size: 10px;
                padding: 6px 40px;
                top: 35px;
                right: -40px;
            }

            .featured-cover {
                aspect-ratio: 16/9;
            }

            .featured-info h3 {
                font-size: 20px;
            }

            .featured-stats {
                gap: 8px;
            }

            .featured-stat {
                padding: 6px 10px;
                font-size: 12px;
            }

            .featured-actions {
                flex-wrap: wrap;
            }

            .featured-btn {
                padding: 14px 16px;
                font-size: 13px;
            }

            .featured-btn-icon {
                flex: 0 0 48px;
                width: 48px;
            }

            .section {
                padding: 50px 16px;
            }

            .section-dark .section-inner {
                padding: 0 16px;
            }

            .section-icon {
                width: 48px;
                height: 48px;
                font-size: 20px;
            }

            .section-title-text h2 {
                font-size: 22px;
            }

            .novel-card {
                flex: 0 0 200px;
            }

            .card-body {
                padding: 14px;
            }

            .card-title {
                font-size: 14px;
                min-height: 42px;
            }

            .updates-grid {
                grid-template-columns: 1fr;
            }

            .update-card {
                padding: 16px;
                gap: 14px;
            }

            .update-cover {
                width: 75px;
                height: 105px;
            }

            .update-title {
                font-size: 15px;
            }

            .categories-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 14px;
            }

            .category-card {
                padding: 22px 16px;
            }

            .category-icon {
                width: 60px;
                height: 60px;
                font-size: 28px;
            }

            .category-name {
                font-size: 14px;
            }

            .ranking-card {
                padding: 22px;
            }

            .ranking-item {
                padding: 12px;
                gap: 12px;
            }

            .ranking-cover {
                width: 48px;
                height: 66px;
            }

            .ranking-title {
                font-size: 13px;
            }

            footer {
                padding: 50px 16px 24px;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 32px;
            }

            .footer-brand {
                grid-column: span 1;
            }

            .footer-newsletter {
                grid-column: span 1;
            }

            .newsletter-form {
                flex-direction: column;
            }

            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }

            .footer-bottom-links {
                justify-content: center;
                flex-wrap: wrap;
                gap: 20px;
            }

            .back-to-top {
                bottom: 20px;
                left: 20px;
                width: 48px;
                height: 48px;
            }
        }

        @media (max-width: 480px) {
            .hero-text h1 {
                font-size: 26px;
            }

            .hero-stats {
                gap: 16px;
            }

            .stat-value {
                font-size: 26px;
            }

            .featured-cover {
                aspect-ratio: 4/3;
            }

            .featured-info h3 {
                font-size: 18px;
            }

            .featured-author {
                flex-wrap: wrap;
            }

            .featured-genres {
                gap: 6px;
            }

            .genre-badge {
                padding: 6px 12px;
                font-size: 11px;
            }

            .novel-card {
                flex: 0 0 170px;
            }

            .categories-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .category-icon {
                width: 52px;
                height: 52px;
                font-size: 24px;
            }

            .mobile-menu {
                width: 100%;
                max-width: none;
            }
        }
    </style>
